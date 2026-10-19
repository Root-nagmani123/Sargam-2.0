<?php

namespace App\Traits;

use Barryvdh\DomPDF\PDF;

/**
 * Footer page numbers for DomPDF reports without enabling isPhpEnabled.
 *
 * A <script type="text/php"> block in the view is the other way to reach the
 * canvas, but it requires isPhpEnabled, which turns any raw block that later
 * appears in that view into server-side code execution on stored data. The
 * canvas API gives the same footer with both options off.
 */
trait StampsPdfPageNumbers
{
    /**
     * Stamp "Page N of M" bottom-right on every page. Must be called after
     * setOptions() and before download()/stream().
     */
    protected function stampPageNumbers(PDF $pdf, string $font = 'DejaVu Sans', float $size = 7): void
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();

        $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $fontObject = $fontMetrics->getFont($font, 'normal');
        $width = $fontMetrics->getTextWidth($text, $fontObject, $size);

        $canvas->page_text(
            $canvas->get_width() - $width - 20,
            $canvas->get_height() - 18,
            $text,
            $fontObject,
            $size,
            [0.4, 0.4, 0.4]
        );
    }
}
