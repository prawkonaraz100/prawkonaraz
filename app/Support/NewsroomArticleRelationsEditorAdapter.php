<?php

namespace App\Support;

use App\Models\ContentArticle;
use App\Models\ContentTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\TrafficSign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class NewsroomArticleRelationsEditorAdapter
{
    public const QUESTION_RELATION_TYPES = [
        'direct',
        'practice',
        'background',
        'related',
    ];

    public const LEGAL_RELATION_TYPES = [
        'direct_basis',
        'changed_rule',
        'supporting_context',
        'related',
    ];

    public const TRAFFIC_SIGN_RELATION_TYPES = [
        'direct',
        'example',
        'related',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     article_data: array<string, mixed>,
     *     relations: array{
     *         questions: list<array{question_id: int, relation_type: string, note: string|null}>,
     *         legal_units: list<array{legal_unit_id: int, relation_type: string, note: string|null}>,
     *         traffic_signs: list<array{traffic_sign_id: int, relation_type: string}>,
     *         topic_ids: list<int>
     *     }
     * }
     */
    public static function extractArticleData(array $data): array
    {
        $questions = self::normalizeOrderedRelations(
            $data['question_relations'] ?? [],
            'question_relations',
            'question_id',
            self::QUESTION_RELATION_TYPES,
            allowNote: true,
        );
        $legalUnits = self::normalizeOrderedRelations(
            $data['legal_unit_relations'] ?? [],
            'legal_unit_relations',
            'legal_unit_id',
            self::LEGAL_RELATION_TYPES,
            allowNote: true,
        );
        $trafficSigns = self::normalizeOrderedRelations(
            $data['traffic_sign_relations'] ?? [],
            'traffic_sign_relations',
            'traffic_sign_id',
            self::TRAFFIC_SIGN_RELATION_TYPES,
            allowNote: false,
        );
        $topicIds = self::normalizeIdList(
            $data['topic_ids'] ?? [],
            'topic_ids',
        );

        self::assertTargetsExist(Question::class, array_column($questions, 'question_id'), 'question_relations');
        self::assertTargetsExist(LegalUnit::class, array_column($legalUnits, 'legal_unit_id'), 'legal_unit_relations');
        self::assertTargetsExist(TrafficSign::class, array_column($trafficSigns, 'traffic_sign_id'), 'traffic_sign_relations');
        self::assertTargetsExist(ContentTopic::class, $topicIds, 'topic_ids');

        unset(
            $data['question_relations'],
            $data['legal_unit_relations'],
            $data['traffic_sign_relations'],
            $data['topic_ids'],
        );

        return [
            'article_data' => $data,
            'relations' => [
                'questions' => $questions,
                'legal_units' => $legalUnits,
                'traffic_signs' => $trafficSigns,
                'topic_ids' => $topicIds,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateArticleData(array $data, ContentArticle $article): array
    {
        $data['question_relations'] = $article->questions()
            ->get()
            ->map(fn (Question $question): array => [
                'question_id' => (int) $question->getKey(),
                'relation_type' => (string) $question->pivot?->relation_type,
                'note' => self::nullableString($question->pivot?->note),
            ])
            ->values()
            ->all();

        $data['legal_unit_relations'] = $article->legalUnits()
            ->get()
            ->map(fn (LegalUnit $unit): array => [
                'legal_unit_id' => (int) $unit->getKey(),
                'relation_type' => (string) $unit->pivot?->relation_type,
                'note' => self::nullableString($unit->pivot?->note),
            ])
            ->values()
            ->all();

        $data['traffic_sign_relations'] = $article->trafficSigns()
            ->get()
            ->map(fn (TrafficSign $sign): array => [
                'traffic_sign_id' => (int) $sign->getKey(),
                'relation_type' => (string) $sign->pivot?->relation_type,
            ])
            ->values()
            ->all();

        $data['topic_ids'] = $article->topics()
            ->pluck('content_topics.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array{
     *     questions: list<array{question_id: int, relation_type: string, note: string|null}>,
     *     legal_units: list<array{legal_unit_id: int, relation_type: string, note: string|null}>,
     *     traffic_signs: list<array{traffic_sign_id: int, relation_type: string}>,
     *     topic_ids: list<int>
     * }  $relations
     */
    public static function sync(ContentArticle $article, array $relations): ContentArticle
    {
        $article->questions()->sync(self::questionPivotMap($relations['questions']));
        $article->legalUnits()->sync(self::legalUnitPivotMap($relations['legal_units']));
        $article->trafficSigns()->sync(self::trafficSignPivotMap($relations['traffic_signs']));
        $article->topics()->sync($relations['topic_ids']);

        $article->touch();

        return $article->refresh();
    }

    /**
     * @param  list<string>  $allowedTypes
     * @return list<array<string, int|string|null>>
     */
    private static function normalizeOrderedRelations(
        mixed $rows,
        string $field,
        string $idKey,
        array $allowedTypes,
        bool $allowNote,
    ): array {
        if ($rows === null) {
            return [];
        }

        if (! is_array($rows)) {
            self::fail($field, 'Powiązania muszą być listą rekordów.');
        }

        $normalized = [];
        $seen = [];

        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                self::fail($field, 'Każde powiązanie musi być rekordem formularza.');
            }

            $id = self::positiveInteger($row[$idKey] ?? null);

            if ($id === null) {
                self::fail($field, 'Każde powiązanie musi wskazywać istniejący rekord.');
            }

            if (isset($seen[$id])) {
                self::fail($field, 'Ten sam rekord nie może być dodany do artykułu więcej niż raz.');
            }

            $seen[$id] = true;
            $relationType = trim((string) ($row['relation_type'] ?? ''));

            if (! in_array($relationType, $allowedTypes, true)) {
                self::fail($field, 'Nieobsługiwany typ relacji.');
            }

            $item = [
                $idKey => $id,
                'relation_type' => $relationType,
            ];

            if ($allowNote) {
                $item['note'] = self::nullableString($row['note'] ?? null);
            }

            $normalized[$index] = $item;
        }

        return array_values($normalized);
    }

    /**
     * @return list<int>
     */
    private static function normalizeIdList(mixed $values, string $field): array
    {
        if ($values === null) {
            return [];
        }

        if (! is_array($values)) {
            self::fail($field, 'Lista tematów ma nieprawidłowy format.');
        }

        $normalized = [];
        $seen = [];

        foreach (array_values($values) as $value) {
            $id = self::positiveInteger($value);

            if ($id === null) {
                self::fail($field, 'Każdy temat musi wskazywać istniejący rekord.');
            }

            if (isset($seen[$id])) {
                self::fail($field, 'Ten sam temat nie może być przypięty więcej niż raz.');
            }

            $seen[$id] = true;
            $normalized[] = $id;
        }

        return $normalized;
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<int>  $ids
     */
    private static function assertTargetsExist(string $model, array $ids, string $field): void
    {
        if ($ids === []) {
            return;
        }

        $existing = $model::query()
            ->whereKey($ids)
            ->count();

        if ($existing !== count($ids)) {
            self::fail($field, 'Co najmniej jeden wybrany rekord nie istnieje lub został usunięty.');
        }
    }

    /**
     * @param  list<array{question_id: int, relation_type: string, note: string|null}>  $rows
     * @return array<int, array{relation_type: string, sort_order: int, note: string|null}>
     */
    private static function questionPivotMap(array $rows): array
    {
        $payload = [];

        foreach ($rows as $index => $row) {
            $payload[$row['question_id']] = [
                'relation_type' => $row['relation_type'],
                'sort_order' => $index,
                'note' => $row['note'],
            ];
        }

        return $payload;
    }

    /**
     * @param  list<array{legal_unit_id: int, relation_type: string, note: string|null}>  $rows
     * @return array<int, array{relation_type: string, sort_order: int, note: string|null}>
     */
    private static function legalUnitPivotMap(array $rows): array
    {
        $payload = [];

        foreach ($rows as $index => $row) {
            $payload[$row['legal_unit_id']] = [
                'relation_type' => $row['relation_type'],
                'sort_order' => $index,
                'note' => $row['note'],
            ];
        }

        return $payload;
    }

    /**
     * @param  list<array{traffic_sign_id: int, relation_type: string}>  $rows
     * @return array<int, array{relation_type: string, sort_order: int}>
     */
    private static function trafficSignPivotMap(array $rows): array
    {
        $payload = [];

        foreach ($rows as $index => $row) {
            $payload[$row['traffic_sign_id']] = [
                'relation_type' => $row['relation_type'],
                'sort_order' => $index,
            ];
        }

        return $payload;
    }

    private static function positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value) || ! ctype_digit($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private static function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            "data.{$field}" => $message,
        ]);
    }
}
