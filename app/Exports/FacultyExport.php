<?php
namespace App\Exports;

use App\Models\FacultyMaster;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Faculty -> .xlsx, full detail (34 columns: personal, qualification,
 * experience, bank, expertise).
 *
 * This is the DEEP dump, kept alongside the grid-shaped exports the listing
 * page serves through ExportsMasterGrid — it is the only way to get
 * qualification / experience / bank data out of the module, so it must not be
 * replaced by a grid export.
 *
 * The branded LBSNAA header band matches MasterGridExport and the print/PDF
 * views, so a downloaded workbook states which institution and report it is
 * from instead of opening on a bare heading row.
 */
class FacultyExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Rows the branded header occupies before the data table starts. */
    private const HEADER_ROWS = 5;

    private int $index = 0;
    private array $sectorMap = [];
    private array $serviceMap = [];
    private int $rowCount = 0;
    private string $exportDate;

    public function __construct()
    {
        $this->exportDate = now()->format('d-m-Y h:i A');

        $this->sectorMap = DB::table('faculty_sector_master')
            ->pluck('name', 'pk')
            ->toArray();

        $this->serviceMap = DB::table('service_master')
            ->pluck('service_name', 'pk')
            ->toArray();
    }

    public function title(): string
    {
        return 'Faculty';
    }

    public function startCell(): string
    {
        return 'A' . (self::HEADER_ROWS + 1);
    }

    public function headings(): array
    {
        return [
            'Sr. No.',
            'Faculty Code',
            'Faculty Type',
            'First Name',
            'Middle Name',
            'Last Name',
            'Full Name',
            'Gender',
            'Landline Number',
            'Mobile Number',
            'Email',
            'Alternate Email',
            'Country',
            'State',
            'District',
            'City',
            'Qualification',
            'Specialization',
            'University',
            'Year of Passing',
            'Percentage/CGPA',
            'Years of Experience',
            'Area of Specialization',
            'Previous Institutions',
            'Position Held',
            'Duration',
            'Nature of Work',
            'Bank Name',
            'Account Number',
            'IFSC Code',
            'PAN Number',
            'Current Sector',
            'Service',
            'Area of Expertise',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = Coordinate::stringFromColumnIndex(count($this->headings()));
                $dataHeaderRow = self::HEADER_ROWS + 1;
                $lastRow = max($dataHeaderRow, $sheet->getHighestRow());

                // -- Branded header band (same recipe as MasterGridExport) --
                $sheet->mergeCells("A1:{$last}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells("A2:{$last}2");
                $sheet->setCellValue('A2', 'FACULTY - FULL DETAILS');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$last}3");
                $sheet->setCellValue('A3', 'Generated: ' . $this->exportDate);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$last}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->rowCount);
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F8']],
                ]);

                $sheet->getStyle("A1:{$last}4")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(6);

                // -- Column headings: navy band, matching the other exports --
                $sheet->getStyle("A{$dataHeaderRow}:{$last}{$dataHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                ]);
                $sheet->getRowDimension($dataHeaderRow)->setRowHeight(24);

                if ($lastRow > $dataHeaderRow) {
                    $sheet->getStyle("A{$dataHeaderRow}:{$last}{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    ]);

                    $sheet->getStyle('A' . ($dataHeaderRow + 1) . ":{$last}{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);

                    // Zebra striping, matching the print/PDF output.
                    for ($r = $dataHeaderRow + 1; $r <= $lastRow; $r++) {
                        if (($r - $dataHeaderRow) % 2 === 0) {
                            $sheet->getStyle("A{$r}:{$last}{$r}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                            ]);
                        }
                    }
                }

                // -- Logo, floated over the header band --
                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (is_file($logoPath) && is_readable($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA');
                    $drawing->setDescription('LBSNAA');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(46);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(6);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                // 34 columns — freeze the band and the headings, and the S. No.
                // column, or scrolling right loses which faculty a row is.
                $sheet->freezePane('B' . ($dataHeaderRow + 1));
            },
        ];
    }

    public function collection()
    {
        $rows = FacultyMaster::with([
            'cityMaster:pk,city_name',
            'stateMaster:Pk,state_name',
            'countryMaster:pk,country_name',
            'districtMaster:pk,district_name',
            'facultyTypeMaster:pk,faculty_type_name',
            'facultyExpertiseMap.facultyExpertise:pk,expertise_name',
            'facultyExpertiseMap:faculty_master_pk,faculty_expertise_pk',
            'facultyExperienceMap:pk,Years_Of_Experience,specialization,pre_Institutions,Position_hold,duration,Nature_of_Work,faculty_master_pk',
            'facultyQualificationMap:faculty_master_pk,Degree_name,University_Institution_Name,Year_of_passing,Percentage_CGPA',
        ])->get();

        // The header band names the record count, and AfterSheet runs after the
        // rows are written — so capture it here rather than re-querying.
        $this->rowCount = $rows->count();

        return $rows;
    }

    public function map($faculty): array
    {
        $qualifications = $faculty->facultyQualificationMap ?? collect([]);
        $experience     = $faculty->facultyExperienceMap ?? collect([]);
        $expertiseMap   = $faculty->facultyExpertiseMap ?? collect([]);

        $degreeNames    = $qualifications->pluck('Degree_name')->filter()->implode(', ');
        $universities   = $qualifications->pluck('University_Institution_Name')->filter()->implode(', ');
        $passingYears   = $qualifications->pluck('Year_of_passing')->filter()->implode(', ');
        $cgpas          = $qualifications->pluck('Percentage_CGPA')->filter()->implode(', ');

        $yearsOfExp        = $experience->pluck('Years_Of_Experience')->filter()->implode(', ');
        $expSpecialization = $experience->pluck('specialization')->filter()->implode(', ');
        $institutions      = $experience->pluck('pre_Institutions')->filter()->implode(', ');
        $positionsHeld     = $experience->pluck('Position_hold')->filter()->implode(', ');
        $durations         = $experience->pluck('duration')->filter()->implode(', ');
        $natureOfWork      = $experience->pluck('Nature_of_Work')->filter()->implode(', ');

        $expertiseAreas = $expertiseMap->map(function ($mapItem) {
            return optional($mapItem->facultyExpertise)->expertise_name;
        })->filter()->implode(', ');

        $sectorName  = $this->sectorMap[$faculty->faculty_sector] ?? '-';
        $serviceName = $this->serviceMap[$faculty->service_master_pk] ?? '-';

        return [
            ++$this->index,
            $faculty->faculty_code ?? '',
            optional($faculty->facultyTypeMaster)->faculty_type_name ?? '',
            $faculty->first_name ?? '',
            $faculty->middle_name ?? '',
            $faculty->last_name ?? '',
            $faculty->full_name ?? '',
            $faculty->gender ?? '',
            $faculty->landline_no ?? '',
            $faculty->mobile_no ?? '',
            $faculty->email_id ?? '',
            $faculty->alternate_email_id ?? '',
            optional($faculty->countryMaster)->country_name ?? '',
            optional($faculty->stateMaster)->state_name ?? '',
            optional($faculty->districtMaster)->district_name ?? '',
            optional($faculty->cityMaster)->city_name ?? '',

            $degreeNames,
            '',                 // Specialization column (not in qualification table)
            $universities,
            $passingYears,
            $cgpas,

            $yearsOfExp,
            $expSpecialization,
            $institutions,
            $positionsHeld,
            $durations,
            $natureOfWork,

            $faculty->bank_name ?? '',
            $faculty->Account_No ?? '',
            $faculty->IFSC_Code ?? '',
            $faculty->PAN_No ?? '',

            $sectorName,
            $serviceName,
            $expertiseAreas,
        ];
    }
}
