<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\ProductAccessGrant;
use App\Models\User;
use App\Notifications\ModeratorAccountStartCredentials;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ModeratorAccountProvisioningService
{
    public const ACCOUNT_TYPE_FULL = 'full';

    public const ACCOUNT_TYPE_TEMPORARY = 'temporary';

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function create(User $moderator, array $attributes): array
    {
        abort_unless($moderator->isModerator(), 403);

        if ($moderator->moderatorQuotaRemaining() <= 0) {
            throw new HttpException(409, 'Moderator wykorzystał całą pulę kont.');
        }

        $accountType = (string) ($attributes['account_type'] ?? self::ACCOUNT_TYPE_FULL);
        $targetCategory = LicenseCategory::query()
            ->whereKey((int) $attributes['target_category_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $startPassword = $this->generateStartPassword();
        $isTemporary = $accountType === self::ACCOUNT_TYPE_TEMPORARY;
        $email = $isTemporary
            ? $this->generateTemporaryEmail($moderator)
            : strtolower(trim((string) $attributes['email']));
        $now = now();

        $created = DB::transaction(function () use ($moderator, $attributes, $targetCategory, $startPassword, $accountType, $isTemporary, $email, $now): array {
            $account = User::query()->create([
                'name' => trim((string) $attributes['name']),
                'email' => $email,
                'password' => $startPassword,
                'password_login_enabled' => true,
                'role' => User::ROLE_STUDENT,
                'is_admin' => false,
                'is_test_account' => false,
                'requires_password_change' => true,
                'is_temporary_account' => $isTemporary,
                'temporary_account_expires_at' => $isTemporary ? $now->copy()->addDays(14) : null,
                'created_by_moderator_id' => $moderator->getKey(),
                'moderator_owner_id' => $moderator->getKey(),
            ]);

            app(UserProfileService::class)->update($account, [
                'target_category_id' => $targetCategory->getKey(),
                'onboarding_step' => $isTemporary
                    ? 'temporary_account_created'
                    : 'moderator_account_created',
            ]);

            $accessGrant = ProductAccessGrant::query()->create([
                'user_id' => $account->getKey(),
                'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
                'status' => ProductAccessGrant::STATUS_ACTIVE,
                'starts_at' => $now,
                'expires_at' => $now->copy()->addDays(90),
                'granted_by_user_id' => $moderator->getKey(),
                'notes' => 'Dostęp nadany podczas tworzenia konta przez moderatora.',
            ]);

            app(AuditLogService::class)->record(
                'moderator.account_created',
                'user',
                (string) $account->getKey(),
                $moderator,
                [
                    'account_type' => $accountType,
                    'target_category_id' => $targetCategory->getKey(),
                    'target_category_code' => $targetCategory->code,
                    'access_expires_at' => $accessGrant->expires_at,
                    'temporary_account_expires_at' => $account->temporary_account_expires_at,
                ],
            );

            return [
                'account' => $account,
                'target_category_code' => $targetCategory->code,
                'access_expires_at' => $accessGrant->expires_at,
                'payload' => $this->accountPayload(
                    $account,
                    $accountType,
                    $startPassword,
                    $targetCategory->code,
                    $accessGrant->expires_at,
                    $account->temporary_account_expires_at,
                ),
            ];
        });

        $emailSent = $isTemporary
            ? null
            : $this->sendStartCredentials(
                $created['account'],
                $moderator,
                $startPassword,
                $created['target_category_code'],
                $created['access_expires_at'],
                'moderator.account_start_credentials_sent',
            );

        return [
            ...$created['payload'],
            'start_credentials_email_sent' => $emailSent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function regenerateStartPassword(User $moderator, User $account): array
    {
        abort_unless($moderator->isModerator(), 403);

        $startPassword = $this->generateStartPassword();

        $updated = DB::transaction(function () use ($moderator, $account, $startPassword): array {
            $lockedAccount = User::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStartPasswordCanBeRegenerated($moderator, $lockedAccount);

            $lockedAccount->forceFill([
                'password' => Hash::make($startPassword),
                'password_login_enabled' => true,
                'requires_password_change' => true,
                'remember_token' => Str::random(60),
            ])->save();

            $lockedAccount->loadMissing('profile.targetCategory');

            $targetCategoryCode = $lockedAccount->profile?->targetCategory?->code ?? 'brak';
            $accessExpiresAt = $lockedAccount->productAccessGrants()
                ->where('source', ProductAccessGrant::SOURCE_MODERATOR_GRANT)
                ->latest('created_at')
                ->first()?->expires_at;

            app(AuditLogService::class)->record(
                'moderator.account_start_password_regenerated',
                'user',
                (string) $lockedAccount->getKey(),
                $moderator,
                [
                    'target_category_code' => $targetCategoryCode,
                    'access_expires_at' => $accessExpiresAt,
                ],
            );

            return [
                'account' => $lockedAccount,
                'target_category_code' => $targetCategoryCode,
                'access_expires_at' => $accessExpiresAt,
                'payload' => $this->accountPayload(
                    $lockedAccount,
                    self::ACCOUNT_TYPE_FULL,
                    $startPassword,
                    $targetCategoryCode,
                    $accessExpiresAt,
                    null,
                ),
            ];
        });

        $emailSent = $this->sendStartCredentials(
            $updated['account'],
            $moderator,
            $startPassword,
            $updated['target_category_code'],
            $updated['access_expires_at'],
            'moderator.account_start_credentials_resent',
        );

        return [
            ...$updated['payload'],
            'start_credentials_email_sent' => $emailSent,
        ];
    }

    protected function ensureStartPasswordCanBeRegenerated(User $moderator, User $account): void
    {
        abort_unless(
            $account->moderator_owner_id !== null
                && (int) $account->moderator_owner_id === (int) $moderator->getKey(),
            403,
        );

        if ($account->isTemporaryAccount() || $account->claimed_at !== null) {
            throw ValidationException::withMessages([
                'account' => 'Hasło startowe można wygenerować ponownie tylko dla pełnego konta z e-mailem.',
            ]);
        }

        if (! $account->mustChangePassword()) {
            throw ValidationException::withMessages([
                'account' => 'Nie można wygenerować hasła startowego, bo użytkownik rozpoczął już korzystanie z konta.',
            ]);
        }

        if ($account->isBanned()) {
            throw ValidationException::withMessages([
                'account' => 'Nie można wygenerować hasła startowego dla zablokowanego konta.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function accountPayload(
        User $account,
        string $accountType,
        string $startPassword,
        string $targetCategoryCode,
        ?CarbonInterface $accessExpiresAt,
        ?CarbonInterface $temporaryAccountExpiresAt,
    ): array {
        return [
            'id' => $account->getKey(),
            'name' => $account->name,
            'login_email' => $account->email,
            'account_type' => $accountType,
            'start_password' => $startPassword,
            'target_category_code' => $targetCategoryCode,
            'access_expires_at' => $accessExpiresAt?->toIso8601String(),
            'temporary_account_expires_at' => $temporaryAccountExpiresAt?->toIso8601String(),
        ];
    }

    protected function sendStartCredentials(
        User $account,
        User $moderator,
        string $startPassword,
        string $targetCategoryCode,
        ?CarbonInterface $accessExpiresAt,
        string $auditAction,
    ): bool {
        $emailSent = true;

        try {
            $account->notify(new ModeratorAccountStartCredentials(
                $startPassword,
                $targetCategoryCode,
                $accessExpiresAt,
            ));
        } catch (Throwable $exception) {
            $emailSent = false;

            report($exception);
        }

        app(AuditLogService::class)->record(
            $auditAction,
            'user',
            (string) $account->getKey(),
            $moderator,
            [
                'target_category_code' => $targetCategoryCode,
                'access_expires_at' => $accessExpiresAt,
                'email_sent' => $emailSent,
            ],
        );

        return $emailSent;
    }

    protected function generateTemporaryEmail(User $moderator): string
    {
        do {
            $email = sprintf(
                'tymczasowe-%d-%s@moderator.local',
                $moderator->getKey(),
                Str::lower(Str::random(12)),
            );
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }

    protected function generateStartPassword(): string
    {
        return sprintf(
            '%s-%s-%d',
            Str::upper(Str::random(4)),
            Str::lower(Str::random(4)),
            random_int(1000, 9999),
        );
    }
}
