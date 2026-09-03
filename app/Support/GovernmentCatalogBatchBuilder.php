<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class GovernmentCatalogBatchBuilder
{
    /**
     * @var array<string, string>
     */
    protected const CATEGORY_NAMES = [
        'AM' => 'Kategoria AM',
        'A' => 'Kategoria A',
        'A1' => 'Kategoria A1',
        'A2' => 'Kategoria A2',
        'B' => 'Kategoria B',
        'B1' => 'Kategoria B1',
        'C' => 'Kategoria C',
        'C1' => 'Kategoria C1',
        'D' => 'Kategoria D',
        'D1' => 'Kategoria D1',
        'T' => 'Kategoria T',
        'PT' => 'Kategoria PT',
    ];

    /**
     * @var array<int, string>
     */
    protected const REQUIRED_HEADERS = [
        'lp',
        'numer pytania',
        'pytanie',
        'poprawna odp',
        'media',
        'zakres struktury',
        'liczba punktow',
        'kategorie',
    ];

    /**
     * @var array<int, string>
     */
    protected const MAIN_SHEETS = [
        'katalog',
    ];

    /**
     * @var array<int, string>
     */
    protected const VERIFICATION_SHEETS = [
        'w trakcie weryfikacji',
    ];

    public function __construct(
        protected GovernmentCatalogSpreadsheetReader $spreadsheetReader,
    ) {}

    /**
     * @param  array<int, string>  $mediaSources
     * @return array<string, mixed>
     */
    public function build(
        string $xlsxPath,
        array $mediaSources,
        string $outputDirectory,
        bool $includeVerification = false,
        bool $materializeMedia = false,
        array $categoryFilter = [],
        bool $readyOnly = false,
        int $sourceOffset = 0,
        ?int $sourceLimit = null,
        bool $allowMissingMedia = false,
    ): array {
        $outputDirectory = $this->normalizeAbsolutePath($outputDirectory);
        $xlsxPath = $this->normalizeAbsolutePath($xlsxPath);
        $batchId = 'gov-pl-'.now()->utc()->format('Ymd-His');
        $categoryFilter = $this->normalizeCategoryFilter($categoryFilter);
        $selectedSheets = $includeVerification
            ? array_merge(self::MAIN_SHEETS, self::VERIFICATION_SHEETS)
            : self::MAIN_SHEETS;

        File::ensureDirectoryExists($outputDirectory);
        File::ensureDirectoryExists($outputDirectory.'/media/shared');

        $rowsBySheet = $this->spreadsheetReader->read($xlsxPath, $selectedSheets);
        $mediaLocator = new GovernmentMediaLocator(array_map([$this, 'normalizeAbsolutePath'], $mediaSources));
        $ffmpegAvailable = $this->ffmpegAvailable();

        $report = [
            'status' => 'ok',
            'batch_id' => $batchId,
            'xlsx_path' => $xlsxPath,
            'output_directory' => $outputDirectory,
            'manifest_path' => $outputDirectory.'/manifest.json',
            'questions_file' => $outputDirectory.'/questions.csv',
            'media_root' => $outputDirectory.'/media',
            'sheets_processed' => array_keys($rowsBySheet),
            'source_offset' => max($sourceOffset, 0),
            'source_limit' => $sourceLimit,
            'source_rows_considered' => 0,
            'rows_total' => 0,
            'rows_prepared' => 0,
            'rows_skipped_by_category' => 0,
            'rows_skipped_not_ready' => 0,
            'question_rows_total' => 0,
            'categories_total' => 0,
            'selected_categories' => $categoryFilter,
            'ready_only' => $readyOnly,
            'allow_missing_media' => $allowMissingMedia,
            'not_ready_reasons' => [],
            'materialize_media' => $materializeMedia,
            'ffmpeg_binary' => $this->ffmpegBinary(),
            'ffmpeg_available' => $ffmpegAvailable,
            'main_media_refs_total' => 0,
            'main_media_found_total' => 0,
            'main_media_missing_total' => 0,
            'missing_main_media' => [],
            'pjm_media_refs_total' => 0,
            'pjm_media_found_total' => 0,
            'pjm_media_missing_total' => 0,
            'missing_pjm_media' => [],
            'images_materialized' => 0,
            'thumbs_materialized' => 0,
            'videos_materialized' => 0,
            'posters_materialized' => 0,
            'media_duplicates' => $mediaLocator->duplicateReport(),
            'missing_media' => [],
            'video_transcode_pending' => [],
            'warnings' => [],
            'errors' => [],
            'errors_count' => 0,
        ];

        $preparedRows = [];
        $categories = [];
        $sourceCursor = 0;
        $limitReached = false;

        foreach ($rowsBySheet as $sheetName => $rows) {
            if ($rows === []) {
                continue;
            }

            $headerRow = array_shift($rows);
            $headerIndex = $this->headerIndex($headerRow ?? []);
            $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headerIndex)));

            if ($missingHeaders !== []) {
                throw new RuntimeException(sprintf(
                    'Arkusz %s nie zawiera wymaganych kolumn: %s',
                    $sheetName,
                    implode(', ', $missingHeaders),
                ));
            }

            foreach ($rows as $rowNumber => $row) {
                if ($sourceCursor < $sourceOffset) {
                    $sourceCursor++;

                    continue;
                }

                if ($sourceLimit !== null && $report['source_rows_considered'] >= $sourceLimit) {
                    $limitReached = true;

                    break;
                }

                $sourceCursor++;
                $report['source_rows_considered']++;
                $report['rows_total']++;

                $prepared = $this->prepareRow(
                    row: $row,
                    rowNumber: $rowNumber + 2,
                    sheetName: $sheetName,
                    headerIndex: $headerIndex,
                    mediaLocator: $mediaLocator,
                    outputDirectory: $outputDirectory,
                    materializeMedia: $materializeMedia,
                    ffmpegAvailable: $ffmpegAvailable,
                    categoryFilter: $categoryFilter,
                    readyOnly: $readyOnly,
                    report: $report,
                );

                if ($prepared === []) {
                    continue;
                }

                $report['rows_prepared']++;

                foreach ($prepared as $preparedRow) {
                    $preparedRows[] = $preparedRow;
                    $categories[$preparedRow['category_id']] = self::CATEGORY_NAMES[$preparedRow['category_id']] ?? ('Kategoria '.$preparedRow['category_id']);
                    $report['question_rows_total']++;
                }
            }

            if ($limitReached) {
                break;
            }
        }

        $report['categories_total'] = count($categories);

        sort($report['missing_main_media']);
        sort($report['missing_pjm_media']);
        $this->writeQuestionsCsv($outputDirectory.'/questions.csv', $preparedRows);
        $this->writeManifest($outputDirectory.'/manifest.json', $batchId, $categories);

        if ($materializeMedia && $report['video_transcode_pending'] !== []) {
            $report['status'] = 'failed';
            $report['errors'][] = [
                'path' => 'media',
                'message' => 'Brakuje ffmpeg do konwersji WMV -> MP4 dla czesci oficjalnych nagran.',
            ];
        }

        if ($materializeMedia && $report['missing_media'] !== []) {
            if ($allowMissingMedia) {
                $report['warnings'][] = 'Czesc mediow z oficjalnej bazy nie zostala odnaleziona, ale batch kontynuuje staging z pelnym audytem brakow.';
            } else {
                $report['status'] = 'failed';
                $report['errors'][] = [
                    'path' => 'media',
                    'message' => 'Czesc mediow z oficjalnej bazy nie zostala odnaleziona w dostarczonych archiwach lub katalogach.',
                ];
            }
        }

        if (! $materializeMedia) {
            $report['warnings'][] = 'Batch zostal przygotowany bez materializacji mediow. Do importu produkcyjnego uruchom komende z --materialize-media.';
        }

        $report['errors_count'] = count($report['errors']);

        return $report;
    }

    /**
     * @param  array<int, string|null>  $headerRow
     * @return array<string, int>
     */
    protected function headerIndex(array $headerRow): array
    {
        $headerIndex = [];

        foreach ($headerRow as $index => $value) {
            $normalized = $this->normalizeHeader($value);

            if ($normalized === '') {
                continue;
            }

            $headerIndex[$normalized] = $index;
        }

        return $headerIndex;
    }

    protected function normalizeHeader(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndex
     * @param  array<string, mixed>  $report
     * @return array<int, array<string, string>>
     */
    protected function prepareRow(
        array $row,
        int $rowNumber,
        string $sheetName,
        array $headerIndex,
        GovernmentMediaLocator $mediaLocator,
        string $outputDirectory,
        bool $materializeMedia,
        bool $ffmpegAvailable,
        array $categoryFilter,
        bool $readyOnly,
        array &$report,
    ): array {
        $governmentQuestionId = $this->rowValue($row, $headerIndex, 'numer pytania');
        $prompt = $this->rowValue($row, $headerIndex, 'pytanie');

        if ($governmentQuestionId === '' || $prompt === '') {
            return [];
        }

        $categories = array_values(array_filter(array_map(
            static fn (string $category): string => strtoupper(trim($category)),
            explode(',', $this->rowValue($row, $headerIndex, 'kategorie'))
        )));
        $categories = array_values(array_unique($categories));

        if ($categories === []) {
            $report['warnings'][] = sprintf('Wiersz %d (%s) nie zawiera kategorii i zostal pominiety.', $rowNumber, $governmentQuestionId);

            return [];
        }

        if ($categoryFilter !== []) {
            $categories = array_values(array_filter(
                $categories,
                static fn (string $category): bool => in_array($category, $categoryFilter, true),
            ));

            if ($categories === []) {
                $report['rows_skipped_by_category']++;

                return [];
            }
        }

        $correctAnswer = strtoupper($this->rowValue($row, $headerIndex, 'poprawna odp'));
        $mainMediaName = $this->rowValue($row, $headerIndex, 'media');
        $questionType = in_array($correctAnswer, ['T', 'N'], true) ? 'boolean' : 'single_choice';
        $answerA = $questionType === 'boolean' ? 'Tak' : $this->rowValue($row, $headerIndex, 'odpowiedz a');
        $answerB = $questionType === 'boolean' ? 'Nie' : $this->rowValue($row, $headerIndex, 'odpowiedz b');
        $answerC = $questionType === 'boolean' ? '' : $this->rowValue($row, $headerIndex, 'odpowiedz c');
        $mappedCorrectAnswer = match ($correctAnswer) {
            'T' => 'A',
            'N' => 'B',
            default => $correctAnswer,
        };

        $pjmReferences = array_filter([
            'question' => $this->rowValueAny($row, $headerIndex, [
                'nazwa media tlumaczenie migowe (pjm) tresc pyt',
                'nazwa media tumaczenie migowe (pjm) tresc pyt',
            ]),
            'answer_a' => $this->rowValueAny($row, $headerIndex, [
                'nazwa media tlumaczenie migowe (pjm) tresc odp a',
                'nazwa media tumaczenie migowe (pjm) tresc odp a',
            ]),
            'answer_b' => $this->rowValueAny($row, $headerIndex, [
                'nazwa media tlumaczenie migowe (pjm) tresc odp b',
                'nazwa media tumaczenie migowe (pjm) tresc odp b',
            ]),
            'answer_c' => $this->rowValueAny($row, $headerIndex, [
                'nazwa media tlumaczenie migowe (pjm) tresc odp c',
                'nazwa media tumaczenie migowe (pjm) tresc odp c',
            ]),
        ]);

        $this->auditMediaReference($mainMediaName, 'main', $mediaLocator, $report);

        foreach ($pjmReferences as $reference) {
            $this->auditMediaReference($reference, 'pjm', $mediaLocator, $report);
        }

        $mainMediaState = $this->mainMediaState(
            mediaName: $mainMediaName,
            mediaLocator: $mediaLocator,
            materializeMedia: $materializeMedia,
            ffmpegAvailable: $ffmpegAvailable,
        );

        if ($readyOnly) {

            if (! $mainMediaState['ready']) {
                $report['rows_skipped_not_ready']++;
                $reason = (string) ($mainMediaState['reason'] ?? 'not_ready');
                $report['not_ready_reasons'][$reason] = (int) ($report['not_ready_reasons'][$reason] ?? 0) + 1;

                return [];
            }
        }

        $shouldMaterializeMainMedia = $materializeMedia
            || ($readyOnly && ($mainMediaState['kind'] ?? null) === 'image');

        $materializedMedia = $this->materializeMainMedia(
            governmentQuestionId: $governmentQuestionId,
            mediaName: $mainMediaName,
            mediaLocator: $mediaLocator,
            outputDirectory: $outputDirectory,
            materializeMedia: $shouldMaterializeMainMedia,
            ffmpegAvailable: $ffmpegAvailable,
            report: $report,
        );

        $metadata = [
            'government_question_id' => $governmentQuestionId,
            'government_row_number' => $this->rowValue($row, $headerIndex, 'lp'),
            'sheet' => $sheetName,
            'structure_scope' => $this->rowValue($row, $headerIndex, 'zakres struktury'),
            'categories_original' => $categories,
            'translations' => array_filter([
                'en' => $this->translationPayload($row, $headerIndex, '[en]'),
                'de' => $this->translationPayload($row, $headerIndex, '[d]'),
                'ua' => $this->translationPayload($row, $headerIndex, '[ua]'),
            ]),
            'pjm' => $pjmReferences,
            'main_media_original' => $mainMediaName !== '' ? $mainMediaName : null,
        ];

        $preparedRows = [];

        foreach ($categories as $category) {
            $preparedRows[] = [
                'external_id' => $governmentQuestionId,
                'category_id' => $category,
                'question_text' => $prompt,
                'answer_a' => $answerA,
                'answer_b' => $answerB,
                'answer_c' => $answerC,
                'correct_answer' => $mappedCorrectAnswer,
                'explanation' => '',
                'points' => $this->rowValue($row, $headerIndex, 'liczba punktow'),
                'difficulty' => '',
                'question_type' => $questionType,
                'source' => 'gov.pl-mi',
                'published_at' => '',
                'metadata_json' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'image_path' => $materializedMedia['image_path'] ?? '',
                'thumb_path' => $materializedMedia['thumb_path'] ?? '',
                'video_path' => $materializedMedia['video_path'] ?? '',
                'poster_path' => $materializedMedia['poster_path'] ?? '',
            ];
        }

        return $preparedRows;
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndex
     * @return array<string, string>|array{}
     */
    protected function translationPayload(array $row, array $headerIndex, string $suffix): array
    {
        $prompt = $this->rowValue($row, $headerIndex, sprintf('pytanie %s', $suffix));
        $answerA = $this->rowValue($row, $headerIndex, sprintf('odpowiedz a %s', $suffix));
        $answerB = $this->rowValue($row, $headerIndex, sprintf('odpowiedz b %s', $suffix));
        $answerC = $this->rowValue($row, $headerIndex, sprintf('odpowiedz c %s', $suffix));

        if ($prompt === '' && $answerA === '' && $answerB === '' && $answerC === '') {
            return [];
        }

        return array_filter([
            'prompt' => $prompt,
            'option_a' => $answerA,
            'option_b' => $answerB,
            'option_c' => $answerC,
        ], static fn (string $value): bool => $value !== '');
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, string>
     */
    protected function materializeMainMedia(
        string $governmentQuestionId,
        string $mediaName,
        GovernmentMediaLocator $mediaLocator,
        string $outputDirectory,
        bool $materializeMedia,
        bool $ffmpegAvailable,
        array &$report,
    ): array {
        if ($mediaName === '' || ! $materializeMedia) {
            return [];
        }

        $extension = strtolower((string) pathinfo($mediaName, PATHINFO_EXTENSION));
        $sharedDirectory = $outputDirectory.'/media/shared/'.$governmentQuestionId;

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
            $targetPath = $sharedDirectory.'/full.'.$extension;
            $thumbPath = $sharedDirectory.'/thumb.webp';

            try {
                $mediaLocator->materialize($mediaName, $targetPath);
                $report['images_materialized']++;
            } catch (RuntimeException) {
                $report['missing_media'][] = $mediaName;

                return [];
            }

            $thumbRelativePath = null;

            if ($ffmpegAvailable && $this->isValidImageFile($targetPath)) {
                try {
                    $this->generateImageThumb($targetPath, $thumbPath);
                    $report['thumbs_materialized']++;
                    $thumbRelativePath = $this->relativeMediaPath($outputDirectory.'/media', $thumbPath);
                } catch (RuntimeException) {
                    $report['warnings'][] = sprintf(
                        'Nie udalo sie wygenerowac miniatury dla obrazu %s.',
                        $mediaName,
                    );
                }
            }

            return [
                'image_path' => $this->relativeMediaPath($outputDirectory.'/media', $targetPath),
                'thumb_path' => $thumbRelativePath,
            ];
        }

        if ($extension === 'wmv') {
            if (! $ffmpegAvailable) {
                $report['video_transcode_pending'][] = $mediaName;

                return [];
            }

            File::ensureDirectoryExists($sharedDirectory);
            $rawSource = $sharedDirectory.'/source.'.$extension;
            $videoTarget = $sharedDirectory.'/full.mp4';
            $posterTarget = $sharedDirectory.'/poster.jpg';

            try {
                $mediaLocator->materialize($mediaName, $rawSource);
            } catch (RuntimeException) {
                $report['missing_media'][] = $mediaName;

                return [];
            }

            $this->transcodeVideo($rawSource, $videoTarget);
            $this->generatePoster($videoTarget, $posterTarget);
            File::delete($rawSource);

            $report['videos_materialized']++;
            $report['posters_materialized']++;

            return [
                'video_path' => $this->relativeMediaPath($outputDirectory.'/media', $videoTarget),
                'poster_path' => $this->relativeMediaPath($outputDirectory.'/media', $posterTarget),
            ];
        }

        $report['warnings'][] = sprintf('Nieobslugiwane rozszerzenie glownego media: %s', $mediaName);

        return [];
    }

    /**
     * @return array{kind:string,exists:bool,ready:bool,reason:?string}
     */
    protected function mainMediaState(
        string $mediaName,
        GovernmentMediaLocator $mediaLocator,
        bool $materializeMedia,
        bool $ffmpegAvailable,
    ): array {
        if ($mediaName === '') {
            return [
                'kind' => 'none',
                'exists' => true,
                'ready' => true,
                'reason' => null,
            ];
        }

        $extension = strtolower((string) pathinfo($mediaName, PATHINFO_EXTENSION));
        $exists = $mediaLocator->locate($mediaName) !== null;

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
            return [
                'kind' => 'image',
                'exists' => $exists,
                'ready' => $exists,
                'reason' => $exists ? null : 'missing_main_media',
            ];
        }

        if ($extension === 'wmv') {
            if (! $exists) {
                return [
                    'kind' => 'video',
                    'exists' => false,
                    'ready' => false,
                    'reason' => 'missing_main_media',
                ];
            }

            if (! $materializeMedia) {
                return [
                    'kind' => 'video',
                    'exists' => true,
                    'ready' => false,
                    'reason' => 'video_not_materialized',
                ];
            }

            if (! $ffmpegAvailable) {
                return [
                    'kind' => 'video',
                    'exists' => true,
                    'ready' => false,
                    'reason' => 'ffmpeg_missing',
                ];
            }

            return [
                'kind' => 'video',
                'exists' => true,
                'ready' => true,
                'reason' => null,
            ];
        }

        return [
            'kind' => 'unsupported',
            'exists' => $exists,
            'ready' => false,
            'reason' => $exists ? 'unsupported_media_extension' : 'missing_main_media',
        ];
    }

    protected function transcodeVideo(string $sourcePath, string $targetPath): void
    {
        $process = new Process([
            $this->ffmpegBinary(),
            '-y',
            '-i',
            $sourcePath,
            '-movflags',
            '+faststart',
            '-c:v',
            'libx264',
            '-preset',
            'fast',
            '-crf',
            '28',
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            $targetPath,
        ]);
        $process->setTimeout(null);
        $process->mustRun();
    }

    protected function generatePoster(string $videoPath, string $posterPath): void
    {
        $process = new Process([
            $this->ffmpegBinary(),
            '-y',
            '-i',
            $videoPath,
            '-frames:v',
            '1',
            $posterPath,
        ]);
        $process->setTimeout(null);
        $process->mustRun();
    }

    protected function generateImageThumb(string $imagePath, string $thumbPath): void
    {
        $process = new Process([
            $this->ffmpegBinary(),
            '-y',
            '-i',
            $imagePath,
            '-vf',
            sprintf("scale='min(%d,iw)':-2", $this->imageThumbWidth()),
            '-c:v',
            'libwebp',
            '-quality',
            (string) $this->imageWebpQuality(),
            $thumbPath,
        ]);
        $process->setTimeout(null);
        $process->mustRun();
    }

    protected function ffmpegAvailable(): bool
    {
        try {
            $process = new Process([$this->ffmpegBinary(), '-version']);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function ffmpegBinary(): string
    {
        return (string) config('media.ffmpeg_binary', 'ffmpeg');
    }

    protected function imageThumbWidth(): int
    {
        return max((int) config('media.image_thumb_width', 480), 120);
    }

    protected function imageWebpQuality(): int
    {
        return min(max((int) config('media.image_webp_quality', 72), 1), 100);
    }

    protected function isValidImageFile(string $path): bool
    {
        return @getimagesize($path) !== false;
    }

    protected function relativeMediaPath(string $outputDirectory, string $absolutePath): string
    {
        $normalizedOutput = rtrim(str_replace('\\', '/', $outputDirectory), '/').'/';
        $normalizedAbsolute = str_replace('\\', '/', $absolutePath);
        $relative = Str::after($normalizedAbsolute, $normalizedOutput);

        return $relative;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    protected function writeQuestionsCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');

        if (! is_resource($handle)) {
            throw new RuntimeException(sprintf('Nie mozna zapisac pliku CSV %s.', $path));
        }

        $header = [
            'external_id',
            'category_id',
            'question_text',
            'answer_a',
            'answer_b',
            'answer_c',
            'correct_answer',
            'explanation',
            'points',
            'difficulty',
            'question_type',
            'source',
            'published_at',
            'metadata_json',
            'image_path',
            'thumb_path',
            'video_path',
            'poster_path',
        ];

        try {
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    static fn (string $column): string => $row[$column] ?? '',
                    $header,
                ));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string>  $categories
     */
    protected function writeManifest(string $path, string $batchId, array $categories): void
    {
        File::put($path, json_encode([
            'batch_id' => $batchId,
            'questions_file' => 'questions.csv',
            'media_root' => 'media',
            'source' => 'gov.pl-mi',
            'categories' => array_values(array_map(
                fn (string $code, string $name): array => [
                    'code' => $code,
                    'name' => $name,
                    'description' => null,
                    'sort_order' => 0,
                ],
                array_keys($categories),
                array_values($categories),
            )),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndex
     */
    protected function rowValue(array $row, array $headerIndex, string $header): string
    {
        $index = $headerIndex[$header] ?? null;

        if ($index === null) {
            return '';
        }

        return trim((string) ($row[$index] ?? ''));
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndex
     * @param  array<int, string>  $headers
     */
    protected function rowValueAny(array $row, array $headerIndex, array $headers): string
    {
        foreach ($headers as $header) {
            $value = $this->rowValue($row, $headerIndex, $header);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function normalizeAbsolutePath(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param  array<int, string>  $categoryFilter
     * @return array<int, string>
     */
    protected function normalizeCategoryFilter(array $categoryFilter): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $category): string => strtoupper(trim($category)),
            $categoryFilter,
        ))));
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function auditMediaReference(
        string $reference,
        string $type,
        GovernmentMediaLocator $mediaLocator,
        array &$report,
    ): void {
        if ($reference === '') {
            return;
        }

        if ($type === 'main') {
            $report['main_media_refs_total']++;

            if ($mediaLocator->locate($reference) !== null) {
                $report['main_media_found_total']++;

                return;
            }

            $report['main_media_missing_total']++;

            if (! in_array($reference, $report['missing_main_media'], true)) {
                $report['missing_main_media'][] = $reference;
            }

            return;
        }

        $report['pjm_media_refs_total']++;

        if ($mediaLocator->locate($reference) !== null) {
            $report['pjm_media_found_total']++;

            return;
        }

        $report['pjm_media_missing_total']++;

        if (! in_array($reference, $report['missing_pjm_media'], true)) {
            $report['missing_pjm_media'][] = $reference;
        }
    }
}
