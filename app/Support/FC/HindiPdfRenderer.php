<?php

namespace App\Support\FC;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Renders HTML to a PDF with correct Devanagari (Hindi) shaping using headless Chrome
 * (HarfBuzz). Returns null when Chrome is unavailable/fails, so the caller can fall back
 * to its own renderer.
 *
 * Why Chrome: mPDF's built-in Indic shaper mis-renders some Hindi here (e.g. the "क्र"
 * rakaar and a cluster-final "है"), and Dompdf is worse still (no Indic matra reordering,
 * so "दिनांक" becomes "दनिांक"). Only a real shaper (Chrome) renders these correctly.
 *
 * This mirrors the headless-Chrome approach already used for FC registration report PDFs
 * in ReportController (fcRegistrationPdfRenderChrome / fcRegistrationEmbeddedFontFaceCss).
 */
class HindiPdfRenderer
{
    /** Embedded font-family name used inside generated HTML. */
    private const FONT_FAMILY = 'FcHindiPdf';

    /**
     * Render $html to PDF bytes via headless Chrome, or null if Chrome is not available
     * (or failed). The Devanagari @font-face + a font-family override are injected first.
     */
    public function renderViaChrome(string $html): ?string
    {
        if ($this->chromeBinary() === null) {
            Log::info('FC document PDF: Chrome not found; caller should fall back to mPDF', [
                'chrome_bin_env' => env('FC_REGISTRATION_CHROME_BIN'),
            ]);

            return null;
        }

        return $this->renderChrome($this->withEmbeddedFont($html));
    }

    /**
     * Splice the @font-face (Noto Sans Devanagari, base64 data URLs) plus a font-family
     * override before </head>. Chrome does per-glyph fallback, so Latin still comes from
     * the blade's own font and only Devanagari is drawn with Noto.
     */
    public function withEmbeddedFont(string $html): string
    {
        $fontFace = $this->embeddedFontFaceCss();
        if ($fontFace === '') {
            return $html;
        }

        $stack = "'".self::FONT_FAMILY."', 'DejaVu Sans', sans-serif";
        $style = '<style>'.$fontFace
            .'body, body * { font-family: '.$stack.' !important; }'
            .'</style>';

        $pos = stripos($html, '</head>');
        if ($pos !== false) {
            return substr($html, 0, $pos).$style.substr($html, $pos);
        }

        return $style.$html;
    }

    private function embeddedFontFaceCss(): string
    {
        $dir = resource_path('fonts/mpdf');
        $regular = $dir.'/NotoSansDevanagari-Regular.ttf';
        if (! is_file($regular) || ! is_readable($regular)) {
            Log::warning('FC document PDF: Noto Sans Devanagari not found; Hindi will not render', ['path' => $regular]);

            return '';
        }

        $rData = base64_encode((string) file_get_contents($regular));
        $css = "@font-face{font-family:'".self::FONT_FAMILY."';font-style:normal;font-weight:400;"
            ."src:url(data:font/ttf;charset=utf-8;base64,{$rData}) format('truetype');}";

        $bold = $dir.'/NotoSansDevanagari-Bold.ttf';
        if (is_file($bold) && is_readable($bold)) {
            $bData = base64_encode((string) file_get_contents($bold));
            $css .= "@font-face{font-family:'".self::FONT_FAMILY."';font-style:normal;font-weight:700;"
                ."src:url(data:font/ttf;charset=utf-8;base64,{$bData}) format('truetype');}";
        }

        return $css;
    }

    /** Headless Chrome print-to-PDF. Returns null on failure. */
    private function renderChrome(string $html): ?string
    {
        $bin = $this->chromeBinary();
        if ($bin === null) {
            return null;
        }

        $work = storage_path('app/temp/fc-pdf');
        if (! is_dir($work)) {
            @mkdir($work, 0755, true);
        }

        $id = uniqid('fcdoc_', true);
        $htmlPath = $work.'/'.$id.'.html';
        $pdfPath = $work.'/'.$id.'.pdf';

        if (file_put_contents($htmlPath, $html) === false) {
            return null;
        }

        $fileUri = 'file://'.str_replace('\\', '/', $htmlPath);

        $cmd = [
            $bin,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--no-pdf-header-footer',
            '--virtual-time-budget=25000',
            '--print-to-pdf='.$pdfPath,
            $fileUri,
        ];

        try {
            $process = new Process($cmd);
            $process->setTimeout(120);
            $process->run();
            if (! $process->isSuccessful()) {
                Log::warning('FC document PDF: Chrome headless failed, falling back to mPDF', [
                    'exit' => $process->getExitCode(),
                    'err'  => $process->getErrorOutput(),
                ]);
                @unlink($htmlPath);
                @unlink($pdfPath);

                return null;
            }
        } catch (\Throwable $e) {
            Log::warning('FC document PDF: Chrome exception, falling back to mPDF', ['message' => $e->getMessage()]);
            @unlink($htmlPath);
            @unlink($pdfPath);

            return null;
        }

        @unlink($htmlPath);

        if (! is_file($pdfPath)) {
            return null;
        }

        $binary = file_get_contents($pdfPath);
        @unlink($pdfPath);

        return $binary === false ? null : $binary;
    }

    public function chromeBinary(): ?string
    {
        $fromEnv = env('FC_REGISTRATION_CHROME_BIN');
        if (is_string($fromEnv) && $fromEnv !== '' && @is_executable($fromEnv)) {
            return $fromEnv;
        }
        foreach ([
            '/usr/bin/google-chrome-stable',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/snap/bin/chromium',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
        ] as $path) {
            if (@is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}
