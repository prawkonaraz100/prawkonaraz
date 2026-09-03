<?php

namespace App\Support;

use App\Models\ProductAccessGrant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ModeratorAccountsPanelService
{
    /**
     * @param  array<string, mixed>|null  $createdAccount
     * @return array<string, mixed>
     */
    public function dataFor(User $viewer, ?array $createdAccount = null): array
    {
        $accounts = $this->accountsQueryFor($viewer)->get();
        $rows = $accounts
            ->map(fn (User $account): array => $this->accountRow($account, $viewer))
            ->values();
        $statusCounts = $this->statusCounts($rows);

        return [
            'panel' => [
                'is_admin_view' => $viewer->isAdministrator(),
                'moderator' => [
                    'id' => $viewer->getKey(),
                    'name' => $viewer->name,
                    'email' => $viewer->email,
                ],
                'quota' => $this->quotaFor($viewer, $accounts),
                'summary' => [
                    'total' => $rows->count(),
                    'active' => $statusCounts['active'] ?? 0,
                    'pending_claim' => $statusCounts['pending_claim'] ?? 0,
                    'expired' => $statusCounts['expired'] ?? 0,
                    'banned' => $statusCounts['banned'] ?? 0,
                ],
                'can_create_accounts' => $viewer->isModerator() && $viewer->moderatorQuotaRemaining() > 0,
            ],
            'categories' => $this->categoriesFor($viewer),
            'accounts' => $rows->all(),
            'createdAccount' => $createdAccount,
        ];
    }

    protected function accountsQueryFor(User $viewer): Builder
    {
        $query = User::query()
            ->with([
                'moderatorOwner:id,name,email',
                'profile.targetCategory:id,code,name',
                'productAccessGrants' => fn ($query) => $query
                    ->where('source', ProductAccessGrant::SOURCE_MODERATOR_GRANT)
                    ->latest('created_at'),
            ])
            ->whereNotNull('moderator_owner_id')
            ->latest('created_at');

        if (! $viewer->isAdministrator()) {
            $query->where('moderator_owner_id', $viewer->getKey());
        }

        return $query;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    protected function statusCounts(Collection $rows): array
    {
        return $rows
            ->countBy(fn (array $row): string => (string) data_get($row, 'status.code'))
            ->all();
    }

    /**
     * @param  Collection<int, User>  $accounts
     * @return array<string, mixed>
     */
    protected function quotaFor(User $viewer, Collection $accounts): array
    {
        if (! $viewer->isModerator()) {
            return [
                'limit' => null,
                'used' => $accounts->count(),
                'remaining' => null,
                'percent' => null,
            ];
        }

        $limit = $viewer->moderatorQuotaLimit();
        $used = $viewer->moderatorAccountsUsed();

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => max($limit - $used, 0),
            'percent' => $limit > 0 ? min(round(($used / $limit) * 100), 100) : 100,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function accountRow(User $account, User $viewer): array
    {
        $status = $this->statusFor($account);
        $accessExpiresAt = $this->accessExpiresAt($account);
        $targetCategory = $account->profile?->targetCategory;

        return [
            'id' => $account->getKey(),
            'name' => $account->name,
            'email' => $account->email,
            'created_at' => $this->formatDateTime($account->created_at),
            'claimed_at' => $this->formatDateTime($account->claimed_at),
            'temporary_account_expires_at' => $this->formatDateTime($account->temporary_account_expires_at),
            'access_expires_at' => $this->formatDateTime($accessExpiresAt),
            'requires_password_change' => $account->mustChangePassword(),
            'is_temporary_account' => $account->isTemporaryAccount(),
            'can_regenerate_start_password' => $this->canRegenerateStartPassword($account, $viewer),
            'status' => $status,
            'target_category' => $targetCategory ? [
                'id' => $targetCategory->getKey(),
                'code' => $targetCategory->code,
                'name' => $targetCategory->name,
            ] : null,
            'moderator_owner' => $viewer->isAdministrator() && $account->moderatorOwner ? [
                'id' => $account->moderatorOwner->getKey(),
                'name' => $account->moderatorOwner->name,
                'email' => $account->moderatorOwner->email,
            ] : null,
        ];
    }

    protected function canRegenerateStartPassword(User $account, User $viewer): bool
    {
        return $viewer->isModerator()
            && (int) $account->moderator_owner_id === (int) $viewer->getKey()
            && ! $account->isTemporaryAccount()
            && $account->claimed_at === null
            && $account->mustChangePassword()
            && ! $account->isBanned();
    }

    /**
     * @return array<string, string>
     */
    protected function statusFor(User $account): array
    {
        if ($account->isBanned()) {
            return [
                'code' => 'banned',
                'label' => 'Zablokowane',
                'tone' => 'danger',
            ];
        }

        if ($account->temporaryAccountExpired()) {
            return [
                'code' => 'expired',
                'label' => 'Wygasłe',
                'tone' => 'warning',
            ];
        }

        if ($account->isUnclaimedTemporaryAccount()) {
            return [
                'code' => 'pending_claim',
                'label' => 'Wymaga przejęcia',
                'tone' => 'info',
            ];
        }

        $accessExpiresAt = $this->accessExpiresAt($account);

        if ($accessExpiresAt !== null && $accessExpiresAt->isPast()) {
            return [
                'code' => 'expired',
                'label' => 'Wygasłe',
                'tone' => 'warning',
            ];
        }

        return [
            'code' => 'active',
            'label' => 'Aktywne',
            'tone' => 'success',
        ];
    }

    protected function accessExpiresAt(User $account): ?CarbonInterface
    {
        $moderatorGrant = $account->productAccessGrants->first();

        return $moderatorGrant?->expires_at ?? $account->temporary_account_expires_at;
    }

    protected function formatDateTime(?CarbonInterface $dateTime): ?string
    {
        return $dateTime?->toIso8601String();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function categoriesFor(User $viewer): array
    {
        return app(StudyContextService::class)
            ->activeCategories($viewer)
            ->map(fn ($category): array => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }
}
