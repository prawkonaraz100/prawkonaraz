<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserIpHistory;
use App\Models\UserProfile;
use App\Models\UserSocialAccount;
use App\Support\UserAvatarService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('admin users can access the user resource list with operational labels', function () {
    $admin = User::factory()->admin()->create();

    User::factory()->create([
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Użytkownicy', false)
        ->assertSee('Stan użytkowników', false)
        ->assertSee('Aktywni 30 dni', false)
        ->assertSee('Wzrost użytkowników', false)
        ->assertSee('Zweryfikowani vs nieweryfikowani', false)
        ->assertSee('Nowi użytkownicy vs aktywność', false)
        ->assertSee('30 dni', false)
        ->assertSee('Wszyscy', false)
        ->assertSee('Status konta', false)
        ->assertSee('Jan Kowalski', false);
});

test('user resource list shows ban state and last ip details', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->create([
        'name' => 'Zbanowany Kursant',
        'email' => 'blocked@example.com',
        'banned_at' => now(),
        'ban_reason' => 'Spam i nadużycia.',
    ]);

    DB::table('sessions')->insert([
        'id' => 'test-session-'.$user->id,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.15',
        'user_agent' => 'PHPUnit',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    AuditLog::factory()->create([
        'actor_user_id' => $user->id,
        'ip_address' => '203.0.113.20',
    ]);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('index', panel: 'admin'))
        ->assertOk()
        ->assertSee('Blokada', false)
        ->assertSee('Ostatnie IP', false)
        ->assertSee('Zablokowane', false)
        ->assertSee('203.0.113.15', false);
});

test('user resource view shows account, activity and profile sections', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->create([
        'name' => 'Anna Nowak',
        'email' => 'anna@example.com',
        'email_verified_at' => now(),
    ]);

    UserProfile::query()->create([
        'user_id' => $user->id,
        'display_name' => 'Ania',
        'study_streak' => 5,
        'tier' => 'basic',
        'onboarding_step' => 'completed',
    ]);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('view', ['record' => $user], panel: 'admin'))
        ->assertOk()
        ->assertSee('Konto', false)
        ->assertSee('Aktywność', false)
        ->assertSee('Profil kursanta', false)
        ->assertSee('Anna Nowak', false)
        ->assertSee('Ania', false);
});

test('user resource view shows security and ip details', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->create([
        'name' => 'Piotr Admin',
        'email' => 'piotr@example.com',
        'banned_at' => now(),
        'ban_reason' => 'Powtarzające się naruszenia regulaminu.',
    ]);

    DB::table('sessions')->insert([
        'id' => 'test-session-view-'.$user->id,
        'user_id' => $user->id,
        'ip_address' => '198.51.100.10',
        'user_agent' => 'PHPUnit',
        'payload' => 'test',
        'last_activity' => now()->timestamp,
    ]);

    AuditLog::factory()->create([
        'actor_user_id' => $user->id,
        'ip_address' => '198.51.100.11',
    ]);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('view', ['record' => $user], panel: 'admin'))
        ->assertOk()
        ->assertSee('Bezpieczeństwo i dostęp', false)
        ->assertSee('Powód blokady', false)
        ->assertSee('198.51.100.10', false)
        ->assertSee('198.51.100.11', false)
        ->assertSee('Powtarzające się naruszenia regulaminu.', false);
});

test('user resource view shows city and country for tracked ip history', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->create([
        'name' => 'Geo User',
        'email' => 'geo@example.com',
    ]);

    UserIpHistory::query()->create([
        'user_id' => $user->id,
        'source' => 'logowanie',
        'ip_address' => '8.8.8.8',
        'country_code' => 'US',
        'country_name' => 'United States',
        'city_name' => 'Mountain View',
        'session_id' => 'geo-session',
        'hit_count' => 1,
        'first_seen_at' => now()->subHour(),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('view', ['record' => $user], panel: 'admin'))
        ->assertOk()
        ->assertSee('Ostatnia lokalizacja', false)
        ->assertSee('Mountain View, United States', false);
});

test('admin users can create a user with verified email timestamp', function () {
    $admin = User::factory()->admin()->create();
    $verifiedAt = Carbon::parse('2026-05-26 17:30:00');

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->set('data.name', 'Verified From Admin')
        ->set('data.email', 'verified-from-admin@example.test')
        ->set('data.password', 'correct-horse-battery')
        ->set('data.role', User::ROLE_STUDENT)
        ->set('data.email_verified_at', $verifiedAt->format('Y-m-d H:i:s'))
        ->set('data.is_admin', false)
        ->set('data.is_test_account', true)
        ->set('data.moderator_quota', User::DEFAULT_MODERATOR_QUOTA)
        ->set('data.requires_password_change', false)
        ->set('data.is_temporary_account', false)
        ->call('create')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'verified-from-admin@example.test')->firstOrFail();

    expect($user->email_verified_at?->format('Y-m-d H:i:s'))->toBe($verifiedAt->format('Y-m-d H:i:s'));
});

test('admin users can update user email verification timestamp', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->unverified()->create([
        'email' => 'pending-from-admin@example.test',
        'is_test_account' => true,
    ]);
    $verifiedAt = Carbon::parse('2026-05-27 09:15:00');

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->set('data.email_verified_at', $verifiedAt->format('Y-m-d H:i:s'))
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->email_verified_at?->format('Y-m-d H:i:s'))->toBe($verifiedAt->format('Y-m-d H:i:s'));
});

test('admin users can remove uploaded profile avatar', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $path = 'profile-avatars/30/avatar-local.jpg';
    Storage::disk('public')->put($path, 'avatar');

    $user = User::factory()->create([
        'avatar_path' => $path,
        'avatar_uploaded_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('removeAvatar', $user)
        ->assertHasNoTableActionErrors();

    expect($user->fresh()->avatar_path)->toBeNull();
    expect($user->fresh()->avatar_social_fallback_disabled_at)->not->toBeNull();

    Storage::disk('public')->assertMissing($path);

    expect(AuditLog::query()
        ->where('action', 'user.avatar_removed')
        ->where('entity_type', 'user')
        ->where('entity_id', (string) $user->getKey())
        ->exists())->toBeTrue();
});

test('admin users can suppress social avatar fallback', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-avatar-admin',
        'email' => $user->email,
        'display_name' => $user->name,
        'avatar_url' => 'https://lh3.googleusercontent.com/bad-avatar.jpg',
        'linked_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('removeAvatar', $user)
        ->assertHasNoTableActionErrors();

    expect($user->fresh()->avatar_social_fallback_disabled_at)->not->toBeNull()
        ->and(app(UserAvatarService::class)->url($user->fresh()))->toBeNull();
});

test('non admin users cannot access the user resource', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(UserResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});
