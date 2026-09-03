<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\LicenseCategory;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'lg' => 12,
                ])->schema([
                    Section::make('Dane konta')
                        ->description('Podstawowe dane logowania i identyfikacji użytkownika.')
                        ->columnSpan([
                            'lg' => 7,
                        ])
                        ->schema([
                            TextInput::make('name')
                                ->label('Imię i nazwisko')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('Adres e-mail')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            TextInput::make('password')
                                ->label('Hasło')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->minLength(8)
                                ->helperText('Pozostaw puste podczas edycji, jeśli hasło ma pozostać bez zmian.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Dostęp i status')
                        ->description('Rola, stan konta i podstawowe flagi dostępu.')
                        ->columnSpan([
                            'lg' => 5,
                        ])
                        ->schema([
                            Select::make('role')
                                ->label('Rola')
                                ->options([
                                    User::ROLE_STUDENT => 'Kursant',
                                    User::ROLE_MODERATOR => 'Moderator',
                                    User::ROLE_ADMIN => 'Administrator',
                                ])
                                ->default(User::ROLE_STUDENT)
                                ->required()
                                ->helperText('Administrator i moderator są kontami systemowymi. Kursant korzysta z produktu przez aktywny dostęp.'),
                            DateTimePicker::make('email_verified_at')
                                ->label('Zweryfikowano e-mail')
                                ->seconds(false)
                                ->helperText('Puste pole oznacza konto oczekujące na weryfikację.'),
                            Toggle::make('is_admin')
                                ->label('Administrator')
                                ->default(false)
                                ->inline(false)
                                ->required()
                                ->helperText('Zostaje dla zgodności ze starymi uprawnieniami panelu. Rola administratora automatycznie włącza ten przełącznik.'),
                            Toggle::make('is_test_account')
                                ->label('Konto testowe')
                                ->default(false)
                                ->inline(false)
                                ->required()
                                ->helperText('Konto testowe ma dostęp systemowy bez płatności.'),
                            TextInput::make('moderator_quota')
                                ->label('Pula moderatora')
                                ->numeric()
                                ->minValue(0)
                                ->default(User::DEFAULT_MODERATOR_QUOTA)
                                ->required()
                                ->helperText('Domyślnie moderator może utworzyć 30 kont. Admin może zwiększyć tę wartość.'),
                            Toggle::make('requires_password_change')
                                ->label('Wymuś zmianę hasła')
                                ->default(false)
                                ->inline(false)
                                ->required(),
                            Toggle::make('is_temporary_account')
                                ->label('Konto tymczasowe')
                                ->default(false)
                                ->inline(false)
                                ->required(),
                            DateTimePicker::make('temporary_account_expires_at')
                                ->label('Ważne do')
                                ->seconds(false)
                                ->helperText('Dla kont tworzonych przez moderatora planujemy 90 dni dostępu.'),
                        ]),
                    Section::make('Stała kategoria nauki')
                        ->description('Kursant ma jedną kategorię. Zmiana przez administratora automatycznie resetuje dane nauki zależne od poprzedniej kategorii.')
                        ->columnSpan([
                            'lg' => 12,
                        ])
                        ->schema([
                            Select::make('target_category_id')
                                ->label('Kategoria kursanta')
                                ->options(fn (): array => LicenseCategory::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('code')
                                    ->get(['id', 'code', 'name'])
                                    ->mapWithKeys(fn (LicenseCategory $category): array => [
                                        $category->getKey() => $category->code.' - '.$category->name,
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('role') === User::ROLE_STUDENT && ! (bool) $get('is_test_account'))
                                ->rules(['nullable', 'integer', 'exists:license_categories,id'])
                                ->dehydrated(false)
                                ->helperText('Dla kont systemowych pole może zostać puste. Dla kursanta to docelowa, twarda kategoria nauki.'),
                        ]),
                ]),
            ]);
    }
}
