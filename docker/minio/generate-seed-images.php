<?php

// Generates placeholder images for every S3 object key the app references,
// so the local MinIO bucket can serve the public/auth pages.
// Run inside the app container:
//   docker compose exec app php docker/minio/generate-seed-images.php

$keys = [
    'hero.jpg' => [1200, 675],
    'collab.png' => [800, 500],
    'custom.png' => [800, 500],
    'catalog.png' => [800, 500],
    'usage.png' => [800, 500],
    'data.png' => [800, 500],
    'community.png' => [800, 500],
    'about1.webp' => [800, 500],
    'Mercedes.webp' => [600, 600],
    'Rodrigo.webp' => [600, 600],
    'public/bg.jpg' => [1600, 900],
];

$seedDir = __DIR__.'/seed';

foreach ($keys as $key => [$w, $h]) {
    $path = "$seedDir/$key";
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $img = imagecreatetruecolor($w, $h);
    $hue = crc32($key) % 360;
    [$r, $g, $b] = hslToRgb($hue, 0.35, 0.45);
    imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));

    $label = "placeholder: $key";
    $white = imagecolorallocate($img, 255, 255, 255);
    $x = (int) (($w - strlen($label) * imagefontwidth(5)) / 2);
    $y = (int) (($h - imagefontheight(5)) / 2);
    imagestring($img, 5, max($x, 10), $y, $label, $white);

    $ext = pathinfo($key, PATHINFO_EXTENSION);
    match ($ext) {
        'jpg' => imagejpeg($img, $path, 80),
        'webp' => imagewebp($img, $path, 80),
        default => imagepng($img, $path),
    };
    imagedestroy($img);
    echo "wrote $path\n";
}

function hslToRgb(int $h, float $s, float $l): array
{
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    [$r, $g, $b] = match (intdiv($h, 60)) {
        0 => [$c, $x, 0],
        1 => [$x, $c, 0],
        2 => [0, $c, $x],
        3 => [0, $x, $c],
        4 => [$x, 0, $c],
        default => [$c, 0, $x],
    };

    return [
        (int) round(($r + $m) * 255),
        (int) round(($g + $m) * 255),
        (int) round(($b + $m) * 255),
    ];
}
