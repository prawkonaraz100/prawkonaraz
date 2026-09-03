<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarService
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array{url: string|null, initials: string, has_uploaded_avatar: bool}
     */
    public function payload(User $user): array
    {
        return [
            'url' => $this->url($user),
            'initials' => $this->initials($user),
            'has_uploaded_avatar' => filled($user->avatar_path),
        ];
    }

    public function url(User $user): ?string
    {
        if (filled($user->avatar_path)) {
            return $this->mediaUrlResolver->resolve((string) $user->avatar_path, 'public');
        }

        if ($user->avatar_social_fallback_disabled_at !== null) {
            return null;
        }

        return $this->socialAvatarUrl($user);
    }

    public function hasVisibleAvatar(User $user): bool
    {
        return $this->url($user) !== null;
    }

    public function initials(User $user): string
    {
        $initials = collect(preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY))
            ->take(2)
            ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8'))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    public function storeUploadedAvatar(User $user, UploadedFile $file): void
    {
        $oldPath = filled($user->avatar_path) ? (string) $user->avatar_path : null;
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'jpg');
        $filename = 'avatar-'.Str::lower(Str::random(24)).'.'.$extension;
        $directory = 'profile-avatars/'.$user->getKey();
        $path = $file->storeAs($directory, $filename, 'public');

        if (! is_string($path)) {
            throw new \RuntimeException('Profile avatar upload failed.');
        }

        $user->forceFill([
            'avatar_path' => $path,
            'avatar_uploaded_at' => now(),
        ])->save();

        if ($oldPath !== null && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    public function deleteUploadedAvatar(User $user): void
    {
        $oldPath = filled($user->avatar_path) ? (string) $user->avatar_path : null;

        $user->forceFill([
            'avatar_path' => null,
            'avatar_uploaded_at' => null,
        ])->save();

        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    public function removeAvatarByAdmin(User $user): void
    {
        $oldPath = filled($user->avatar_path) ? (string) $user->avatar_path : null;

        $user->forceFill([
            'avatar_path' => null,
            'avatar_uploaded_at' => null,
            'avatar_social_fallback_disabled_at' => now(),
        ])->save();

        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    protected function socialAvatarUrl(User $user): ?string
    {
        $user->loadMissing('socialAccounts');

        foreach ([UserSocialAccount::PROVIDER_GOOGLE, UserSocialAccount::PROVIDER_FACEBOOK] as $provider) {
            $avatarUrl = $user->socialAccounts
                ->firstWhere('provider', $provider)
                ?->avatar_url;

            if (filled($avatarUrl)) {
                return (string) $avatarUrl;
            }
        }

        return $user->socialAccounts
            ->filter(fn (UserSocialAccount $account): bool => filled($account->avatar_url))
            ->sortByDesc(fn (UserSocialAccount $account): int => $account->linked_at?->getTimestamp() ?? 0)
            ->first()
            ?->avatar_url;
    }
}
