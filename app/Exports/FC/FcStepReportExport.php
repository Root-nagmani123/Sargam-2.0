<?php

namespace App\Exports\FC;

use App\Http\Controllers\FC\StepReportController;
use App\Models\FC\FcForm;
use App\Services\FC\FcStepReport;
use App\Support\FC\FcUploadUrl;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Any {@see FcStepReport} as .xlsx.
 *
 * FromQuery + WithChunkReading so rows stream out of the database in batches rather than being
 * materialised in one go — these steps' free-text fields make each row far heavier than a
 * typical report's, so holding the whole set would cost real memory on a large course.
 */
class FcStepReportExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const CHUNK = 500;

    private int $serial = 0;

    /**
     * Upload URLs to attach as cell hyperlinks, as [sheetRow][columnIndex] => url.
     *
     * Collected during map() because that is the only place the stored path is in hand, and
     * applied in registerEvents() because that is the only place the sheet is.
     *
     * At the 5,000-row cap a three-upload report (Bank Details) holds roughly 4.6 MB of URLs
     * here — each token is ~320 bytes — alongside PhpSpreadsheet's own cell objects. That is
     * part of what the cap is sized against.
     *
     * @var array<int,array<int,string>>
     */
    private array $links = [];

    /**
     * Column key => 1-based sheet column index, built once.
     *
     * Previously recomputed with array_search(array_keys(...)) inside the per-row, per-column
     * loop — 15,000 rebuilds of the key array on a capped three-upload export.
     *
     * @var array<string,int>
     */
    private array $columnIndex = [];

    /** @param array<string,array<string,mixed>> $columns */
    public function __construct(
        private FcStepReport $report,
        private FcForm $form,
        private array $columns,
        private Request $request
    ) {
        // +2: one for the leading S.No. column, one to make it 1-based for Coordinate.
        $this->columnIndex = array_flip(array_keys($this->columns));
        foreach ($this->columnIndex as $key => $i) {
            $this->columnIndex[$key] = $i + 2;
        }
    }

    public function title(): string
    {
        // Excel rejects a sheet name over 31 chars or containing []:*?/\
        return mb_substr(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $this->report->title()), 0, 31);
    }

    public function query()
    {
        // Select the ticked columns, filter on all of them — same contract as the table.
        $query = $this->report->build($this->form, $this->columns);
        $this->report->applyFilters($query, $this->request);

        return $query->orderBy('s1.first_name');
    }

    public function chunkSize(): int
    {
        return self::CHUNK;
    }

    public function headings(): array
    {
        $headings = ['S.No.'];
        foreach ($this->columns as $column) {
            $headings[] = $column['label'];
        }

        return $headings;
    }

    public function map($row): array
    {
        $this->serial++;
        $line = [$this->serial];

        foreach ($this->columns as $key => $column) {
            $value = trim((string) ($row->{$key} ?? ''));

            if (! empty($column['file'])) {
                // The FILENAME is written, not the address. The upload URL carries a ~300
                // character encrypted token, which made the column unreadable and pushed every
                // other value off screen. The address is not lost — it is attached to the cell
                // as a hyperlink in registerEvents(), so the name stays clickable, and it
                // matches the file name inside the Documents ZIP so rows can be tied to it.
                if ($value === '') {
                    $line[] = '';
                    continue;
                }

                $line[] = basename($value);
                // Sheet row = serial + 1 for the heading row.
                $this->links[$this->serial + 1][$this->columnIndex[$key]]
                    = FcUploadUrl::for($value, StepReportController::FILE_PATH);
                continue;
            }

            // Free text is written out in full — the truncation on screen is a readability
            // measure for the grid, not a limit on the data.
            $line[] = $value !== '' ? $value : (! empty($column['long']) ? 'Not submitted' : '');
        }

        return $line;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Coordinate, not chr(): past 26 columns chr() runs off the end of the alphabet.
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->columns) + 1);
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '004A93']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->freezePane('A2');
                $sheet->getColumnDimension('A')->setWidth(7);

                $index = 2;   // column B onwards
                foreach ($this->columns as $key => $column) {
                    $letter = Coordinate::stringFromColumnIndex($index);
                    if (! empty($column['long'])) {
                        // Wide + wrapped: the point of the sheet is that the text is readable
                        // without clicking into each cell.
                        $sheet->getColumnDimension($letter)->setWidth(70);
                        $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")
                            ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                    } elseif (! empty($column['file'])) {
                        // Sized for a file name now that the cell holds one — the address rides
                        // along as a hyperlink rather than as visible text.
                        $sheet->getColumnDimension($letter)->setWidth(30);
                    } else {
                        $sheet->getColumnDimension($letter)->setWidth(match ($key) {
                            'display_name' => 28,
                            'email' => 30,
                            'login_username' => 18,
                            'adjustment_type' => 30,
                            default => 14,
                        });
                    }
                    $index++;
                }

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'D0D5DD']]],
                ]);

                // Attach the upload addresses to the file-name cells, styled the way a reader
                // expects a link to look. Applied last so the border pass above cannot reset it.
                foreach ($this->links as $rowNumber => $cells) {
                    foreach ($cells as $columnIndex => $url) {
                        $coordinate = Coordinate::stringFromColumnIndex($columnIndex).$rowNumber;
                        $sheet->getCell($coordinate)->getHyperlink()->setUrl($url);
                        $sheet->getStyle($coordinate)->applyFromArray([
                            'font' => ['color' => ['rgb' => '004A93'], 'underline' => 'single'],
                        ]);
                    }
                }
            },
        ];
    }
}
