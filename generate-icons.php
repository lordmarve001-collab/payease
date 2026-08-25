<?php

/**
 * PayEase PWA Icon Generator
 * Run once: php generate-icons.php
 * Outputs to public/icons/
 */

$iconDir = __DIR__ . '/public/icons';

if (!is_dir($iconDir)) {
    mkdir($iconDir, 0755, true);
}

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);

    $bg = imagecolorallocate($img, 3, 56, 30);
    imagefilledrectangle($img, 0, 0, $size, $size, $bg);

    $gold = imagecolorallocate($img, 245, 158, 11);
    $white = imagecolorallocate($img, 255, 255, 255);

    $cx = $size / 2;
    $cy = $size / 2;
    $r = $size * 0.35;

    imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $gold);

    $fontSize = max(8, (int)($size * 0.22));
    $font = 5;

    $nairaW = imagefontwidth($font) * 2;
    $nairaH = imagefontheight($font);

    imagestring($img, $font, $cx - $nairaW / 2, $cy - $nairaH / 2, '₦', $white);

    $path = $iconDir . "/icon-{$size}x{$size}.png";
    imagepng($img, $path);
    imagedestroy($img);

    echo "Created: {$path}\n";
}

// Shortcut icons
.shortcuts = [
    'send-money' => [72, 96],
    'wallet' => [72, 96],
    'pay-bills' => [72, 96],
    'ajo' => [72, 96],
];

$shortcutColors = [
    'send-money' => [245, 158, 11],
    'wallet' => [139, 92, 246],
    'pay-bills' => [16, 185, 129],
    'ajo' => [236, 72, 153],
];

foreach ($shortcutColors as $name => $rgb) {
    foreach ([72, 96] as $size) {
        $img = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($img, 15, 23, 42);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $accent = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        $cx = $size / 2;
        $cy = $size / 2;
        $r = $size * 0.3;

        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $accent);

        $white = imagecolorallocate($img, 255, 255, 255);
        $font = 5;
        $char = strtoupper(substr($name, 0, 1));
        $fw = imagefontwidth($font);
        $fh = imagefontheight($font);
        imagestring($img, $font, $cx - $fw / 2, $cy - $fh / 2, $char, $white);

        $path = $iconDir . "/{$name}-{$size}x{$size}.png";
        imagepng($img, $path);
        imagedestroy($img);
        echo "Created: {$path}\n";
    }
}

// Screenshot placeholders (wide + narrow)
$screenshots = [
    'screenshot-wide' => [1280, 720],
    'screenshot-narrow' => [720, 1280],
];

foreach ($screenshots as $name => [$w, $h]) {
    $img = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($img, 15, 23, 42);
    imagefilledrectangle($img, 0, 0, $w, $h, $bg);

    $gold = imagecolorallocate($img, 245, 158, 11);
    $cx = $w / 2;
    $cy = $h / 2;
    $r = min($w, $h) * 0.15;

    imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $gold);

    $white = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 5, $cx - 30, $cy - 5, 'PayEase', $white);

    $path = $iconDir . "/{$name}.png";
    imagepng($img, $path);
    imagedestroy($img);
    echo "Created: {$path}\n";
}

echo "\nAll icons generated!\n";
