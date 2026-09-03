<?php

namespace Database\Seeders;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use App\Support\PolishProhibitionSignCatalog;
use App\Support\PolishProhibitionSignContentBuilder;
use App\Support\TrafficSignAuthorProfile;
use Illuminate\Database\Seeder;

class TrafficSignProhibitionInventorySeeder extends Seeder
{
    public function run(
        PolishProhibitionSignCatalog $polishProhibitionSignCatalog,
        PolishProhibitionSignContentBuilder $polishProhibitionSignContentBuilder,
    ): void {
        $this->call(TrafficSignSeoSeeder::class);

        $author = TrafficSignAuthorProfile::upsert();

        $category = TrafficSignCategory::query()->firstOrCreate(
            ['slug' => 'znaki-zakazu'],
            [
                'name' => 'Znaki zakazu',
                'description' => 'Znaki zakazu wprowadzają konkretne ograniczenia lub zakazy zachowania na drodze.',
                'intro_title' => 'Jak czytać znaki zakazu',
                'intro_body' => 'W tej kategorii skupiamy się na tym, czego kierowca nie może zrobić i jakie są praktyczne skutki zignorowania znaku.',
                'sort_order' => 15,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
        );

        foreach ($polishProhibitionSignCatalog->all() as $index => $signData) {
            $sign = TrafficSign::query()->firstOrNew([
                'code' => $signData['code'],
            ]);

            if (! $sign->exists) {
                $content = $polishProhibitionSignContentBuilder->build($signData, $index);

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
                    $content = $polishProhibitionSignContentBuilder->build($signData, $index);

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
                    'batch_label' => 'rollout-02-prohibitions',
                    'target_path' => '/znaki-drogowe/'.$signData['slug'],
                    'traffic_sign_id' => $sign->getKey(),
                    'traffic_sign_category_id' => $category->getKey(),
                    'watch_reason' => 'Element pełnego oficjalnego inwentarza polskich znaków zakazu. Nie publikujemy dalej, dopóki nie domkniemy pełnej karty treściowej.',
                    'source_plan' => 'Oprzeć stronę o oficjalne znaczenie znaku z rozporządzenia oraz praktyczny kontekst egzaminacyjny i drogowy.',
                    'correction_notes' => 'Przed publikacją sprawdzić, czy znak nie wymaga wartości zmiennej, scenariusza wyjątków lub mocniejszego rozróżnienia wobec podobnych zakazów.',
                    'competitor_notes' => 'Ten wpis istnieje po to, żeby żadna strona znaku zakazu nie wypadła z planu produkcji treści.',
                    'first_mover_note' => null,
                    'notes' => 'Wpis dodany automatycznie z kanonicznego inwentarza znaków zakazu.',
                ],
            );
        }
    }

    protected function priorityFor(string $code): string
    {
        return in_array($code, [
            'B-3',
            'B-5',
            'B-20',
            'B-21',
            'B-22',
            'B-23',
            'B-25',
            'B-31',
            'B-33',
            'B-37',
            'B-38',
            'B-39',
            'B-40',
            'B-41',
            'B-42',
            'B-43',
            'B-44',
        ], true)
            ? TrafficSignQueryMapEntry::PRIORITY_P1
            : TrafficSignQueryMapEntry::PRIORITY_P2;
    }

    protected function searchIntentFor(string $code): string
    {
        return match ($code) {
            'B-20', 'B-21', 'B-22', 'B-23', 'B-25', 'B-26', 'B-33', 'B-43', 'B-44' => TrafficSignQueryMapEntry::INTENT_EXAM,
            'B-35', 'B-36', 'B-37', 'B-38', 'B-39', 'B-40' => TrafficSignQueryMapEntry::INTENT_COMPARISON,
            'B-15', 'B-16', 'B-17', 'B-18', 'B-19' => TrafficSignQueryMapEntry::INTENT_LEGAL,
            default => TrafficSignQueryMapEntry::INTENT_INFORMATIONAL,
        };
    }
}
