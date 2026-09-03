<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class GovernmentCatalogSpreadsheetReader
{
    /**
     * @param  array<int, string>|null  $sheetNames
     * @return array<string, array<int, array<int, string|null>>>
     */
    public function read(string $path, ?array $sheetNames = null): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException(sprintf('Nie mozna otworzyc pliku XLSX: %s', $path));
        }

        try {
            $sharedStrings = $this->loadSharedStrings($zip);
            $sheetMap = $this->sheetMap($zip);
            $selectedSheets = $sheetNames !== null
                ? array_map(static fn (string $name): string => Str::lower(trim($name)), $sheetNames)
                : null;
            $rowsBySheet = [];

            foreach ($sheetMap as $sheetName => $sheetPath) {
                if ($selectedSheets !== null && ! in_array(Str::lower($sheetName), $selectedSheets, true)) {
                    continue;
                }

                $content = $zip->getFromName($sheetPath);

                if ($content === false) {
                    throw new RuntimeException(sprintf('Brak arkusza %s w pliku XLSX.', $sheetName));
                }

                $rowsBySheet[$sheetName] = $this->parseWorksheet($content, $sharedStrings);
            }

            return $rowsBySheet;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    protected function loadSharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');

        if ($content === false) {
            return [];
        }

        $xml = $this->loadXml($content, 'shared strings');
        $namespace = $this->mainNamespace($xml);
        $items = [];

        foreach ($xml->children($namespace)->si as $item) {
            $items[] = $this->stringValue($item, $namespace);
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    protected function sheetMap(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            throw new RuntimeException('Plik XLSX nie zawiera wymaganych informacji o arkuszach.');
        }

        $workbook = $this->loadXml($workbookXml, 'workbook');
        $workbookNamespace = $this->mainNamespace($workbook);
        $relationshipNamespace = $workbook->getNamespaces(true)['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $relationships = $this->loadXml($relationshipsXml, 'workbook relationships');
        $relationshipsNamespace = $this->mainNamespace($relationships);
        $targets = [];

        foreach ($relationships->children($relationshipsNamespace)->Relationship as $relationship) {
            $attributes = $relationship->attributes();
            $targets[(string) $attributes['Id']] = $this->normalizeSheetTarget((string) $attributes['Target']);
        }

        $sheetMap = [];

        foreach ($workbook->children($workbookNamespace)->sheets->sheet as $sheet) {
            $attributes = $sheet->attributes();
            $relationshipAttributes = $sheet->attributes($relationshipNamespace);
            $sheetName = (string) $attributes['name'];
            $relationshipId = (string) $relationshipAttributes['id'];

            if ($sheetName === '' || ! isset($targets[$relationshipId])) {
                continue;
            }

            $sheetMap[$sheetName] = $targets[$relationshipId];
        }

        return $sheetMap;
    }

    protected function normalizeSheetTarget(string $target): string
    {
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        if (str_starts_with($target, 'xl/')) {
            return $target;
        }

        return 'xl/'.ltrim($target, '/');
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, string|null>>
     */
    protected function parseWorksheet(string $content, array $sharedStrings): array
    {
        $xml = $this->loadXml($content, 'worksheet');
        $namespace = $this->mainNamespace($xml);
        $rows = [];

        foreach ($xml->children($namespace)->sheetData->row as $row) {
            $values = [];

            foreach ($row->children($namespace)->c as $cell) {
                $columnIndex = $this->columnIndex((string) ($cell->attributes()['r'] ?? 'A1'));
                $values[$columnIndex] = $this->cellValue($cell, $sharedStrings, $namespace);
            }

            if ($values === []) {
                continue;
            }

            ksort($values);
            $rows[] = array_values(array_replace(array_fill(0, max(array_keys($values)) + 1, null), $values));
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    protected function cellValue(SimpleXMLElement $cell, array $sharedStrings, string $namespace): ?string
    {
        $type = (string) ($cell->attributes()['t'] ?? '');

        return match ($type) {
            's' => $sharedStrings[(int) ($cell->children($namespace)->v ?? 0)] ?? null,
            'inlineStr' => $this->stringValue($cell->children($namespace)->is, $namespace),
            'b' => ((string) ($cell->children($namespace)->v ?? '0')) === '1' ? '1' : '0',
            default => $this->scalarValue($cell, $namespace),
        };
    }

    protected function scalarValue(SimpleXMLElement $cell, string $namespace): ?string
    {
        $value = $cell->children($namespace)->v;

        if ($value === null) {
            return null;
        }

        $string = (string) $value;

        return $string !== '' ? $string : null;
    }

    protected function stringValue(SimpleXMLElement $node, string $namespace): string
    {
        if (isset($node->t)) {
            return (string) $node->t;
        }

        $text = '';

        foreach ($node->children($namespace)->r as $run) {
            $text .= (string) $run->children($namespace)->t;
        }

        return $text;
    }

    protected function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);

        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max($index - 1, 0);
    }

    protected function loadXml(string $content, string $context): SimpleXMLElement
    {
        $xml = simplexml_load_string($content);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException(sprintf('Nie mozna odczytac XML dla %s.', $context));
        }

        return $xml;
    }

    protected function mainNamespace(SimpleXMLElement $xml): string
    {
        $namespaces = $xml->getNamespaces(true);

        return $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    }
}
