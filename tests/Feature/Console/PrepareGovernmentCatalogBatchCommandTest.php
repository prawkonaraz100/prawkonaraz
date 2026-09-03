<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-gov-batch'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-gov-batch'));
});

test('government batch preparation converts official-like rows into a staged manifest batch', function () {
    $directory = storage_path('app/testing-gov-batch/success');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/stop.jpg', 'fake-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '99',
                'Pytanie' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
                'Poprawna odp' => 'T',
                'Media' => 'stop.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'A,B',
                'Pytanie [EN]' => 'Must you stop the vehicle in this situation?',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/batch',
        'mediaSources' => [$mediaDirectory],
        '--materialize-media' => true,
    ])->assertSuccessful();

    $report = json_decode((string) File::get($directory.'/batch/report.json'), true);
    $manifest = json_decode((string) File::get($directory.'/batch/manifest.json'), true);
    $csvLines = file($directory.'/batch/questions.csv', FILE_IGNORE_NEW_LINES);

    expect($report['status'])->toBe('ok');
    expect($report['rows_prepared'])->toBe(1);
    expect($report['question_rows_total'])->toBe(2);
    expect($report['main_media_refs_total'])->toBe(1);
    expect($report['main_media_found_total'])->toBe(1);
    expect($report['pjm_media_refs_total'])->toBe(0);
    expect($report['images_materialized'])->toBe(1);
    expect($manifest['questions_file'])->toBe('questions.csv');
    expect($manifest['categories'])->toHaveCount(2);
    expect($csvLines)->toHaveCount(3);
    expect(File::exists($directory.'/batch/media/shared/99/full.jpg'))->toBeTrue();

    $firstRow = str_getcsv($csvLines[1]);

    expect($firstRow[1])->toBe('A');
    expect($firstRow[3])->toBe('Tak');
    expect($firstRow[4])->toBe('Nie');
    expect($firstRow[6])->toBe('A');
    expect($firstRow[10])->toBe('boolean');
    expect($firstRow[14])->toBe('shared/99/full.jpg');

    $metadata = json_decode((string) $firstRow[13], true);

    expect($metadata['government_question_id'])->toBe('99');
    expect($metadata['translations']['en']['prompt'])->toBe('Must you stop the vehicle in this situation?');
});

test('government batch preparation can filter categories and audit missing pjm references', function () {
    $directory = storage_path('app/testing-gov-batch/filter');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/stop.jpg', 'fake-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '99',
                'Pytanie' => 'Czy w tej sytuacji masz obowiązek zatrzymać pojazd?',
                'Poprawna odp' => 'T',
                'Media' => 'stop.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'A,B',
                'Nazwa media tłumaczenie migowe (PJM) treść pyt' => 'pjm100.wmv',
            ]),
            officialDataRow([
                'Lp' => 2,
                'Numer pytania' => '100',
                'Pytanie' => 'Czy możesz jechać dalej?',
                'Poprawna odp' => 'N',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'C',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/batch',
        'mediaSources' => [$mediaDirectory],
        '--categories' => 'B',
    ])->assertSuccessful();

    $report = json_decode((string) File::get($directory.'/batch/report.json'), true);
    $manifest = json_decode((string) File::get($directory.'/batch/manifest.json'), true);
    $csvLines = file($directory.'/batch/questions.csv', FILE_IGNORE_NEW_LINES);

    expect($report['rows_prepared'])->toBe(1);
    expect($report['rows_skipped_by_category'])->toBe(1);
    expect($report['question_rows_total'])->toBe(1);
    expect($report['selected_categories'])->toBe(['B']);
    expect($report['main_media_refs_total'])->toBe(1);
    expect($report['main_media_found_total'])->toBe(1);
    expect($report['main_media_missing_total'])->toBe(0);
    expect($report['pjm_media_refs_total'])->toBe(1);
    expect($report['pjm_media_found_total'])->toBe(0);
    expect($report['pjm_media_missing_total'])->toBe(1);
    expect($report['missing_pjm_media'])->toBe(['pjm100.wmv']);
    expect($manifest['categories'])->toBe([
        [
            'code' => 'B',
            'name' => 'Kategoria B',
            'description' => null,
            'sort_order' => 0,
        ],
    ]);
    expect($csvLines)->toHaveCount(2);

    $firstRow = str_getcsv($csvLines[1]);

    expect($firstRow[1])->toBe('B');
});

test('government batch preparation can keep only records ready for import', function () {
    $directory = storage_path('app/testing-gov-batch/ready-only');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/image.jpg', 'fake-image');
    File::put($mediaDirectory.'/video.wmv', 'fake-video');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '201',
                'Pytanie' => 'Czy pytanie obrazkowe jest gotowe?',
                'Poprawna odp' => 'T',
                'Media' => 'image.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 2,
                'Numer pytania' => '202',
                'Pytanie' => 'Czy pytanie wideo jest gotowe bez ffmpeg?',
                'Poprawna odp' => 'N',
                'Media' => 'video.wmv',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 3,
                'Numer pytania' => '203',
                'Pytanie' => 'Czy pytanie tekstowe jest gotowe?',
                'Poprawna odp' => 'T',
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
        '--categories' => 'B',
        '--ready-only' => true,
    ])->assertSuccessful();

    $report = json_decode((string) File::get($directory.'/batch/report.json'), true);
    $csvLines = file($directory.'/batch/questions.csv', FILE_IGNORE_NEW_LINES);

    expect($report['ready_only'])->toBeTrue();
    expect($report['rows_prepared'])->toBe(2);
    expect($report['rows_skipped_not_ready'])->toBe(1);
    expect($report['not_ready_reasons'])->toBe([
        'video_not_materialized' => 1,
    ]);
    expect($report['images_materialized'])->toBe(1);
    expect($report['question_rows_total'])->toBe(2);
    expect($csvLines)->toHaveCount(3);
    expect(File::exists($directory.'/batch/media/shared/201/full.jpg'))->toBeTrue();

    $firstDataRow = str_getcsv($csvLines[1]);
    $secondDataRow = str_getcsv($csvLines[2]);

    expect([$firstDataRow[0], $secondDataRow[0]])->toBe(['201', '203']);
    expect($firstDataRow[14])->toBe('shared/201/full.jpg');
    expect($secondDataRow[14])->toBe('');
});

test('government batch preparation can process a chunk of source rows', function () {
    $directory = storage_path('app/testing-gov-batch/chunk');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/first.jpg', 'first-image');
    File::put($mediaDirectory.'/second.jpg', 'second-image');
    File::put($mediaDirectory.'/third.jpg', 'third-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '301',
                'Pytanie' => 'Pierwszy rekord',
                'Poprawna odp' => 'T',
                'Media' => 'first.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 2,
                'Numer pytania' => '302',
                'Pytanie' => 'Drugi rekord',
                'Poprawna odp' => 'N',
                'Media' => 'second.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '2',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 3,
                'Numer pytania' => '303',
                'Pytanie' => 'Trzeci rekord',
                'Poprawna odp' => 'T',
                'Media' => 'third.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '1',
                'Kategorie' => 'B',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/batch',
        'mediaSources' => [$mediaDirectory],
        '--categories' => 'B',
        '--ready-only' => true,
        '--source-offset' => 1,
        '--source-limit' => 1,
    ])->assertSuccessful();

    $report = json_decode((string) File::get($directory.'/batch/report.json'), true);
    $csvLines = file($directory.'/batch/questions.csv', FILE_IGNORE_NEW_LINES);
    $firstDataRow = str_getcsv($csvLines[1]);

    expect($report['source_offset'])->toBe(1);
    expect($report['source_limit'])->toBe(1);
    expect($report['source_rows_considered'])->toBe(1);
    expect($report['rows_total'])->toBe(1);
    expect($report['rows_prepared'])->toBe(1);
    expect($report['question_rows_total'])->toBe(1);
    expect($report['images_materialized'])->toBe(1);
    expect($csvLines)->toHaveCount(2);
    expect($firstDataRow[0])->toBe('302');
    expect(File::exists($directory.'/batch/media/shared/302/full.jpg'))->toBeTrue();
});

test('catalog manifest import persists metadata_json into questions metadata', function () {
    Storage::fake('r2');

    config([
        'media.upload_disk' => 'r2',
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-gov-batch/manifest-metadata');
    $mediaDirectory = $directory.'/media/shared/99';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/full.jpg', 'image-full');

    File::put($directory.'/manifest.json', json_encode([
        'batch_id' => 'gov-metadata-import',
        'questions_file' => 'questions.csv',
        'media_root' => 'media',
        'source' => 'gov.pl-mi',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    File::put($directory.'/questions.csv', implode("\n", [
        'external_id,category_id,question_text,answer_a,answer_b,answer_c,correct_answer,explanation,points,difficulty,question_type,source,published_at,metadata_json,image_path,thumb_path,video_path,poster_path',
        '99,B,"Czy w tej sytuacji masz obowiązek zatrzymać pojazd?","Tak","Nie","","A","",3,,boolean,gov.pl-mi,,"{""government_question_id"":""99"",""sheet"":""katalog""}","shared/99/full.jpg","","",""',
    ]));

    $this->artisan('catalog:import-manifest', [
        'path' => $directory.'/manifest.json',
        '--skip-sitemap' => true,
    ])->assertSuccessful();

    $question = Question::query()->firstOrFail();
    $category = LicenseCategory::query()->firstOrFail();

    expect($category->code)->toBe('B');
    expect($question->external_id)->toBe('99');
    expect($question->metadata)->toBe([
        'government_question_id' => '99',
        'sheet' => 'katalog',
        'structure_scope' => 'PODSTAWOWY',
    ]);
});

test('government batch series preparation builds multiple chunk directories with a summary report', function () {
    $directory = storage_path('app/testing-gov-batch/series');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/first.jpg', 'first-image');
    File::put($mediaDirectory.'/second.jpg', 'second-image');
    File::put($mediaDirectory.'/third.jpg', 'third-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '401',
                'Pytanie' => 'Pierwszy rekord serii',
                'Poprawna odp' => 'T',
                'Media' => 'first.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 2,
                'Numer pytania' => '402',
                'Pytanie' => 'Drugi rekord serii',
                'Poprawna odp' => 'N',
                'Media' => 'second.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '2',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 3,
                'Numer pytania' => '403',
                'Pytanie' => 'Trzeci rekord serii',
                'Poprawna odp' => 'T',
                'Media' => 'third.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '1',
                'Kategorie' => 'B',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch-series', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/series-output',
        'mediaSources' => [$mediaDirectory],
        '--categories' => 'B',
        '--ready-only' => true,
        '--chunk-size' => 1,
        '--max-chunks' => 2,
    ])->assertSuccessful();

    $summary = json_decode((string) File::get($directory.'/series-output/series-report.json'), true);
    $chunkOneLines = file($directory.'/series-output/chunk-0001/questions.csv', FILE_IGNORE_NEW_LINES);
    $chunkTwoLines = file($directory.'/series-output/chunk-0002/questions.csv', FILE_IGNORE_NEW_LINES);

    expect($summary['status'])->toBe('ok');
    expect($summary['chunks_total'])->toBe(2);
    expect($summary['source_rows_considered_total'])->toBe(2);
    expect($summary['rows_prepared_total'])->toBe(2);
    expect($summary['question_rows_total'])->toBe(2);
    expect($summary['images_materialized_total'])->toBe(2);
    expect($summary['stopped_reason'])->toBe('max_chunks_reached');
    expect($summary['chunks'][0]['source_offset'])->toBe(0);
    expect($summary['chunks'][1]['source_offset'])->toBe(1);
    expect($chunkOneLines)->toHaveCount(2);
    expect($chunkTwoLines)->toHaveCount(2);
    expect(str_getcsv($chunkOneLines[1])[0])->toBe('401');
    expect(str_getcsv($chunkTwoLines[1])[0])->toBe('402');
});

test('government batch series preparation can resume on existing successful chunks', function () {
    $directory = storage_path('app/testing-gov-batch/series-resume');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/first.jpg', 'first-image');
    File::put($mediaDirectory.'/second.jpg', 'second-image');
    File::put($mediaDirectory.'/third.jpg', 'third-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '451',
                'Pytanie' => 'Pierwszy rekord serii resume',
                'Poprawna odp' => 'T',
                'Media' => 'first.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 2,
                'Numer pytania' => '452',
                'Pytanie' => 'Drugi rekord serii resume',
                'Poprawna odp' => 'N',
                'Media' => 'second.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '2',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 3,
                'Numer pytania' => '453',
                'Pytanie' => 'Trzeci rekord serii resume',
                'Poprawna odp' => 'T',
                'Media' => 'third.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '1',
                'Kategorie' => 'B',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch-series', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/series-output',
        'mediaSources' => [$mediaDirectory],
        '--categories' => 'B',
        '--ready-only' => true,
        '--chunk-size' => 1,
        '--max-chunks' => 1,
    ])->assertSuccessful();

    $this->artisan('catalog:prepare-gov-batch-series', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/series-output',
        'mediaSources' => [$mediaDirectory],
        '--categories' => 'B',
        '--ready-only' => true,
        '--chunk-size' => 1,
        '--max-chunks' => 3,
        '--skip-existing' => true,
    ])->assertSuccessful();

    $summary = json_decode((string) File::get($directory.'/series-output/series-report.json'), true);
    $chunkOneLines = file($directory.'/series-output/chunk-0001/questions.csv', FILE_IGNORE_NEW_LINES);
    $chunkTwoLines = file($directory.'/series-output/chunk-0002/questions.csv', FILE_IGNORE_NEW_LINES);
    $chunkThreeLines = file($directory.'/series-output/chunk-0003/questions.csv', FILE_IGNORE_NEW_LINES);

    expect($summary['status'])->toBe('ok');
    expect($summary['skip_existing'])->toBeTrue();
    expect($summary['chunks_total'])->toBe(3);
    expect($summary['chunks_reused'])->toBe(1);
    expect($summary['chunks_built'])->toBe(2);
    expect($summary['source_rows_considered_total'])->toBe(3);
    expect($summary['rows_prepared_total'])->toBe(3);
    expect($summary['question_rows_total'])->toBe(3);
    expect($summary['images_materialized_total'])->toBe(3);
    expect($summary['stopped_reason'])->toBe('max_chunks_reached');
    expect(collect($summary['chunks'])->pluck('status')->all())->toBe(['reused', 'ok', 'ok']);
    expect($summary['chunks'][0]['source_offset'])->toBe(0);
    expect($summary['chunks'][1]['source_offset'])->toBe(1);
    expect($summary['chunks'][2]['source_offset'])->toBe(2);
    expect(str_getcsv($chunkOneLines[1])[0])->toBe('451');
    expect(str_getcsv($chunkTwoLines[1])[0])->toBe('452');
    expect(str_getcsv($chunkThreeLines[1])[0])->toBe('453');
});

test('government batch series preparation can continue when missing media are allowed', function () {
    $directory = storage_path('app/testing-gov-batch/series-allow-missing');
    $mediaDirectory = $directory.'/source-media';
    File::ensureDirectoryExists($mediaDirectory);
    File::put($mediaDirectory.'/first.jpg', 'first-image');

    $xlsxPath = $directory.'/official.xlsx';

    writeMinimalXlsx($xlsxPath, [
        'katalog' => [
            officialHeaderRow(),
            officialDataRow([
                'Lp' => 1,
                'Numer pytania' => '461',
                'Pytanie' => 'Pierwszy rekord z mediami',
                'Poprawna odp' => 'T',
                'Media' => 'first.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '3',
                'Kategorie' => 'B',
            ]),
            officialDataRow([
                'Lp' => 2,
                'Numer pytania' => '462',
                'Pytanie' => 'Drugi rekord bez pliku w paczce',
                'Poprawna odp' => 'N',
                'Media' => 'missing.jpg',
                'Zakres struktury' => 'PODSTAWOWY',
                'Liczba punktów' => '2',
                'Kategorie' => 'B',
            ]),
        ],
    ]);

    $this->artisan('catalog:prepare-gov-batch-series', [
        'xlsx' => $xlsxPath,
        'output' => $directory.'/series-output',
        'mediaSources' => [$mediaDirectory],
        '--categories' => 'B',
        '--materialize-media' => true,
        '--allow-missing-media' => true,
        '--chunk-size' => 1,
        '--max-chunks' => 2,
    ])->assertSuccessful();

    $summary = json_decode((string) File::get($directory.'/series-output/series-report.json'), true);
    $chunkTwoReport = json_decode((string) File::get($directory.'/series-output/chunk-0002/report.json'), true);

    expect($summary['status'])->toBe('ok');
    expect($summary['allow_missing_media'])->toBeTrue();
    expect($summary['chunks_total'])->toBe(2);
    expect($summary['errors_total'])->toBe(0);
    expect($summary['warnings_total'])->toBe(1);
    expect($summary['stopped_reason'])->toBe('max_chunks_reached');
    expect($summary['chunks'][1]['status'])->toBe('ok');
    expect($chunkTwoReport['status'])->toBe('ok');
    expect($chunkTwoReport['allow_missing_media'])->toBeTrue();
    expect($chunkTwoReport['missing_main_media'])->toBe(['missing.jpg']);
    expect($chunkTwoReport['errors_count'])->toBe(0);
    expect($chunkTwoReport['warnings'])->not->toBeEmpty();
});

/**
 * @param  array<string, array<int, array<int|string, scalar|null>>>  $sheets
 */
function writeMinimalXlsx(string $path, array $sheets): void
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

        $zip->addFromString($sheetPath, buildWorksheetXml($rows));
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
function buildWorksheetXml(array $rows): string
{
    $xml = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'];

    foreach ($rows as $rowIndex => $row) {
        $xml[] = sprintf('<row r="%d">', $rowIndex + 1);
        $values = array_values($row);

        foreach ($values as $columnIndex => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $cellRef = columnLetters($columnIndex + 1).($rowIndex + 1);

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

function columnLetters(int $index): string
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
function officialHeaderRow(): array
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
        'Nazwa media tłumaczenie migowe (PJM) treść pyt',
        'Nazwa media tłumaczenie migowe (PJM) treść odp A',
        'Nazwa media tłumaczenie migowe (PJM) treść odp B',
        'Nazwa media tłumaczenie migowe (PJM) treść odp C',
        'Pytanie [EN]',
        'Odpowiedź A [EN]',
        'Odpowiedź B [EN]',
        'Odpowiedź C [EN]',
        'Pytanie [D]',
        'Odpowiedź A [D]',
        'Odpowiedź B [D]',
        'Odpowiedź C [D]',
        'Pytanie [UA]',
        'Odpowiedź A [UA]',
        'Odpowiedź B [UA]',
        'Odpowiedź C [UA]',
    ];
}

/**
 * @param  array<string, scalar|null>  $values
 * @return array<int, string|null>
 */
function officialDataRow(array $values): array
{
    $row = array_fill(0, count(officialHeaderRow()), null);

    foreach (officialHeaderRow() as $index => $header) {
        $row[$index] = array_key_exists($header, $values)
            ? ($values[$header] === null ? null : (string) $values[$header])
            : null;
    }

    return $row;
}
