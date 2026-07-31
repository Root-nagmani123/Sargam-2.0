<?php

namespace App\Support\FC;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Renders HTML to a PDF with correct Devanagari (Hindi) shaping.
 *
 * mPDF's built-in Indic shaper cannot form all conjuncts/matras correctly with the
 * fonts available to it (e.g. the "क्र" rakaar and a cluster-final "है" both fail,
 * each with a different font) — so these FC document forms render Hindi wrong under
 * mPDF. Headless Chrome (HarfBuzz) shapes them correctly; Dompdf is the fallback when
 * Chrome is absent (note: Dompdf does NOT shape complex scripts either, so on a
 * Chrome-less host Hindi conjuncts will still be imperfect — Chrome is required for a
 * fully correct render).
 *
 * This mirrors the approach already used for FC registration report PDFs in
 * ReportController (fcRegistrationPdfRenderChrome / fcRegistrationEmbeddedFontFaceCss).
 */
class HindiPdfRenderer
{
    /** Embedded font-family name used inside generated HTML. */
    private const FONT_FAMILY = 'FcHindiPdf';

    /**
     * Inject the embedded Devanagari @font-face + a font-family override into $html,
     * then render it to PDF bytes (Chrome first, Dompdf fallback).
     */
    public function render(string $html, string $title): string
    {
        $html = $this->withEmbeddedFont($html);

        $engine = strtolower((string) env('FC_REGISTRATION_PDF_ENGINE', 'auto'));

        if ($engine !== 'dompdf') {
            $chromePdf = $this->renderChrome($html);
            if ($chromePdf !== null) {
                return $chromePdf;
            }
            Log::warning('FC document PDF: Chrome unavailable/failed, falling back to Dompdf (Hindi conjuncts may be imperfect)', [
                'engine'     => $engine,
                'chrome_bin' => $this->chromeBinary(),
                'title'      => $title,
            ]);
        }

        $this->ensureDompdfFontCacheDir();

        return Pdf::loadHTML($html)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', false)
            ->setPaper('a4', 'portrait')
            ->addInfo(['Title' => $title])
            ->output();
    }

    /**
     * Splice the @font-face (Noto Sans Devanagari, base64 data URLs so both Chrome and
     * Dompdf load it) plus a font-family override before </head>. The override keeps the
     * blade's own Latin styling and only adds Noto ahead of it — Chrome does per-glyph
     * fallback, so Latin still comes from the blade's font, Devanagari from Noto.
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

        // No <head> (bare fragment): prepend the style.
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

    /** Headless Chrome print-to-PDF (best Hindi + Latin shaping). Returns null on failure. */
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
                Log::warning('FC document PDF: Chrome headless failed', [
                    'exit' => $process->getExitCode(),
                    'err'  => $process->getErrorOutput(),
                ]);
                @unlink($htmlPath);
                @unlink($pdfPath);

                return null;
            }
        } catch (\Throwable $e) {
            Log::warning('FC document PDF: Chrome exception', ['message' => $e->getMessage()]);
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

    private function chromeBinary(): ?string
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

    private function ensureDompdfFontCacheDir(): void
    {
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            File::makeDirectory($fontDir, 0775, true);
        }
    }
}
