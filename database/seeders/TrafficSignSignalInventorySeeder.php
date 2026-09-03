<?php

namespace Database\Seeders;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishSignalSignCatalog;
use App\Support\PolishSignalSignContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignSignalInventorySeeder extends Seeder
{
    private const BATCH_LABEL = 'rollout-16-signals-inventory';

    public function run(
        PolishSignalSignCatalog $polishSignalSignCatalog,
        PolishSignalSignContentBuilder $polishSignalSignContentBuilder,
    ): void {
        $this->call(TrafficSignSeoSeeder::class);

        $author = TrafficSignAuthorProfile::upsert();

        $category = TrafficSignCategory::query()->firstOrCreate(
            ['slug' => 'sygnaly-swietlne'],
            [
                'name' => 'Sygnały świetlne',
                'description' => 'Sygnalizatory świetlne regulujące pierwszeństwo i kierujące ruchem na skrzyżowaniach.',
                'intro_title' => 'Hierarchia i sygnały świetlne na drodze',
                'intro_body' => 'Sygnały świetlne (grupa S) to najważniejsze urządzenia na drodze zaraz po poleceniach policjanta. Wyłączają one przepisy o pierwszeństwie łamanym i znakach pionowych.',
                'sort_order' => 55,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
        );

        foreach ($polishSignalSignCatalog->all() as $index => $signData) {
            $sign = TrafficSign::query()->firstOrNew([
                'code' => $signData['code'],
            ]);

            if (! $sign->exists) {
                $content = $polishSignalSignContentBuilder->build($signData, $index);

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
                    'workflow_status' => TrafficSign::WORKFLOW_IN_REVIEW,
                    'is_published' => false,
                    'published_at' => null,
                    'reviewed_at' => null,
                    'source_checked_at' => null,
                    'freshness_review_due_at' => null,
                ]);
            } else {
                $sign->fill([
                    'content_author_id' => $sign->content_author_id ?: $author->getKey(),
                    'traffic_sign_category_id' => $sign->traffic_sign_category_id ?: $category->getKey(),
                ]);

                if (! $sign->isPubliclyVisible()) {
                    $content = $polishSignalSignContentBuilder->build($signData, $index);

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
                        'workflow_status' => TrafficSign::WORKFLOW_IN_REVIEW,
                        'reviewed_at' => null,
                        'source_checked_at' => null,
                        'freshness_review_due_at' => null,
                        'is_published' => false,
                        'published_at' => null,
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
                    'mapped_title' => $sign->publicTitle(),
                    'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                    'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                    'priority' => TrafficSignQueryMapEntry::PRIORITY_P2,
                    'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
                    'batch_label' => self::BATCH_LABEL,
                    'target_path' => '/znaki-drogowe/'.$signData['slug'],
                    'traffic_sign_id' => $sign->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'watch_reason' => 'Element inwentarza sygnałów S.',
                    'source_plan' => 'Oprzeć stronę o krytyczne zasady jazdy i hierarchię ważności.',
                    'correction_notes' => 'Upewnić się, że zielona strzałka ma dobrze wyjaśnione warunki.',
                    'competitor_notes' => 'Wpis buduje kompletność katalogu sygnałów.',
                    'first_mover_note' => null,
                    'notes' => 'Wpis dodany automatycznie z inwentarza sygnałów.',
                ],
            );
        }
    }
}
