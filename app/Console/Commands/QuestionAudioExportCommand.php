<?php

namespace App\Console\Commands;

use App\Support\QuestionAudioExportManifestBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class QuestionAudioExportCommand extends Command
{
    protected $signature = 'questions:audio-export
        {--external-id=* : Limit export to selected external_id values}
        {--category= : Limit export to one license category code}
        {--question-scope= : Limit export to all, basic, or specialist questions}
        {--types=question : Comma-separated audio types}
        {--limit= : Limit number of canonical questions}
        {--output= : Output JSON manifest path}
        {--json : Print manifest to stdout}';

    protected $description = 'Export question audio generation manifest for the local audio generator.';

    public function handle(QuestionAudioExportManifestBuilder $manifestBuilder): int
    {
        try {
            $manifest = $manifestBuilder->build([
                'external_ids' => (array) $this->option('external-id'),
                'category' => $this->option('category') ?: null,
                'question_scope' => $this->option('question-scope') ?: null,
                'types' => (string) $this->option('types'),
                'limit' => filled($this->option('limit')) ? (int) $this->option('limit') : null,
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($json)) {
            $this->error('Cannot encode audio manifest.');

            return self::FAILURE;
        }

        $output = trim((string) ($this->option('output') ?? ''));

        if ($output !== '') {
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $json.PHP_EOL);
            $this->info("Audio manifest saved: {$output}");
            $this->line('Items: '.$manifest['items_count']);
            $this->line('Review required: '.($manifest['review_count'] ?? 0));
            $this->line('Errors: '.$manifest['errors_count']);
        }

        if ((bool) $this->option('json') || $output === '') {
            $this->line($json);
        }

        return ((int) $manifest['errors_count'] > 0) ? self::FAILURE : self::SUCCESS;
    }
}
