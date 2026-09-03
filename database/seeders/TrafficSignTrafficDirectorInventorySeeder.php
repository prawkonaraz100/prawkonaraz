<?php

namespace Database\Seeders;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishTrafficDirectorCatalog;
use App\Support\PolishTrafficDirectorContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignTrafficDirectorInventorySeeder extends Seeder
{
    private const BATCH_LABEL = 'rollout-18-traffic-director-inventory';

    public function run(
        PolishTrafficDirectorCatalog $polishTrafficDirectorCatalog,
        PolishTrafficDirectorContentBuilder $polishTrafficDirectorContentBuilder,
    ): void {
        $this->call(TrafficSignSeoSeeder::class);

        $author = TrafficSignAuthorProfile::upsert();

        $category = TrafficSignCategory::query()->firstOrCreate(
            ['slug' => 'osoba-kierujaca-ruchem'],
            [
                'name' => 'Osoba kierująca ruchem',
                'description' => 'Postawy i sygnały dawane przez policjanta lub inną osobę uprawnioną do kierowania ruchem.',
                'intro_title' => 'Jak odczytywać sygnały policjanta',
                'intro_body' => 'Osoba kierująca ruchem ma najwyższą hierarchię na drodze. Jej polecenia znoszą zasady wynikające ze znaków, świateł oraz przepisów ogólnych.',
                'sort_order' => 65,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
        );

        foreach ($polishTrafficDirectorCatalog->all() as $index => $signData) {
            $sign = TrafficSign::query()->firstOrNew([
                'code' => $signData['code'],
            ]);

            if (! $sign->exists) {
                $content = $polishTrafficDirectorContentBuilder->build($signData, $index);

                $sign->fill([
                    'content_author_id' => $author->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'slug' => $signData['slug'],
                    'name' => $signData['name'],
                    'intro_definition' => $content['intro_definition'],
                    'meaning' => $content['meaning'],
                    'placement' => $content['placement'],
                    'driver_behavior' => $content['driver_behavior'],
                    'legal_summary' => $content['legal_summary'],
                    'legal_reference_label' => $content['legal_reference_label'],
                    'legal_reference_url' => $content['legal_reference_url'],
                    'fine_summary' => $content['fine_summary'],
                    'common_mistakes' => $content['common_mistakes'],
                    'editorial_notes' => $content['editorial_notes'],
                    'review_notes' => $content['review_notes'],
                    'source_notes' => $content['source_notes'],
                    'faq_items' => $content['faq_items'],
                    'meta_title' => $content['meta_title'],
                    'meta_description' => $content['meta_description'],
                    'image_path' => $content['image_path'],
                    'image_alt' => $content['image_alt'],
                    'image_width' => $content['image_width'],
                    'image_height' => $content['image_height'],
                    'og_image_path' => $content['og_image_path'],
                    'og_image_alt' => $content['og_image_alt'],
                    'og_image_width' => $content['og_image_width'],
                    'og_image_height' => $content['og_image_height'],
                    'sort_order' => $content['sort_order'],
                    'workflow_status' => TrafficSign::WORKFLOW_PUBLISHED,
                    'is_published' => true,
                    'published_at' => now()->subDay(),
                    'reviewed_at' => now()->subDay(),
                    'source_checked_at' => now()->subDay(),
                    'freshness_review_due_at' => null,
                ]);
            } else {
                $sign->fill([
                    'content_author_id' => $sign->content_author_id ?: $author->getKey(),
                    'traffic_sign_category_id' => $sign->traffic_sign_category_id ?: $category->getKey(),
                ]);

                if (! $sign->isPubliclyVisible()) {
                    $content = $polishTrafficDirectorContentBuilder->build($signData, $index);

                    $sign->fill([
                        'slug' => $sign->slug ?: $signData['slug'],
                        'name' => $sign->name ?: $signData['name'],
                        'intro_definition' => $content['intro_definition'],
                        'meaning' => $content['meaning'],
                        'placement' => $content['placement'],
                        'driver_behavior' => $content['driver_behavior'],
                        'legal_summary' => $content['legal_summary'],
                        'legal_reference_label' => $content['legal_reference_label'],
                        'legal_reference_url' => $content['legal_reference_url'],
                        'fine_summary' => $content['fine_summary'],
                        'common_mistakes' => $content['common_mistakes'],
                        'editorial_notes' => $content['editorial_notes'],
                        'review_notes' => $content['review_notes'],
                        'source_notes' => $content['source_notes'],
                        'faq_items' => $content['faq_items'],
                        'meta_title' => $content['meta_title'],
                        'meta_description' => $content['meta_description'],
                        'image_path' => $content['image_path'],
                        'image_alt' => $content['image_alt'],
                        'image_width' => $content['image_width'],
                        'image_height' => $content['image_height'],
                        'og_image_path' => $content['og_image_path'],
                        'og_image_alt' => $content['og_image_alt'],
                        'og_image_width' => $content['og_image_width'],
                        'og_image_height' => $content['og_image_height'],
                        'sort_order' => $content['sort_order'],
                        'workflow_status' => TrafficSign::WORKFLOW_PUBLISHED,
                        'reviewed_at' => now()->subDay(),
                        'source_checked_at' => now()->subDay(),
                        'freshness_review_due_at' => null,
                        'is_published' => true,
                        'published_at' => now()->subDay(),
                    ]);
                }
            }

            $sign->save();

            if ($sign->isPubliclyVisible()) {
                continue;
            }

            TrafficSignQueryMapEntry::query()->updateOrCreate(
                ['primary_query' => $signData['primary_query']],
                [
                    'mapped_title' => $signData['code'].' '.$signData['name'],
                    'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                    'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                    'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                    'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
                    'batch_label' => self::BATCH_LABEL,
                    'target_path' => '/znaki-drogowe/'.$signData['slug'],
                    'traffic_sign_id' => $sign->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'watch_reason' => 'Osoba kierująca ruchem w inwentarzu.',
                    'source_plan' => 'Wyjaśnić postawę i odpowiednik świetlny.',
                    'correction_notes' => 'Zaznaczyć najwyższą hierarchię policjanta.',
                    'competitor_notes' => 'Często wyszukiwane przed egzaminem.',
                    'first_mover_note' => null,
                    'notes' => 'Wpis dodany automatycznie z inwentarza.',
                ],
            );
        }
    }
}
