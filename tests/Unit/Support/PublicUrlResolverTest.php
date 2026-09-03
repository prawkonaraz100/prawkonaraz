<?php

use App\Support\PublicUrlResolver;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'app.url' => 'http://localhost:8000',
    ]);

    URL::forceRootUrl('https://cold-items-fold.loca.lt');
});

afterEach(function (): void {
    URL::forceRootUrl(null);
});

test('it rewrites local app urls to the current public origin', function () {
    $resolver = app(PublicUrlResolver::class);

    expect($resolver->normalize('http://localhost:8000/storage-bulk/media/questions/example.jpg'))
        ->toBe('http://cold-items-fold.loca.lt/storage-bulk/media/questions/example.jpg');
});

test('it keeps dedicated local media server urls on their own origin', function () {
    $resolver = app(PublicUrlResolver::class);

    expect($resolver->normalize('http://127.0.0.1:8081/media/questions/example.jpg'))
        ->toBe('http://127.0.0.1:8081/media/questions/example.jpg');
});
