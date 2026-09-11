<?php

namespace App\Filament\Resources\UserReviews\Pages;

use App\Filament\Resources\UserReviews\UserReviewResource;
use App\Models\UserReview;
use App\Support\AuditLogService;
use Filament\Resources\Pages\EditRecord;

class EditUserReview extends EditRecord
{
    protected static string $resource = UserReviewResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $reviewBeforeSave = [];

    public function getHeading(): string
    {
        return 'Edytuj opinię';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord()->loadMissing('user');

        return implode(' · ', array_filter([
            $record->user?->name,
            $record->user?->email,
        ]));
    }

    protected function beforeSave(): void
    {
        $this->reviewBeforeSave = $this->getRecord()->only([
            'rating',
            'content',
            'social_links',
            'status',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = (string) ($data['status'] ?? UserReview::STATUS_PENDING);

        $data['published_at'] = $status === UserReview::STATUS_APPROVED
            ? ($this->getRecord()->published_at ?? now())
            : null;
        $data['moderated_by_user_id'] = $status === UserReview::STATUS_PENDING
            ? null
            : auth()->id();
        $data['moderated_at'] = $status === UserReview::STATUS_PENDING
            ? null
            : now();

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->refresh();

        app(AuditLogService::class)->record(
            'user_review.admin_updated',
            'user_review',
            $record->getKey(),
            auth()->user(),
            [
                'before' => $this->reviewBeforeSave,
                'after' => $record->only(['rating', 'content', 'social_links', 'status']),
            ],
        );
    }

    protected function getRedirectUrl(): string
    {
        return UserReviewResource::getUrl('index');
    }
}
