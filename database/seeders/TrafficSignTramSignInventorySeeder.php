<?php

namespace Database\Seeders;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishTramSignCatalog;
use App\Support\PolishTramSignContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignTramSignInventorySeeder extends Seeder
{
    private const BATCH_LABEL = 'rollout-21-tram-sign-inventory';

    public function run(
        PolishTramSignCatalog $polishTramSignCatalog,
        PolishTramSignContentBuilder $polishTramSignContentBuilder,
    ): void {
        $this->call(TrafficSignSeoSeeder::class);

        $author = TrafficSignAuthorProfile::upsert();

        $category = TrafficSignCategory::query()->firstOrCreate(
            ['slug' => 'znaki-tramwajowe'],
            [
                'name' => 'Znaki tramwajowe',
                'description' => 'Specjalistyczne znaki pionowe umieszczane przy torowiskach, dedykowane kierującym tramwajami.',
                'intro_title' => 'Czym są znaki tramwajowe',
                'intro_body' => 'Plakietki w kształcie rombów i kwadratów wiszące przy sieci trakcyjnej dotyczą wyłącznie motorniczych. Dowiedz się, dlaczego jako kierowca samochodu nie musisz na nie zwracać uwagi.',
                'sort_order' => 80,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
        );

        foreach ($polishTramSignCatalog->all() as $index => $signData) {
            $sign = TrafficSign::query()->firstOrNew([
                'code' => $signData['code'],
            ]);

            if (! $sign->exists) {
                $content = $polishTramSignContentBuilder->build($signData, $index);

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
                    $content = $polishTramSignContentBuilder->build($signData, $index);

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
                    'priority' => TrafficSignQueryMapEntry::PRIORITY_P3,
                    'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
                    'batch_label' => self::BATCH_LABEL,
                    'target_path' => '/znaki-drogowe/'.$signData['slug'],
                    'traffic_sign_id' => $sign->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'watch_reason' => 'Znak tramwajowy w inwentarzu.',
                    'source_plan' => 'Wyjaśnić różnicę między znakiem drogowym a tramwajowym.',
                    'correction_notes' => 'Zaznaczyć, że nie dotyczy aut osobowych.',
                    'competitor_notes' => 'Niszowe zapytania egzaminacyjne.',
                    'first_mover_note' => null,
                    'notes' => 'Wpis dodany automatycznie z inwentarza.',
                ],
            );
        }
    }
}
