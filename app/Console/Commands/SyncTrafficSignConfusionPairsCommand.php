<?php

namespace App\Console\Commands;

use App\Models\TrafficSignConfusionPair;
use App\Support\TrafficSignConfusionPairService;
use Illuminate\Console\Command;

class SyncTrafficSignConfusionPairsCommand extends Command
{
    protected $signature = 'traffic-signs:sync-confusion-pairs
        {--fresh : Delete existing supporting-page pairs before syncing}
        {--category=* : Limit sync to selected traffic sign category slugs}';

    protected $description = 'Materialize traffic sign confusion pairs from supporting comparison pages.';

    public function handle(TrafficSignConfusionPairService $confusionPairService): int
    {
        $categorySlugs = array_values(array_filter((array) $this->option('category')));

        if ((bool) $this->option('fresh')) {
            $deleted = TrafficSignConfusionPair::query()
                ->where('source', TrafficSignConfusionPair::SOURCE_SUPPORTING_PAGE)
                ->delete();

            $this->info("Deleted {$deleted} supporting-page pairs.");
        }

        $upserted = $confusionPairService->upsertFromSupportingPages($categorySlugs !== [] ? $categorySlugs : null);

        $this->info("Synced {$upserted} directed confusion pairs.");

        return self::SUCCESS;
    }
}
