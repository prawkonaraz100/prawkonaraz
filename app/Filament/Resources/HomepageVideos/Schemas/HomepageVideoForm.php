<?php

namespace App\Filament\Resources\HomepageVideos\Schemas;

use App\Models\HomepageVideo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomepageVideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Materiał')
                        ->description('Materiał pojawi się w sekcji Video lub Podcasty na stronie głównej po włączeniu publikacji.')
                        ->columnSpan([
                            'lg' => 8,
                        ])
                        ->schema([
                            Select::make('kind')
                                ->label('Rodzaj materiału')
                                ->options([
                                    HomepageVideo::KIND_VIDEO => 'Video',
                                    HomepageVideo::KIND_PODCAST => 'Podcast',
                                ])
                                ->default(HomepageVideo::KIND_VIDEO)
                                ->required()
                                ->native(false),
                            TextInput::make('title')
                                ->label('Tytuł materiału')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('youtube_url')
                                ->label('Link do filmu na YouTube')
                                ->placeholder('https://www.youtube.com/watch?v=...')
                                ->url()
                                ->required()
                                ->rule('regex:~^https?://(?:www\.|m\.)?(?:youtube\.com/watch\?[^\s]*v=|youtube\.com/(?:embed|shorts)/|youtu\.be/)[A-Za-z0-9_-]{6,20}~i')
                                ->validationMessages([
                                    'regex' => 'Podaj prawidłowy link do filmu w serwisie YouTube.',
                                ])
                                ->maxLength(2048)
                                ->columnSpanFull(),
                            FileUpload::make('thumbnail_path')
                                ->label('Własna miniatura')
                                ->helperText('Opcjonalnie. Bez pliku użyjemy miniatury pobieranej z YouTube.')
                                ->disk('public')
                                ->directory('homepage-videos/thumbnails')
                                ->image()
                                ->imageEditor()
                                ->maxSize(5120)
                                ->columnSpanFull(),
                        ]),
                    Section::make('Publikacja')
                        ->columnSpan([
                            'lg' => 4,
                        ])
                        ->schema([
                            Toggle::make('is_published')
                                ->label('Opublikowany')
                                ->default(false)
                                ->inline(false),
                            DatePicker::make('published_on')
                                ->label('Data publikacji')
                                ->native(false),
                            TextInput::make('duration_seconds')
                                ->label('Czas trwania w sekundach')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(86400)
                                ->placeholder('np. 97')
                                ->helperText('Na stronie pokażemy zapis 1:37.'),
                            TextInput::make('sort_order')
                                ->label('Kolejność')
                                ->numeric()
                                ->default(0)
                                ->helperText('Niższa liczba oznacza wyższą pozycję na liście.'),
                        ]),
                ]),
            ]);
    }
}
