<?php

// Code-native, high-resolution PNG icons for email clients (no SVG support required).
$directory = dirname(__DIR__).'/public/images/email/verification';
if (! is_dir($directory)) {
    mkdir($directory, 0755, true);
}
$scale = 4;
foreach (['brand-shield', 'graduation', 'envelope', 'clock', 'shield', 'link'] as $name) {
    $image = imagecreatetruecolor(48 * $scale, 48 * $scale);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagealphablending($image, true);
    $navy = imagecolorallocate($image, 20, 52, 98);
    $amber = imagecolorallocate($image, 255, 191, 62);
    $pale = imagecolorallocate($image, 255, 231, 180);
    $white = imagecolorallocate($image, 255, 255, 255);
    $muted = imagecolorallocate($image, 133, 146, 170);
    $background = imagecolorallocate($image, 231, 236, 243);
    $line = function (float $x1, float $y1, float $x2, float $y2, int $color, float $width = 2) use ($image, $scale): void {
        imagesetthickness($image, (int) ($width * $scale));
        imageline($image, (int) ($x1 * $scale), (int) ($y1 * $scale), (int) ($x2 * $scale), (int) ($y2 * $scale), $color);
    };
    $polygon = function (array $points, int $color) use ($image, $scale): void {
        imagefilledpolygon($image, array_map(fn ($v) => (int) ($v * $scale), $points), $color);
    };
    if ($name === 'brand-shield') {
        $polygon([7, 10, 17, 7, 27, 10, 27, 23, 25, 29, 17, 36, 9, 29, 7, 23], $amber);
        $line(12, 20, 16, 24, $white);
        $line(16, 24, 23, 17, $white);
        foreach ([[36, 10, 40, 6], [39, 19, 44, 19], [36, 28, 40, 32], [29, 36, 31, 42], [18, 40, 18, 45]] as $ray) {
            $line(...[...$ray, $pale, 2.5]);
        }
    } elseif ($name === 'graduation') {
        $polygon([2, 17, 24, 7, 46, 17, 24, 27], $amber);
        $polygon([12, 25, 24, 30, 36, 25, 36, 34, 24, 40, 12, 34], $amber);
        $line(5, 20, 5, 36, $amber, 2.5);
    } elseif ($name === 'envelope') {
        foreach ([[5, 9, 43, 9], [43, 9, 43, 39], [43, 39, 5, 39], [5, 39, 5, 9], [5, 11, 24, 26], [24, 26, 43, 11]] as $edge) {
            $line(...[...$edge, $white, 2.5]);
        }
    } elseif ($name === 'clock') {
        imagesetthickness($image, 2 * $scale);
        imageellipse($image, 24 * $scale, 24 * $scale, 38 * $scale, 38 * $scale, $muted);
        $line(24, 24, 24, 11, $muted, 2.5);
        $line(24, 24, 33, 24, $muted, 2.5);
    } else {
        imagefilledellipse($image, 24 * $scale, 24 * $scale, 48 * $scale, 48 * $scale, $background);
        if ($name === 'shield') {
            foreach ([[16, 16, 24, 13], [24, 13, 32, 16], [32, 16, 31, 27], [31, 27, 24, 33], [24, 33, 17, 27], [17, 27, 16, 16]] as $edge) {
                $line(...[...$edge, $navy, 1.8]);
            }
        } else {
            imagesetthickness($image, 2 * $scale);
            imagearc($image, 20 * $scale, 29 * $scale, 14 * $scale, 14 * $scale, 35, 290, $muted);
            imagearc($image, 29 * $scale, 19 * $scale, 14 * $scale, 14 * $scale, 210, 465, $muted);
            $line(20, 28, 29, 19, $muted);
        }
    }
    imagepng($image, $directory.'/'.$name.'.png');
    imagedestroy($image);
}
echo "Generated six verification email icons.\n";
