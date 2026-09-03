<?php

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;

test('traffic signs hub shows only publicly visible categories and signs', function () {
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Jan Kowalski',
        'slug' => 'jan-kowalski',
    ]);

    $visibleCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);

    $hiddenCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu',
        'slug' => 'znaki-zakazu',
    ]);

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $visibleCategory->getKey(),
        'code' => 'A-7',
        'slug' => 'a-7-ustap-pierwszenstwa',
        'name' => 'Ustąp pierwszeństwa',
        'intro_definition' => 'Widoczny znak testowy.',
    ]);

    TrafficSign::factory()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $hiddenCategory->getKey(),
        'code' => 'B-2',
        'slug' => 'b-2-zakaz-wjazdu',
        'name' => 'Zakaz wjazdu',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);

    $response = $this->get(route('traffic-signs.index'));

    $response
        ->assertOk()
        ->assertSeeText('Znaki drogowe')
        ->assertSeeText('Kurs')
        ->assertSeeText('Wykłady')
        ->assertSeeText('O serwisie')
        ->assertSeeText('Kategorie znaków')
        ->assertSeeText('Ostatnio aktualizowane')
        ->assertSeeText('Chcesz uczyć się skuteczniej?')
        ->assertSeeText('Znaki ostrzegawcze')
        ->assertSeeText('A-7 Ustąp pierwszeństwa')
        ->assertDontSeeText('B-2 Zakaz wjazdu');
});

test('published category page is available and draft category returns 404', function () {
    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);

    $draftCategory = TrafficSignCategory::factory()->create([
        'name' => 'Znaki tymczasowe',
        'slug' => 'znaki-tymczasowe',
    ]);

    $this->get(route('traffic-signs.categories.show', $category->slug))
        ->assertOk()
        ->assertSeeText('Znaki ostrzegawcze')
        ->assertSeeText('Powiązane porównania')
        ->assertSeeText('A-1 do A-4 - jak czytać znaki ostrzegające o zakrętach');

    $this->get(route('traffic-signs.categories.show', $draftCategory->slug))
        ->assertNotFound();
});

test('published traffic sign page shows sign, author and dates', function () {
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Anna Nowak',
        'slug' => 'anna-nowak',
    ]);

    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu',
        'slug' => 'znaki-zakazu',
    ]);

    $sign = TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'code' => 'B-20',
        'slug' => 'b-20-stop',
        'name' => 'STOP',
        'published_at' => now()->subDays(3),
        'updated_at' => now()->subDay(),
        'legal_reference_label' => 'Rozporządzenie o znakach',
        'legal_reference_url' => 'https://example.test/legal/b20',
    ]);

    $this->get(route('traffic-signs.show', $sign->slug))
        ->assertOk()
        ->assertSeeText('B-20 STOP')
        ->assertSeeText('Anna Nowak')
        ->assertSeeText('Rozporządzenie o znakach')
        ->assertSeeText($sign->updated_at->format('d.m.Y'));
});

test('supporting comparison page is available and linked from related signs', function () {
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Redakcja BRD',
        'slug' => 'redakcja-brd',
    ]);

    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu',
        'slug' => 'znaki-zakazu',
    ]);

    $a7Category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
    ]);

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $a7Category->getKey(),
        'code' => 'A-7',
        'slug' => 'a-7-ustap-pierwszenstwa',
        'name' => 'Ustąp pierwszeństwa',
    ]);

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'code' => 'B-20',
        'slug' => 'b-20-stop',
        'name' => 'STOP',
    ]);

    $this->get(route('traffic-signs.supporting.show', 'a-7-vs-b-20'))
        ->assertOk()
        ->assertSeeText('A-7 a B-20 STOP')
        ->assertSeeText('Najkrótsza różnica')
        ->assertSeeText('A-7 Ustąp pierwszeństwa')
        ->assertSeeText('B-20 STOP');

    $this->get(route('traffic-signs.show', 'a-7-ustap-pierwszenstwa'))
        ->assertOk()
        ->assertSeeText('Powiązane materiały')
        ->assertSeeText('A-7 a B-20 STOP - najważniejsze różnice dla kierowcy');
});

test('traffic sign page returns 404 for future sign or unpublished author', function () {
    $publishedAuthor = ContentAuthor::factory()->published()->create();
    $draftAuthor = ContentAuthor::factory()->create();
    $category = TrafficSignCategory::factory()->published()->create();

    $futureSign = TrafficSign::factory()->create([
        'content_author_id' => $publishedAuthor->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'is_published' => true,
        'published_at' => now()->addHour(),
    ]);

    $hiddenByAuthor = TrafficSign::factory()->published()->create([
        'content_author_id' => $draftAuthor->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
    ]);

    $this->get(route('traffic-signs.show', $futureSign->slug))
        ->assertNotFound();

    $this->get(route('traffic-signs.show', $hiddenByAuthor->slug))
        ->assertNotFound();
});

test('published author page lists only published signs and draft author returns 404', function () {
    $author = ContentAuthor::factory()->published()->create([
        'name' => 'Piotr Wiśniewski',
        'slug' => 'piotr-wisniewski',
    ]);

    $draftAuthor = ContentAuthor::factory()->create([
        'name' => 'Draft Author',
        'slug' => 'draft-author',
    ]);

    $category = TrafficSignCategory::factory()->published()->create();

    TrafficSign::factory()->published()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'code' => 'D-6',
        'slug' => 'd-6-przejscie-dla-pieszych',
        'name' => 'Przejście dla pieszych',
    ]);

    TrafficSign::factory()->create([
        'content_author_id' => $author->getKey(),
        'traffic_sign_category_id' => $category->getKey(),
        'code' => 'D-7',
        'slug' => 'd-7-droga-ekspresowa',
        'name' => 'Droga ekspresowa',
    ]);

    $this->get(route('content-authors.show', $author->slug))
        ->assertOk()
        ->assertSeeText('Piotr Wiśniewski')
        ->assertSeeText('D-6 Przejście dla pieszych')
        ->assertDontSeeText('D-7 Droga ekspresowa');

    $this->get(route('content-authors.show', $draftAuthor->slug))
        ->assertNotFound();
});
