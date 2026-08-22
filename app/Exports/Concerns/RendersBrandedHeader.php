<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * The LBSNAA header block every Mess master workbook opens with: the emblem on
 * the left, the academy name across the merged title rows, and the 75-years logo
 * on the right, anchored to the sheet's last column.
 *
 * That anchoring is fine on a wide report and collides on a narrow one. The
 * academy line needs roughly 455px; the right logo is 84px and sits hard against
 * the final column. Sub Store Master totals three columns — 518px — so the
 * centred title ran under the logo by ~78px, and Client Master (588px) by ~29px.
 * Store (770px) and Vendor (1050px) had the room and looked correct, which is
 * why the header only appeared broken on some exports.
 *
 * {@see ensureHeaderRoom()} gives the narrow sheets the width the header needs
 * before the logos are placed. Data column widths are left exactly as each
 * export declared them — only the last column grows, so the table keeps its
 * intended proportions and the header stops overlapping itself.
 */
trait RendersBrandedHeader
{
    /**
     * Roughly how wide, in pixels, the header block needs to be:
     * emblem (47) + academy line (~455) + 75-years logo (84) + breathing room.
     */
    private const HEADER_MIN_PX = 640;

    /** PhpSpreadsheet column widths are character units of about 7px. */
    private const PX_PER_WIDTH_UNIT = 7;

    /** Rendered width of the 75-years logo at 48px tall. */
    private const RIGHT_LOGO_PX = 84;

    /**
     * Widen the last column if the sheet is too narrow to seat the header block.
     *
     * Call before placing the logos.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @param  string  $lastCol  The sheet's final column letter.
     */
    private function ensureHeaderRoom($sheet, string $lastCol): void
    {
        $totalPx = 0;
        $columns = [];

        foreach ($this->columnLettersUpTo($lastCol) as $letter) {
            $width = $sheet->getColumnDimension($letter)->getWidth();
            $width = $width > 0 ? $width : 8;
            $columns[$letter] = $width;
            $totalPx += (int) round($width * self::PX_PER_WIDTH_UNIT);
        }

        if ($totalPx >= self::HEADER_MIN_PX) {
            return;
        }

        $shortfallUnits = (int) ceil((self::HEADER_MIN_PX - $totalPx) / self::PX_PER_WIDTH_UNIT);

        $sheet->getColumnDimension($lastCol)->setWidth($columns[$lastCol] + $shortfallUnits);
    }

    /**
     * @return string[] 'A' … $lastCol inclusive.
     */
    private function columnLettersUpTo(string $lastCol): array
    {
        $letters = [];

        for ($letter = 'A'; ; $letter++) {
            $letters[] = $letter;
            if ($letter === $lastCol) {
                break;
            }
            // Guard against a malformed $lastCol running away.
            if (count($letters) > 64) {
                break;
            }
        }

        return $letters;
    }

    /**
     * The 75-years logo at the far right of the header band.
     *
     * Anchoring it at the last column's cell puts it at that column's LEFT edge,
     * which only reads as "the right of the page" while the last column happens
     * to be narrow. Widening a column to make header room therefore stranded the
     * logo mid-sheet, still sitting under the title. Offsetting it by the
     * column's own width less the logo's puts it where it belongs whatever that
     * column ends up measuring.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     */
    private function placeRightLogo($sheet, string $path, string $lastCol): void
    {
        $widthUnits = $sheet->getColumnDimension($lastCol)->getWidth();
        $widthUnits = $widthUnits > 0 ? $widthUnits : 8;
        $columnPx = (int) round($widthUnits * self::PX_PER_WIDTH_UNIT);

        $offsetX = max(2, $columnPx - self::RIGHT_LOGO_PX - 6);

        $this->placeLogo($sheet, $path, $lastCol . '1', $offsetX);
    }

    /**
     * Anchor one 48px-tall logo at a cell, nudged by $offsetX.
     */
    private function placeLogo($sheet, string $path, string $coordinates, int $offsetX): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            return;
        }
        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setHeight(48);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY(3);
        $drawing->setWorksheet($sheet);
    }
}
