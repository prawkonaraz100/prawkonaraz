<?php

use App\Models\User;
use App\Models\UserSocialAccount;
use App\Support\UserAvatarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('user can upload a profile avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 512, 512)->size(256),
        ])
        ->assertRedirect(route('profile.edit', absolute: false))
        ->assertSessionHas('status', 'profile-avatar-updated');

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull()
        ->and($user->avatar_uploaded_at)->not->toBeNull();

    Storage::disk('public')->assertExists($user->avatar_path);
});

test('profile avatar upload rejects unsupported files', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('profile.avatar.store'), [
            'avatar' => UploadedFile::fake()->create('avatar.svg', 20, 'image/svg+xml'),
        ])
        ->assertRedirect(route('profile.edit', absolute: false))
        ->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar_path)->toBeNull();
});

test('user can delete uploaded avatar and fall back to social avatar', function () {
    Storage::fake('public');

    $path = 'profile-avatars/1/avatar-old.jpg';
    Storage::disk('public')->put($path, 'old-avatar');

    $user = User::factory()->create([
        'avatar_path' => $path,
        'avatar_uploaded_at' => now(),
    ]);

    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-avatar',
        'email' => $user->email,
        'display_name' => $user->name,
        'avatar_url' => 'https://lh3.googleusercontent.com/avatar.jpg',
        'linked_at' => now(),
    ]);

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('profile.edit', absolute: false))
        ->assertSessionHas('status', 'profile-avatar-deleted');

    $user->refresh();

    expect($user->avatar_path)->toBeNull()
        ->and(app(UserAvatarService::class)->url($user))->toBe('https://lh3.googleusercontent.com/avatar.jpg');

    Storage::disk('public')->assertMissing($path);
});

test('uploaded avatar wins over google avatar', function () {
    Storage::fake('public');

    $path = 'profile-avatars/5/avatar-local.jpg';
    Storage::disk('public')->put($path, 'local-avatar');

    $user = User::factory()->create([
        'avatar_path' => $path,
        'avatar_uploaded_at' => now(),
    ]);

    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-local-priority',
        'email' => $user->email,
        'display_name' => $user->name,
        'avatar_url' => 'https://lh3.googleusercontent.com/google-avatar.jpg',
        'linked_at' => now(),
    ]);

    $avatarUrl = app(UserAvatarService::class)->url($user);

    expect($avatarUrl)->not->toBe('https://lh3.googleusercontent.com/google-avatar.jpg')
        ->and($avatarUrl)->toContain('profile-avatars/5/avatar-local.jpg');
});

test('profile page shares avatar data through inertia auth props', function () {
    Storage::fake('public');

    $path = 'profile-avatars/9/avatar-local.webp';
    Storage::disk('public')->put($path, 'local-avatar');

    $user = User::factory()->create([
        'name' => 'Anna Kowalska',
        'avatar_path' => $path,
        'avatar_uploaded_at' => now(),
    ]);

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.avatar_initials', 'AK')
            ->where('auth.user.has_uploaded_avatar', true)
            ->where('auth.user.avatar_url', fn (?string $url): bool => $url !== null && str_contains($url, 'profile-avatars/9/avatar-local.webp'))
        );
});
