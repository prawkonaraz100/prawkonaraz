<?php

namespace App\Filament\Resources\QuestionTopics\Pages;

use App\Filament\Resources\QuestionTopics\QuestionTopicResource;
use App\Models\QuestionTopic;
use App\Support\AuditLogService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestionTopic extends EditRecord
{
    protected static string $resource = QuestionTopicResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $heroSnapshotBeforeSave = [];

    public function getHeading(): string
    {
        return 'Edytuj globalne zdjęcie działu';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return filled($record->key)
            ? "Klucz: {$record->key}"
            : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Podgląd'),
        ];
    }

    protected function beforeSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof QuestionTopic) {
            $this->heroSnapshotBeforeSave = $this->heroAuditSnapshot($record);
        }
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof QuestionTopic) {
            return;
        }

        $after = $this->heroAuditSnapshot($record);

        if ($this->heroSnapshotBeforeSave === $after) {
            return;
        }

        app(AuditLogService::class)->record(
            'admin.question_topic.hero_updated',
            'question_topic',
            (string) $record->getKey(),
            auth()->user(),
            [
                'source' => 'admin_panel',
                'question_topic_id' => (int) $record->getKey(),
                'question_topic_key' => (string) $record->key,
                'question_topic_name' => (string) $record->name,
                'before' => $this->heroSnapshotBeforeSave,
                'after' => $after,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function heroAuditSnapshot(QuestionTopic $record): array
    {
        return [
            'hero_image_path' => $record->hero_image_path,
            'hero_image_alt' => $record->hero_image_alt,
            'hero_image_position' => $record->hero_image_position,
        ];
    }
}
