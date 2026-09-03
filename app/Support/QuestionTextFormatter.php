<?php

namespace App\Support;

class QuestionTextFormatter
{
    public function plainText(?string $value): string
    {
        $normalized = $this->normalizeLineEndings($value ?? '');

        if ($normalized === '') {
            return '';
        }

        $withoutTags = strip_tags($normalized);
        $withoutMarkers = $this->stripInlineMarkers($withoutTags);

        return preg_replace('/\s+/u', ' ', trim($withoutMarkers)) ?? '';
    }

    public function inlineHtml(?string $value): string
    {
        $normalized = trim($this->normalizeLineEndings($value ?? ''));

        if ($normalized === '') {
            return '';
        }

        return str_replace("\n", '<br>', $this->applyInlineMarkup(e($normalized)));
    }

    public function richHtml(?string $value): string
    {
        $normalized = trim($this->normalizeLineEndings($value ?? ''));

        if ($normalized === '') {
            return '';
        }

        $escaped = e($normalized);
        $withInlineMarkup = $this->applyInlineMarkup($escaped);
        $paragraphs = preg_split("/\n{2,}/", $withInlineMarkup) ?: [];

        return collect($paragraphs)
            ->filter(fn (string $paragraph): bool => trim($paragraph) !== '')
            ->map(fn (string $paragraph): string => '<p>'.str_replace("\n", '<br>', $paragraph).'</p>')
            ->implode('');
    }

    protected function normalizeLineEndings(string $value): string
    {
        return preg_replace("/\r\n?/", "\n", $value) ?? $value;
    }

    protected function applyInlineMarkup(string $value): string
    {
        $withColors = preg_replace(
            '/\[(?:green|zielony)\]([\s\S]+?)\[\/(?:green|zielony)\]/iu',
            '<span style="color:#163222;">$1</span>',
            $value,
        ) ?? $value;

        $withColors = preg_replace(
            '/\[(?:red|czerwony)\]([\s\S]+?)\[\/(?:red|czerwony)\]/iu',
            '<span style="color:#612d2d;">$1</span>',
            $withColors,
        ) ?? $withColors;

        $withBold = preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $withColors) ?? $withColors;

        return preg_replace('/__(.+?)__/us', '<strong>$1</strong>', $withBold) ?? $withBold;
    }

    protected function stripInlineMarkers(string $value): string
    {
        $withoutColors = preg_replace(
            '/\[(?:green|zielony|red|czerwony)\]([\s\S]+?)\[\/(?:green|zielony|red|czerwony)\]/iu',
            '$1',
            $value,
        ) ?? $value;

        $withoutBold = preg_replace('/\*\*(.+?)\*\*/us', '$1', $withoutColors) ?? $withoutColors;

        return preg_replace('/__(.+?)__/us', '$1', $withoutBold) ?? $withoutBold;
    }
}
