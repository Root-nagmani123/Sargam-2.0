<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guard for PR #311 review F-027: dompdf must never fetch anything over the network.
 *
 * dompdf renders server-side, so an http(s) `src` makes the SERVER fetch it while building
 * the document. Before this guard the mess PDF exports resolved the national emblem by
 * calling https://upload.wikimedia.org/... on EVERY render (Http::timeout(20)) and handed
 * dompdf the raw URL when that call failed. Measured at 18a676afb: 474ms spent on a call
 * that failed, after which the emblem was simply absent from the rendered sheet.
 *
 * The durable part of the fix is this test, not the 31 edits. A single new export written
 * with 'isRemoteEnabled' => true, or a header partial pasted in with a remote logo URL,
 * puts the whole surface back; a per-site test could never catch that.
 *
 * Nothing here needs a database or a browser - it is a source scan, so it runs anywhere.
 */
class PdfRemoteAssetGuardTest extends TestCase
{
    /** Hosts that were being fetched at render time before F-027 was fixed. */
    private const FORBIDDEN_IMAGE_HOSTS = [
        'upload.wikimedia.org',
        'www.lbsnaa.gov.in',
        // F-028: a dead helper rendered QR codes by posting their contents to this
        // service at render time. Listed so it cannot come back quietly.
        'api.qrserver.com',
    ];

    /** @return string[] every .php file under app/ */
    private function phpFiles(string $dir): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $out[] = str_replace('\\', '/', $file->getPathname());
            }
        }

        sort($out);

        return $out;
    }

    private function relative(string $path): string
    {
        return ltrim(str_replace(str_replace('\\', '/', base_path()), '', $path), '/');
    }

    /**
     * No dompdf call site may enable remote loading.
     *
     * This is the switch that decides whether a stray remote URL is a broken image or an
     * outbound request from the server.
     */
    public function test_no_dompdf_call_site_enables_remote_loading(): void
    {
        $offenders = [];

        foreach ($this->phpFiles(app_path()) as $path) {
            $source = file_get_contents($path);

            if ($source === false) {
                continue;
            }

            foreach (preg_split('/\R/', $source) as $i => $line) {
                $enablesViaArray = preg_match('/[\'"]isRemoteEnabled[\'"]\s*=>\s*true/', $line);
                $enablesViaSetter = preg_match('/setOption\(\s*[\'"]isRemoteEnabled[\'"]\s*,\s*true/', $line);

                if ($enablesViaArray || $enablesViaSetter) {
                    $offenders[] = $this->relative($path).':'.($i + 1).'  '.trim($line);
                }
            }
        }

        $this->assertSame([], $offenders, "dompdf remote loading is enabled at:\n".implode("\n", $offenders));
    }

    /**
     * No image handed to a PDF may be a remote URL.
     *
     * Scans both the PDF blades and the controllers that build their src values, because
     * the defect F-027 recorded lived in a controller helper, not in a view.
     */
    public function test_no_pdf_asset_is_fetched_from_a_remote_host(): void
    {
        $offenders = [];
        $roots = [app_path(), resource_path('views')];

        foreach ($roots as $root) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

            foreach ($it as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = str_replace('\\', '/', $file->getPathname());

                // Only the PDF surface: blades that dompdf renders, and the controllers
                // that feed them. Browser views may legitimately link out - that is the
                // user's browser fetching, not this server.
                $isPdfSurface = (bool) preg_match('#/(pdf|export)[^/]*\.blade\.php$#i', $path)
                    || (bool) preg_match('#/pdf/#i', $path)
                    || str_ends_with($path, 'Controller.php');

                if (! $isPdfSurface) {
                    continue;
                }

                $source = file_get_contents($path);

                if ($source === false) {
                    continue;
                }

                foreach (preg_split('/\R/', $source) as $i => $line) {
                    // A comment explaining the history is not a fetch.
                    $trimmed = ltrim($line);
                    if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#')) {
                        continue;
                    }

                    foreach (self::FORBIDDEN_IMAGE_HOSTS as $host) {
                        if (str_contains($line, $host)) {
                            $offenders[] = $this->relative($path).':'.($i + 1).'  '.trim($line);
                        }
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "A PDF asset is still fetched from a remote host:\n".implode("\n", $offenders)
        );
    }

    /** The replacements must actually resolve, or the guard above would pass on blank images. */
    public function test_the_local_pdf_branding_helpers_resolve_to_embedded_images(): void
    {
        foreach (['pdf_emblem_src' => 'emblem', 'pdf_lbsnaa_logo_src' => 'LBSNAA logo'] as $fn => $label) {
            $value = $fn();

            $this->assertStringStartsWith(
                'data:image/',
                $value,
                "The {$label} helper did not resolve to an embedded image - the asset is missing from public/."
            );
            $this->assertGreaterThan(
                1000,
                strlen($value),
                "The {$label} helper returned something too small to be a real image."
            );
        }
    }
}
