<?php

namespace App\Exports\FC;

use App\Models\FC\FcForm;
use App\Services\FC\FcDescriptiveDataQuery;
use App\Support\FC\FcUploadUrl;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Excel export of the Descriptive Data report.
 *
 * Layout follows the academy's existing export format (see App\Exports\FeedbackDatabaseExport,
 * the Faculty > Feedback Database export): five banner rows — crest + academy name, report
 * title, the filters this file was produced under, the record count, a spacer — then a navy
 * heading row and bordered data with the header frozen.
 *
 * Differences from that reference, both deliberate:
 *  - Column letters come from Coordinate::stringFromColumnIndex(), not chr(64 + n). This
 *    report can emit 28 columns; chr() produces '\' past column Z and corrupts every range
 *    it is used in. The reference caps out at 10 columns so never hits it.
 *  - FromQuery + WithChunkReading (G3) rather than FromArray, so a full course is streamed
 *    500 rows at a time instead of being held in memory. It runs the SAME query as the
 *    on-screen table, filters included, so the file matches what the admin was looking at.
 */
class FcDescriptiveDataExport implements FromQuery, WithChunkReading, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const CHUNK = 1000;

    /**
     * Column width, in characters. Fixed rather than auto-sized: ShouldAutoSize measures
     * every cell in every column to derive a width, which measured 30.8s vs 12.4s on a
     * 20,000-row x 28-column sheet — 60% of the entire export spent on column widths.
     */
    private const COLUMN_WIDTH = 20;

    private const COLUMN_WIDTH_NARROW = 8;   // S.No.

    private const COLUMN_WIDTH_WIDE = 46;    // address / file URLs

    /** Banner rows above the heading row; the table therefore starts at row 6. */
    private const BANNER_ROWS = 5;

    /**
     * @param  array<string,array<string,mixed>>  $fields
     */
    public function __construct(
        private FcForm $form,
        private array $fields,
        private Request $request,
        /** Username is toggleable in the report's Columns menu like any other column. */
        private bool $includeUsername = true
    ) {
    }

    public function title(): string
    {
        return 'Descriptive Data';
    }

    public function startCell(): string
    {
        return 'A'.(self::BANNER_ROWS + 1);
    }

    public function query()
    {
        $service = app(FcDescriptiveDataQuery::class);
        $query = $service->build($this->form, $this->fields);
        $service->applyFilters($query, $this->fields, $this->request);

        return $query->orderBy('s1.first_name');
    }

    public function chunkSize(): int
    {
        return self::CHUNK;
    }

    public function headings(): array
    {
        $headings = ['S.No.'];
        if ($this->includeUsername) {
            $headings[] = 'Username';
        }
        foreach ($this->fields as $field) {
            $headings[] = $field['label'];
        }

        return $headings;
    }

    /**
     * Running row number for the S.No. column.
     *
     * An instance property, NOT a `static` local inside map(): a static local is bound to the
     * method, so a second export in the same PHP process continues the first one's numbering
     * (verified — the second file started at 12). Unreachable under PHP-FPM, where each
     * request is a fresh process, but real the moment this export is queued or runs under a
     * persistent worker.
     */
    private int $serial = 0;

    /**
     * @param  object  $row
     */
    public function map($row): array
    {
        $this->serial++;

        $out = [$this->serial];
        if ($this->includeUsername) {
            $out[] = (string) ($row->login_username ?? '');
        }

        foreach ($this->fields as $key => $field) {
            $value = $row->{$key} ?? null;

            if ($field['type'] === 'file') {
                // Plain text here; registerEvents() attaches the real hyperlink afterwards.
                // A spreadsheet hyperlink is cell metadata, not content — writing the URL as
                // a string alone leaves it inert.
                $out[] = FcUploadUrl::for($value);
                continue;
            }

            if ($field['type'] === 'date') {
                $out[] = $this->formatDate($value);
                continue;
            }

            $out[] = trim((string) $value);
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));
                $headingRow = self::BANNER_ROWS + 1;
                $firstDataRow = $headingRow + 1;
                $lastRow = $sheet->getHighestRow();
                $recordCount = max(0, $lastRow - $headingRow);

                $this->setColumnWidths($sheet);
                $this->writeBanner($sheet, $lastCol, $recordCount);
                $this->styleTable($sheet, $lastCol, $headingRow, $firstDataRow, $lastRow);
                $this->linkFileColumns($sheet, $firstDataRow, $lastRow);

                $sheet->freezePane('A'.$firstDataRow);
            },
        ];
    }

    /**
     * Explicit widths, in place of ShouldAutoSize (see COLUMN_WIDTH). Set once per column
     * rather than derived per cell, so this costs nothing as the row count grows.
     */
    private function setColumnWidths($sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(self::COLUMN_WIDTH_NARROW);

        $index = 1;
        if ($this->includeUsername) {
            $index++;
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(self::COLUMN_WIDTH);
        }

        foreach ($this->fields as $field) {
            $index++;
            $wide = $field['type'] === 'address';   // file cells now show a short name
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))
                ->setWidth($wide ? self::COLUMN_WIDTH_WIDE : self::COLUMN_WIDTH);
        }
    }

    /**
     * Crest + academy name, report title, the filters used, the record count.
     *
     * Left-aligned, unlike the 10-column reference export which centres these. Centring text
     * merged across THIS report's 28 columns puts it around column N — invisible when the
     * file is opened and off page 1 when printed. The full-width merge and the outline box
     * are kept, so the banner still reads as one block across the table.
     */
    private function writeBanner($sheet, string $lastCol, int $recordCount): void
    {
        // Indent past the crest, which is anchored over column A.
        $bannerAlign = ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 8];

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
            'alignment' => $bannerAlign + ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'DESCRIPTIVE DATA REPORT');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
            'alignment' => $bannerAlign,
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue('A3', $this->filterSummary());
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
            'alignment' => $bannerAlign,
        ]);

        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->setCellValue('A4', 'Total records: '.$recordCount);
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
            'alignment' => $bannerAlign,
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
        ]);

        $sheet->getRowDimension(5)->setRowHeight(6);

        $sheet->getStyle("A1:{$lastCol}4")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
        ]);

        $logoPath = public_path('images/lbsnaa_logo.jpg');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('LBSNAA Logo');
            $drawing->setDescription('LBSNAA Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($sheet);
        }
    }

    private function styleTable($sheet, string $lastCol, int $headingRow, int $firstDataRow, int $lastRow): void
    {
        $sheet->getStyle("A{$headingRow}:{$lastCol}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '002244']]],
        ]);

        if ($lastRow < $firstDataRow) {
            return;
        }

        $sheet->getStyle("A{$firstDataRow}:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);

        // S.No. plus the short codes read better centred; free text stays left-aligned.
        foreach ($this->centredColumnLetters() as $letter) {
            $sheet->getStyle("{$letter}{$headingRow}:{$letter}{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    /**
     * Photo / signature cells become clickable links showing the FILE NAME, not the URL.
     *
     * map() writes the full URL into the cell because that is the only place the value is
     * known; here it is turned into the real hyperlink and the visible text is replaced with
     * the file name — matching the PDF export. Leaving the URL as the text meant every cell
     * displayed a 90-character opaque token, which is unreadable and destroys the column
     * width. The name is recovered by decoding the token, so there is still exactly one
     * source of truth for the link.
     */
    private function linkFileColumns($sheet, int $firstDataRow, int $lastRow): void
    {
        $columns = $this->columnLettersFor(fn ($field) => $field['type'] === 'file');
        if ($columns === [] || $lastRow < $firstDataRow) {
            return;
        }

        foreach ($columns as $letter) {
            for ($row = $firstDataRow; $row <= $lastRow; $row++) {
                $cell = $sheet->getCell($letter.$row);
                $url = (string) $cell->getValue();
                if ($url === '' || ! str_starts_with($url, 'http')) {
                    continue;
                }

                $cell->setValueExplicit(
                    $this->fileNameFromUrl($url),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
                $cell->getHyperlink()->setUrl($url);
                $cell->getHyperlink()->setTooltip('Open file');
            }

            // Styled once per column range, not per cell — a style object per row is what
            // makes large exports crawl.
            $sheet->getStyle($letter.$firstDataRow.':'.$letter.$lastRow)->applyFromArray([
                'font' => ['color' => ['rgb' => '0563C1'], 'underline' => Font::UNDERLINE_SINGLE],
            ]);
        }
    }

    /**
     * The stored file name behind a token URL, for display. Falls back to the URL itself if
     * the token cannot be read, so a cell is never left blank.
     */
    private function fileNameFromUrl(string $url): string
    {
        $query = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        $path = FcUploadUrl::decode($params[FcUploadUrl::TOKEN_PARAM] ?? null);

        return $path !== null ? basename($path) : $url;
    }

    /** @return list<string> */
    private function centredColumnLetters(): array
    {
        $letters = ['A']; // S.No.


        return array_merge($letters, $this->columnLettersFor(
            fn ($field) => in_array($field['type'], ['date', 'file'], true)
        ));
    }

    /**
     * Letters for the field columns matching a predicate. Offset past the leading S.No.
     * column and, when shown, Username — so it stays correct however many fields a course
     * maps and whichever columns the admin left ticked.
     *
     * @return list<string>
     */
    private function columnLettersFor(callable $matches): array
    {
        $letters = [];
        $index = $this->includeUsername ? 2 : 1;

        foreach ($this->fields as $field) {
            $index++;
            if ($matches($field)) {
                $letters[] = Coordinate::stringFromColumnIndex($index);
            }
        }

        return $letters;
    }

    /** The filters this file was produced under, so a saved copy stays self-describing. */
    private function filterSummary(): string
    {
        $parts = ['Course: '.($this->form->form_name ?: '—')];

        foreach ($this->fields as $key => $field) {
            $filter = $field['filter'] ?? null;
            if ($filter === null) {
                continue;
            }

            if ($filter === 'date_range') {
                $from = trim((string) $this->request->input('f_'.$key.'_from', ''));
                $to = trim((string) $this->request->input('f_'.$key.'_to', ''));
                if ($from !== '' || $to !== '') {
                    $parts[] = $field['label'].': '.($from ?: 'any').' to '.($to ?: 'any');
                }
                continue;
            }

            $value = trim((string) $this->request->input('f_'.$key, ''));
            if ($value === '') {
                continue;
            }

            // A lookup filter holds an id; show the label the admin picked, not the number.
            $parts[] = $field['label'].': '.$this->lookupLabel($field, $value);
        }

        $search = trim((string) $this->request->input('search_term', ''));
        if ($search !== '') {
            $parts[] = 'Search: "'.$search.'"';
        }

        return implode('  |  ', $parts).'  |  Generated: '.now()->format('d/m/Y H:i');
    }

    private function lookupLabel(array $field, string $value): string
    {
        if (! isset($field['lookup'])) {
            return $value;
        }

        $lk = $field['lookup'];
        $label = \Illuminate\Support\Facades\DB::table($lk['table'])
            ->where($lk['value'], $value)
            ->value($lk['label']);

        return $label !== null ? (string) $label : $value;
    }

    private function formatDate($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000')) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
