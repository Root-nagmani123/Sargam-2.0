<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class FacultyFeedback_AvgExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    ShouldAutoSize, 
    WithTitle, 
    WithEvents
{
    protected $filters;
    protected $data;
    protected $programs;
    protected $faculties;

    public function __construct($filters, $data, $programs = [], $faculties = [])
    {
        $this->filters = $filters;
        $this->data = collect($data)->values();
        $this->programs = $programs;
        $this->faculties = $faculties;
    }

    /**
     * Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Faculty Feedback Average';
    }

    /**
     * Headings (same as PDF)
     */
    public function headings(): array
    {
        return [
            'Sl No.',
            'Faculty Name',
            'Topic',
            'Program',
            'Content (%)',
            'Presentation (%)',
            'Participants',
            'Session Date',
            'Session Time'
        ];
    }

    /**
     * Map data
     */
    public function map($row): array
    {
        static $i = 0;
        $i++;

        return [
            $i,
            $row['faculty_name'] ?? '',
            $row['topic_name'] ?? '',
            $row['program_name'] ?? '',
            number_format($row['content_percentage'] ?? 0, 2) . '%',
            number_format($row['presentation_percentage'] ?? 0, 2) . '%',
            $row['participants'] ?? 0,
            !empty($row['session_date']) 
                ? Carbon::parse($row['session_date'])->format('d M Y') 
                : '',
            $row['class_session'] ?? ''
        ];
    }

    /**
     * Styles
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Header style
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B4F8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Borders + vertical alignment
        $sheet->getStyle('A1:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Center align Participants column
        $sheet->getStyle('G2:G' . $lastRow)
              ->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Alternate row color (like PDF)
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA'],
                    ],
                ]);
            }
        }

        return [];
    }

    /**
     * Events (CLEANED - no summary, no filters)
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet;

                // ✅ Keep only freeze header
                $sheet->freezePane('A2');

            },
        ];
    }
}