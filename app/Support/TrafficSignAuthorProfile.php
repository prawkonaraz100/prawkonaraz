<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use Illuminate\Support\Carbon;

class TrafficSignAuthorProfile
{
    public const SLUG = 'katarzyna-wisniewska';

    public const LEGACY_SLUG = 'redakcja-brd';

    public static function upsert(?Carbon $publishedAt = null): ContentAuthor
    {
        $publishedAt ??= now()->subDay();

        return ContentAuthor::query()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Katarzyna Wiśniewska',
                'job_title' => 'Specjalistka ds. organizacji ruchu drogowego i BRD',
                'bio' => 'Doświadczona specjalistka z ponad 8-letnią praktyką w organizacji ruchu drogowego oraz bezpieczeństwie ruchu drogowego. Specjalizuje się w projektowaniu, wdrażaniu i kontroli stałej oraz czasowej organizacji ruchu, w tym oznakowania pionowego, poziomego, sygnalizacji świetlnej i tabliczek uzupełniających. Bardzo dobrze zna rozporządzenie w sprawie szczegółowych warunków technicznych dla znaków i sygnałów drogowych oraz wytyczne techniczne dotyczące oznakowania dróg. Jako była instruktorka nauki jazdy i techniki jazdy łączy wiedzę techniczną z praktycznym spojrzeniem kierowcy. Ma doświadczenie w audytach BRD oraz współpracy z zarządcami dróg przy kontroli i optymalizacji istniejącego oznakowania.',
                'photo_path' => 'images/authors/katarzyna-wisniewska.png',
                'linkedin_url' => null,
                'external_profile_url' => null,
                'is_published' => true,
                'published_at' => $publishedAt,
            ],
        );
    }

    public static function deprecateLegacyAuthor(ContentAuthor $replacement): void
    {
        ContentAuthor::query()
            ->where('slug', self::LEGACY_SLUG)
            ->whereKeyNot($replacement->getKey())
            ->update([
                'is_published' => false,
                'published_at' => null,
            ]);
    }

    public static function moveLegacyTrafficSignsTo(ContentAuthor $replacement): void
    {
        $legacyAuthor = ContentAuthor::query()
            ->where('slug', self::LEGACY_SLUG)
            ->whereKeyNot($replacement->getKey())
            ->first();

        if (! $legacyAuthor instanceof ContentAuthor) {
            return;
        }

        TrafficSign::query()
            ->where('content_author_id', $legacyAuthor->getKey())
            ->update(['content_author_id' => $replacement->getKey()]);

        self::deprecateLegacyAuthor($replacement);
    }
}
