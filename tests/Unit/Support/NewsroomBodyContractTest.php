<?php

use App\Support\NewsroomBodyContract;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Tests\TestCase;

uses(TestCase::class);

function validNewsroomRichText(): array
{
    return [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2, 'class' => 'should-be-dropped'],
                'content' => [
                    ['type' => 'text', 'text' => 'Najważniejsza zmiana'],
                ],
            ],
            [
                'type' => 'paragraph',
                'attrs' => ['style' => 'position:fixed'],
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Sprawdź źródło',
                        'marks' => [
                            [
                                'type' => 'link',
                                'attrs' => [
                                    'href' => 'https://example.com/source',
                                    'target' => '_blank',
                                    'rel' => null,
                                    'style' => 'position:fixed',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

test('body contract fixes the v1 editor and serialization choices', function () {
    expect(NewsroomBodyContract::CURRENT_SCHEMA_VERSION)->toBe(1)
        ->and(NewsroomBodyContract::EDITOR_COMPONENT)->toBe('Filament\\Forms\\Components\\Builder')
        ->and(NewsroomBodyContract::RICH_TEXT_COMPONENT)->toBe('Filament\\Forms\\Components\\RichEditor')
        ->and(NewsroomBodyContract::RICH_TEXT_FORMAT)->toBe('tiptap-json')
        ->and(NewsroomBodyContract::richTextToolbarButtons())->toBe([
            ['bold', 'italic', 'link'],
            ['h2', 'h3'],
            ['bulletList', 'orderedList'],
            ['undo', 'redo'],
        ])
        ->and(NewsroomBodyContract::enabledBlockTypes())->toBe([
            'rich_text',
            'image',
            'quote',
            'table',
            'context',
            'related_article',
            'legal_reference',
            'question_group',
            'traffic_sign_group',
            'product_cta',
        ])
        ->and(NewsroomBodyContract::disabledBlockTypes())->toBe(['embed']);
});

test('every enabled v1 block has an executable payload schema', function () {
    expect(array_keys(NewsroomBodyContract::blockSchemas()))
        ->toBe(NewsroomBodyContract::enabledBlockTypes())
        ->not->toContain('embed');
});

test('normalization preserves the canonical type data shape and strips rich text presentation attributes', function () {
    $normalized = NewsroomBodyContract::normalize([
        '018f7d4a-0d15-7f4a-9f19-2b6adcc7b8cb' => [
            'type' => 'rich_text',
            'data' => ['content' => validNewsroomRichText()],
        ],
        [
            'type' => 'context',
            'data' => [
                'variant' => 'uwaga',
                'title' => 'Uwaga',
                'text' => 'To jest kontrolowany callout.',
            ],
        ],
    ]);

    expect($normalized[0]['key'])->toBe('018f7d4a-0d15-7f4a-9f19-2b6adcc7b8cb')
        ->and($normalized[0]['type'])->toBe('rich_text')
        ->and($normalized[0]['data']['content']['content'][0]['attrs'])->toBe(['level' => 2])
        ->and($normalized[0]['data']['content']['content'][1])->not->toHaveKey('attrs')
        ->and($normalized[0]['data']['content']['content'][1]['content'][0]['marks'][0]['attrs'])
        ->toBe([
            'href' => 'https://example.com/source',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ])
        ->and($normalized[1]['data'])->toBe([
            'variant' => 'uwaga',
            'title' => 'Uwaga',
            'text' => 'To jest kontrolowany callout.',
        ]);
});

test('canonical stored keys survive a second normalization pass', function () {
    $first = NewsroomBodyContract::normalize([
        '018f7d4a-0d15-7f4a-9f19-2b6adcc7b8cb' => [
            'type' => 'context',
            'data' => [
                'variant' => 'metodologia',
                'text' => 'Opis metodologii.',
            ],
        ],
    ]);

    $second = NewsroomBodyContract::normalize($first);

    expect($second)->toBe($first);
});

test('rich text script-looking text stays text and renders escaped instead of executable html', function () {
    $document = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => '<script>alert("xss")</script>',
                    ],
                ],
            ],
        ],
    ];

    $normalized = NewsroomBodyContract::normalizeRichTextDocument($document);
    $html = RichContentRenderer::make($normalized)->toHtml();

    expect($html)
        ->not->toContain('<script>')
        ->not->toContain('alert("xss")</script>')
        ->toContain('&lt;script&gt;');
});

test('rich text rejects executable and unsupported structures', function (array $document) {
    NewsroomBodyContract::normalizeRichTextDocument($document);
})->with([
    'javascript href' => [[
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'Kliknij',
                'marks' => [[
                    'type' => 'link',
                    'attrs' => ['href' => 'javascript:alert(1)'],
                ]],
            ]],
        ]],
    ]],
    'raw html node' => [[
        'type' => 'doc',
        'content' => [[
            'type' => 'html',
            'attrs' => ['html' => '<img src=x onerror=alert(1)>'],
        ]],
    ]],
    'h1 heading' => [[
        'type' => 'doc',
        'content' => [[
            'type' => 'heading',
            'attrs' => ['level' => 1],
            'content' => [['type' => 'text', 'text' => 'Nieprawidłowy H1']],
        ]],
    ]],
    'style mark' => [[
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'Tekst',
                'marks' => [['type' => 'textStyle', 'attrs' => ['style' => 'position:fixed']]],
            ]],
        ]],
    ]],
])->throws(InvalidArgumentException::class);

test('canonical body block keys must stay unique and cannot conflict with Builder item ids', function () {
    NewsroomBodyContract::normalize([
        [
            'key' => 'same-block-key',
            'type' => 'context',
            'data' => ['variant' => 'uwaga', 'text' => 'Pierwszy'],
        ],
        [
            'key' => 'same-block-key',
            'type' => 'context',
            'data' => ['variant' => 'uwaga', 'text' => 'Drugi'],
        ],
    ]);
})->throws(InvalidArgumentException::class, 'Duplicate newsroom body block key');

test('builder item key cannot disagree with an explicit canonical key', function () {
    NewsroomBodyContract::normalize([
        'builder-item-key' => [
            'key' => 'different-key',
            'type' => 'context',
            'data' => ['variant' => 'uwaga', 'text' => 'Treść'],
        ],
    ]);
})->throws(InvalidArgumentException::class, 'key conflicts with its Builder item key');

test('unknown and embed blocks fail closed', function (string $type) {
    NewsroomBodyContract::normalize([
        ['type' => $type, 'data' => []],
    ]);
})->with(['future_block', 'embed'])->throws(InvalidArgumentException::class);

test('schema version changes fail closed until compatibility is implemented', function () {
    NewsroomBodyContract::normalize([], 2);
})->throws(InvalidArgumentException::class, 'Unsupported newsroom body schema version: 2.');

test('image payload uses a storage relative path and normalized focal point pair', function () {
    $normalized = NewsroomBodyContract::normalize([
        [
            'type' => 'image',
            'data' => [
                'path' => 'newsroom/articles/immutable-image.webp',
                'alt' => 'Samochód egzaminacyjny przed WORD',
                'caption' => 'Przykładowy podpis',
                'credit' => 'Redakcja',
                'width' => 1600,
                'height' => 900,
                'focal_x' => 0.5,
                'focal_y' => 0.4,
            ],
        ],
    ]);

    expect($normalized[0]['data']['path'])->toBe('newsroom/articles/immutable-image.webp')
        ->and($normalized[0]['data']['focal_x'])->toBe(0.5)
        ->and($normalized[0]['data']['focal_y'])->toBe(0.4);
});

test('image payload rejects urls traversal and incomplete focal points', function (array $data) {
    NewsroomBodyContract::normalize([
        ['type' => 'image', 'data' => $data],
    ]);
})->with([
    'absolute url' => [[
        'path' => 'https://cdn.example.com/image.webp',
        'alt' => 'Alt',
    ]],
    'traversal' => [[
        'path' => '../private/image.webp',
        'alt' => 'Alt',
    ]],
    'one focal coordinate' => [[
        'path' => 'newsroom/image.webp',
        'alt' => 'Alt',
        'focal_x' => 0.5,
    ]],
])->throws(InvalidArgumentException::class);

test('domain blocks accept ids only and reject duplicates', function () {
    $normalized = NewsroomBodyContract::normalize([
        ['type' => 'related_article', 'data' => ['article_id' => 7]],
        ['type' => 'legal_reference', 'data' => ['legal_unit_id' => 11]],
        ['type' => 'question_group', 'data' => ['question_ids' => [1, 2, 3]]],
        ['type' => 'traffic_sign_group', 'data' => ['traffic_sign_ids' => [4, 5]]],
        ['type' => 'product_cta', 'data' => ['kind' => 'related_questions']],
    ]);

    expect($normalized)->toHaveCount(5)
        ->and($normalized[4]['data'])->toBe(['kind' => 'related_questions']);

    NewsroomBodyContract::normalize([
        ['type' => 'question_group', 'data' => ['question_ids' => [1, 1]]],
    ]);
})->throws(InvalidArgumentException::class);

test('quote payload keeps plain editorial text and only safe source urls', function () {
    $normalized = NewsroomBodyContract::normalize([
        [
            'type' => 'quote',
            'data' => [
                'text' => 'Egzamin rozpoczyna się o godzinie wskazanej w harmonogramie.',
                'attribution' => 'Źródło oficjalne',
                'source_url' => 'https://example.com/source',
            ],
        ],
    ]);

    expect($normalized[0]['data'])->toBe([
        'text' => 'Egzamin rozpoczyna się o godzinie wskazanej w harmonogramie.',
        'attribution' => 'Źródło oficjalne',
        'source_url' => 'https://example.com/source',
    ]);

    NewsroomBodyContract::normalize([
        [
            'type' => 'quote',
            'data' => [
                'text' => 'Treść',
                'attribution' => 'Źródło',
                'source_url' => 'javascript:alert(1)',
            ],
        ],
    ]);
})->throws(InvalidArgumentException::class);

test('rich text rejects unsupported link targets even when href is safe', function () {
    NewsroomBodyContract::normalizeRichTextDocument([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'Link',
                'marks' => [[
                    'type' => 'link',
                    'attrs' => [
                        'href' => 'https://example.com',
                        'target' => '_parent',
                    ],
                ]],
            ]],
        ]],
    ]);
})->throws(InvalidArgumentException::class, 'target is not allowed');

test('table payload requires semantic headers and matching row widths', function () {
    $normalized = NewsroomBodyContract::normalize([
        [
            'type' => 'table',
            'data' => [
                'caption' => 'Opłaty',
                'headers' => ['Pozycja', 'Kwota'],
                'rows' => [
                    ['Egzamin', '100 zł'],
                    ['Powtórka', '100 zł'],
                ],
            ],
        ],
    ]);

    expect($normalized[0]['data']['headers'])->toBe(['Pozycja', 'Kwota'])
        ->and($normalized[0]['data']['rows'])->toHaveCount(2);

    NewsroomBodyContract::normalize([
        [
            'type' => 'table',
            'data' => [
                'headers' => ['Pozycja', 'Kwota'],
                'rows' => [['Tylko jedna komórka']],
            ],
        ],
    ]);
})->throws(InvalidArgumentException::class);

test('block payloads reject hidden unsupported fields instead of persisting them', function () {
    NewsroomBodyContract::normalize([
        [
            'type' => 'context',
            'data' => [
                'variant' => 'uwaga',
                'text' => 'Treść',
                'html' => '<script>alert(1)</script>',
            ],
        ],
    ]);
})->throws(InvalidArgumentException::class);
