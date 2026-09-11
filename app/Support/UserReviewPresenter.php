<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserReview;
use Illuminate\Support\Str;

class UserReviewPresenter
{
    /** @var array<string, int> */
    private const SOCIAL_ICON_WIDTHS = [
        'facebook' => 315,
        'instagram' => 340,
        'tiktok' => 334,
        'youtube' => 328,
        'website' => 485,
    ];

    public function __construct(
        protected UserAvatarService $userAvatars,
    ) {}

    /**
     * @return array{id: int, name: string, initials: string, avatar_url: string|null, rating: int, content: string, content_preview: string, content_has_more: bool, published_label: string|null, photo_url: string|null, social_links: list<array{platform: string, label: string, url: string, icon_width: int, icon_height: int}>}
     */
    public function payload(UserReview $review): array
    {
        $avatar = $this->userAvatars->payload($review->user);

        $content = trim((string) $review->content);

        return [
            'id' => (int) $review->getKey(),
            'name' => $this->publicReviewerName($review->user),
            'initials' => $avatar['initials'],
            'avatar_url' => $avatar['url'],
            'rating' => $review->rating,
            'content' => $content,
            'content_preview' => Str::limit($content, 240, '…'),
            'content_has_more' => mb_strlen($content, 'UTF-8') > 240,
            'published_label' => $review->published_at?->locale('pl')->translatedFormat('j F Y'),
            'photo_url' => filled($review->photo_path)
                ? route('reviews.photo', ['review' => $review], absolute: false)
                : null,
            'social_links' => $this->socialLinks($review),
        ];
    }

    /**
     * @return list<array{platform: string, label: string, url: string, icon_width: int, icon_height: int}>
     */
    private function socialLinks(UserReview $review): array
    {
        $storedLinks = is_array($review->social_links) ? $review->social_links : [];
        $links = [];

        foreach (UserReview::SOCIAL_LINK_LABELS as $platform => $label) {
            $url = trim((string) ($storedLinks[$platform] ?? ''));
            $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME), 'UTF-8');

            if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
                continue;
            }

            $links[] = [
                'platform' => $platform,
                'label' => $label,
                'url' => $url,
                'icon_width' => self::SOCIAL_ICON_WIDTHS[$platform],
                'icon_height' => 96,
            ];
        }

        return $links;
    }

    private function publicReviewerName(User $user): string
    {
        $parts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'Użytkownik PrawkoNaRaz';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].' '.mb_strtoupper(mb_substr($parts[1], 0, 1, 'UTF-8'), 'UTF-8').'.';
    }
}
