<?php

use App\Models\QuestionSignLanguageAsset;
use App\Support\PjmSignLanguageFilenameParser;

it('parses PJM question and answer filenames', function (): void {
    $parser = new PjmSignLanguageFilenameParser;

    expect($parser->parse('pjm10793.mp4'))->toMatchArray([
        'external_id' => '10793',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
        'extension' => 'mp4',
    ]);

    expect($parser->parse('pjm10793a.mp4'))->toMatchArray([
        'external_id' => '10793',
        'asset_role' => QuestionSignLanguageAsset::ROLE_ANSWER_A,
    ]);

    expect($parser->parse('PJM10793C.WMV'))->toMatchArray([
        'external_id' => '10793',
        'asset_role' => QuestionSignLanguageAsset::ROLE_ANSWER_C,
        'extension' => 'wmv',
    ]);
});

it('rejects filenames outside the PJM convention', function (): void {
    $parser = new PjmSignLanguageFilenameParser;

    expect($parser->parse('pjm10793d.mp4'))->toBeNull();
    expect($parser->parse('10793.mp4'))->toBeNull();
    expect($parser->parse('pjmabc.mp4'))->toBeNull();
    expect($parser->parse('pjm10793.txt'))->toBeNull();
});
