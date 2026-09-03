<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Support\PolishWarningSignCatalog;

$catalog = new PolishWarningSignCatalog;
$baseDir = dirname(__DIR__).'/public/traffic-signs/placeholders/warnings';
$ogDir = dirname(__DIR__).'/public/traffic-signs/placeholders/og/warnings';

if (! is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

if (! is_dir($ogDir)) {
    mkdir($ogDir, 0777, true);
}

foreach ($catalog->all() as $sign) {
    $mainPath = $baseDir.'/'.$sign['slug'].'.svg';
    $ogPath = $ogDir.'/'.$sign['slug'].'.svg';

    file_put_contents($mainPath, svgMain($sign['code'], $sign['name'], $sign['primary_query']));
    file_put_contents($ogPath, svgOg($sign['code'], $sign['name'], $sign['primary_query']));
}

function svgMain(string $code, string $name, string $query): string
{
    $title = e($code.' '.$name);
    $codeEsc = e($code);
    $nameEsc = e($name);
    $theme = familyTheme($code);
    $queryHint = e(shortQueryHint($query));

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="320" viewBox="0 0 320 320" role="img" aria-labelledby="title desc">
  <title id="title">{$title}</title>
  <desc id="desc">Grafika referencyjna dla znaku {$title}</desc>
  <rect width="320" height="320" fill="#ffffff"/>
  <rect x="18" y="18" width="284" height="284" rx="22" fill="{$theme['surface']}" stroke="{$theme['accent']}" stroke-width="10"/>
  <polygon points="160,48 258,232 62,232" fill="#ffffff" stroke="#d32f2f" stroke-width="14"/>
  <polygon points="160,78 231,215 89,215" fill="#fef2f2"/>
  <text x="160" y="174" text-anchor="middle" font-size="42" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="{$theme['title']}">{$codeEsc}</text>
  <text x="160" y="258" text-anchor="middle" font-size="15" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="{$theme['eyebrow']}">Znaki ostrzegawcze</text>
  <foreignObject x="40" y="268" width="240" height="34">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.25; color: #475569; text-align: center;">{$queryHint}</div>
  </foreignObject>
  <foreignObject x="48" y="226" width="224" height="36">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.2; color: #0f172a; text-align: center; font-weight: 600;">{$nameEsc}</div>
  </foreignObject>
</svg>
SVG;
}

function svgOg(string $code, string $name, string $query): string
{
    $title = e($code.' '.$name);
    $codeEsc = e($code);
    $nameEsc = e($name);
    $queryHint = e(shortQueryHint($query));
    $theme = familyTheme($code);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-labelledby="title desc">
  <title id="title">{$title}</title>
  <desc id="desc">Grafika Open Graph dla znaku {$title}</desc>
  <rect width="1200" height="630" fill="{$theme['background']}"/>
  <rect x="48" y="48" width="1104" height="534" rx="30" fill="#ffffff" stroke="{$theme['accent']}" stroke-width="16"/>
  <polygon points="280,110 495,472 65,472" fill="#ffffff" stroke="#d32f2f" stroke-width="28"/>
  <polygon points="280,162 452,448 108,448" fill="#fef2f2"/>
  <text x="280" y="365" text-anchor="middle" font-size="118" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="{$theme['title']}">{$codeEsc}</text>
  <text x="560" y="154" font-size="30" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="{$theme['eyebrow']}">Znaki ostrzegawcze</text>
  <foreignObject x="560" y="206" width="500" height="160">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, Helvetica, sans-serif; font-size: 52px; line-height: 1.14; color: #0f172a; font-weight: 700;">{$nameEsc}</div>
  </foreignObject>
  <foreignObject x="560" y="404" width="500" height="96">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, Helvetica, sans-serif; font-size: 24px; line-height: 1.35; color: #475569;">{$queryHint}</div>
  </foreignObject>
  <text x="560" y="542" font-size="24" font-family="Arial, Helvetica, sans-serif" fill="#64748b">Asset wygenerowany do publicznego rolloutu warning clusteru</text>
</svg>
SVG;
}

/**
 * @return array{accent: string, background: string, surface: string, title: string, eyebrow: string}
 */
function familyTheme(string $code): array
{
    $number = warningNumber($code);

    return match (true) {
        $number <= 8 => [
            'accent' => '#1d4ed8',
            'background' => '#eff6ff',
            'surface' => '#f8fbff',
            'title' => '#0f172a',
            'eyebrow' => '#1d4ed8',
        ],
        $number <= 18 => [
            'accent' => '#0f766e',
            'background' => '#ecfeff',
            'surface' => '#f5ffff',
            'title' => '#0f172a',
            'eyebrow' => '#0f766e',
        ],
        $number <= 28 => [
            'accent' => '#7c3aed',
            'background' => '#f5f3ff',
            'surface' => '#fbfaff',
            'title' => '#0f172a',
            'eyebrow' => '#6d28d9',
        ],
        default => [
            'accent' => '#ea580c',
            'background' => '#fff7ed',
            'surface' => '#fffaf5',
            'title' => '#0f172a',
            'eyebrow' => '#c2410c',
        ],
    };
}

function warningNumber(string $code): int
{
    if (preg_match('/A-(\d+)/', $code, $matches) !== 1) {
        return 0;
    }

    return (int) $matches[1];
}

function shortQueryHint(string $query): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $query) ?? $query);

    return mb_strtoupper($clean, 'UTF-8');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
