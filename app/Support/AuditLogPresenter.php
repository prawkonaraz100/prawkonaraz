<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogPresenter
{
    /**
     * @return array<string, string>
     */
    public static function actionOptions(): array
    {
        return [
            'admin.question.created' => 'Dodano pytanie',
            'admin.question.updated' => 'Zaktualizowano pytanie',
            'admin.question_media.created' => 'Dodano medium pytania',
            'admin.question_media.updated' => 'Zaktualizowano medium pytania',
            'admin.question_media.reordered' => 'Zmieniono kolejność mediów',
            'admin.question_media.deleted' => 'Usunięto medium pytania',
            'admin.question_topic_category_label.created' => 'Dodano nazwę działu',
            'admin.question_topic_category_label.updated' => 'Zmieniono nazwę działu',
            'admin.question_topic_category_label.deleted' => 'Usunięto nazwę działu',
            'admin.question_topic_category_hero.created' => 'Dodano zdjęcie działu',
            'admin.question_topic_category_hero.updated' => 'Zmieniono zdjęcie działu',
            'admin.question_topic_category_hero.deleted' => 'Usunięto zdjęcie działu',
            'admin.question_topic.hero_updated' => 'Zmieniono globalne zdjęcie działu',
            'admin.question_collection.availability_updated' => 'Zmieniono dostępność kursu',
            'user.avatar_removed' => 'Usunięto zdjęcie profilowe',
            'user.banned' => 'Zablokowano użytkownika',
            'user.unbanned' => 'Odblokowano użytkownika',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function entityTypeOptions(): array
    {
        return [
            'question' => 'Pytanie',
            'question_media' => 'Medium pytania',
            'question_topic' => 'Dział pytań',
            'question_topic_category_label' => 'Nazwa działu kategorii',
            'question_topic_category_hero' => 'Zdjęcie działu kategorii',
            'question_collection' => 'Kolekcja pytań',
            'user' => 'Użytkownik',
            'content_import_run' => 'Import',
        ];
    }

    public static function actionLabel(string $action): string
    {
        return static::actionOptions()[$action]
            ?? Str::title(str_replace(['.', '_'], ' ', $action));
    }

    public static function entityTypeLabel(?string $entityType): string
    {
        if (! filled($entityType)) {
            return 'Encja';
        }

        return static::entityTypeOptions()[(string) $entityType]
            ?? Str::title(str_replace('_', ' ', (string) $entityType));
    }

    public static function actorLabel(AuditLog $auditLog): string
    {
        return $auditLog->actorUser?->name
            ?: $auditLog->actorUser?->email
            ?: 'System';
    }

    public static function actorDetails(AuditLog $auditLog): string
    {
        $parts = array_filter([
            $auditLog->actorUser?->email && $auditLog->actorUser?->name
                ? $auditLog->actorUser->email
                : null,
            static::sourceLabel($auditLog),
        ]);

        return $parts === [] ? 'Brak dodatkowego kontekstu użytkownika.' : implode(' · ', $parts);
    }

    public static function entitySummary(AuditLog $auditLog): string
    {
        $parts = [
            static::entityTypeLabel($auditLog->entity_type),
            '#'.$auditLog->entity_id,
        ];

        $externalId = Arr::get($auditLog->metadata ?? [], 'external_id');

        if (filled($externalId)) {
            $parts[] = 'ID źródła '.$externalId;
        }

        return implode(' · ', $parts);
    }

    public static function actionDescription(AuditLog $auditLog): string
    {
        $metadata = is_array($auditLog->metadata) ? $auditLog->metadata : [];

        return match ($auditLog->action) {
            'admin.question.created', 'admin.question.updated' => static::questionDescription($metadata),
            'admin.question_media.created', 'admin.question_media.updated', 'admin.question_media.deleted' => static::mediaDescription($metadata),
            'admin.question_media.reordered' => static::mediaReorderDescription($metadata),
            'admin.question_topic_category_label.created',
            'admin.question_topic_category_label.updated',
            'admin.question_topic_category_label.deleted' => static::topicCategoryLabelDescription($metadata),
            'admin.question_topic_category_hero.created',
            'admin.question_topic_category_hero.updated',
            'admin.question_topic_category_hero.deleted' => static::topicCategoryHeroDescription($metadata),
            'admin.question_topic.hero_updated' => static::questionTopicHeroDescription($metadata),
            'user.banned' => static::banDescription($metadata, blocked: true),
            'user.unbanned' => static::banDescription($metadata, blocked: false),
            default => static::defaultDescription($metadata),
        };
    }

    public static function requestSummary(AuditLog $auditLog): string
    {
        $parts = array_filter([
            static::sourceLabel($auditLog),
            filled($auditLog->ip_address) ? $auditLog->ip_address : null,
        ]);

        return $parts === [] ? 'Brak kontekstu żądania' : implode(' · ', $parts);
    }

    public static function requestDetails(AuditLog $auditLog): string
    {
        $parts = array_filter([
            filled($auditLog->request_id) ? 'Request ID: '.$auditLog->request_id : null,
            filled($auditLog->user_agent) ? Str::limit($auditLog->user_agent, 110) : null,
        ]);

        return $parts === [] ? 'Brak dodatkowych danych technicznych.' : implode(' · ', $parts);
    }

    public static function sourceLabel(AuditLog $auditLog): string
    {
        $source = Arr::get($auditLog->metadata ?? [], 'source');

        return match ($source) {
            'admin_api' => 'Panel admina',
            'admin_panel' => 'Panel admina',
            null, '' => 'Brak źródła',
            default => Str::title(str_replace(['_', '-'], ' ', (string) $source)),
        };
    }

    public static function metadataJson(AuditLog $auditLog): string
    {
        $metadata = is_array($auditLog->metadata) ? $auditLog->metadata : [];

        if ($metadata === []) {
            return '{}';
        }

        return json_encode(
            $metadata,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) ?: '{}';
    }

    public static function importantDetails(AuditLog $auditLog): string
    {
        $metadata = is_array($auditLog->metadata) ? $auditLog->metadata : [];

        $pairs = array_filter([
            filled(Arr::get($metadata, 'question_id')) ? 'Pytanie: #'.Arr::get($metadata, 'question_id') : null,
            filled(Arr::get($metadata, 'kind')) ? 'Typ medium: '.Arr::get($metadata, 'kind') : null,
            filled(Arr::get($metadata, 'variant')) ? 'Wariant: '.Arr::get($metadata, 'variant') : null,
            Arr::has($metadata, 'media_count') ? 'Liczba mediów: '.number_format((int) Arr::get($metadata, 'media_count'), 0, ',', ' ') : null,
            filled(Arr::get($metadata, 'license_category_code')) ? 'Kategoria: '.Arr::get($metadata, 'license_category_code') : null,
            filled(Arr::get($metadata, 'question_topic_key')) ? 'Dział: '.Arr::get($metadata, 'question_topic_key') : null,
            Arr::has($metadata, 'bytes') ? 'Rozmiar: '.static::formatBytes((int) Arr::get($metadata, 'bytes')) : null,
            filled(Arr::get($metadata, 'reason')) ? 'Powód: '.Arr::get($metadata, 'reason') : null,
        ]);

        return $pairs === [] ? 'Brak dodatkowych szczegółów operacji.' : implode(' · ', $pairs);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function questionDescription(array $metadata): string
    {
        $parts = array_filter([
            filled(Arr::get($metadata, 'external_id')) ? 'ID źródła '.Arr::get($metadata, 'external_id') : null,
            filled(Arr::get($metadata, 'question_type')) ? static::questionTypeLabel((string) Arr::get($metadata, 'question_type')) : null,
            Arr::has($metadata, 'difficulty') ? 'trudność '.number_format((int) Arr::get($metadata, 'difficulty'), 0, ',', ' ') : null,
            Arr::has($metadata, 'media_count') ? number_format((int) Arr::get($metadata, 'media_count'), 0, ',', ' ').' mediów' : null,
        ]);

        return $parts === []
            ? 'Zmieniono rekord pytania w bazie.'
            : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function mediaDescription(array $metadata): string
    {
        $parts = array_filter([
            filled(Arr::get($metadata, 'question_id')) ? 'Pytanie #'.Arr::get($metadata, 'question_id') : null,
            filled(Arr::get($metadata, 'kind')) ? 'typ '.Arr::get($metadata, 'kind') : null,
            filled(Arr::get($metadata, 'variant')) ? 'wariant '.Arr::get($metadata, 'variant') : null,
            Arr::has($metadata, 'bytes') ? static::formatBytes((int) Arr::get($metadata, 'bytes')) : null,
        ]);

        return $parts === []
            ? 'Operacja na medium pytania.'
            : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function mediaReorderDescription(array $metadata): string
    {
        $count = (int) Arr::get($metadata, 'media_count', 0);
        $questionId = Arr::get($metadata, 'question_id');

        $parts = array_filter([
            filled($questionId) ? 'Pytanie #'.$questionId : null,
            $count > 0 ? number_format($count, 0, ',', ' ').' mediów po zmianie kolejności' : null,
        ]);

        return $parts === []
            ? 'Zmieniono kolejność mediów pytania.'
            : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function topicCategoryLabelDescription(array $metadata): string
    {
        $before = Arr::get($metadata, 'before');
        $after = Arr::get($metadata, 'after');
        $oldName = is_array($before) ? Arr::get($before, 'display_name') : null;
        $newName = is_array($after) ? Arr::get($after, 'display_name') : null;

        $parts = array_filter([
            filled(Arr::get($metadata, 'license_category_code')) ? 'Kategoria '.Arr::get($metadata, 'license_category_code') : null,
            filled(Arr::get($metadata, 'question_topic_key')) ? 'Klucz '.$metadata['question_topic_key'] : null,
            filled($newName) ? 'Nazwa: '.$newName : null,
            filled($oldName) && $oldName !== $newName ? 'Poprzednio: '.$oldName : null,
        ]);

        return $parts === []
            ? 'Zmieniono prezentacyjną nazwę działu w kategorii.'
            : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function topicCategoryHeroDescription(array $metadata): string
    {
        $before = Arr::get($metadata, 'before');
        $after = Arr::get($metadata, 'after');
        $oldPath = is_array($before) ? Arr::get($before, 'hero_image_path') : null;
        $newPath = is_array($after) ? Arr::get($after, 'hero_image_path') : null;
        $newPosition = is_array($after) ? Arr::get($after, 'hero_image_position') : null;

        $parts = array_filter([
            filled(Arr::get($metadata, 'license_category_code')) ? 'Kategoria '.Arr::get($metadata, 'license_category_code') : null,
            filled(Arr::get($metadata, 'question_topic_key')) ? 'Klucz '.$metadata['question_topic_key'] : null,
            filled($newPath) ? 'Obraz: '.$newPath : null,
            filled($newPosition) ? 'Kadrowanie: '.$newPosition : null,
            filled($oldPath) && $oldPath !== $newPath ? 'Poprzednio: '.$oldPath : null,
        ]);

        return $parts === []
            ? 'Zmieniono prezentacyjne zdjęcie działu w kategorii.'
            : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function questionTopicHeroDescription(array $metadata): string
    {
        $before = Arr::get($metadata, 'before');
        $after = Arr::get($metadata, 'after');
        $oldPath = is_array($before) ? Arr::get($before, 'hero_image_path') : null;
        $newPath = is_array($after) ? Arr::get($after, 'hero_image_path') : null;
        $newPosition = is_array($after) ? Arr::get($after, 'hero_image_position') : null;

        $parts = array_filter([
            filled(Arr::get($metadata, 'question_topic_key')) ? 'Klucz '.$metadata['question_topic_key'] : null,
            filled(Arr::get($metadata, 'question_topic_name')) ? 'Dział: '.Arr::get($metadata, 'question_topic_name') : null,
            filled($newPath) ? 'Obraz: '.$newPath : null,
            filled($newPosition) ? 'Kadrowanie: '.$newPosition : null,
            filled($oldPath) && $oldPath !== $newPath ? 'Poprzednio: '.$oldPath : null,
        ]);

        return $parts === []
            ? 'Zmieniono globalne zdjęcie działu.'
            : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function banDescription(array $metadata, bool $blocked): string
    {
        if ($blocked && filled(Arr::get($metadata, 'reason'))) {
            return 'Powód: '.Arr::get($metadata, 'reason');
        }

        return $blocked
            ? 'Konto zostało zablokowane przez operatora.'
            : 'Konto odzyskało możliwość logowania.';
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected static function defaultDescription(array $metadata): string
    {
        $visibleMetadata = array_filter($metadata, fn (mixed $value, string $key): bool => ! in_array($key, ['source'], true), ARRAY_FILTER_USE_BOTH);

        if ($visibleMetadata === []) {
            return 'Brak dodatkowych szczegółów tej operacji.';
        }

        $pairs = collect($visibleMetadata)
            ->take(3)
            ->map(function (mixed $value, string|int $key): string {
                $label = Str::title(str_replace('_', ' ', (string) $key));

                if (is_scalar($value) || $value === null) {
                    return $label.': '.(filled($value) ? (string) $value : '-');
                }

                if (is_array($value)) {
                    return $label.': '.number_format(count($value), 0, ',', ' ').' elementów';
                }

                return $label.': '.Str::limit((string) $value, 60);
            })
            ->values()
            ->all();

        return implode(' · ', $pairs);
    }

    protected static function questionTypeLabel(string $questionType): string
    {
        return match ($questionType) {
            'boolean' => 'tak / nie',
            'single_choice' => 'jednokrotny wybór',
            default => str_replace('_', ' ', $questionType),
        };
    }

    protected static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1, ',', ' ').' '.$units[$power];
    }
}
