<?php

namespace App\Support;

class PublicExplanationMemoryRuleExtractor
{
    private const MIN_VISIBLE_LENGTH = 20;

    private const MAX_VISIBLE_LENGTH = 600;

    public function extract(?string $body): ?string
    {
        $paragraphs = $this->paragraphs($body);
        $markerIndexes = array_keys(array_filter(
            $paragraphs,
            fn (string $paragraph): bool => $this->isMemoryRuleParagraph($paragraph),
        ));

        if (count($markerIndexes) !== 1) {
            return null;
        }

        $markerIndex = (int) $markerIndexes[0];
        $markerParagraph = $paragraphs[$markerIndex];
        $rule = $this->isRememberHeading($markerParagraph)
            ? $this->extractRememberHeadingRule($paragraphs[$markerIndex + 1] ?? null)
            : $this->extractRule($markerParagraph);

        if ($rule === null) {
            return null;
        }

        $rule = $this->normalizeRule($rule);

        if ($rule === null) {
            return null;
        }

        return '**Zasada do zapamiętania:** '.$rule;
    }

    /**
     * @return list<string>
     */
    protected function paragraphs(?string $body): array
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $body ?? ''));

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split("/\n\s*\n/u", $normalized) ?: []),
            fn (string $paragraph): bool => $paragraph !== '',
        ));
    }

    protected function extractRule(string $paragraph): ?string
    {
        $label = '(?:(?:prosta|najważniejsza|najwazniejsza|najprostsza)\s+)?zasada\s+do\s+zapamiętania(?:\s+(?:brzmi|według\s+aktualnych\s+przepisów))?\s*:';
        $alternativeLabel = '(?:zapamiętaj|reguła\s+do\s+zapamiętania)\s*:';

        foreach ([
            '/^\s*\*\*(?:'.$label.')\*\*\s*(?<rule>.+)$/iu',
            '/^\s*(?:'.$label.')\s*(?<rule>.+)$/iu',
            '/^\s*\*\*(?:'.$label.')\s*(?<rule>.+?)\*\*\s*$/iu',
            '/^.+?\b(?:'.$label.')\s*(?<rule>.+)$/iu',
            '/^\s*(?:\*\*prosta\s+zasada:\*\*|prosta\s+zasada:)\s*(?<rule>.+)$/iu',
            '/^\s*\*\*(?:'.$alternativeLabel.')\*\*\s*(?<rule>.+)$/iu',
            '/^\s*(?:'.$alternativeLabel.')\s*(?<rule>.+)$/iu',
            '/^\s*\*\*(?:'.$alternativeLabel.')\s*(?<rule>.+?)\*\*\s*$/iu',
        ] as $pattern) {
            if (preg_match($pattern, $paragraph, $matches) !== 1) {
                continue;
            }

            $rule = trim((string) ($matches['rule'] ?? ''));

            return $this->unwrapWholeBoldRule($rule);
        }

        return null;
    }

    protected function isMemoryRuleParagraph(string $paragraph): bool
    {
        if (preg_match('/zasada\s+do\s+zapamiętania/iu', $paragraph) === 1) {
            return true;
        }

        if ($this->isRememberHeading($paragraph)) {
            return true;
        }

        if (preg_match(
            '/^\s*\*\*(?:prosta\s+zasada|zapamiętaj|reguła\s+do\s+zapamiętania)\s*:\s*.+\*\*\s*$/iu',
            $paragraph,
        ) === 1) {
            return true;
        }

        return preg_match(
            '/^\s*(?:\*\*(?:prosta\s+zasada|zapamiętaj|reguła\s+do\s+zapamiętania):\*\*|(?:prosta\s+zasada|zapamiętaj|reguła\s+do\s+zapamiętania):)\s*\S/iu',
            $paragraph,
        ) === 1;
    }

    protected function isRememberHeading(string $paragraph): bool
    {
        return preg_match('/^#{1,4}\s*zapamiętaj\s*$/iu', $paragraph) === 1;
    }

    protected function extractRememberHeadingRule(?string $paragraph): ?string
    {
        if (! is_string($paragraph)) {
            return null;
        }

        return $this->unwrapWholeBoldRule(trim($paragraph));
    }

    protected function unwrapWholeBoldRule(string $rule): string
    {
        if (! str_starts_with($rule, '**')) {
            return $rule;
        }

        if (preg_match('/^\*\*(?<rule>.+?)\*\*(?:[[:punct:]]|\s|$)/us', $rule, $matches) !== 1) {
            return $rule;
        }

        $unwrappedRule = trim((string) ($matches['rule'] ?? ''));
        $visibleLength = mb_strlen(trim(str_replace(['**', '__'], '', $unwrappedRule)));

        return $visibleLength >= self::MIN_VISIBLE_LENGTH ? $unwrappedRule : $rule;
    }

    protected function normalizeRule(string $rule): ?string
    {
        $rule = preg_replace('/\s+/u', ' ', trim($rule)) ?? '';

        if ($rule === '') {
            return null;
        }

        if (substr_count($rule, '**') % 2 !== 0) {
            $repaired = preg_replace('/\*\*(?=[[:punct:]\s]*$)/u', '', $rule, 1);

            if (! is_string($repaired) || substr_count($repaired, '**') % 2 !== 0) {
                return null;
            }

            $rule = trim($repaired);
        }

        $visibleRule = str_replace(['**', '__'], '', $rule);
        $visibleLength = mb_strlen(trim($visibleRule));

        if ($visibleLength < self::MIN_VISIBLE_LENGTH || $visibleLength > self::MAX_VISIBLE_LENGTH) {
            return null;
        }

        return $rule;
    }
}
