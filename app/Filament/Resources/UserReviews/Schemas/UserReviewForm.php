<?php

namespace App\Filament\Resources\UserReviews\Schemas;

use App\Models\UserReview;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Treść opinii')
                    ->description('Administrator może poprawić ocenę i treść oraz zdecydować o statusie publikacji.')
                    ->schema([
                        Select::make('rating')
                            ->label('Ocena')
                            ->options([
                                5 => '5 — doskonała',
                                4 => '4 — bardzo dobra',
                                3 => '3 — dobra',
                                2 => '2 — słaba',
                                1 => '1 — bardzo słaba',
                            ])
                            ->required()
                            ->native(false),
                        Textarea::make('content')
                            ->label('Treść')
                            ->required()
                            ->minLength(3)
                            ->maxLength(500)
                            ->rows(9)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                UserReview::STATUS_PENDING => 'Czeka na zatwierdzenie',
                                UserReview::STATUS_APPROVED => 'Opublikowana',
                                UserReview::STATUS_REJECTED => 'Wymaga poprawy',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Zmiana statusu na „Opublikowana” pokaże opinię publicznie. „Wymaga poprawy” ukryje ją i pozwoli użytkownikowi ponownie ją edytować.'),
                    ])
                    ->columns(2),
                Section::make('Linki użytkownika')
                    ->description('Opcjonalne publiczne profile wyświetlane przy opinii.')
                    ->schema([
                        TextInput::make('social_links.facebook')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.instagram')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.tiktok')
                            ->label('TikTok')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.youtube')
                            ->label('YouTube')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.website')
                            ->label('Strona WWW')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
