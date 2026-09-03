<?php

use App\Models\TrafficSign;
use App\Support\MediaUrlResolver;
use App\Support\PolishInformationalSignCatalog;
use App\Support\TrafficSignSupportingPageCatalog;
use Database\Seeders\TrafficSignSeoSeeder;

test('published informational cluster pages keep core sections and supporting links', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $supportingPageCatalog = app(TrafficSignSupportingPageCatalog::class);
    $catalog = app(PolishInformationalSignCatalog::class);

    $informationalSigns = TrafficSign::query()
        ->published()
        ->with(['author', 'category'])
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-informacyjne'))
        ->orderBy('code')
        ->get();

    expect($informationalSigns)->toHaveCount(count($catalog->all()));

    foreach ($informationalSigns as $sign) {
        $response = $this->get(route('traffic-signs.show', $sign->slug))
            ->assertOk()
            ->assertSeeText($sign->code.' '.$sign->name)
            ->assertSeeText($sign->author->name)
            ->assertSeeText('Podstawa prawna')
            ->assertSeeText('Najczęstsze pytania o ten znak')
            ->assertSeeText('Powiązane znaki');

        $supportingPages = $supportingPageCatalog->forSign($sign);

        if ($supportingPages !== []) {
            $response
                ->assertSeeText('Powiązane materiały')
                ->assertSeeText($supportingPages[0]['title']);
        }
    }
});

test('quality pass prioritizes semantically related informational signs on sign pages', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $this->get(route('traffic-signs.show', 'd-1-droga-z-pierwszenstwem'))
        ->assertOk()
        ->assertSeeText('D-2')
        ->assertSeeText('Koniec drogi z pierwszeństwem')
        ->assertSeeText('D-3')
        ->assertSeeText('Droga jednokierunkowa');

    $this->get(route('traffic-signs.show', 'd-6-przejscie-dla-pieszych'))
        ->assertOk()
        ->assertSeeText('D-6a')
        ->assertSeeText('Przejazd dla rowerzystów')
        ->assertSeeText('D-4a')
        ->assertSeeText('Droga bez przejazdu');
});

test('published informational signs keep substantive content and avoid generic boilerplate', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $catalog = app(PolishInformationalSignCatalog::class);

    $informationalSigns = TrafficSign::query()
        ->published()
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-informacyjne'))
        ->orderBy('code')
        ->get();

    $bannedPhrases = [
        'przekazuje informację opisaną jako',
        'najczęściej taki znak stoi tam',
        'po zauważeniu znaku warto wcześniej przygotować obserwację',
        'mało istotnego tła',
    ];

    expect($informationalSigns)->toHaveCount(count($catalog->all()));

    $violations = [];

    foreach ($informationalSigns as $sign) {
        $label = "{$sign->code} {$sign->name}";

        $minimumLengths = [
            'intro_definition' => [120, mb_strlen(trim((string) $sign->intro_definition))],
            'meaning' => [150, mb_strlen(trim((string) $sign->meaning))],
            'placement' => [110, mb_strlen(trim((string) $sign->placement))],
            'driver_behavior' => [140, mb_strlen(trim((string) $sign->driver_behavior))],
            'legal_summary' => [120, mb_strlen(trim((string) $sign->legal_summary))],
            'fine_summary' => [120, mb_strlen(trim((string) $sign->fine_summary))],
            'common_mistakes' => [120, mb_strlen(trim((string) $sign->common_mistakes))],
        ];

        foreach ($minimumLengths as $field => [$minimum, $actual]) {
            if ($actual <= $minimum) {
                $violations[] = "[{$label}] {$field} is too short ({$actual} <= {$minimum})";
            }
        }

        $contentBlocks = [
            $sign->intro_definition,
            $sign->meaning,
            $sign->placement,
            $sign->driver_behavior,
            $sign->legal_summary,
            $sign->fine_summary,
            $sign->common_mistakes,
        ];

        foreach ($contentBlocks as $block) {
            $normalizedBlock = mb_strtolower((string) $block);

            foreach ($bannedPhrases as $phrase) {
                if (str_contains($normalizedBlock, $phrase)) {
                    $violations[] = "Generic boilerplate [{$phrase}] found in {$label}";
                }
            }
        }

        $faqItems = collect($sign->faq_items ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->values();

        if ($faqItems->count() < 2) {
            $violations[] = "[{$label}] should expose at least 2 FAQ items";
        }

        foreach ($faqItems as $faqItem) {
            $answerLength = mb_strlen(trim((string) $faqItem['answer']));

            if ($answerLength <= 80) {
                $violations[] = "[{$label}] FAQ answer is too short ({$answerLength} <= 80)";
            }
        }
    }

    expect($violations)->toBe([], implode(PHP_EOL, $violations));
});

test('published informational sign exposes the same webp asset in page markup, og:image and json-ld', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $sign = TrafficSign::query()
        ->published()
        ->where('slug', 'd-1-droga-z-pierwszenstwem')
        ->firstOrFail();

    $assetUrl = app(MediaUrlResolver::class)->resolve($sign->image_path);

    expect($assetUrl)->not->toBeNull();

    $this->get(route('traffic-signs.show', $sign->slug))
        ->assertOk()
        ->assertSee('meta property="og:image" content="'.$assetUrl.'"', false)
        ->assertSee('meta property="og:image:alt" content="'.$sign->og_image_alt.'"', false)
        ->assertSee('meta property="og:image:width" content="'.$sign->og_image_width.'"', false)
        ->assertSee('meta property="og:image:height" content="'.$sign->og_image_height.'"', false)
        ->assertSee('"url":"'.$assetUrl.'"', false)
        ->assertSee('"caption":"'.$sign->image_alt.'"', false)
        ->assertSee('rel="preload" href="'.$assetUrl.'" as="image"', false);
});

test('D-17 tram stop is published with its final asset and passenger safety guidance', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $sign = TrafficSign::query()
        ->where('code', 'D-17')
        ->firstOrFail();

    expect($sign->slug)->toBe('d-17-przystanek-tramwajowy')
        ->and($sign->image_path)->toBe('traffic-signs/signs/informational/znak-d-17-przystanek-tramwajowy.webp')
        ->and($sign->image_width)->toBe(1200)
        ->and($sign->image_height)->toBe(1200)
        ->and(is_file(public_path($sign->image_path)))->toBeTrue();

    $this->get(route('traffic-signs.show', $sign->slug))
        ->assertOk()
        ->assertSeeText('D-17 Przystanek tramwajowy')
        ->assertSeeText('przystanek nie ma wysepki')
        ->assertSee('traffic-signs/sign-cutouts/d-17-przystanek-tramwajowy.png', false);
});
