<?php

namespace App\Console\Commands;

use App\Support\NewsroomSemanticLinkService;
use Illuminate\Console\Command;

final class AuditNewsroomLinksCommand extends Command
{
    protected $signature = 'newsroom:audit-links';

    protected $description = 'Audit public newsroom semantic-link, source, breaking, and target integrity.';

    public function handle(NewsroomSemanticLinkService $semanticLinks): int
    {
        $errors = $semanticLinks->auditAll();

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Newsroom link audit passed.');

        return self::SUCCESS;
    }
}
