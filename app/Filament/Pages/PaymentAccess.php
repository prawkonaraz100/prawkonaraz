<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\PaymentRequirementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;

class PaymentAccess extends Page
{
    public bool $requiresPayment = true;

    /** @var array{at:string,actor:string}|null */
    public ?array $lastChange = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Dostęp';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Płatności i dostęp';

    protected string $view = 'filament.pages.payment-access';

    protected static ?string $slug = 'platnosci-i-dostep';

    public static function getNavigationLabel(): string
    {
        return 'Płatności i dostęp';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? Heroicon::CreditCard;
    }

    public function getHeading(): string|Htmlable
    {
        return 'Płatności i dostęp';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Globalny wymóg płatności dla pełnej nauki. Rejestracja pozostaje bezpłatna w obu stanach.';
    }

    public function mount(): void
    {
        $this->loadState();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openAccess')
                ->label('Otwórz dostęp')
                ->icon(Heroicon::LockOpen)
                ->color('warning')
                ->visible(fn (): bool => $this->requiresPayment)
                ->requiresConfirmation()
                ->modalHeading('Otworzyć dostęp bez płatności?')
                ->modalDescription('Zweryfikowani kursanci otrzymają od razu pełny dostęp do nauki. Nie powstaną dla nich zamówienia ani granty.')
                ->modalSubmitActionLabel('Otwórz dostęp')
                ->action(function (): void {
                    $this->updateRequirement(false);
                }),
            Action::make('requirePayment')
                ->label('Włącz wymóg płatności')
                ->icon(Heroicon::LockClosed)
                ->color('danger')
                ->visible(fn (): bool => ! $this->requiresPayment)
                ->requiresConfirmation()
                ->modalHeading('Włączyć wymóg płatności?')
                ->modalDescription('Kursanci bez zakupu lub innego aktywnego dostępu wrócą do standardowej aktywacji. Istniejące zakupy i ręczne granty pozostaną aktywne.')
                ->modalSubmitActionLabel('Włącz wymóg płatności')
                ->action(function (): void {
                    $this->updateRequirement(true);
                }),
        ];
    }

    protected function updateRequirement(bool $requiresPayment): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $changed = app(PaymentRequirementService::class)->setRequiresPayment($requiresPayment, $actor);
        $this->loadState();

        if (! $changed) {
            Notification::make()
                ->title('Nie udało się zapisać ustawienia')
                ->body('Stan dostępu nie został zmieniony. Spróbuj ponownie lub sprawdź połączenie z bazą.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($requiresPayment ? 'Wymóg płatności jest aktywny' : 'Dostęp został otwarty')
            ->body($requiresPayment
                ? 'Nowe konta potrzebują zakupu albo innego aktywnego dostępu po potwierdzeniu e-maila.'
                : 'Zweryfikowane konta mogą od razu rozpocząć pełną naukę bez zakupu.')
            ->success()
            ->send();
    }

    protected function loadState(): void
    {
        $this->requiresPayment = app(PaymentRequirementService::class)->requiresPayment();

        $lastChange = AuditLog::query()
            ->with('actorUser:id,name')
            ->where('action', PaymentRequirementService::AUDIT_ACTION)
            ->latest('id')
            ->first();

        $this->lastChange = $lastChange ? [
            'at' => $lastChange->created_at?->format('d.m.Y H:i') ?? 'brak daty',
            'actor' => $lastChange->actorUser?->name ?? 'konto systemowe',
        ] : null;
    }
}
