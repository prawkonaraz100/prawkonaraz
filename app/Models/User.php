<?php

namespace App\Models;

use App\Support\UserReviewPhotoService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_STUDENT = 'student';

    public const DEFAULT_MODERATOR_QUOTA = 30;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'avatar_path',
        'avatar_uploaded_at',
        'avatar_social_fallback_disabled_at',
        'password_login_enabled',
        'is_admin',
        'role',
        'is_test_account',
        'requires_password_change',
        'is_temporary_account',
        'temporary_account_expires_at',
        'claimed_at',
        'moderator_quota',
        'created_by_moderator_id',
        'moderator_owner_id',
        'banned_at',
        'ban_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_test_account' => 'boolean',
            'requires_password_change' => 'boolean',
            'is_temporary_account' => 'boolean',
            'temporary_account_expires_at' => 'datetime',
            'claimed_at' => 'datetime',
            'avatar_uploaded_at' => 'datetime',
            'avatar_social_fallback_disabled_at' => 'datetime',
            'moderator_quota' => 'integer',
            'banned_at' => 'datetime',
            'password' => 'hashed',
            'password_login_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $user->role = filled($user->role) ? (string) $user->role : self::ROLE_STUDENT;

            if ($user->role === self::ROLE_ADMIN) {
                $user->is_admin = true;
            }

            if ($user->is_admin) {
                $user->role = self::ROLE_ADMIN;
            }

            $user->moderator_quota ??= self::DEFAULT_MODERATOR_QUOTA;
        });

        static::deleting(function (User $user): void {
            app(UserReviewPhotoService::class)->delete(
                $user->review()->value('photo_path'),
            );
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isAdministrator() && ! $this->isBanned();
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function questionProgress(): HasMany
    {
        return $this->hasMany(UserQuestionProgress::class);
    }

    public function incorrectQuestions(): HasMany
    {
        return $this->hasMany(UserIncorrectQuestion::class);
    }

    public function collectionIncorrectQuestions(): HasMany
    {
        return $this->hasMany(QuestionCollectionIncorrectQuestion::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function productAccessGrants(): HasMany
    {
        return $this->hasMany(ProductAccessGrant::class);
    }

    public function ownedFriendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class, 'owner_user_id');
    }

    public function acceptedFriendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class, 'accepted_by_user_id');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(UserReview::class);
    }

    public function createdModeratorAccounts(): HasMany
    {
        return $this->hasMany(self::class, 'created_by_moderator_id');
    }

    public function ownedModeratorAccounts(): HasMany
    {
        return $this->hasMany(self::class, 'moderator_owner_id');
    }

    public function quotaCountedModeratorAccounts(): HasMany
    {
        return $this->ownedModeratorAccounts()->countedForModeratorQuota();
    }

    public function createdByModerator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by_moderator_id');
    }

    public function moderatorOwner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'moderator_owner_id');
    }

    public function rankedQueueEntries(): HasMany
    {
        return $this->hasMany(RankedQueueEntry::class);
    }

    public function rankedMatchPlayers(): HasMany
    {
        return $this->hasMany(RankedMatchPlayer::class);
    }

    public function rankedMatchAnswers(): HasMany
    {
        return $this->hasMany(RankedMatchAnswer::class);
    }

    public function rankedPlayerRating(): HasOne
    {
        return $this->hasOne(RankedPlayerRating::class);
    }

    public function ipHistories(): HasMany
    {
        return $this->hasMany(UserIpHistory::class)->orderByDesc('last_seen_at');
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMIN || (bool) $this->is_admin;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT
            && ! $this->isAdministrator()
            && ! $this->isModerator()
            && ! $this->isTestAccount();
    }

    public function isTestAccount(): bool
    {
        return (bool) $this->is_test_account;
    }

    public function hasSystemProductAccess(): bool
    {
        return ! $this->isBanned()
            && ($this->isAdministrator() || $this->isModerator() || $this->isTestAccount());
    }

    public function canUseAllStudyCategories(): bool
    {
        return $this->isAdministrator() || $this->isModerator() || $this->isTestAccount();
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->requires_password_change;
    }

    public function isTemporaryAccount(): bool
    {
        return (bool) $this->is_temporary_account;
    }

    public function isUnclaimedTemporaryAccount(): bool
    {
        return $this->isTemporaryAccount() && $this->claimed_at === null;
    }

    public function temporaryAccountExpired(): bool
    {
        return $this->isUnclaimedTemporaryAccount()
            && $this->temporary_account_expires_at !== null
            && $this->temporary_account_expires_at->isPast();
    }

    public function scopeCountedForModeratorQuota(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('is_temporary_account', false)
                ->orWhereNull('is_temporary_account')
                ->orWhereNotNull('claimed_at')
                ->orWhereNull('temporary_account_expires_at')
                ->orWhere('temporary_account_expires_at', '>', now());
        });
    }

    public function moderatorQuotaLimit(): int
    {
        return max((int) ($this->moderator_quota ?? self::DEFAULT_MODERATOR_QUOTA), 0);
    }

    public function moderatorAccountsUsed(): int
    {
        return (int) ($this->moderator_accounts_used_count ?? $this->quotaCountedModeratorAccounts()->count());
    }

    public function moderatorQuotaRemaining(): int
    {
        return max($this->moderatorQuotaLimit() - $this->moderatorAccountsUsed(), 0);
    }

    public function roleLabel(): string
    {
        if ($this->isAdministrator()) {
            return 'Administrator';
        }

        if ($this->isModerator()) {
            return 'Moderator';
        }

        if ($this->isTestAccount()) {
            return 'Konto testowe';
        }

        return 'Kursant';
    }

    public function roleColor(): string
    {
        if ($this->isAdministrator()) {
            return 'gray';
        }

        if ($this->isModerator()) {
            return 'info';
        }

        if ($this->isTestAccount()) {
            return 'warning';
        }

        return 'success';
    }
}
