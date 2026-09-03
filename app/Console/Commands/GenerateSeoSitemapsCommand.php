<?php

namespace App\Console\Commands;

use App\Support\SeoSitemapGenerator;
use Illuminate\Console\Command;

class GenerateSeoSitemapsCommand extends Command
{
    protected $signature = 'seo:generate-sitemaps {--dry-run : Build the sitemap payloads without writing files}';

    protected $description = 'Generate static SEO sitemap XML files under public/.';

    public function handle(SeoSitemapGenerator $generator): int
    {
        $files = $generator->generate((bool) $this->option('dry-run'));

        foreach ($files as $file) {
            $this->line(sprintf(
                '%s urls=%d bytes=%d',
                $file['path'],
                $file['urls'],
                $file['bytes'],
            ));
        }

        $this->info($this->option('dry-run') ? 'Sitemap dry-run finished.' : 'Sitemap files generated.');

        return self::SUCCESS;
    }
}
