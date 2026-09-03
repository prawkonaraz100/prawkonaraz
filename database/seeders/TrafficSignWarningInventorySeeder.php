<?php

namespace Database\Seeders;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishWarningSignCatalog;
use App\Support\PolishWarningSignContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignWarningInventorySeeder extends Seeder
{
    private const BATCH_LABEL = 'rollout-07-warnings-inventory';

    public function run(
        PolishWarningSignCatalog $polishWarningSignCatalog,
        PolishWarningSignContentBuilder $polishWarningSignContentBuilder,
    ): void {
        $this->call(TrafficSignSeoSeeder::class);

        $author = TrafficSignAuthorProfile::upsert();

        $category = TrafficSignCategory::query()->firstOrCreate(
            ['slug' => 'znaki-ostrzegawcze'],
            [
                'name' => 'Znaki ostrzegawcze',
                'description' => 'Znaki ostrzegawcze uprzedzają o zagrożeniu i wymagają wcześniejszej reakcji kierowcy.',
                'intro_title' => 'Jak czytać znaki ostrzegawcze',
                'intro_body' => 'Ta grupa znaków ma przygotować kierowcę na zmianę warunków na drodze jeszcze zanim dojedzie do miejsca zagrożenia.',
                'sort_order' => 10,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
        );

        foreach ($polishWarningSignCatalog->all() as $index => $signData) {
            $sign = TrafficSign::query()->firstOrNew([
                'code' => $signData['code'],
            ]);

            if (! $sign->exists) {
                $content = $polishWarningSignContentBuilder->build($signData, $index);

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
                    $content = $polishWarningSignContentBuilder->build($signData, $index);

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
                    'mapped_title' => $signData['code'].' '.$signData['name'],
                    'target_type' => TrafficSignQueryMapEntry::TARGET_SIGN,
                    'search_intent' => $this->searchIntentFor($signData['code']),
                    'priority' => $this->priorityFor($signData['code']),
                    'rollout_status' => TrafficSignQueryMapEntry::STATUS_BACKLOG,
                    'batch_label' => self::BATCH_LABEL,
                    'target_path' => '/znaki-drogowe/'.$signData['slug'],
                    'traffic_sign_id' => $sign->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'watch_reason' => 'Element pełnego oficjalnego inwentarza polskich znaków ostrzegawczych. Chcemy utrzymać pełne pokrycie kategorii bez luk w katalogu kodów.',
                    'source_plan' => 'Oprzeć stronę o oficjalne znaczenie znaku z rozporządzenia oraz praktyczny kontekst zachowania kierowcy przed miejscem zagrożenia.',
                    'correction_notes' => 'Przed publikacją doprecyzować przykład sytuacyjny, typowe błędy kierowcy i relację do podobnych znaków z tej samej rodziny.',
                    'competitor_notes' => 'Ten wpis utrzymuje kompletność katalogu warning signs i ułatwia planowanie kolejności rolloutów według realnej intencji użytkownika.',
                    'first_mover_note' => null,
                    'notes' => 'Wpis dodany automatycznie z kanonicznego inwentarza znaków ostrzegawczych.',
                ],
            );
        }
    }

    protected function priorityFor(string $code): string
    {
        return in_array($code, [
            'A-5',
            'A-6a',
            'A-6b',
            'A-6c',
            'A-7',
            'A-8',
            'A-16',
            'A-17',
            'A-20',
            'A-24',
            'A-29',
            'A-30',
        ], true)
            ? TrafficSignQueryMapEntry::PRIORITY_P1
            : TrafficSignQueryMapEntry::PRIORITY_P2;
    }

    protected function searchIntentFor(string $code): string
    {
        return match ($code) {
            'A-5', 'A-6a', 'A-6b', 'A-6c', 'A-6d', 'A-6e', 'A-7', 'A-8', 'A-16', 'A-17', 'A-20', 'A-24', 'A-29' => TrafficSignQueryMapEntry::INTENT_EXAM,
            'A-1', 'A-2', 'A-3', 'A-4', 'A-11', 'A-11a', 'A-14', 'A-15', 'A-18b', 'A-19', 'A-22', 'A-23', 'A-31', 'A-32', 'A-33' => TrafficSignQueryMapEntry::INTENT_BEHAVIORAL,
            default => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
        };
    }
}
