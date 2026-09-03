<?php

namespace App\Support;

use App\Models\QuestionSignLanguageAsset;

class PjmSignLanguageFilenameParser
{
    /**
     * @return array{external_id: string, asset_role: string, extension: string}|null
     */
    public function parse(string $filename): ?array
    {
        if (! preg_match('/^pjm(?<external_id>\d+)(?<answer>[abc])?\.(?<extension>mp4|wmv|m4v|mov)$/i', $filename, $matches)) {
            return null;
        }

        $answer = strtolower($matches['answer'] ?? '');

        return [
            'external_id' => $matches['external_id'],
            'asset_role' => match ($answer) {
                'a' => QuestionSignLanguageAsset::ROLE_ANSWER_A,
                'b' => QuestionSignLanguageAsset::ROLE_ANSWER_B,
                'c' => QuestionSignLanguageAsset::ROLE_ANSWER_C,
                default => QuestionSignLanguageAsset::ROLE_QUESTION,
            },
            'extension' => strtolower($matches['extension']),
        ];
    }
}
