<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

final class NewsroomRichTextHtmlRenderer
{
    /**
     * Render the validated TipTap subset used by newsroom body blocks.
     *
     * @param  array<string, mixed>  $document
     */
    public function render(array $document): HtmlString
    {
        $normalized = NewsroomBodyContract::normalizeRichTextDocument($document);

        return new HtmlString($this->renderNode($normalized));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderNode(array $node): string
    {
        $type = (string) ($node['type'] ?? '');
        $children = implode('', array_map(
            fn (array $child): string => $this->renderNode($child),
            array_values($node['content'] ?? []),
        ));

        return match ($type) {
            'doc' => $children,
            'paragraph' => '<p>'.$children.'</p>',
            'heading' => $this->renderHeading($node, $children),
            'bulletList' => '<ul>'.$children.'</ul>',
            'orderedList' => $this->renderOrderedList($node, $children),
            'listItem' => '<li>'.$children.'</li>',
            'text' => $this->renderText($node),
            'hardBreak' => '<br>',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderHeading(array $node, string $children): string
    {
        $level = (int) data_get($node, 'attrs.level', 2);
        $level = in_array($level, [2, 3], true) ? $level : 2;

        return "<h{$level}>{$children}</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderOrderedList(array $node, string $children): string
    {
        $start = max((int) data_get($node, 'attrs.start', 1), 1);
        $attribute = $start === 1 ? '' : ' start="'.e((string) $start).'"';

        return "<ol{$attribute}>{$children}</ol>";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderText(array $node): string
    {
        $html = e((string) ($node['text'] ?? ''));

        foreach (array_values($node['marks'] ?? []) as $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $html = match ($mark['type'] ?? null) {
                'bold' => '<strong>'.$html.'</strong>',
                'italic' => '<em>'.$html.'</em>',
                'link' => $this->renderLink($html, (array) ($mark['attrs'] ?? [])),
                default => $html,
            };
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function renderLink(string $html, array $attrs): string
    {
        $href = e((string) ($attrs['href'] ?? ''));
        $target = ($attrs['target'] ?? null) === '_blank' ? ' target="_blank"' : '';
        $rel = $target === '' ? '' : ' rel="noopener noreferrer"';

        return '<a href="'.$href.'"'.$target.$rel.'>'.$html.'</a>';
    }
}
