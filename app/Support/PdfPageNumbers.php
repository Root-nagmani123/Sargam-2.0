<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;

/**
 * Stamps "Page N of M" onto a rendered DomPDF document.
 *
 * The obvious way to number pages in DomPDF is a `<script type="text/php">`
 * block inside the view, but that block only runs when the `isPhpEnabled`
 * option is on — and that option does not scope itself to the page-number
 * script. It turns the renderer into a server-side PHP execution context for
 * the WHOLE view, so a single raw `{!! !!}` block appearing in an export blade
 * later is enough to execute admin-entered data. Page numbers are not worth
 * that trade, which is why every export here leaves the flag off and calls this
 * instead.
 *
 * The CSS route is not an alternative: DomPDF resolves `counter(pages)` before
 * the page count exists and silently prints "of 0".
 *
 * So the numbering happens in the one place it still can — on the canvas, after
 * the document has been rendered and the page count is known.
 */
class PdfPageNumbers
{
    /** Right inset in points, matching the export blades' own page margin. */
    private const RIGHT_INSET = 28;

    /** Ink colour of the stamp, matching the export blades' muted footer grey. */
    private const COLOUR = [0.42, 0.45, 0.5];

    /**
     * Render $pdf and stamp the page number onto every page.
     *
     * Returns the same instance so the caller can chain ->download() / ->stream().
     *
     * @param  int  $bottomInset  Points above the foot of the page. 24 matches the
     *                            shared branded grid; the users export sits at 20.
     */
    public static function stamp(PDF $pdf, int $bottomInset = 24): PDF
    {
        // Must run before the stamp: page_text() resolves {PAGE_COUNT} only once
        // every page exists. render() also marks the document rendered, so the
        // caller's download()/output() will not re-render and discard this.
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();

        $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $size = 7;
        $width = $fontMetrics->getTextWidth($text, $font, $size);

        $canvas->page_text(
            $canvas->get_width() - $width - self::RIGHT_INSET,
            $canvas->get_height() - $bottomInset,
            $text,
            $font,
            $size,
            self::COLOUR
        );

        return $pdf;
    }
}
