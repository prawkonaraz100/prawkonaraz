<?php

namespace App\Filament\Resources\UserReviews\Tables;

use App\Models\UserReview;
use App\Support\AuditLogService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Szukaj po użytkowniku lub treści opinii')
            ->persistFiltersInSession()
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Brak opinii')
            ->emptyStateDescription('Nowe opinie przesłane przez użytkowników pojawią się w tym miejscu.')
            ->emptyStateIcon(Heroicon::OutlinedStar)
            ->columns([
                TextColumn::make('user.name')
                    ->label('Użytkownik')
                    ->description(fn (UserReview $record): string => (string) $record->user?->email)
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('rating')
                    ->label('Ocena')
                    ->state(fn (UserReview $record): string => str_repeat('★', $record->rating).' '.$record->rating.'/5')
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Treść')
                    ->searchable()
                    ->limit(150)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (UserReview $record): string => $record->statusLabel())
                    ->badge()
                    ->color(fn (UserReview $record): string => match ($record->status) {
                        UserReview::STATUS_APPROVED => 'success',
                        UserReview::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Opublikowano')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        UserReview::STATUS_PENDING => 'Czeka na zatwierdzenie',
                        UserReview::STATUS_APPROVED => 'Opublikowana',
                        UserReview::STATUS_REJECTED => 'Odrzucona',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edytuj'),
                Action::make('photo')
                    ->label('Podejrzyj zdjęcie')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->color('gray')
                    ->visible(fn (UserReview $record): bool => filled($record->photo_path))
                    ->url(fn (UserReview $record): string => route('reviews.photo', ['review' => $record]))
                    ->openUrlInNewTab(),
                Action::make('approve')
                    ->label('Opublikuj')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (UserReview $record): bool => $record->status !== UserReview::STATUS_APPROVED)
                    ->requiresConfirmation()
                    ->modalHeading('Opublikować tę opinię?')
                    ->modalDescription('Opinia wraz z zaakceptowanym zdjęciem stanie się widoczna na stronie głównej i stronie wszystkich opinii.')
                    ->action(function (UserReview $record): void {
                        $record->forceFill([
                            'status' => UserReview::STATUS_APPROVED,
                            'published_at' => now(),
                            'moderated_by_user_id' => auth()->id(),
                            'moderated_at' => now(),
                        ])->save();

                        app(AuditLogService::class)->record(
                            'user_review.approved',
                            'user_review',
                            $record->getKey(),
                            auth()->user(),
                        );

                        Notification::make()->title('Opinia została opublikowana')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Odrzuć')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (UserReview $record): bool => $record->status !== UserReview::STATUS_REJECTED)
                    ->requiresConfirmation()
                    ->modalHeading('Odrzucić tę opinię?')
                    ->modalDescription('Opinia nie będzie widoczna publicznie. Użytkownik będzie mógł ją poprawić i przesłać ponownie.')
                    ->action(function (UserReview $record): void {
                        $record->forceFill([
                            'status' => UserReview::STATUS_REJECTED,
                            'published_at' => null,
                            'moderated_by_user_id' => auth()->id(),
                            'moderated_at' => now(),
                        ])->save();

                        app(AuditLogService::class)->record(
                            'user_review.rejected',
                            'user_review',
                            $record->getKey(),
                            auth()->user(),
                        );

                        Notification::make()->title('Opinia została odrzucona')->warning()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
