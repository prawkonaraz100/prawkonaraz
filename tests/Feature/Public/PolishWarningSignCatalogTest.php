<?php

use App\Support\PolishWarningSignCatalog;

test('polish warning sign catalog contains the full official warning sign inventory', function () {
    $catalog = app(PolishWarningSignCatalog::class)->all();

    expect($catalog)->toHaveCount(42);
    expect(collect($catalog)->pluck('code')->all())->toContain(
        'A-1',
        'A-6a',
        'A-11a',
        'A-18b',
        'A-30',
        'A-34',
    );

    expect(collect($catalog)->pluck('slug')->all())->toContain(
        'a-7-ustap-pierwszenstwa',
        'a-17-dzieci',
        'a-29-sygnaly-swietlne',
        'a-34-wypadek-drogowy',
    );
});
