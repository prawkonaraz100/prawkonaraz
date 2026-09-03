<?php

namespace Database\Factories;

use App\Models\ProductAccessGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_admin' => false,
            'role' => User::ROLE_STUDENT,
            'password_login_enabled' => true,
            'is_test_account' => false,
            'requires_password_change' => false,
            'is_temporary_account' => false,
            'temporary_account_expires_at' => null,
            'claimed_at' => null,
            'moderator_quota' => User::DEFAULT_MODERATOR_QUOTA,
            'created_by_moderator_id' => null,
            'moderator_owner_id' => null,
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => false,
            'role' => User::ROLE_MODERATOR,
            'moderator_quota' => User::DEFAULT_MODERATOR_QUOTA,
        ]);
    }

    public function testAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => false,
            'role' => User::ROLE_STUDENT,
            'is_test_account' => true,
        ]);
    }

    public function temporaryModeratorAccount(?User $moderator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_temporary_account' => true,
            'requires_password_change' => true,
            'temporary_account_expires_at' => now()->addDays(90),
            'created_by_moderator_id' => $moderator?->id,
            'moderator_owner_id' => $moderator?->id,
        ]);
    }

    public function withPurchasedAccess(): static
    {
        return $this->afterCreating(function (User $user): void {
            ProductAccessGrant::query()->create([
                'user_id' => $user->id,
                'source' => ProductAccessGrant::SOURCE_PURCHASE,
                'starts_at' => now()->subMinute(),
                'expires_at' => now()->addMonth(),
            ]);
        });
    }
}
