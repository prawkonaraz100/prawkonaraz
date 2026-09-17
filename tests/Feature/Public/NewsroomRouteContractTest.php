<?php

use App\Support\NewsroomRouteContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function newsroomRouteNameForPath(string $path): ?string
{
    try {
        return app('router')
            ->getRoutes()
            ->match(Request::create($path, 'GET'))
            ->getName();
    } catch (NotFoundHttpException) {
        return null;
    }
}

test('newsroom top level placeholders keep existing route names and are explicitly noindex', function () {
    $this->get(route('public.news'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');

    $this->get(route('public.guides'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, follow');

    expect(route('public.news', absolute: false))->toBe('/aktualnosci')
        ->and(route('public.guides', absolute: false))->toBe('/poradniki');
});

test('unrelated marketing placeholders do not inherit newsroom noindex header', function () {
    $response = $this->get(route('public.course'));

    $response->assertOk();

    expect($response->headers->has('X-Robots-Tag'))->toBeFalse();
});

test('newsroom route namespaces match before article catch all', function () {
    expect(route('public.news.feed', absolute: false))->toBe('/aktualnosci/feed.xml')
        ->and(route('public.news.categories.show', ['categorySlug' => 'przepisy'], false))
        ->toBe('/aktualnosci/kategoria/przepisy')
        ->and(route('public.news.topics.show', ['topicSlug' => 'pkk'], false))
        ->toBe('/aktualnosci/temat/pkk')
        ->and(route('public.news.show', ['articleSlug' => 'nowe-zasady'], false))
        ->toBe('/aktualnosci/nowe-zasady')
        ->and(route('public.guides.show', ['articleSlug' => 'jak-zalozyc-pkk'], false))
        ->toBe('/poradniki/jak-zalozyc-pkk');

    expect(newsroomRouteNameForPath('/aktualnosci/feed.xml'))->toBe('public.news.feed')
        ->and(newsroomRouteNameForPath('/aktualnosci/kategoria/przepisy'))->toBe('public.news.categories.show')
        ->and(newsroomRouteNameForPath('/aktualnosci/temat/pkk'))->toBe('public.news.topics.show')
        ->and(newsroomRouteNameForPath('/aktualnosci/nowe-zasady'))->toBe('public.news.show')
        ->and(newsroomRouteNameForPath('/poradniki/jak-zalozyc-pkk'))->toBe('public.guides.show');
});

test('newsroom article catch all excludes reserved namespace segments and invalid slugs', function () {
    expect(newsroomRouteNameForPath('/aktualnosci/kategoria'))->toBeNull()
        ->and(newsroomRouteNameForPath('/aktualnosci/temat'))->toBeNull()
        ->and(newsroomRouteNameForPath('/aktualnosci/Feed.XML'))->toBeNull()
        ->and(newsroomRouteNameForPath('/aktualnosci/NOWE-ZASADY'))->toBeNull()
        ->and(newsroomRouteNameForPath('/aktualnosci/kategoria-x'))->toBe('public.news.show')
        ->and(newsroomRouteNameForPath('/poradniki/kategoria'))->toBe('public.guides.show');
});

test('unimplemented feed category and topic routes stay 404 while unknown detail slugs fail closed', function () {
    $this->get('/aktualnosci/feed.xml')->assertNotFound();
    $this->get('/aktualnosci/kategoria/przepisy')->assertNotFound();
    $this->get('/aktualnosci/temat/pkk')->assertNotFound();
    $this->get('/aktualnosci/nowe-zasady')->assertNotFound();
    $this->get('/poradniki/jak-zalozyc-pkk')->assertNotFound();
});

test('route family resolver maps article types to one canonical family', function () {
    foreach (['news', 'explainer', 'analysis', 'report'] as $type) {
        expect(NewsroomRouteContract::familyForType($type))->toBe(NewsroomRouteContract::FAMILY_NEWSROOM)
            ->and(NewsroomRouteContract::canonicalPath($type, 'zmiany-egzaminu'))
            ->toBe('/aktualnosci/zmiany-egzaminu');
    }

    expect(NewsroomRouteContract::familyForType('guide'))->toBe(NewsroomRouteContract::FAMILY_GUIDES)
        ->and(NewsroomRouteContract::canonicalPath('guide', 'jak-zalozyc-pkk'))
        ->toBe('/poradniki/jak-zalozyc-pkk');
});

test('route family resolver rejects reserved or unsupported canonical paths', function () {
    expect(fn () => NewsroomRouteContract::canonicalPath('news', 'kategoria'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => NewsroomRouteContract::canonicalPath('analysis', 'temat'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => NewsroomRouteContract::canonicalPath('news', 'Niepoprawny-Slug'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => NewsroomRouteContract::canonicalPath('video', 'material'))
        ->toThrow(InvalidArgumentException::class);

    expect(NewsroomRouteContract::canonicalPath('guide', 'kategoria'))
        ->toBe('/poradniki/kategoria');
});

test('published article type cannot cross public route families', function () {
    $publishedAt = new DateTimeImmutable('2026-09-16T00:00:00+02:00');

    NewsroomRouteContract::assertTypeTransitionAllowed('news', 'analysis', $publishedAt);
    NewsroomRouteContract::assertTypeTransitionAllowed('news', 'guide', null);

    expect(fn () => NewsroomRouteContract::assertTypeTransitionAllowed('news', 'video', null))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => NewsroomRouteContract::assertTypeTransitionAllowed('news', 'guide', $publishedAt))
        ->toThrow(DomainException::class)
        ->and(fn () => NewsroomRouteContract::assertTypeTransitionAllowed('guide', 'report', $publishedAt))
        ->toThrow(DomainException::class);
});
