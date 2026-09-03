<?php

namespace App\Console\Commands;

use App\Support\SeoSitemapAuditor;
use App\Support\SeoSitemapGenerator;
use Illuminate\Console\Command;

class RefreshSeoSitemapsCommand extends Command
{
    protected $signature = 'seo:refresh-sitemaps {--dry-run : Build the sitemap payloads without writing files or running the file audit}';

    protected $description = 'Generate static SEO sitemap files and audit the generated output.';

    public function handle(SeoSitemapGenerator $generator, SeoSitemapAuditor $auditor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $files = $generator->generate($dryRun);

        foreach ($files as $file) {
            $this->line(sprintf(
                '%s urls=%d bytes=%d',
                $file['path'],
                $file['urls'],
                $file['bytes'],
            ));
        }

        if ($dryRun) {
            $this->info('Sitemap refresh dry-run finished.');

            return self::SUCCESS;
        }

        $errors = $auditor->audit();

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Sitemap files generated and audit passed.');

        return self::SUCCESS;
    }
}
