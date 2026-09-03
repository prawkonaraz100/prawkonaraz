<?php

namespace App\Support;

use App\Models\TrafficSign;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicQuestionSignReferenceService
{
    private const SIGN_CODE_PATTERN = '/(?<![\pL\pN])([A-Z]{1,3}-\d{1,3}[a-zA-Z]?[A-Z]?(?:-[A-Z])?(?:\/\d{1,3}[a-zA-Z]?)?)(?![\pL\pN])/iu';

    /**
     * @var array<string, array<string, mixed>|null>
     */
    protected array $cardCache = [];

    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
    ) {}

    public function decorateHtml(?string $html): string
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        $cards = $this->cardsByCodes($this->codesFromText(strip_tags($html)));

        if ($cards === []) {
            return $html;
        }

        $seenCodes = [];
        $parts = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        return collect($parts)
            ->map(function (string $part) use ($cards, &$seenCodes): string {
                if ($part === '' || str_starts_with($part, '<')) {
                    return $part;
                }

                return $this->decorateTextPart($part, $cards, $seenCodes);
            })
            ->implode('');
    }

    public function decoratePlainText(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        return $this->decorateHtml(e($text));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cardsForText(?string $text): array
    {
        return array_values($this->cardsByCodesForText($text));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function cardsByCodesForText(?string $text): array
    {
        return $this->cardsByCodes($this->codesFromText($text));
    }

    /**
     * Warm the in-request sign card cache for a batch of explanation texts.
     *
     * @param  iterable<string|null>  $texts
     */
    public function preloadForTexts(iterable $texts): void
    {
        $codes = collect($texts)
            ->flatMap(fn (?string $text): array => $this->codesFromText($text))
            ->unique(fn (string $code): string => $this->codeKey($code))
            ->values()
            ->all();

        $this->cardsByCodes($codes);
    }

    public function firstCardForText(?string ...$texts): ?array
    {
        foreach ($texts as $text) {
            $card = $this->cardsForText($text)[0] ?? null;

            if (is_array($card)) {
                return $card;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function codesFromText(?string $text): array
    {
        $text = (string) $text;

        if ($text === '') {
            return [];
        }

        preg_match_all(self::SIGN_CODE_PATTERN, $text, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (mixed $code): string => $this->canonicalCodeCandidate((string) $code))
            ->filter(fn (string $code): bool => $code !== '')
            ->unique(fn (string $code): string => $this->codeKey($code))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $card
     */
    public function renderBadge(?array $card, string $variant = 'inline'): string
    {
        if ($card === null || ! filled($card['code'] ?? null)) {
            return '';
        }

        return view('components.public.sign-badge', [
            'sign' => $card,
            'variant' => $variant,
        ])->render();
    }

    /**
     * @return array<string, mixed>
     */
    public function cardForTrafficSign(TrafficSign $sign): array
    {
        $title = $sign->publicTitle();
        $description = Str::squish(strip_tags((string) (
            $sign->intro_definition
                ?: $sign->meaning
                ?: $sign->driver_behavior
                ?: ''
        )));

        return [
            'code' => $sign->publicCode(),
            'name' => (string) $sign->name,
            'title' => $title !== '' ? $title : (string) $sign->code,
            'description' => $description !== '' ? Str::limit($description, 360, '...') : null,
            'image_url' => $this->signImageUrl($sign),
            'image_alt' => $sign->publicImageAlt(),
            'url' => $this->publicSignUrl($sign),
            'link_label' => $this->publicSignUrl($sign) !== null ? 'Zobacz opis znaku' : null,
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, array<string, mixed>>
     */
    protected function cardsByCodes(array $codes): array
    {
        $codes = collect($codes)
            ->map(fn (string $code): string => $this->canonicalCodeCandidate($code))
            ->filter(fn (string $code): bool => $code !== '')
            ->unique(fn (string $code): string => $this->codeKey($code))
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        $missingCodes = $codes
            ->reject(fn (string $code): bool => array_key_exists($this->codeKey($code), $this->cardCache))
            ->values();

        if ($missingCodes->isNotEmpty()) {
            $this->loadCards($missingCodes);
        }

        return $codes
            ->mapWithKeys(function (string $code): array {
                $key = $this->codeKey($code);
                $card = $this->cardCache[$key] ?? null;

                return is_array($card) ? [$key => $card] : [];
            })
            ->all();
    }

    /**
     * @param  Collection<int, string>  $codes
     */
    protected function loadCards(Collection $codes): void
    {
        $codes->each(function (string $code): void {
            $this->cardCache[$this->codeKey($code)] = null;
        });

        TrafficSign::query()
            ->published()
            ->whereIn('code', $codes->all())
            ->with([
                'author:id,is_published,published_at',
                'category:id,is_published,published_at',
            ])
            ->get()
            ->each(function (TrafficSign $sign): void {
                $this->cardCache[$this->codeKey((string) $sign->code)] = $this->cardForTrafficSign($sign);
            });
    }

    /**
     * @param  array<string, array<string, mixed>>  $cards
     * @param  array<string, bool>  $seenCodes
     */
    protected function decorateTextPart(string $text, array $cards, array &$seenCodes): string
    {
        return preg_replace_callback(
            self::SIGN_CODE_PATTERN,
            function (array $matches) use ($cards, &$seenCodes): string {
                $code = $this->canonicalCodeCandidate((string) ($matches[1] ?? ''));
                $key = $this->codeKey($code);

                if (($seenCodes[$key] ?? false) || ! isset($cards[$key])) {
                    return (string) ($matches[0] ?? '');
                }

                $seenCodes[$key] = true;

                return $this->renderBadge($cards[$key]);
            },
            $text,
        ) ?? $text;
    }

    protected function canonicalCodeCandidate(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        $code = str_replace(' ', '', $code);

        if (preg_match('/^([a-z]{1,3})-(.+)$/i', $code, $matches) !== 1) {
            return $code;
        }

        return mb_strtoupper($matches[1]).'-'.$matches[2];
    }

    protected function codeKey(string $code): string
    {
        return mb_strtoupper($this->canonicalCodeCandidate($code));
    }

    protected function signImageUrl(TrafficSign $sign): ?string
    {
        $cutoutPath = "traffic-signs/sign-cutouts/{$sign->slug}.png";

        if (is_file(public_path($cutoutPath))) {
            return $this->mediaUrlResolver->resolve($cutoutPath);
        }

        return $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path);
    }

    protected function publicSignUrl(TrafficSign $sign): ?string
    {
        if (! $sign->isPubliclyVisible()) {
            return null;
        }

        $author = $sign->relationLoaded('author') ? $sign->author : null;
        $category = $sign->relationLoaded('category') ? $sign->category : null;

        if (
            ($author !== null && method_exists($author, 'isPubliclyVisible') && ! $author->isPubliclyVisible())
            || ($category !== null && method_exists($category, 'isPubliclyVisible') && ! $category->isPubliclyVisible())
        ) {
            return null;
        }

        return route('traffic-signs.show', $sign->slug, absolute: false);
    }
}
