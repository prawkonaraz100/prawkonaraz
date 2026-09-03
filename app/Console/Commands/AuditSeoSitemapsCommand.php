<?php

namespace App\Console\Commands;

use App\Support\SeoSitemapAuditor;
use Illuminate\Console\Command;

class AuditSeoSitemapsCommand extends Command
{
    protected $signature = 'seo:audit-sitemaps';

    protected $description = 'Audit generated sitemap files and robots.txt for SEO contract issues.';

    public function handle(SeoSitemapAuditor $auditor): int
    {
        $errors = $auditor->audit();

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Sitemap audit passed.');

        return self::SUCCESS;
    }
}
