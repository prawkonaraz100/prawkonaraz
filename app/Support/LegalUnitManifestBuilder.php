<?php

namespace App\Support;

use App\Models\LegalAct;
use App\Models\LegalUnit;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LegalUnitManifestBuilder
{
    private const DEFAULT_SOURCE_URL = 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602';

    private const DEFAULT_ELI_URL = 'https://eli.gov.pl/eli/DU/1997/602/ogl';

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(string $source, array $options = []): array
    {
        $source = trim($source);

        if ($source === '') {
            throw new InvalidArgumentException('Podaj URL albo lokalna sciezke do tekstu HTML aktu.');
        }

        $html = $this->readSource($source);
        $act = $this->actPayload($options);
        $unitStatus = $this->optionString($options, 'unit_status') ?: LegalUnit::STATUS_NEEDS_REVIEW;
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : null;
        $parsed = $this->parseUnits($html, $source, (string) $act['source_url'], $unitStatus, $limit);

        return [
            'source' => $source,
            'unit_count' => count($parsed['units']),
            'skipped_replacement_units' => $parsed['skipped_replacement_units'],
            'skipped_without_parent' => $parsed['skipped_without_parent'],
            'manifest' => [
                'act' => $act,
                'units' => $parsed['units'],
            ],
        ];
    }

    /**
     * @return array{units:list<array<string, mixed>>,skipped_replacement_units:int,skipped_without_parent:int}
     */
    protected function parseUnits(
        string $html,
        string $source,
        string $fallbackSourceUrl,
        string $unitStatus,
        ?int $limit,
    ): array {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' unit_arti ')"
            ." or contains(concat(' ', normalize-space(@class), ' '), ' unit_pass ')"
            ." or contains(concat(' ', normalize-space(@class), ' '), ' unit_para ')"
            ." or contains(concat(' ', normalize-space(@class), ' '), ' unit_pint ')"
            ." or contains(concat(' ', normalize-space(@class), ' '), ' unit_lett ')]"
        );

        $units = [];
        $unitsByNodeId = [];
        $skippedReplacementUnits = 0;
        $skippedWithoutParent = 0;

        foreach ($nodes ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $class = $node->getAttribute('class');

            if (str_contains($class, 'pro-rplc-text')) {
                $skippedReplacementUnits++;

                continue;
            }

            $type = $this->unitType($class);
            $number = $this->unitNumber($node, $type);

            if ($type === null || $number === null) {
                continue;
            }

            $parent = $type === 'article' ? null : $this->nearestImportedParent($node, $unitsByNodeId);

            if ($type !== 'article' && $parent === null) {
                $skippedWithoutParent++;

                continue;
            }

            $canonicalPath = $type === 'article'
                ? $number
                : $this->childCanonicalPath((string) $parent['canonical_path'], $type, $number);
            $label = $type === 'article'
                ? 'art. '.$number
                : $this->childLabel((string) $parent['label'], $type, $number);
            $excerpt = $this->directText($node);
            $unit = [
                'type' => $type,
                'label' => $label,
                'canonical_path' => $canonicalPath,
                'slug' => Str::slug($label),
                'title' => $label,
                'official_excerpt' => $excerpt,
                'source_url' => $this->unitSourceUrl($source, $fallbackSourceUrl, $node),
                'status' => $unitStatus,
            ];

            if ($parent !== null) {
                $unit['parent_canonical_path'] = $parent['canonical_path'];
            }

            $units[] = $unit;
            $unitsByNodeId[$this->nodeId($node)] = [
                'canonical_path' => $canonicalPath,
                'label' => $label,
            ];

            if ($limit !== null && count($units) >= $limit) {
                break;
            }
        }

        return [
            'units' => $units,
            'skipped_replacement_units' => $skippedReplacementUnits,
            'skipped_without_parent' => $skippedWithoutParent,
        ];
    }

    protected function readSource(string $source): string
    {
        if (preg_match('/^https?:\/\//i', $source) === 1) {
            $response = Http::timeout(30)->get($source);

            if (! $response->successful()) {
                throw new InvalidArgumentException(sprintf('Nie mozna pobrac zrodla: %s', $source));
            }

            return $response->body();
        }

        if (! File::exists($source)) {
            throw new InvalidArgumentException(sprintf('Nie znaleziono pliku zrodlowego: %s', $source));
        }

        return File::get($source);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function actPayload(array $options): array
    {
        return [
            'slug' => $this->optionString($options, 'act_slug') ?: 'prawo-o-ruchu-drogowym',
            'title' => $this->optionString($options, 'title') ?: 'Ustawa z dnia 20 czerwca 1997 r. - Prawo o ruchu drogowym',
            'short_title' => $this->optionString($options, 'short_title') ?: 'Prawo o ruchu drogowym',
            'publisher' => $this->optionString($options, 'publisher') ?: 'Dziennik Ustaw',
            'source_url' => $this->optionString($options, 'source_url') ?: self::DEFAULT_SOURCE_URL,
            'eli_url' => $this->optionString($options, 'eli_url') ?: self::DEFAULT_ELI_URL,
            'isap_url' => $this->optionString($options, 'isap_url') ?: self::DEFAULT_SOURCE_URL,
            'effective_from' => $this->optionString($options, 'effective_from') ?: '1998-01-01',
            'last_checked_at' => $this->optionString($options, 'last_checked_at') ?: now()->toDateTimeString(),
            'status' => $this->optionString($options, 'act_status') ?: LegalAct::STATUS_NEEDS_REVIEW,
        ];
    }

    protected function unitType(string $class): ?string
    {
        return match (true) {
            str_contains($class, 'unit_arti') => 'article',
            str_contains($class, 'unit_pass') => 'section',
            str_contains($class, 'unit_para') => 'paragraph',
            str_contains($class, 'unit_pint') => 'point',
            str_contains($class, 'unit_lett') => 'letter',
            default => null,
        };
    }

    protected function unitNumber(DOMElement $node, ?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $heading = $this->headingText($node);

        return match ($type) {
            'article' => $this->matchHeading($heading, '/Art\.\s*([0-9]+[a-z]*)\.?/iu'),
            'section' => $this->matchHeading($heading, '/^([0-9]+[a-z]*)\.$/iu'),
            'paragraph' => $this->matchHeading($heading, '/^§\s*([0-9]+[a-z]*)\.$/iu'),
            'point' => $this->matchHeading($heading, '/^([0-9]+[a-z]*)\)$/iu'),
            'letter' => $this->matchHeading($heading, '/^([a-z]+)\)$/iu'),
            default => null,
        };
    }

    protected function headingText(DOMElement $node): string
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && mb_strtolower($child->tagName) === 'h3') {
                return $this->squish($child->textContent);
            }
        }

        return '';
    }

    protected function matchHeading(string $heading, string $pattern): ?string
    {
        if (preg_match($pattern, $heading, $matches) !== 1) {
            return null;
        }

        return mb_strtolower((string) $matches[1]);
    }

    /**
     * @param  array<string, array{canonical_path:string,label:string}>  $unitsByNodeId
     * @return array{canonical_path:string,label:string}|null
     */
    protected function nearestImportedParent(DOMElement $node, array $unitsByNodeId): ?array
    {
        $parent = $node->parentNode;

        while ($parent instanceof DOMNode) {
            if ($parent instanceof DOMElement) {
                $nodeId = $this->nodeId($parent);

                if (isset($unitsByNodeId[$nodeId])) {
                    return $unitsByNodeId[$nodeId];
                }
            }

            $parent = $parent->parentNode;
        }

        return null;
    }

    protected function childCanonicalPath(string $parentCanonicalPath, string $type, string $number): string
    {
        $segment = $type === 'paragraph' ? 'par-'.$number : $number;

        return $parentCanonicalPath.'/'.$segment;
    }

    protected function childLabel(string $parentLabel, string $type, string $number): string
    {
        $suffix = match ($type) {
            'section' => 'ust. '.$number,
            'paragraph' => '§ '.$number,
            'point' => 'pkt '.$number,
            'letter' => 'lit. '.$number,
            default => $number,
        };

        return $parentLabel.' '.$suffix;
    }

    protected function directText(DOMElement $node): ?string
    {
        $inner = null;

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $this->hasClass($child, 'unit-inner')) {
                $inner = $child;

                break;
            }
        }

        if (! $inner instanceof DOMElement) {
            return null;
        }

        $parts = [];

        foreach ($inner->childNodes as $child) {
            if ($child instanceof DOMElement && $child->getAttribute('data-template') === 'xText') {
                $parts[] = $this->squish($child->textContent);
            }
        }

        $text = $this->squish(implode(' ', array_filter($parts)));

        return $text !== '' ? $text : null;
    }

    protected function unitSourceUrl(string $source, string $fallbackSourceUrl, DOMElement $node): string
    {
        $base = preg_match('/^https?:\/\//i', $source) === 1 ? $source : $fallbackSourceUrl;
        $id = trim($node->getAttribute('id'));

        return $id !== '' ? $base.'#'.$id : $base;
    }

    protected function hasClass(DOMElement $node, string $class): bool
    {
        return str_contains(' '.$node->getAttribute('class').' ', ' '.$class.' ');
    }

    protected function nodeId(DOMElement $node): string
    {
        $id = trim($node->getAttribute('id'));

        return $id !== '' ? $id : spl_object_hash($node);
    }

    protected function optionString(array $options, string $key): ?string
    {
        $value = $options[$key] ?? null;

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function squish(string $value): string
    {
        $value = str_replace("\u{00A0}", ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    }
}
