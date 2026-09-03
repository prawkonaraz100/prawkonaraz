<?php

namespace App\Support;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class CatalogManifestImportService
{
    public function __construct(
        protected QuestionCatalogImporter $questionCatalogImporter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $manifestPath, bool $dryRun = false): array
    {
        $manifestPath = $this->normalizeAbsolutePath($manifestPath);
        $manifest = $this->loadManifest($manifestPath);
        $workingDirectory = dirname($manifestPath);
        $questionsFile = $this->resolveQuestionsFile($workingDirectory, $manifest);
        $mediaRoot = $this->resolveMediaRoot($workingDirectory, $manifest);
        $rows = $this->loadRows($questionsFile);

        $preflight = $this->preparePayload($manifest, $rows, $mediaRoot);
        $report = $this->baseReport(
            manifestPath: $manifestPath,
            questionsFile: $questionsFile,
            mediaRoot: $mediaRoot,
            questionsFormat: $this->questionsFormat($questionsFile),
            batchId: (string) ($manifest['batch_id'] ?? 'manifest-import'),
            dryRun: $dryRun,
            rowsTotal: count($rows),
            assetPlan: $preflight['asset_plan'],
        );

        if ($preflight['errors'] !== []) {
            $report['errors'] = $preflight['errors'];
            $report['errors_count'] = count($preflight['errors']);
            $report['completed_at'] = now()->utc()->toIso8601String();

            return $report;
        }

        $validationReport = $this->questionCatalogImporter->import($preflight['payload'], true);
        $report = $this->mergeImporterReport($report, $validationReport);

        if (($report['errors_count'] ?? 0) > 0 || $dryRun) {
            $report['completed_at'] = now()->utc()->toIso8601String();

            return $report;
        }

        $uploadedAssets = [];

        try {
            $uploadedAssets = $this->uploadAssets($preflight['asset_plan']);
            $persistedReport = $this->questionCatalogImporter->import($preflight['payload'], false);
            $report = $this->mergeImporterReport($report, $persistedReport);
            $report['uploaded_assets_total'] = count($uploadedAssets);
            $report['uploaded_assets'] = $uploadedAssets;
            $report['completed_at'] = now()->utc()->toIso8601String();

            return $report;
        } catch (Throwable $exception) {
            $this->cleanupUploadedAssets($uploadedAssets);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{payload: array<string, mixed>, asset_plan: array<int, array<string, mixed>>, errors: array<int, array<string, string>>}
     */
    protected function preparePayload(array $manifest, array $rows, string $mediaRoot): array
    {
        $categories = [];
        $assetPlan = [];
        $errors = [];
        $categoriesMeta = $this->categoryMetadata($manifest);

        foreach ($rows as $index => $row) {
            $path = "rows.{$index}";

            if (! is_array($row)) {
                $errors[] = [
                    'path' => $path,
                    'message' => 'Question row must be an object or associative array.',
                ];

                continue;
            }

            $row = $this->normalizeRow($row, $manifest);
            $validator = Validator::make($row, [
                'category_code' => ['required', 'string', 'max:50'],
                'external_id' => ['nullable', 'string', 'max:255'],
                'prompt' => ['required', 'string'],
                'explanation' => ['nullable', 'string'],
                'option_a' => ['required', 'string'],
                'option_b' => ['required', 'string'],
                'option_c' => ['nullable', 'string'],
                'correct_answer' => ['required', 'string', 'in:a,b,c'],
                'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
                'points' => ['nullable', 'integer', 'min:1', 'max:10'],
                'question_type' => ['nullable', 'string', 'in:single_choice,boolean'],
                'is_active' => ['nullable', 'boolean'],
                'source' => ['nullable', 'string', 'max:255'],
                'published_at' => ['nullable', 'date'],
                'metadata' => ['nullable', 'array'],
            ]);

            $validator->after(function (ValidatorContract $validator) use ($row): void {
                if (($row['question_type'] ?? 'single_choice') === 'boolean' && ($row['correct_answer'] ?? null) === 'c') {
                    $validator->errors()->add('correct_answer', 'Boolean questions can only use answers A or B.');
                }

                if (($row['correct_answer'] ?? null) === 'c' && blank($row['option_c'] ?? null)) {
                    $validator->errors()->add('option_c', 'Option C is required when correct_answer is C.');
                }
            });

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = [
                        'path' => $path,
                        'message' => $message,
                    ];
                }

                continue;
            }

            try {
                $preparedAssets = $this->prepareAssets(
                    categoryCode: (string) $row['category_code'],
                    questionExternalId: (string) ($row['external_id'] ?? ''),
                    prompt: (string) $row['prompt'],
                    mediaRoot: $mediaRoot,
                    row: $row,
                );
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'path' => "{$path}.{$field}",
                            'message' => (string) $message,
                        ];
                    }
                }

                continue;
            }

            $categoryCode = strtoupper((string) $row['category_code']);
            $categoryMeta = $categoriesMeta[$categoryCode] ?? [
                'code' => $categoryCode,
                'name' => 'Kategoria '.$categoryCode,
                'description' => null,
                'sort_order' => 0,
            ];

            if (! isset($categories[$categoryCode])) {
                $categories[$categoryCode] = [
                    'code' => $categoryMeta['code'],
                    'name' => $categoryMeta['name'],
                    'description' => $categoryMeta['description'],
                    'sort_order' => $categoryMeta['sort_order'],
                    'questions' => [],
                ];
            }

            $categories[$categoryCode]['questions'][] = [
                'external_id' => $row['external_id'] !== '' ? $row['external_id'] : null,
                'prompt' => $row['prompt'],
                'explanation' => $row['explanation'],
                'option_a' => $row['option_a'],
                'option_b' => $row['option_b'],
                'option_c' => $row['option_c'],
                'correct_answer' => $row['correct_answer'],
                'difficulty' => $row['difficulty'],
                'points' => $row['points'],
                'question_type' => $row['question_type'],
                'is_active' => $row['is_active'],
                'source' => $row['source'],
                'published_at' => $row['published_at'],
                'metadata' => $row['metadata'],
                'media' => $preparedAssets['media'],
                'collection' => is_array($manifest['collection'] ?? null) ? $manifest['collection'] : null,
                'module' => is_array($manifest['module'] ?? null) ? $manifest['module'] : null,
                'module_position' => $row['module_position'],
            ];

            foreach ($preparedAssets['uploads'] as $asset) {
                $assetPlan[] = $asset;
            }
        }

        return [
            'payload' => [
                'batch_id' => (string) ($manifest['batch_id'] ?? 'manifest-import'),
                'categories' => array_values($categories),
            ],
            'asset_plan' => $assetPlan,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array{code:string,name:string,description:?string,sort_order:int}>
     */
    protected function categoryMetadata(array $manifest): array
    {
        $categories = [];

        foreach ((array) ($manifest['categories'] ?? []) as $category) {
            if (! is_array($category) || blank($category['code'] ?? null)) {
                continue;
            }

            $code = strtoupper(trim((string) $category['code']));

            $categories[$code] = [
                'code' => $code,
                'name' => (string) ($category['name'] ?? ('Kategoria '.$code)),
                'description' => filled($category['description'] ?? null) ? (string) $category['description'] : null,
                'sort_order' => (int) ($category['sort_order'] ?? 0),
            ];
        }

        if (filled($manifest['category_id'] ?? null)) {
            $code = strtoupper(trim((string) $manifest['category_id']));

            $categories[$code] ??= [
                'code' => $code,
                'name' => (string) ($manifest['category_name'] ?? ('Kategoria '.$code)),
                'description' => filled($manifest['category_description'] ?? null)
                    ? (string) $manifest['category_description']
                    : null,
                'sort_order' => 0,
            ];
        }

        return $categories;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row, array $manifest): array
    {
        $questionType = strtolower(trim((string) ($row['question_type'] ?? 'single_choice')));
        $metadata = $this->metadataValue($row['metadata_json'] ?? $row['metadata'] ?? null);

        if ($questionType === '') {
            $questionType = 'single_choice';
        }

        return [
            'category_code' => strtoupper(trim((string) ($row['category_id'] ?? $row['category_code'] ?? $manifest['category_id'] ?? ''))),
            'external_id' => trim((string) ($row['external_id'] ?? '')),
            'prompt' => trim((string) ($row['question_text'] ?? $row['prompt'] ?? '')),
            'explanation' => $this->nullableTrim($row['explanation'] ?? null),
            'option_a' => trim((string) ($row['answer_a'] ?? $row['option_a'] ?? '')),
            'option_b' => trim((string) ($row['answer_b'] ?? $row['option_b'] ?? '')),
            'option_c' => $this->nullableTrim($row['answer_c'] ?? $row['option_c'] ?? null),
            'correct_answer' => strtolower(trim((string) ($row['correct_answer'] ?? ''))),
            'difficulty' => $this->nullableInt($row['difficulty'] ?? null),
            'points' => $this->nullableInt($row['points'] ?? null),
            'question_type' => $questionType,
            'is_active' => $this->boolValue($row['is_active'] ?? true),
            'source' => $this->nullableTrim($row['source'] ?? $manifest['source'] ?? 'manifest-import'),
            'published_at' => $this->nullableTrim($row['published_at'] ?? null),
            'metadata' => $metadata,
            'module_position' => $this->nullableInt(
                $row['module_position']
                    ?? $row['ordinal_number']
                    ?? $metadata['source_ordinal_number']
                    ?? null,
            ),
            'image_path' => $this->nullableTrim($row['image_path'] ?? $row['full_image_path'] ?? null),
            'thumb_path' => $this->nullableTrim($row['thumb_path'] ?? null),
            'video_path' => $this->nullableTrim($row['video_path'] ?? null),
            'poster_path' => $this->nullableTrim($row['poster_path'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{media: array<int, array<string, mixed>>, uploads: array<int, array<string, mixed>>}
     */
    protected function prepareAssets(
        string $categoryCode,
        string $questionExternalId,
        string $prompt,
        string $mediaRoot,
        array $row,
    ): array {
        $media = [];
        $uploads = [];

        if (filled($row['image_path'])) {
            $imageAssetGroup = $this->assetGroupKey(
                categoryCode: $categoryCode,
                questionExternalId: $questionExternalId,
                prompt: $prompt,
                kind: 'image',
                seed: (string) ($row['image_path'] ?? $row['thumb_path'] ?? 'image'),
            );
            $asset = $this->prepareSingleAsset(
                $categoryCode,
                $questionExternalId,
                $prompt,
                (string) $row['image_path'],
                $mediaRoot,
                'image',
                'full',
                [
                    'asset_group' => $imageAssetGroup,
                    'source_role' => 'full',
                ],
            );
            $media[] = $asset['media'];
            $uploads[] = $asset['upload'];
        }

        if (filled($row['thumb_path'])) {
            $imageAssetGroup ??= $this->assetGroupKey(
                categoryCode: $categoryCode,
                questionExternalId: $questionExternalId,
                prompt: $prompt,
                kind: 'image',
                seed: (string) ($row['thumb_path'] ?? $row['image_path'] ?? 'image'),
            );
            $asset = $this->prepareSingleAsset(
                $categoryCode,
                $questionExternalId,
                $prompt,
                (string) $row['thumb_path'],
                $mediaRoot,
                'image',
                'thumb',
                [
                    'asset_group' => $imageAssetGroup,
                    'source_role' => 'thumb',
                ],
            );
            $media[] = $asset['media'];
            $uploads[] = $asset['upload'];
        }

        if (filled($row['video_path'])) {
            $videoAssetGroup = $this->assetGroupKey(
                categoryCode: $categoryCode,
                questionExternalId: $questionExternalId,
                prompt: $prompt,
                kind: 'video',
                seed: (string) ($row['video_path'] ?? $row['poster_path'] ?? 'video'),
            );
            $video = $this->prepareSingleAsset(
                $categoryCode,
                $questionExternalId,
                $prompt,
                (string) $row['video_path'],
                $mediaRoot,
                'video',
                'full',
                [
                    'asset_group' => $videoAssetGroup,
                    'source_role' => 'full',
                ],
            );
            $posterPath = null;

            if (filled($row['poster_path'])) {
                $poster = $this->prepareSingleAsset(
                    $categoryCode,
                    $questionExternalId,
                    $prompt,
                    (string) $row['poster_path'],
                    $mediaRoot,
                    'image',
                    'poster',
                    [
                        'asset_group' => $videoAssetGroup,
                        'source_role' => 'poster',
                    ],
                );
                $posterPath = $poster['media']['path'];
                $uploads[] = $poster['upload'];
            }

            $videoMedia = $video['media'];
            $videoMedia['poster_path'] = $posterPath;

            $media[] = $videoMedia;
            $uploads[] = $video['upload'];
        } elseif (filled($row['poster_path'])) {
            $poster = $this->prepareSingleAsset(
                $categoryCode,
                $questionExternalId,
                $prompt,
                (string) $row['poster_path'],
                $mediaRoot,
                'image',
                'poster',
                [
                    'asset_group' => $this->assetGroupKey(
                        categoryCode: $categoryCode,
                        questionExternalId: $questionExternalId,
                        prompt: $prompt,
                        kind: 'image',
                        seed: (string) $row['poster_path'],
                    ),
                    'source_role' => 'poster',
                ],
            );
            $media[] = $poster['media'];
            $uploads[] = $poster['upload'];
        }

        return [
            'media' => $media,
            'uploads' => $uploads,
        ];
    }

    /**
     * @return array{media: array<string, mixed>, upload: array<string, mixed>}
     */
    protected function prepareSingleAsset(
        string $categoryCode,
        string $questionExternalId,
        string $prompt,
        string $relativePath,
        string $mediaRoot,
        string $kind,
        string $variant,
        array $metadata = [],
    ): array {
        $sourcePath = $this->resolveMediaPath($mediaRoot, $relativePath);
        $mimeType = $this->mimeTypeForPath($sourcePath);
        $bytes = filesize($sourcePath);

        if ($bytes === false || $bytes < 1) {
            throw ValidationException::withMessages([
                $relativePath => 'Media file must not be empty.',
            ]);
        }

        if (! in_array($mimeType, (array) config("media.allowed_mime_types.{$kind}", []), true)) {
            throw ValidationException::withMessages([
                $relativePath => 'Unsupported MIME type for the selected media kind.',
            ]);
        }

        $maxBytes = (int) config("media.max_bytes.{$kind}", 0);

        if ($maxBytes > 0 && $bytes > $maxBytes && $kind === 'image') {
            $optimizedSourcePath = $this->optimizeOversizedImageForImport($sourcePath, $maxBytes);

            if ($optimizedSourcePath !== null) {
                $sourcePath = $optimizedSourcePath;
                $mimeType = $this->mimeTypeForPath($sourcePath);
                $bytes = filesize($sourcePath);
            }
        }

        if ($maxBytes > 0 && $bytes > $maxBytes) {
            throw ValidationException::withMessages([
                $relativePath => 'Media asset exceeds the configured size limit.',
            ]);
        }

        $targetPath = $this->buildTargetPath(
            categoryCode: $categoryCode,
            questionExternalId: $questionExternalId,
            prompt: $prompt,
            kind: $kind,
            variant: $variant,
            extension: pathinfo($sourcePath, PATHINFO_EXTENSION),
            sourcePath: $sourcePath,
        );

        $dimensions = $kind === 'image' ? $this->imageDimensions($sourcePath) : ['width' => null, 'height' => null];

        return [
            'media' => [
                'kind' => $kind,
                'disk' => (string) config('media.upload_disk', 'r2'),
                'path' => $targetPath,
                'poster_path' => null,
                'mime_type' => $mimeType,
                'bytes' => $bytes,
                'duration_seconds' => null,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'variant' => $variant,
                'metadata' => $metadata,
            ],
            'upload' => [
                'kind' => $kind,
                'variant' => $variant,
                'disk' => (string) config('media.upload_disk', 'r2'),
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'mime_type' => $mimeType,
                'bytes' => $bytes,
            ],
        ];
    }

    protected function optimizeOversizedImageForImport(string $sourcePath, int $maxBytes): ?string
    {
        if ($maxBytes < 1 || ! $this->ffmpegAvailable()) {
            return null;
        }

        $optimizedPath = $this->optimizedImagePath($sourcePath, $maxBytes);

        if (is_file($optimizedPath)) {
            $optimizedBytes = filesize($optimizedPath);

            if (
                $optimizedBytes !== false
                && $optimizedBytes > 0
                && $optimizedBytes <= $maxBytes
                && filemtime($optimizedPath) >= filemtime($sourcePath)
            ) {
                return $optimizedPath;
            }

            File::delete($optimizedPath);
        }

        File::ensureDirectoryExists(dirname($optimizedPath));

        foreach ($this->imageOptimizationPresets() as $preset) {
            $this->generateOptimizedImage(
                sourcePath: $sourcePath,
                targetPath: $optimizedPath,
                maxWidth: (int) $preset['max_width'],
                quality: (int) $preset['quality'],
            );

            $optimizedBytes = filesize($optimizedPath);

            if ($optimizedBytes !== false && $optimizedBytes > 0 && $optimizedBytes <= $maxBytes) {
                return $optimizedPath;
            }

            File::delete($optimizedPath);
        }

        return null;
    }

    protected function optimizedImagePath(string $sourcePath, int $maxBytes): string
    {
        $directory = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);

        return $directory.DIRECTORY_SEPARATOR.$filename.'.import-fit-'.$maxBytes.'.webp';
    }

    /**
     * @return array<int, array{max_width:int,quality:int}>
     */
    protected function imageOptimizationPresets(): array
    {
        return [
            ['max_width' => 1920, 'quality' => 82],
            ['max_width' => 1920, 'quality' => 74],
            ['max_width' => 1600, 'quality' => 76],
            ['max_width' => 1600, 'quality' => 68],
            ['max_width' => 1280, 'quality' => 72],
            ['max_width' => 1280, 'quality' => 64],
            ['max_width' => 960, 'quality' => 68],
        ];
    }

    protected function generateOptimizedImage(
        string $sourcePath,
        string $targetPath,
        int $maxWidth,
        int $quality,
    ): void {
        $process = new Process([
            $this->ffmpegBinary(),
            '-y',
            '-i',
            $sourcePath,
            '-vf',
            sprintf("scale='min(%d,iw)':-2", max($maxWidth, 320)),
            '-c:v',
            'libwebp',
            '-quality',
            (string) min(max($quality, 1), 100),
            $targetPath,
        ]);
        $process->setTimeout(null);
        $process->mustRun();
    }

    protected function ffmpegAvailable(): bool
    {
        static $available;

        if ($available !== null) {
            return $available;
        }

        try {
            $process = new Process([$this->ffmpegBinary(), '-version']);
            $process->run();

            return $available = $process->isSuccessful();
        } catch (Throwable) {
            return $available = false;
        }
    }

    protected function ffmpegBinary(): string
    {
        return (string) config('media.ffmpeg_binary', 'ffmpeg');
    }

    protected function assetGroupKey(
        string $categoryCode,
        string $questionExternalId,
        string $prompt,
        string $kind,
        string $seed,
    ): string {
        return sha1(implode('|', [
            strtoupper($categoryCode),
            $questionExternalId !== '' ? $questionExternalId : Str::limit($prompt, 120, ''),
            $kind,
            $seed,
        ]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $assetPlan
     * @return array<int, array<string, mixed>>
     */
    protected function uploadAssets(array $assetPlan): array
    {
        $uploadedAssets = [];

        foreach ($assetPlan as $asset) {
            $disk = Storage::disk((string) $asset['disk']);
            $targetPath = (string) $asset['target_path'];
            $status = 'skipped';

            if (! $disk->exists($targetPath)) {
                $stream = fopen((string) $asset['source_path'], 'rb');

                if ($stream === false) {
                    throw new RuntimeException(sprintf('Nie mozna odczytac pliku %s.', (string) $asset['source_path']));
                }

                try {
                    $success = $disk->put($targetPath, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $success) {
                    throw new RuntimeException(sprintf('Upload assetu %s nie powiodl sie.', $targetPath));
                }

                $status = 'uploaded';
            }

            $uploadedAssets[] = [
                'kind' => $asset['kind'],
                'variant' => $asset['variant'],
                'disk' => $asset['disk'],
                'source_path' => $asset['source_path'],
                'target_path' => $targetPath,
                'bytes' => $asset['bytes'],
                'status' => $status,
            ];
        }

        return $uploadedAssets;
    }

    /**
     * @param  array<int, array<string, mixed>>  $uploadedAssets
     */
    protected function cleanupUploadedAssets(array $uploadedAssets): void
    {
        foreach ($uploadedAssets as $asset) {
            if (($asset['status'] ?? null) !== 'uploaded') {
                continue;
            }

            try {
                Storage::disk((string) $asset['disk'])->delete((string) $asset['target_path']);
            } catch (Throwable) {
                // Cleanup is best-effort after a failed batch.
            }
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $importerReport
     * @return array<string, mixed>
     */
    protected function mergeImporterReport(array $report, array $importerReport): array
    {
        foreach ([
            'categories_total',
            'categories_created',
            'categories_updated',
            'questions_total',
            'questions_created',
            'questions_updated',
            'collections_total',
            'collections_created',
            'collections_updated',
            'modules_total',
            'modules_created',
            'modules_updated',
            'module_assignments_total',
            'module_assignments_created',
            'module_assignments_updated',
            'module_assignments_unchanged',
            'module_assignments_deleted',
            'media_total',
            'media_created',
            'media_updated',
            'media_unchanged',
            'media_deleted',
            'updated_records',
            'created_assets',
            'errors',
            'errors_count',
        ] as $key) {
            $report[$key] = $importerReport[$key] ?? $report[$key] ?? null;
        }

        return $report;
    }

    /**
     * @param  array<int, array<string, mixed>>  $assetPlan
     * @return array<string, mixed>
     */
    protected function baseReport(
        string $manifestPath,
        string $questionsFile,
        string $mediaRoot,
        string $questionsFormat,
        string $batchId,
        bool $dryRun,
        int $rowsTotal,
        array $assetPlan,
    ): array {
        return [
            'batch_id' => $batchId,
            'dry_run' => $dryRun,
            'strict_batch' => true,
            'manifest_path' => $manifestPath,
            'questions_file' => $questionsFile,
            'questions_format' => $questionsFormat,
            'media_root' => $mediaRoot,
            'started_at' => now()->utc()->toIso8601String(),
            'completed_at' => null,
            'rows_total' => $rowsTotal,
            'asset_plan_total' => count($assetPlan),
            'asset_plan' => $assetPlan,
            'uploaded_assets_total' => 0,
            'uploaded_assets' => [],
            'categories_total' => 0,
            'categories_created' => 0,
            'categories_updated' => 0,
            'questions_total' => 0,
            'questions_created' => 0,
            'questions_updated' => 0,
            'collections_total' => 0,
            'collections_created' => 0,
            'collections_updated' => 0,
            'modules_total' => 0,
            'modules_created' => 0,
            'modules_updated' => 0,
            'module_assignments_total' => 0,
            'module_assignments_created' => 0,
            'module_assignments_updated' => 0,
            'module_assignments_unchanged' => 0,
            'module_assignments_deleted' => 0,
            'media_total' => 0,
            'media_created' => 0,
            'media_updated' => 0,
            'media_unchanged' => 0,
            'media_deleted' => 0,
            'errors' => [],
            'errors_count' => 0,
            'updated_records' => [
                'categories' => [],
                'questions' => [],
                'collections' => [],
                'modules' => [],
            ],
            'created_assets' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadManifest(string $manifestPath): array
    {
        if (! is_file($manifestPath)) {
            throw new RuntimeException(sprintf('Nie znaleziono manifestu: %s', $manifestPath));
        }

        $payload = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($payload)) {
            throw new RuntimeException('Manifest JSON ma niepoprawny format.');
        }

        if (blank($payload['questions_file'] ?? null)) {
            throw new RuntimeException('Manifest musi zawierac pole questions_file.');
        }

        return $payload;
    }

    protected function resolveQuestionsFile(string $workingDirectory, array $manifest): string
    {
        return $this->resolveRelativeOrAbsolutePath($workingDirectory, (string) $manifest['questions_file']);
    }

    protected function resolveMediaRoot(string $workingDirectory, array $manifest): string
    {
        return $this->resolveRelativeOrAbsolutePath(
            $workingDirectory,
            (string) ($manifest['media_root'] ?? 'media'),
            allowMissing: false,
            directory: true,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadRows(string $questionsFile): array
    {
        return match ($this->questionsFormat($questionsFile)) {
            'json' => $this->loadJsonRows($questionsFile),
            'csv' => $this->loadCsvRows($questionsFile),
            default => throw new RuntimeException('Questions file must use .csv or .json format.'),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadJsonRows(string $questionsFile): array
    {
        $payload = json_decode((string) file_get_contents($questionsFile), true);

        if (is_array($payload) && array_is_list($payload)) {
            return array_map(fn ($row) => is_array($row) ? $row : [], $payload);
        }

        if (is_array($payload) && is_array($payload['questions'] ?? null)) {
            return array_map(fn ($row) => is_array($row) ? $row : [], $payload['questions']);
        }

        throw new RuntimeException('Questions JSON must be an array of rows or an object with a questions array.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadCsvRows(string $questionsFile): array
    {
        $handle = fopen($questionsFile, 'rb');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Nie mozna odczytac pliku %s.', $questionsFile));
        }

        $header = fgetcsv($handle);

        if (! is_array($header)) {
            fclose($handle);

            throw new RuntimeException('Questions CSV musi zawierac naglowek.');
        }

        $header = array_map(fn ($column) => trim((string) $column), $header);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $rows[] = array_combine(
                $header,
                array_pad($row, count($header), null),
            ) ?: [];
        }

        fclose($handle);

        return $rows;
    }

    protected function questionsFormat(string $questionsFile): string
    {
        return strtolower((string) pathinfo($questionsFile, PATHINFO_EXTENSION));
    }

    protected function resolveMediaPath(string $mediaRoot, string $relativePath): string
    {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $candidates = [];

        if ($this->isAbsolutePath($relativePath)) {
            $candidates[] = $relativePath;
        } else {
            $candidates[] = $this->normalizeAbsolutePath($relativePath);
            $candidates[] = $mediaRoot.DIRECTORY_SEPARATOR.$relativePath;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages([
            $relativePath => 'Referenced media file was not found in the staging directory.',
        ]);
    }

    protected function buildTargetPath(
        string $categoryCode,
        string $questionExternalId,
        string $prompt,
        string $kind,
        string $variant,
        string $extension,
        string $sourcePath,
    ): string {
        $prefix = trim((string) config('media.upload_prefix', 'media/questions'), '/');
        $categorySegment = Str::slug($categoryCode) ?: 'category';
        $questionSegment = Str::slug($questionExternalId !== '' ? $questionExternalId : Str::limit($prompt, 60, ''))
            ?: 'question-'.substr(sha1($prompt), 0, 12);
        $hash = substr(hash_file('sha256', $sourcePath) ?: sha1($sourcePath), 0, 12);
        $extension = strtolower(trim($extension));

        return implode('/', [
            $prefix,
            $categorySegment,
            $questionSegment,
            $kind,
            "{$variant}.{$hash}.{$extension}",
        ]);
    }

    protected function mimeTypeForPath(string $path): string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'mp4' => 'video/mp4',
            default => throw ValidationException::withMessages([
                $path => 'Unsupported file extension for import pipeline.',
            ]),
        };
    }

    /**
     * @return array{width:?int,height:?int}
     */
    protected function imageDimensions(string $path): array
    {
        $size = @getimagesize($path);

        if (! is_array($size)) {
            return [
                'width' => null,
                'height' => null,
            ];
        }

        return [
            'width' => isset($size[0]) ? (int) $size[0] : null,
            'height' => isset($size[1]) ? (int) $size[1] : null,
        ];
    }

    protected function normalizeAbsolutePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function resolveRelativeOrAbsolutePath(
        string $workingDirectory,
        string $path,
        bool $allowMissing = false,
        bool $directory = false,
    ): string {
        $candidate = $this->isAbsolutePath($path)
            ? $path
            : $workingDirectory.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($allowMissing) {
            return $candidate;
        }

        $exists = $directory ? is_dir($candidate) : is_file($candidate);

        if (! $exists) {
            throw new RuntimeException(sprintf(
                $directory ? 'Nie znaleziono katalogu: %s' : 'Nie znaleziono pliku: %s',
                $candidate,
            ));
        }

        return $candidate;
    }

    protected function isAbsolutePath(string $path): bool
    {
        return Str::startsWith($path, ['/', '\\']) || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    protected function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function metadataValue(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        return is_array($decoded) ? $decoded : null;
    }
}
