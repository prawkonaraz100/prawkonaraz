<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HomepageVideo extends Model
{
    public const KIND_VIDEO = 'video';

    public const KIND_PODCAST = 'podcast';

    protected $fillable = [
        'title',
        'kind',
        'youtube_url',
        'thumbnail_path',
        'duration_seconds',
        'published_on',
        'sort_order',
        'is_published',
    ];

    protected static function booted(): void
    {
        static::saving(function (HomepageVideo $video): void {
            $video->youtube_video_id = static::extractYouTubeVideoId($video->youtube_url) ?? '';
        });
    }

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'published_on' => 'date',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('youtube_video_id', '!=', '');
    }

    public function embedUrl(): string
    {
        return "https://www.youtube-nocookie.com/embed/{$this->youtube_video_id}?rel=0";
    }

    public function thumbnailUrl(): string
    {
        if (filled($this->thumbnail_path)) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        return "https://i.ytimg.com/vi/{$this->youtube_video_id}/hqdefault.jpg";
    }

    public function formattedDuration(): ?string
    {
        if ($this->duration_seconds === null) {
            return null;
        }

        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%d:%02d', $minutes, $seconds);
    }

    public static function extractYouTubeVideoId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url(trim($url));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $candidate = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $candidate = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $candidate = $query['v'] ?? null;
            } elseif (preg_match('~^(?:embed|shorts)/([^/]+)~', $path, $matches) === 1) {
                $candidate = $matches[1];
            }
        }

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $candidate) === 1
            ? $candidate
            : null;
    }
}
