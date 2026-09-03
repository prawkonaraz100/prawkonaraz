<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Support\PolishProhibitionSignCatalog;

$catalog = new PolishProhibitionSignCatalog();
$baseDir = dirname(__DIR__).'/public/traffic-signs/placeholders';
$ogDir = $baseDir.'/og';

if (! is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

if (! is_dir($ogDir)) {
    mkdir($ogDir, 0777, true);
}

/**
 * @param  list<array{code: string, slug: string, name: string, primary_query: string}>  $signs
 */
foreach ($catalog->all() as $sign) {
    $key = strtolower(str_replace('-', '', $sign['code']));

    $mainPath = $baseDir.'/'.$key.'.svg';
    $ogPath = $ogDir.'/'.$key.'.svg';

    if (! is_file($mainPath)) {
        file_put_contents($mainPath, svgMain($sign['code'], $sign['name']));
    }

    if (! is_file($ogPath)) {
        file_put_contents($ogPath, svgOg($sign['code'], $sign['name']));
    }
}

function svgMain(string $code, string $name): string
{
    $title = e($code.' '.$name);
    $codeEsc = e($code);
    $nameEsc = e($name);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="320" viewBox="0 0 320 320" role="img" aria-labelledby="title desc">
  <title id="title">{$title}</title>
  <desc id="desc">Placeholder asset dla znaku {$title}</desc>
  <rect width="320" height="320" fill="#ffffff"/>
  <rect x="20" y="20" width="280" height="280" rx="18" fill="#fff7f7" stroke="#c62828" stroke-width="14"/>
  <text x="160" y="128" text-anchor="middle" font-size="54" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="#b91c1c">{$codeEsc}</text>
  <text x="160" y="178" text-anchor="middle" font-size="15" font-family="Arial, Helvetica, sans-serif" fill="#0f172a">Znaki zakazu</text>
  <foreignObject x="44" y="196" width="232" height="70">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.35; color: #334155; text-align: center;">{$nameEsc}</div>
  </foreignObject>
</svg>
SVG;
}

function svgOg(string $code, string $name): string
{
    $title = e($code.' '.$name);
    $codeEsc = e($code);
    $nameEsc = e($name);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-labelledby="title desc">
  <title id="title">{$title}</title>
  <desc id="desc">Open Graph placeholder dla znaku {$title}</desc>
  <rect width="1200" height="630" fill="#fffaf9"/>
  <rect x="60" y="60" width="1080" height="510" rx="28" fill="#ffffff" stroke="#dc2626" stroke-width="18"/>
  <text x="120" y="170" font-size="28" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="#0d47a1">Znaki zakazu</text>
  <text x="120" y="320" font-size="120" font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="#b91c1c">{$codeEsc}</text>
  <foreignObject x="120" y="360" width="900" height="130">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, Helvetica, sans-serif; font-size: 44px; line-height: 1.2; color: #0f172a;">{$nameEsc}</div>
  </foreignObject>
  <text x="120" y="530" font-size="24" font-family="Arial, Helvetica, sans-serif" fill="#475569">Szkic assetu do finalnej publikacji i review redakcyjnego</text>
</svg>
SVG;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
