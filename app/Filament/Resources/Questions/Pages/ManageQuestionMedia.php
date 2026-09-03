<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Support\MediaUrlResolver;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ManageQuestionMedia extends Page
{
    use InteractsWithRecord;

    protected static string $resource = QuestionResource::class;

    protected string $view = 'filament.resources.questions.pages.manage-question-media';

    protected static ?string $title = 'Media';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);

        $this->getRecord()->loadMissing('licenseCategory', 'media');
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();
        $context = Str::limit((string) $record->prompt, 100);

        if (filled($record->external_id)) {
            return "{$record->external_id} | {$context}";
        }

        return $context;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Podgląd pytania')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => QuestionResource::getUrl('view', ['record' => $this->getRecord()])),
            Action::make('edit')
                ->label('Edytuj')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->url(fn (): string => QuestionResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFrontendConfig(): array
    {
        /** @var Question $record */
        $record = $this->getRecord()->loadMissing('licenseCategory', 'media');
        $mediaUrlResolver = app(MediaUrlResolver::class);

        return [
            'csrfToken' => csrf_token(),
            'presignUrl' => route('api.v1.admin.media.presign'),
            'confirmUrl' => route('api.v1.admin.media.confirm'),
            'reorderUrl' => route('api.v1.admin.questions.media.reorder', ['question' => $record]),
            'deleteBaseUrl' => url('/api/v1/admin/media'),
            'question' => [
                'id' => $record->getKey(),
                'externalId' => $record->external_id,
                'prompt' => $record->prompt,
                'questionTypeLabel' => $this->questionTypeLabel($record),
                'deliveryStatus' => $record->deliveryStatus(),
                'deliveryIssueLabel' => $record->deliveryIssueLabel(),
                'requiresPrimaryMedia' => $record->expectsPrimaryMedia(),
                'licenseCategory' => [
                    'id' => $record->licenseCategory?->getKey(),
                    'code' => $record->licenseCategory?->code,
                    'name' => $record->licenseCategory?->name,
                ],
            ],
            'existingMedia' => $record->media
                ->map(fn (QuestionMedia $media): array => $this->mediaPayload($media, $mediaUrlResolver))
                ->values()
                ->all(),
            'allowedMimeTypes' => config('media.allowed_mime_types', []),
            'allowedVariants' => config('media.allowed_variants', []),
            'maxBytes' => config('media.max_bytes', []),
        ];
    }

    protected function questionTypeLabel(Question $question): string
    {
        return match ($question->question_type) {
            'boolean' => 'Tak / nie',
            'single_choice' => 'Jednokrotny wybór',
            default => filled($question->question_type) ? (string) $question->question_type : '-',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function mediaPayload(QuestionMedia $media, MediaUrlResolver $mediaUrlResolver): array
    {
        return [
            'id' => $media->getKey(),
            'kind' => $media->kind,
            'disk' => $media->disk,
            'path' => $media->path,
            'posterPath' => $media->poster_path,
            'url' => $mediaUrlResolver->resolve($media->path, $media->disk),
            'posterUrl' => $mediaUrlResolver->resolve($media->poster_path, $media->disk),
            'mimeType' => $media->mime_type,
            'bytes' => $media->bytes,
            'width' => $media->width,
            'height' => $media->height,
            'durationSeconds' => $media->duration_seconds,
            'variant' => $media->variant,
            'sortOrder' => $media->sort_order,
            'metadata' => $media->metadata,
        ];
    }
}
