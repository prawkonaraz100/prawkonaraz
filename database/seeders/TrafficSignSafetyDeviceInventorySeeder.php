<?php

namespace Database\Seeders;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishSafetyDeviceCatalog;
use App\Support\PolishSafetyDeviceContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignSafetyDeviceInventorySeeder extends Seeder
{
    private const BATCH_LABEL = 'rollout-22-safety-device-inventory';

    public function run(
        PolishSafetyDeviceCatalog $polishSafetyDeviceCatalog,
        PolishSafetyDeviceContentBuilder $polishSafetyDeviceContentBuilder,
    ): void {
        $this->call(TrafficSignSeoSeeder::class);

        $author = TrafficSignAuthorProfile::upsert();

        $category = TrafficSignCategory::query()->firstOrCreate(
            ['slug' => 'urzadzenia-bezpieczenstwa-ruchu'],
            [
                'name' => 'Urządzenia bezpieczeństwa ruchu',
                'description' => 'Optyczne prowadzenie ruchu i zabezpieczenie pieszego oraz robót drogowych.',
                'intro_title' => 'Czym są urządzenia BRD?',
                'intro_body' => 'Pachołki, sierżanty i słupki prowadzące to elementy, które nie są znakami, ale fizycznie wytyczają tor jazdy. Dowiedz się, po jakich kolorach odblasków rozpoznasz lewą i prawą stronę drogi w nocy.',
                'sort_order' => 85,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
        );

        foreach ($polishSafetyDeviceCatalog->all() as $index => $deviceData) {
            $sign = TrafficSign::query()->firstOrNew([
                'code' => $deviceData['code'],
            ]);

            if (! $sign->exists) {
                $content = $polishSafetyDeviceContentBuilder->build($deviceData, $index);

                $sign->fill([
                    'content_author_id' => $author->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'slug' => $deviceData['slug'],
                    'name' => $deviceData['name'],
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
                    $content = $polishSafetyDeviceContentBuilder->build($deviceData, $index);

                    $sign->fill([
                        'slug' => $sign->slug ?: $deviceData['slug'],
                        'name' => $sign->name ?: $deviceData['name'],
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
                ['primary_query' => $deviceData['primary_query']],
                [
                    'mapped_title' => $deviceData['code'].' '.$deviceData['name'],
                    'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                    'search_intent' => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
                    'priority' => TrafficSignQueryMapEntry::PRIORITY_P1,
                    'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
                    'batch_label' => self::BATCH_LABEL,
                    'target_path' => '/znaki-drogowe/'.$deviceData['slug'],
                    'traffic_sign_id' => $sign->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'watch_reason' => 'Urządzenie BRD w inwentarzu.',
                    'source_plan' => 'Wyjaśnić jak reagować na urządzenia BRD.',
                    'correction_notes' => 'Zaznaczyć kierunek omijania i kolory odblasków.',
                    'competitor_notes' => 'Kluczowe pytania egzaminacyjne z Grupy U.',
                    'first_mover_note' => null,
                    'notes' => 'Wpis dodany automatycznie z inwentarza.',
                ],
            );
        }
    }
}
