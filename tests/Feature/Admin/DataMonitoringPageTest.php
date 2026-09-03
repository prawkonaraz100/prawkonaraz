<?php

use App\Filament\Pages\DataMonitoring;
use App\Models\User;
use App\Support\AdminDataMonitoringService;

test('admin users can access the data monitoring page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(DataMonitoring::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Monitoring danych', false)
        ->assertSee('Stan monitoringu', false)
        ->assertSee('Presja infrastruktury', false)
        ->assertSee('Load CPU', false)
        ->assertSee('Pamięć', false)
        ->assertSee('Backup', false)
        ->assertSee('Health score', false)
        ->assertSee('Jakość danych', false)
        ->assertSee('Wpływ retencji', false)
        ->assertSee('Co zrobić teraz', false)
        ->assertSee('Progi alarmowe', false)
        ->assertSee('Ryzyka i rekomendacje', false)
        ->assertSee('Prognoza 30 dni', false)
        ->assertSee('Anomalie i trendy', false)
        ->assertSee('Ostatnie zdarzenia operatorskie', false)
        ->assertSee('Wykresy wzrostu', false)
        ->assertSee('Przyrost trenera pamięci', false)
        ->assertSee('Zweryfikowana pamięć', false)
        ->assertSee('Latencja DB', false)
        ->assertSee('Backup freshness', false)
        ->assertSee('Najszybszy wzrost', false)
        ->assertSee('Przyrost warstwy analitycznej', false)
        ->assertSee('Archiwum miesięczne', false)
        ->assertSee('365 dni', false);
});

test('non admin users cannot access the data monitoring page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(DataMonitoring::getUrl(panel: 'admin'))
        ->assertForbidden();
});

test('admin users see a fallback panel when monitoring service fails', function () {
    $admin = User::factory()->admin()->create();

    app()->bind(AdminDataMonitoringService::class, fn () => new class
    {
        public function build(): array
        {
            throw new RuntimeException('Testowa awaria monitoringu.');
        }
    });

    $this->actingAs($admin)
        ->get(DataMonitoring::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Monitoring jest chwilowo niedostępny', false)
        ->assertSee('Awaria odczytu', false)
        ->assertSee('Testowa awaria monitoringu.', false);
});
