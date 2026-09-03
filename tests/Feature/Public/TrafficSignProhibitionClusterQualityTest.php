<?php

use App\Models\TrafficSign;
use App\Support\TrafficSignSupportingPageCatalog;
use Database\Seeders\TrafficSignSeoSeeder;

test('published prohibition cluster pages keep core sections and supporting links', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $supportingPageCatalog = app(TrafficSignSupportingPageCatalog::class);

    $prohibitionSigns = TrafficSign::query()
        ->published()
        ->with(['author', 'category'])
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-zakazu'))
        ->orderBy('code')
        ->get();

    expect($prohibitionSigns)->toHaveCount(51);

    foreach ($prohibitionSigns as $sign) {
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

test('quality pass prioritizes semantically related prohibition signs on sign pages', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $this->get(route('traffic-signs.show', 'b-39-strefa-ograniczonego-postoju'))
        ->assertOk()
        ->assertSeeText('B-40 Koniec strefy ograniczonego postoju')
        ->assertSeeText('B-35 Zakaz postoju');

    $this->get(route('traffic-signs.show', 'b-26-zakaz-wyprzedzania-przez-samochody-ciezarowe'))
        ->assertOk()
        ->assertSeeText('B-25 Zakaz wyprzedzania')
        ->assertSeeText('B-28 Koniec zakazu wyprzedzania przez samochody ciężarowe');
});

test('published prohibition sign exposes the same webp asset in page markup, og:image and json-ld', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $assetUrl = 'http://localhost:8000/traffic-signs/signs/prohibitions/znak-b-20-stop.webp';

    $this->get(route('traffic-signs.show', 'b-20-stop'))
        ->assertOk()
        ->assertSee('meta property="og:image" content="'.$assetUrl.'"', false)
        ->assertSee('meta property="og:image:alt" content="STOP"', false)
        ->assertSee('meta property="og:image:width" content="1200"', false)
        ->assertSee('meta property="og:image:height" content="1200"', false)
        ->assertSee('"url":"'.$assetUrl.'"', false)
        ->assertSee('"caption":"STOP"', false)
        ->assertSee('rel="preload" href="'.$assetUrl.'" as="image"', false);
});
