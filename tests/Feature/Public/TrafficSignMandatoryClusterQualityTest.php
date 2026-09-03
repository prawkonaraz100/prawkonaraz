<?php

use App\Models\TrafficSign;
use App\Support\TrafficSignSupportingPageCatalog;
use Database\Seeders\TrafficSignSeoSeeder;

test('published mandatory cluster pages keep core sections and supporting links', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $supportingPageCatalog = app(TrafficSignSupportingPageCatalog::class);

    $mandatorySigns = TrafficSign::query()
        ->published()
        ->with(['author', 'category'])
        ->whereHas('category', fn ($query) => $query->where('slug', 'znaki-nakazu'))
        ->orderBy('code')
        ->get();

    expect($mandatorySigns)->toHaveCount(23);

    foreach ($mandatorySigns as $sign) {
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

test('quality pass prioritizes semantically related mandatory signs on sign pages', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $this->get(route('traffic-signs.show', 'c-18-nakaz-uzywania-lancuchow-przeciwslizgowych'))
        ->assertOk()
        ->assertSeeText('C-19 Koniec nakazu używania łańcuchów przeciwślizgowych')
        ->assertSeeText('C-14 Prędkość minimalna 40 km/h');

    $this->get(route('traffic-signs.show', 'c-13-droga-dla-rowerow'))
        ->assertOk()
        ->assertSeeText('C-13a Koniec drogi dla rowerów')
        ->assertSeeText('C-13/16 Droga dla rowerów i pieszych');
});

test('published mandatory sign exposes the same webp asset in page markup, og:image and json-ld', function () {
    $this->seed(TrafficSignSeoSeeder::class);

    $assetUrl = 'http://localhost:8000/traffic-signs/signs/mandatory/znak-c-1-nakaz-jazdy-w-prawo.webp';

    $this->get(route('traffic-signs.show', 'c-1-nakaz-jazdy-w-prawo'))
        ->assertOk()
        ->assertSee('meta property="og:image" content="'.$assetUrl.'"', false)
        ->assertSee('meta property="og:image:alt" content="Nakaz jazdy w prawo"', false)
        ->assertSee('meta property="og:image:width" content="1200"', false)
        ->assertSee('meta property="og:image:height" content="1200"', false)
        ->assertSee('"url":"'.$assetUrl.'"', false)
        ->assertSee('"caption":"Nakaz jazdy w prawo"', false)
        ->assertSee('rel="preload" href="'.$assetUrl.'" as="image"', false);
});
