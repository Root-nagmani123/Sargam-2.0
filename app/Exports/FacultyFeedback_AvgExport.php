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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class FacultyFeedback_AvgExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
    protected $filters;
    protected $data;
    protected $programs;
    protected $faculties;

    public function __construct($filters, $data, $programs = [], $faculties = [])
    {
        $this->filters = $filters;
        $this->data = $data;
        $this->programs = $programs;
        $this->faculties = $faculties;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect($this->data);
    }

    public function title(): string
    {
        return 'Faculty Feedback Average';
    }

    public function headings(): array
    {
        return [
            'S.No',
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

    public function map($row): array
    {
        static $serial = 0;
        $serial++;
        
        return [
            $serial,
            $row['faculty_name'],
            $row['topic_name'],
            $row['program_name'],
            number_format($row['content_percentage'], 2),
            number_format($row['presentation_percentage'], 2),
            $row['participants'],
            Carbon::parse($row['session_date'])->format('d-M-Y'),
            $row['class_session']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Get the last row with data
        $lastRow = $sheet->getHighestRow();
        
        // Style the header row
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

        // Style the data rows
        $sheet->getStyle('A2:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Set row height for header
        $sheet->getRowDimension('1')->setRowHeight(25);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $sheet->getHighestRow();
                
                // Add title at the top
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'FACULTY FEEDBACK AVERAGE REPORT');
                
                // Add filter information
                $filterRow = $lastRow + 2;
                $sheet->mergeCells('A' . $filterRow . ':I' . $filterRow);
                $sheet->setCellValue('A' . $filterRow, 'FILTERS APPLIED:');
                $sheet->getStyle('A' . $filterRow)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A' . $filterRow)->getFont()->getColor()->setRGB('0B4F8A');
                
                // Build filter text
                $filterText = [];
                $filterText[] = "Course Type: " . ucfirst($this->filters['course_type'] ?? 'current');
                
                if (!empty($this->filters['program_name']) && isset($this->programs[$this->filters['program_name']])) {
                    $filterText[] = "Program: " . $this->programs[$this->filters['program_name']];
                }
                
                if (!empty($this->filters['faculty_name']) && isset($this->faculties[$this->filters['faculty_name']])) {
                    $filterText[] = "Faculty: " . $this->faculties[$this->filters['faculty_name']];
                }
                
                if (!empty($this->filters['from_date'])) {
                    $filterText[] = "From: " . Carbon::parse($this->filters['from_date'])->format('d-M-Y');
                }
                
                if (!empty($this->filters['to_date'])) {
                    $filterText[] = "To: " . Carbon::parse($this->filters['to_date'])->format('d-M-Y');
                }
                
                // Add filter details
                $filterDetailRow = $filterRow + 1;
                $sheet->mergeCells('A' . $filterDetailRow . ':I' . $filterDetailRow);
                $sheet->setCellValue('A' . $filterDetailRow, implode('  |  ', $filterText));
                $sheet->getStyle('A' . $filterDetailRow)->getFont()->setItalic(true);
                
                // Add summary statistics
                $summaryRow = $filterDetailRow + 2;
                $sheet->mergeCells('A' . $summaryRow . ':I' . $summaryRow);
                $sheet->setCellValue('A' . $summaryRow, 'SUMMARY STATISTICS');
                $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A' . $summaryRow)->getFont()->getColor()->setRGB('0B4F8A');
                
                // Calculate summary
                $totalSessions = $this->data->count();
                $avgContent = $this->data->avg('content_percentage');
                $avgPresentation = $this->data->avg('presentation_percentage');
                $totalParticipants = $this->data->sum('participants');
                
                // Add summary details
                $summaryDetailRow = $summaryRow + 1;
                $sheet->setCellValue('A' . $summaryDetailRow, 'Total Sessions:');
                $sheet->setCellValue('B' . $summaryDetailRow, $totalSessions);
                $sheet->setCellValue('D' . $summaryDetailRow, 'Average Content:');
                $sheet->setCellValue('E' . $summaryDetailRow, number_format($avgContent, 2) . '%');
                $sheet->setCellValue('G' . $summaryDetailRow, 'Average Presentation:');
                $sheet->setCellValue('H' . $summaryDetailRow, number_format($avgPresentation, 2) . '%');
                
                $summaryDetailRow2 = $summaryDetailRow + 1;
                $sheet->setCellValue('A' . $summaryDetailRow2, 'Total Participants:');
                $sheet->setCellValue('B' . $summaryDetailRow2, $totalParticipants);
                
                // Add export date and time
                $exportRow = $summaryDetailRow2 + 2;
                $sheet->mergeCells('A' . $exportRow . ':I' . $exportRow);
                $sheet->setCellValue('A' . $exportRow, 'Report Generated on: ' . now()->format('d-M-Y H:i:s'));
                $sheet->getStyle('A' . $exportRow)->getFont()->setItalic(true)->setSize(10);
                
                // Freeze the header row
                                $sheet->freezePane('A2');
            },
        ];
    }
}