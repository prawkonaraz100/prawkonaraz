<?php

use App\Support\NewsroomBodyContract;
use App\Support\NewsroomBodyEditorAdapter;

test('body editor adapter converts builder table rows and form scalar values into canonical schema', function () {
    $canonical = NewsroomBodyEditorAdapter::toCanonical([
        'stable-block-key' => [
            'type' => 'table',
            'data' => [
                'caption' => 'Opłaty',
                'headers' => ['Pozycja', 'Kwota'],
                'rows' => [
                    'row-a' => ['cells' => ['Egzamin', '100 zł']],
                    'row-b' => ['cells' => ['Powtórka', '100 zł']],
                ],
            ],
        ],
        [
            'type' => 'image',
            'data' => [
                'path' => 'newsroom/articles/example.webp',
                'alt' => 'Przykład',
                'width' => '1600',
                'height' => '900',
                'focal_x' => '0.5',
                'focal_y' => '0.25',
            ],
        ],
        [
            'type' => 'question_group',
            'data' => [
                'question_ids' => ['11', '12'],
            ],
        ],
    ]);

    expect($canonical[0]['key'])->toBe('stable-block-key')
        ->and($canonical[0]['data']['rows'])->toBe([
            ['Egzamin', '100 zł'],
            ['Powtórka', '100 zł'],
        ])
        ->and($canonical[1]['data']['width'])->toBe(1600)
        ->and($canonical[1]['data']['height'])->toBe(900)
        ->and($canonical[1]['data']['focal_x'])->toBe(0.5)
        ->and($canonical[1]['data']['focal_y'])->toBe(0.25)
        ->and($canonical[2]['data']['question_ids'])->toBe([11, 12]);
});

test('body editor adapter hydrates canonical table rows and preserves stable block keys', function () {
    $builder = NewsroomBodyEditorAdapter::toBuilder([
        [
            'key' => 'table-key',
            'type' => 'table',
            'data' => [
                'caption' => null,
                'headers' => ['A', 'B'],
                'rows' => [
                    ['1', '2'],
                    ['3', '4'],
                ],
            ],
        ],
    ]);

    expect(array_keys($builder))->toBe([0])
        ->and($builder[0]['key'])->toBe('table-key')
        ->and($builder[0]['type'])->toBe('table')
        ->and($builder[0]['data']['rows'])->toBe([
            ['cells' => ['1', '2']],
            ['cells' => ['3', '4']],
        ]);

    $roundTrip = NewsroomBodyEditorAdapter::toCanonical($builder);

    expect($roundTrip[0]['key'])->toBe('table-key');
});

test('body editor adapter rejects malformed state before persistence', function (mixed $blocks) {
    NewsroomBodyEditorAdapter::toCanonical($blocks);
})->with([
    'non-array body' => ['invalid'],
    'unknown block' => [[
        ['type' => 'future_block', 'data' => []],
    ]],
    'disabled embed provider' => [[
        [
            'type' => 'embed',
            'data' => [
                'provider' => 'unsafe-provider',
                'url' => 'https://attacker.example/embed',
            ],
        ],
    ]],
    'invalid table row' => [[
        [
            'type' => 'table',
            'data' => [
                'headers' => ['A', 'B'],
                'rows' => [
                    ['cells' => ['only-one']],
                ],
            ],
        ],
    ]],
])->throws(InvalidArgumentException::class);

test('article data normalization always writes the current body schema version', function () {
    $data = NewsroomBodyEditorAdapter::normalizeArticleData([
        'body_blocks' => [],
    ]);

    expect($data['body_blocks'])->toBe([])
        ->and($data['body_schema_version'])->toBe(NewsroomBodyContract::CURRENT_SCHEMA_VERSION);
});
