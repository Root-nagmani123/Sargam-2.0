<?php
/**
 * One-time (re-runnable) optimizer for the login/landing background carousel images.
 *
 * The 10 slides in resources/views/auth/login.blade.php are served from
 * public/images/carasoul/{1..10}.webp. They were originally camera-resolution
 * (~7008x4672, 2.4-3.5 MB each, ~30 MB total) even though they render only as a
 * darkened, overlaid, full-screen background. This script downsizes them to a
 * sensible max width and re-encodes them as WebP so the page payload drops
 * dramatically with no visible quality loss.
 *
 * Behaviour:
 *   - Backs up each untouched original to public/images/carasoul/original/{i}.webp
 *     (skipped if a backup already exists, so re-runs never overwrite the pristine source).
 *   - Resizes to MAX_WIDTH (aspect ratio preserved, never upscaled).
 *   - Re-encodes as WebP at QUALITY, overwriting the served file in place.
 *   - Prints a before/after size report.
 *
 * Requires PHP GD with WebP support (imagecreatefromwebp / imagewebp).
 *
 * Usage:  php scripts/optimize-carousel-images.php
 */

const MAX_WIDTH = 1920;   // max realistic display width for the full-screen background
const QUALITY   = 80;     // WebP quality (image is darkened + overlaid, so 80 is imperceptible)

if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
    fwrite(STDERR, "ERROR: PHP GD with WebP support is required but not available.\n");
    exit(1);
}

$carouselDir = dirname(__DIR__) . '/public/images/carasoul';
$backupDir   = $carouselDir . '/original';

if (!is_dir($carouselDir)) {
    fwrite(STDERR, "ERROR: carousel directory not found: {$carouselDir}\n");
    exit(1);
}

if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "ERROR: could not create backup directory: {$backupDir}\n");
    exit(1);
}

$totalBefore = 0;
$totalAfter  = 0;
$processed   = 0;

printf("%-10s %14s %14s %8s   %s\n", 'file', 'before', 'after', 'saved', 'dimensions');
printf("%s\n", str_repeat('-', 72));

for ($i = 1; $i <= 10; $i++) {
    $path = "{$carouselDir}/{$i}.webp";
    if (!is_file($path)) {
        fwrite(STDERR, "SKIP {$i}.webp: file not found\n");
        continue;
    }

    // 1. Back up the pristine original once.
    $backupPath = "{$backupDir}/{$i}.webp";
    if (!is_file($backupPath) && !copy($path, $backupPath)) {
        fwrite(STDERR, "ERROR: failed to back up {$i}.webp\n");
        continue;
    }

    // Always read from the pristine backup so re-runs don't compound quality loss.
    $sourcePath = $backupPath;
    $beforeSize = filesize($path);

    $src = @imagecreatefromwebp($sourcePath);
    if ($src === false) {
        fwrite(STDERR, "ERROR: could not decode {$i}.webp\n");
        continue;
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    // 2. Resize (preserve aspect ratio, never upscale).
    if ($origW > MAX_WIDTH) {
        $resized = imagescale($src, MAX_WIDTH); // height auto, high-quality resampling
        imagedestroy($src);
        $img = $resized;
    } else {
        $img = $src;
    }

    // 3. Re-encode in place as WebP.
    if (!imagewebp($img, $path, QUALITY)) {
        fwrite(STDERR, "ERROR: could not write optimized {$i}.webp\n");
        imagedestroy($img);
        continue;
    }
    imagedestroy($img);
    clearstatcache(true, $path);

    $afterSize = filesize($path);
    $newW = min($origW, MAX_WIDTH);
    $newH = (int) round($origH * ($newW / $origW));

    $totalBefore += $beforeSize;
    $totalAfter  += $afterSize;
    $processed++;

    printf(
        "%-10s %14s %14s %7.1f%%   %dx%d\n",
        "{$i}.webp",
        number_format($beforeSize),
        number_format($afterSize),
        $beforeSize > 0 ? (1 - $afterSize / $beforeSize) * 100 : 0,
        "{$newW}",
        "{$newH}"
    );
}

printf("%s\n", str_repeat('-', 72));
printf(
    "Processed %d image(s). Total: %s -> %s bytes (%.1f%% smaller).\n",
    $processed,
    number_format($totalBefore),
    number_format($totalAfter),
    $totalBefore > 0 ? (1 - $totalAfter / $totalBefore) * 100 : 0
);
printf("Pristine originals preserved in: %s\n", $backupDir);
