<?php

namespace App\Exports;

use App\Models\Mess\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Vendor Master listing.
 *
 * Mirrors {@see StoreMasterExport}: a formatted workbook whose header block +
 * table styling match the branded Print / PDF layout (institution logos, blue
 * title band, blue column header, bordered zebra rows). Kept in step with
 * {@see \App\Http\Controllers\Mess\VendorController::export()}.
 */
class VendorMasterExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected ?string $search;

    /**
     * On-screen data-column indexes (0..5) the user left visible via the Column
     * Visibility modal, or null when every column shows. S.No (0) is always kept;
     * Action (6) is never exported.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    /** Data-row count, captured while streaming the collection (for the meta line). */
    protected int $rowCount = 0;

    public function __construct(?string $search = null, ?array $visibleColumns = null)
    {
        $this->search = ($search !== null && trim($search) !== '') ? trim($search) : null;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
    }

    /**
     * All exportable data columns, keyed by their on-screen table index (0..5).
     *
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            0 => ['key' => 's_no',           'width' => 8,  'align' => 'center'],
            1 => ['key' => 'name',           'width' => 28, 'align' => null],
            2 => ['key' => 'email',          'width' => 32, 'align' => null],
            3 => ['key' => 'contact_person', 'width' => 22, 'align' => null],
            4 => ['key' => 'phone',          'width' => 18, 'align' => 'center'],
            5 => ['key' => 'address',        'width' => 42, 'align' => null],
        ];
    }

    /**
     * The visible subset of {@see columnDefinitions()}, in order (all when
     * unfiltered). S.No (index 0) is always included.
     *
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function activeColumns(): array
    {
        $cols = $this->columnDefinitions();
        if ($this->visibleColumns === null) {
            return array_values($cols);
        }

        $keep = array_merge([0], $this->visibleColumns);

        $filtered = [];
        foreach ($cols as $idx => $col) {
            if (in_array($idx, $keep, true)) {
                $filtered[] = $col;
            }
        }

        return $filtered !== [] ? $filtered : array_values($cols);
    }

    /** Headings for the active columns, in the same order as {@see activeColumns()}. */
    public function activeHeadings(): array
    {
        $headings = array_merge(['S.No.'], $this->columnHeadings());
        $keys = array_column($this->columnDefinitions(), 'key');

        return array_map(
            fn ($col) => $headings[array_search($col['key'], $keys, true)] ?? '',
            $this->activeColumns()
        );
    }

    /** Narrow an associative row down to the active columns, in order. */
    private function pickActive(array $row): array
    {
        $out = [];
        foreach ($this->activeColumns() as $col) {
            $out[$col['key']] = $row[$col['key']] ?? '';
        }

        return $out;
    }

    public function collection()
    {
        $data = $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                $this->pickActive(array_merge(['s_no' => $index + 1], $this->mapRecord($record)))
            ));

        $this->rowCount = $data->count();

        return $data;
    }

    /** Rows for the PDF layout (associative order stripped to values, S.No prepended). */
    public function pdfRows(): Collection
    {
        return $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                $this->pickActive(array_merge(['s_no' => $index + 1], $this->mapRecord($record)))
            ));
    }

    public function title(): string
    {
        return 'Vendor Master';
    }

    /**
     * Column widths tuned to the content. Only the active columns are written,
     * mapped onto sequential sheet letters so a hidden column never leaves a gap.
     */
    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->activeColumns() as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 1)] = $col['width'];
        }

        return $widths;
    }

    /**
     * Build the branded header block + table styling after the data is written.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnHeadings = $this->activeHeadings();
                $colCount = count($columnHeadings);
                $lastCol = Coordinate::stringFromColumnIndex($colCount);

                // --- Meta lines shown above the table (same content as Print/PDF) ---
                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => 'Vendor Master', 'style' => 'title'];

                if ($this->search !== null) {
                    $metaLines[] = ['text' => 'Applied Filters:   Search: ' . $this->search, 'style' => 'meta'];
                }

                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total records: ' . $this->rowCount,
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                $headerRows = count($metaLines) + 1;
                $sheet->insertNewRowBefore(1, $headerRows);

                $headingRow = count($metaLines) + 1;
                $firstDataRow = $headingRow + 1;
                $lastDataRow = $headingRow + max($this->rowCount, 0);

                $sheet->setShowGridlines(false);

                // --- Meta rows: merge across the table width and style per role ---
                foreach ($metaLines as $i => $line) {
                    $r = $i + 1;
                    $range = "A{$r}:{$lastCol}{$r}";
                    $sheet->mergeCells($range);
                    $sheet->setCellValue("A{$r}", $line['text']);
                    $sheet->getStyle($range)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $font = $sheet->getStyle("A{$r}")->getFont();
                    switch ($line['style']) {
                        case 'inst':
                            $font->setBold(true)->setSize(13)->getColor()->setRGB('102A43');
                            $sheet->getRowDimension($r)->setRowHeight(42); // room for the logos
                            break;
                        case 'title':
                            $font->setBold(true)->setSize(16)->getColor()->setRGB('004A93');
                            $sheet->getStyle($range)->getBorders()->getBottom()
                                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('004A93');
                            $sheet->getRowDimension($r)->setRowHeight(24);
                            break;
                        case 'spacer':
                            $sheet->getRowDimension($r)->setRowHeight(6);
                            break;
                        default: // meta
                            $font->setSize(9)->getColor()->setRGB('555555');
                    }
                }

                // --- Column-heading row: blue band, white bold, centred, bordered ---
                foreach ($columnHeadings as $ci => $heading) {
                    $sheet->setCellValueByColumnAndRow($ci + 1, $headingRow, $heading);
                }
                $headingRange = "A{$headingRow}:{$lastCol}{$headingRow}";
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('004A93');
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension($headingRow)->setRowHeight(26);

                // --- Data rows: borders, top-align + wrap, zebra striping ---
                if ($this->rowCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(10);
                    $sheet->getStyle($bodyRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP)
                        ->setWrapText(true);
                    foreach ($this->activeColumns() as $i => $col) {
                        if (($col['align'] ?? null) === 'center') {
                            $letter = Coordinate::stringFromColumnIndex($i + 1);
                            $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }

                    for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                        if (($r - $firstDataRow) % 2 === 1) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');
                        }
                    }
                }

                // --- Borders around the whole table (heading + body) ---
                $tableBottom = max($lastDataRow, $headingRow);
                $sheet->getStyle("A{$headingRow}:{$lastCol}{$tableBottom}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('8FA3BD');

                // --- Institution logos, floated over the first (institution) row ---
                $this->placeLogo($sheet, public_path('admin_assets/images/logos/logo_new.png'), 'A1', 6);

                $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
                if (! is_file($rightLogo)) {
                    $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
                }
                $this->placeLogo($sheet, $rightLogo, $lastCol . '1', 2);
            },
        ];
    }

    /** Anchor an image (if it exists) over the given cell, sized to the header row. */
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

    private function recordsQuery()
    {
        $query = Vendor::query();

        if ($this->search !== null) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                  ->orWhere('email', 'like', '%' . $term . '%')
                  ->orWhere('contact_person', 'like', '%' . $term . '%')
                  ->orWhere('phone', 'like', '%' . $term . '%')
                  ->orWhere('address', 'like', '%' . $term . '%');
            });
        }

        // Same order the listing uses (VendorController@index → orderByDesc('id')).
        return $query->orderByDesc('id')->get();
    }

    private function mapRecord($record): array
    {
        return [
            'name'           => (string) ($record->name ?? ''),
            'email'          => $record->email ?: '-',
            'contact_person' => $record->contact_person ?: '-',
            'phone'          => $record->phone ?: '-',
            'address'        => $record->address ?: '-',
        ];
    }

    /**
     * Data-column headings (excluding S.No), matching the listing table headers.
     *
     * @return array<int,string>
     */
    public function columnHeadings(): array
    {
        return [
            'Vendor Name',
            'Email',
            'Contact Person',
            'Phone',
            'Address',
        ];
    }
}
