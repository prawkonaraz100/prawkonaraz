<?php

namespace App\Console\Commands;

use App\Support\PublicQuestionCatalogService;
use App\Support\PublicQuestionRelationsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class MakeQuestionRelationV2PreviewUrlCommand extends Command
{
    protected $signature = 'seo:make-question-relation-v2-preview-url
        {question : External ID pytania objęte aktywnym runem V2 shadow}
        {--minutes=120 : Ważność linku w minutach (5–1440)}';

    protected $description = 'Generate a temporary signed noindex URL that renders V2 relations for one public question only.';

    public function handle(
        PublicQuestionCatalogService $catalog,
        PublicQuestionRelationsService $relations,
    ): int {
        $minutes = (int) $this->option('minutes');

        if ($minutes < 5 || $minutes > 1440) {
            $this->error('Opcja --minutes musi mieścić się w zakresie 5–1440.');

            return self::FAILURE;
        }

        $externalId = trim((string) $this->argument('question'));
        $question = $catalog->findCanonicalQuestionByExternalId($externalId);

        if ($question === null) {
            $this->error("Nie znaleziono kanonicznego pytania {$externalId}.");

            return self::FAILURE;
        }

        $preview = $relations->v2SignedPreviewForQuestion($question);

        if ($preview === null || (int) $preview['total'] < 1) {
            $this->error('Pytanie nie ma aktywnego snapshotu V2 w trybie shadow; URL nie został wygenerowany.');

            return self::FAILURE;
        }

        $url = URL::temporarySignedRoute(
            'public.questions.show',
            now()->addMinutes($minutes),
            [
                'externalId' => $catalog->publicExternalIdForQuestion($question),
                'slug' => $catalog->slugForQuestion($question),
                'relation_preview' => 'v2',
            ],
        );

        $this->line($url);
        $this->info("Podgląd obejmuje {$preview['total']} rekomendacji i wygasa za {$minutes} min. Nie zmienia rolloutu ani HTML zwykłego URL-a.");

        return self::SUCCESS;
    }
}
