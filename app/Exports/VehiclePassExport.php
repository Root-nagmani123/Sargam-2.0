<?php

namespace App\Exports;

use App\Models\VehiclePassTWApply;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled Excel (.xlsx) export for the Vehicle Pass Request listing.
 *
 * A plain CSV can't carry colour/borders/logos, so this builds a formatted
 * workbook whose header block + table styling mirror the Print / PDF layout:
 * institution logos, blue title band, blue column header, bordered zebra rows.
 * Mirrors {@see DuplicateVehiclePassExport}.
 */
class VehiclePassExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected string $tab;
    protected mixed $employeePk;
    protected mixed $pkOld;
    protected ?string $search;

    /** Data-row count, captured while streaming the collection (for the meta line). */
    protected int $rowCount = 0;

    public function __construct(string $tab, mixed $employeePk, mixed $pkOld = null, ?string $search = null)
    {
        $this->tab = in_array($tab, ['active', 'archive', 'all'], true) ? $tab : 'active';
        $this->employeePk = $employeePk;
        $this->pkOld = $pkOld;
        $this->search = $search !== null && trim($search) !== '' ? trim($search) : null;
    }

    /**
     * All exportable data columns, in on-screen order.
     *
     * @return array<int,array{key:string,width:int,align:?string}>
     */
    private function columnDefinitions(): array
    {
        return [
            ['key' => 's_no',            'width' => 6,  'align' => 'center'],
            ['key' => 'employee_name',   'width' => 30, 'align' => null],
            ['key' => 'vehicle_pass_no', 'width' => 18, 'align' => null],
            ['key' => 'vehicle_type',    'width' => 18, 'align' => null],
            ['key' => 'vehicle_no',      'width' => 18, 'align' => null],
            ['key' => 'request_date',    'width' => 18, 'align' => 'center'],
            ['key' => 'status',          'width' => 14, 'align' => 'center'],
        ];
    }

    /** The flat list of column headings (S.No included), same order as the layout. */
    public function activeHeadings(): array
    {
        return ['S.No.', 'Employee Name', 'Vehicle Pass No', 'Vehicle Type', 'Vehicle Number', 'Requested Date', 'Status'];
    }

    public function collection()
    {
        $data = $this->pdfRows();
        $this->rowCount = $data->count();

        return $data;
    }

    /** Rows as sequential arrays (S.No prepended), reused by the PDF / Print views. */
    public function pdfRows(): Collection
    {
        return $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                array_merge(['s_no' => $index + 1], $this->mapRecord($record))
            ));
    }

    public function title(): string
    {
        return 'Vehicle Pass';
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->columnDefinitions() as $i => $col) {
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

                // --- Meta lines shown above the table (same content as Print / PDF) ---
                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => 'Vehicle Pass Request', 'style' => 'title'];

                $filterLine = $this->exportFilterLine();
                if ($filterLine !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
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
                            $sheet->getRowDimension($r)->setRowHeight(42);
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
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
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
                    $sheet->getStyle($bodyRange)->getFont()->setSize(9);
                    $sheet->getStyle($bodyRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP)
                        ->setWrapText(true);

                    foreach ($this->columnDefinitions() as $i => $col) {
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

    /** Scoped to the current user's passes, split by the tab (active/archive/all) + search. */
    private function recordsQuery()
    {
        if ($this->employeePk === null || $this->employeePk === '') {
            return collect();
        }

        $query = VehiclePassTWApply::with(['vehicleType', 'employee'])
            ->where(function ($q) {
                $q->where('veh_created_by', $this->employeePk);
                if ($this->pkOld) {
                    $q->orWhere('veh_created_by', $this->pkOld);
                }
            });

        if ($this->tab === 'archive') {
            $query->whereIn('vech_card_status', [2, 3]);
        } elseif ($this->tab !== 'all') {
            $query->where('vech_card_status', 1);
        }

        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('vehicle_no', 'like', "%{$term}%")
                    ->orWhere('vehicle_req_id', 'like', "%{$term}%")
                    ->orWhere('applicant_name', 'like', "%{$term}%")
                    ->orWhere('employee_id_card', 'like', "%{$term}%")
                    ->orWhereHas('employee', function ($e) use ($term) {
                        $e->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('vehicleType', function ($v) use ($term) {
                        $v->where('vehicle_type', 'like', "%{$term}%");
                    });
            });
        }

        return $query->orderBy('created_date', 'desc')->get();
    }

    private function mapRecord($record): array
    {
        $status = match ((int) $record->vech_card_status) {
            2       => 'Approved',
            3       => 'Rejected',
            default => 'Pending',
        };

        return [
            'employee_name'   => $record->display_name ?: '--',
            'vehicle_pass_no' => $record->vehicle_req_id ?: '--',
            'vehicle_type'    => optional($record->vehicleType)->vehicle_type ?: '--',
            'vehicle_no'      => $record->vehicle_no ?: '--',
            'request_date'    => $record->created_date ? $record->created_date->format('d-m-Y H:i') : '--',
            'status'          => $status,
        ];
    }

    /**
     * "Applied Filters: …" summary line, mirroring the export header.
     */
    private function exportFilterLine(): string
    {
        $label = match ($this->tab) {
            'archive' => 'Archived',
            'all'     => 'All',
            default   => 'Active',
        };
        $parts = ['Status: ' . $label];

        if ($this->search) {
            $parts[] = 'Search: ' . $this->search;
        }

        return 'Applied Filters:   ' . implode('   |   ', $parts);
    }
}
