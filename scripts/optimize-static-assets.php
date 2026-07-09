<?php
/**
 * One-time (re-runnable) optimizer for heavy login/landing static assets.
 *
 * Targets flagged by HAR analysis of the login page:
 *   1. admin_assets/images/logos/logo.svg  (~1 MB) — a "vector" SVG that is really
 *      a single opaque base64 PNG (1774x887) wrapped in a <pattern>. Palette-256
 *      quantisation of that raster is visually identical (flat-colour wordmark,
 *      opaque background) and cuts it to ~65 KB. Pixel dimensions are kept, so the
 *      SVG's <image> width/height and transform matrix stay valid.
 *   2. admin_assets/css/accesibility-style_v1.css (~39 KB, unminified) — minified
 *      with a string/url-aware pass that preserves the /*! license header and the
 *      contents of strings / url() / data: URIs.
 *
 * Pristine originals are backed up alongside each file (…/original/) and re-runs
 * always read from that backup, so quality never compounds.
 *
 * NOTE: logo.png is intentionally NOT touched here — it has real transparency that
 * palette reduction would break, and lossless GD re-encoding makes it larger. Its
 * slow load is addressed by the caching/gzip headers in public/.htaccess instead.
 *
 * Usage:  php scripts/optimize-static-assets.php
 */

$publicDir = dirname(__DIR__) . '/public';

// Colour count for the logo.svg embedded wordmark. It is a flat 3-4 colour mark
// (red / grey / blue on white), so 16 colours is visually identical (verified)
// and cuts the embedded PNG ~4x further than palette-256.
const SVG_LOGO_COLORS = 16;

// Right-sized web variant width for logo.png. The login header shows it at
// ~180px; 400px covers 2x (retina) displays with room to spare, versus the
// shipped 1193px original that every page currently downloads.
const LOGO_WEB_WIDTH = 400;

/* ─────────────────────────── 1. logo.svg ─────────────────────────── */
function optimizeLogoSvg(string $publicDir): void
{
    $svgPath   = $publicDir . '/admin_assets/images/logos/logo.svg';
    $backupDir = $publicDir . '/admin_assets/images/logos/original';
    $backup    = $backupDir . '/logo.svg';

    if (! is_file($svgPath)) { fwrite(STDERR, "SKIP logo.svg: not found\n"); return; }
    if (! function_exists('imagecreatefromstring')) { fwrite(STDERR, "SKIP logo.svg: GD unavailable\n"); return; }

    if (! is_dir($backupDir)) { @mkdir($backupDir, 0755, true); }
    if (! is_file($backup)) { copy($svgPath, $backup); }

    $before = filesize($svgPath);
    $svg = file_get_contents($backup); // always start from pristine copy

    if (! preg_match('/data:image\/png;base64,([A-Za-z0-9+\/=]+)/', $svg, $m)) {
        fwrite(STDERR, "SKIP logo.svg: no embedded PNG found\n"); return;
    }

    $img = @imagecreatefromstring(base64_decode($m[1]));
    if ($img === false) { fwrite(STDERR, "SKIP logo.svg: embedded PNG undecodable\n"); return; }

    $w = imagesx($img); $h = imagesy($img);
    // Same dimensions → matrix/width/height in the SVG remain correct.
    $pal = imagecreatetruecolor($w, $h);
    imagealphablending($pal, false);
    imagesavealpha($pal, true);
    imagecopy($pal, $img, 0, 0, 0, 0, $w, $h);
    imagetruecolortopalette($pal, true, SVG_LOGO_COLORS);
    imagesavealpha($pal, true);

    $tmp = tempnam(sys_get_temp_dir(), 'svgpng');
    imagepng($pal, $tmp, 9);
    $newPng = file_get_contents($tmp);
    @unlink($tmp);

    $newSvg = str_replace($m[1], base64_encode($newPng), $svg);
    file_put_contents($svgPath, $newSvg);
    clearstatcache(true, $svgPath);

    printf(
        "logo.svg            %12s -> %12s  (%.1f%% smaller, %dx%d preserved)\n",
        number_format($before), number_format(filesize($svgPath)),
        (1 - filesize($svgPath) / $before) * 100, $w, $h
    );
}

/* ─────────────────────────── 2. CSS minify ────────────────────────── */
/**
 * String/url-aware CSS minifier: strips comments (keeping a leading /*! block)
 * and collapses insignificant whitespace, while copying the contents of strings
 * ('…' / "…") verbatim so url() and data: URIs are never mangled.
 */
function minifyCss(string $css): string
{
    $len = strlen($css);
    $out = '';
    $i = 0;

    // Preserve a leading important-comment (/*! … */) license header, tolerating
    // any leading whitespace before it (MIT requires keeping the copyright notice).
    $lead = strspn($css, " \t\r\n");
    if (strncmp(substr($css, $lead), '/*!', 3) === 0 && ($end = strpos($css, '*/', $lead + 3)) !== false) {
        $out .= substr($css, $lead, $end + 2 - $lead) . "\n";
        $i = $end + 2;
    }

    for (; $i < $len; $i++) {
        $ch = $css[$i];

        // Comment: /* … */ (never inside a string here).
        if ($ch === '/' && $i + 1 < $len && $css[$i + 1] === '*') {
            $end = strpos($css, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1; // skip to end of comment
            continue;
        }

        // String literal: copy verbatim through the matching quote.
        if ($ch === '"' || $ch === "'") {
            $quote = $ch;
            $out .= $ch;
            for ($i++; $i < $len; $i++) {
                $out .= $css[$i];
                if ($css[$i] === '\\' && $i + 1 < $len) { $out .= $css[++$i]; continue; }
                if ($css[$i] === $quote) { break; }
            }
            continue;
        }

        // Collapse any whitespace run to a single space.
        if (ctype_space($ch)) {
            $j = $i;
            while ($j < $len && ctype_space($css[$j])) { $j++; }
            $out .= ' ';
            $i = $j - 1;
            continue;
        }

        $out .= $ch;
    }

    // Tidy spaces around structural punctuation (safe: strings already copied out).
    $out = preg_replace('/\s*([{}:;,>])\s*/', '$1', $out);
    $out = str_replace(';}', '}', $out);          // drop last semicolon in a block
    $out = preg_replace('/\s+/', ' ', $out);      // final whitespace pass
    return trim($out);
}

function optimizeCss(string $publicDir): void
{
    $cssPath   = $publicDir . '/admin_assets/css/accesibility-style_v1.css';
    $backupDir = $publicDir . '/admin_assets/css/original';
    $backup    = $backupDir . '/accesibility-style_v1.css';

    if (! is_file($cssPath)) { fwrite(STDERR, "SKIP css: not found\n"); return; }
    if (! is_dir($backupDir)) { @mkdir($backupDir, 0755, true); }
    if (! is_file($backup)) { copy($cssPath, $backup); }

    $before = filesize($cssPath);
    $min = minifyCss(file_get_contents($backup)); // always from pristine copy

    // Safety: brace balance must be preserved or we abort the write.
    if (substr_count($min, '{') !== substr_count($min, '}')) {
        fwrite(STDERR, "ABORT css: brace mismatch after minify — original left intact\n");
        return;
    }

    file_put_contents($cssPath, $min);
    clearstatcache(true, $cssPath);
    printf(
        "accesibility.css    %12s -> %12s  (%.1f%% smaller)\n",
        number_format($before), number_format(filesize($cssPath)),
        (1 - filesize($cssPath) / $before) * 100
    );
}

/* ─────────────────── 3. logo.png web variants ─────────────────────── */
/**
 * The shared logo.png (1193x284, transparent emblem) must stay untouched — many
 * PDF/print exports embed it and its alpha can't be palette-reduced safely. But
 * on-screen it renders at ~180px, so we emit right-sized web variants the login
 * header can use via <picture>: a WebP (with PNG fallback for old browsers).
 */
function buildLogoPngWebVariants(string $publicDir): void
{
    $src = $publicDir . '/admin_assets/images/logos/logo.png';
    if (! is_file($src)) { fwrite(STDERR, "SKIP logo.png variants: not found\n"); return; }
    if (! function_exists('imagecreatefrompng')) { fwrite(STDERR, "SKIP logo.png variants: GD unavailable\n"); return; }

    $img = imagecreatefrompng($src);
    if ($img === false) { fwrite(STDERR, "SKIP logo.png variants: undecodable\n"); return; }

    $w = imagesx($img); $h = imagesy($img);
    $tw = min(LOGO_WEB_WIDTH, $w);
    $th = (int) round($h * ($tw / $w));

    $resized = imagecreatetruecolor($tw, $th);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    imagesavealpha($resized, true);

    $webpPath = $publicDir . '/admin_assets/images/logos/logo-web.webp';
    $pngPath  = $publicDir . '/admin_assets/images/logos/logo-web.png';
    imagewebp($resized, $webpPath, 88);
    imagepng($resized, $pngPath, 9);
    clearstatcache();

    printf(
        "logo-web.webp/png   generated %dx%d  (webp %s B, png %s B)  [source logo.png %s B untouched]\n",
        $tw, $th,
        number_format(filesize($webpPath)), number_format(filesize($pngPath)),
        number_format(filesize($src))
    );
}

optimizeLogoSvg($publicDir);
optimizeCss($publicDir);
buildLogoPngWebVariants($publicDir);
echo "Backups kept in …/original/ next to each file.\n";
