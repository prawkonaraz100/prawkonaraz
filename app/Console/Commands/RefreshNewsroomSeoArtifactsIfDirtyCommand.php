<?php

namespace App\Console\Commands;

use App\Support\NewsroomSeoArtifactRefreshCoordinator;
use App\Support\SeoSitemapAuditor;
use App\Support\SeoSitemapGenerator;
use Illuminate\Console\Command;
use Throwable;

final class RefreshNewsroomSeoArtifactsIfDirtyCommand extends Command
{
    protected $signature = 'newsroom:refresh-seo-artifacts-if-dirty';

    protected $description = 'Refresh static SEO sitemap artifacts when the newsroom dirty version requires it.';

    public function handle(
        NewsroomSeoArtifactRefreshCoordinator $coordinator,
        SeoSitemapGenerator $generator,
        SeoSitemapAuditor $auditor,
    ): int {
        $lock = $coordinator->lock();

        if (! $lock->get()) {
            $this->comment('Newsroom SEO artifact refresh is already locked by another process.');

            return self::SUCCESS;
        }

        try {
            if (! $coordinator->isDirty()) {
                $this->info('Newsroom SEO artifacts are already clean.');

                return self::SUCCESS;
            }

            $version = $coordinator->currentVersion();

            try {
                $files = $generator->generate();
                $errors = $auditor->audit();
            } catch (Throwable $exception) {
                report($exception);
                $this->error('Newsroom SEO artifact refresh failed: '.$exception->getMessage());

                return self::FAILURE;
            }

            foreach ($files as $file) {
                $this->line(sprintf(
                    '%s urls=%d bytes=%d',
                    $file['path'],
                    $file['urls'],
                    $file['bytes'],
                ));
            }

            if ($errors !== []) {
                foreach ($errors as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            if (! $coordinator->markCleanIfUnchanged($version)) {
                $this->warn('Newsroom SEO artifacts changed during refresh; dirty state was retained for the next pass.');

                return self::SUCCESS;
            }

            $this->info(sprintf(
                'Newsroom SEO artifacts refreshed for version %d.',
                $version,
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
