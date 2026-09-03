<?php

use App\Models\ContentImportRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-content-import-runs'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-content-import-runs'));
});

test('government batch preparation records a content import run', function () {
    $directory = storage_path('app/testing-content-import-runs/gov-batch');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/stop.jpg', 'fake-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeContentRunMinimalXlsx($xlsxPath, [
        'katalog' => [
            contentRunOfficialHeaderRow(),
            contentRunOfficialDataRow([
                'Lp' => 1,
                'Numer pytania' => '901',
                'Pytanie' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
                'Poprawna odp' => 'T',
                'Media' => 'stop.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/batch',
        'mediaSources' => [$mediaDirectory],
        '--ready-only' => true,
        '--categories' => 'B',
    ])->assertSuccessful();

    $run = ContentImportRun::query()->latest('id')->firstOrFail();

    expect($run->kind)->toBe('gov_batch_prepare');
    expect($run->status)->toBe('ok');
    expect($run->identifier)->toStartWith('gov-pl-');
    expect($run->questions_total)->toBe(1);
    expect($run->summary['selected_categories'])->toBe(['B']);
});

test('manifest series import records a content import run', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $seriesDirectory = storage_path('app/testing-content-import-runs/series');
    makeContentRunManifestChunk(
        $seriesDirectory.'/chunk-0001',
        'series-batch-1',
        '951',
        'Pytanie pierwsze',
        'shared/951/full.jpg',
        'image-one',
    );
    makeContentRunManifestChunk(
        $seriesDirectory.'/chunk-0002',
        'series-batch-2',
        '952',
        'Pytanie drugie',
        'shared/952/full.jpg',
        'image-two',
    );

    $this->artisan('catalog:import-manifest-series', [
        'path' => $seriesDirectory,
        '--dry-run' => true,
    ])->assertSuccessful();

    $run = ContentImportRun::query()->latest('id')->firstOrFail();

    expect($run->kind)->toBe('manifest_series_import');
    expect($run->status)->toBe('ok');
    expect($run->dry_run)->toBeTrue();
    expect($run->questions_total)->toBe(2);
    expect($run->summary['chunks'])->toHaveCount(2);
});

/**
 * @param  array<string, array<int, array<int|string, scalar|null>>>  $sheets
 */
function writeContentRunMinimalXlsx(string $path, array $sheets): void
{
    File::ensureDirectoryExists(dirname($path));

    $zip = new ZipArchive;
    $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    expect($opened)->toBeTrue();

    $contentTypes = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">', '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>', '<Default Extension="xml" ContentType="application/xml"/>', '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'];
    $workbookRels = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'];
    $workbookSheets = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'];

    $sheetIndex = 1;

    foreach ($sheets as $name => $rows) {
        $sheetPath = "xl/worksheets/sheet{$sheetIndex}.xml";
        $relationshipId = "rId{$sheetIndex}";

        $zip->addFromString($sheetPath, buildContentRunWorksheetXml($rows));
        $contentTypes[] = sprintf('<Override PartName="/%s" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>', $sheetPath);
        $workbookRels[] = sprintf('<Relationship Id="%s" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet%d.xml"/>', $relationshipId, $sheetIndex);
        $workbookSheets[] = sprintf('<sheet name="%s" sheetId="%d" r:id="%s"/>', htmlspecialchars($name, ENT_XML1), $sheetIndex, $relationshipId);

        $sheetIndex++;
    }

    $contentTypes[] = '</Types>';
    $workbookRels[] = '</Relationships>';
    $workbookSheets[] = '</sheets></workbook>';

    $zip->addFromString('[Content_Types].xml', implode('', $contentTypes));
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', implode('', $workbookSheets));
    $zip->addFromString('xl/_rels/workbook.xml.rels', implode('', $workbookRels));
    $zip->close();
}

/**
 * @param  array<int, array<int|string, scalar|null>>  $rows
 */
function buildContentRunWorksheetXml(array $rows): string
{
    $xml = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'];

    foreach ($rows as $rowIndex => $row) {
        $xml[] = sprintf('<row r="%d">', $rowIndex + 1);
        $values = array_values($row);

        foreach ($values as $columnIndex => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $cellRef = contentRunColumnLetters($columnIndex + 1).($rowIndex + 1);

            if (is_numeric($value) && ! str_starts_with((string) $value, '0')) {
                $xml[] = sprintf('<c r="%s"><v>%s</v></c>', $cellRef, htmlspecialchars((string) $value, ENT_XML1));

                continue;
            }

            $xml[] = sprintf(
                '<c r="%s" t="inlineStr"><is><t>%s</t></is></c>',
                $cellRef,
                htmlspecialchars((string) $value, ENT_XML1),
            );
        }

        $xml[] = '</row>';
    }

    $xml[] = '</sheetData></worksheet>';

    return implode('', $xml);
}

function contentRunColumnLetters(int $index): string
{
    $letters = '';

    while ($index > 0) {
        $index--;
        $letters = chr(65 + ($index % 26)).$letters;
        $index = intdiv($index, 26);
    }

    return $letters;
}

/**
 * @return array<int, string>
 */
function contentRunOfficialHeaderRow(): array
{
    return [
        'Lp',
        'Numer pytania',
        'Pytanie',
        'Odpowiedź A',
        'Odpowiedź B',
        'Odpowiedź C',
        'Poprawna odp',
        'Media',
        'Zakres struktury',
        'Liczba punktów',
        'Kategorie',
    ];
}

/**
 * @param  array<string, scalar|null>  $values
 * @return array<int, string|null>
 */
function contentRunOfficialDataRow(array $values): array
{
    $row = array_fill(0, count(contentRunOfficialHeaderRow()), null);

    foreach (contentRunOfficialHeaderRow() as $index => $header) {
        $row[$index] = array_key_exists($header, $values)
            ? ($values[$header] === null ? null : (string) $values[$header])
            : null;
    }

    return $row;
}

function makeContentRunManifestChunk(
    string $directory,
    string $batchId,
    string $externalId,
    string $prompt,
    string $imagePath,
    ?string $imageContent,
): void {
    File::ensureDirectoryExists($directory.'/media/shared/'.basename(dirname($imagePath)));

    File::put($directory.'/manifest.json', json_encode([
        'batch_id' => $batchId,
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
        'source' => 'gov.pl-mi',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if ($imageContent !== null) {
        File::put($directory.'/media/'.$imagePath, $imageContent);
    }

    File::put($directory.'/questions.csv', implode("\n", [
        'external_id,category_id,question_text,answer_a,answer_b,answer_c,correct_answer,explanation,points,difficulty,question_type,source,published_at,metadata_json,image_path,thumb_path,video_path,poster_path',
        sprintf(
            '%s,B,"%s","Tak","Nie","","A","",3,,boolean,gov.pl-mi,,"{""government_question_id"":""%s""}","%s","","",""',
            $externalId,
            $prompt,
            $externalId,
            $imagePath,
        ),
    ]));
}
