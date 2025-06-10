<?php
namespace App\Exports;

use App\Models\FacultyMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FacultyExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private int $index = 0;

    public function headings(): array
    {
        return [
            'Sr. No.',
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
            'Area of Expertise',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Total number of rows (including heading)
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Center-align all cells (heading + content)
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Style header row (row 1)
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'], // Light Yellow
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'], // Black
                    ],
                ],
            ],
        ];
    }

    public function collection()
    {
        return FacultyMaster::with([
            'cityMaster:pk,city_name',
            'stateMaster:Pk,state_name',
            'countryMaster:pk,country_name',
            'districtMaster:pk,district_name',
            'facultyTypeMaster:pk,faculty_type_name',
            'facultyExpertiseMap.facultyExpertise:pk,expertise_name', 
            'facultyExpertiseMap:faculty_master_pk,faculty_expertise_pk',
            'facultyExperienceMap:pk,Years_Of_Experience,specialization,pre_Institutions,Position_hold,duration,Nature_of_Work,faculty_master_pk', 
            'facultyQualificationMap:faculty_master_pk,Degree_name,University_Institution_Name,Year_of_passing,Percentage_CGPA'
        ])->get();
    }

    public function map($faculty): array
    {
        $qualifications     = $faculty->facultyQualificationMap ?? collect([]);
        $experience         = $faculty->facultyExperienceMap ?? collect([]);
        $expertiseMap       = $faculty->facultyExpertiseMap ?? collect([]);

        $degreeNames        = $qualifications->pluck('Degree_name')->filter()->implode(', ');
        $specializations    = $qualifications->pluck('Specialization')->filter()->implode(', ');
        $universities       = $qualifications->pluck('University_Institution_Name')->filter()->implode(', ');
        $passingYears       = $qualifications->pluck('Year_of_Passing')->filter()->implode(', ');
        $cgpas              = $qualifications->pluck('Percentage_CGPA')->filter()->implode(', ');

        $yearsOfExp        = $experience->pluck('Years_Of_Experience')->filter()->implode(', ');
        $expSpecialization = $experience->pluck('Specialization')->filter()->implode(', ');
        $institutions      = $experience->pluck('pre_Institutions')->filter()->implode(', ');
        $positionsHeld     = $experience->pluck('Position_Held')->filter()->implode(', ');
        $durations         = $experience->pluck('Duration')->filter()->implode(', ');
        $natureOfWork      = $experience->pluck('Nature_of_Work')->filter()->implode(', ');

        $expertiseAreas = $expertiseMap->map(function ($mapItem) {
            return optional($mapItem->facultyExpertise)->expertise_name;
        })->filter()->implode(', ');

        $sector = $faculty->faculty_sector === 1 ? 'Government Sector' : 'Private Sector';

        return [
            ++$this->index,
            optional($faculty->facultyTypeMaster)->faculty_type_name,
            $faculty->first_name ?? '',
            $faculty->middle_name ?? '',
            $faculty->last_name ?? '',
            $faculty->full_name ?? '',
            $faculty->gender ?? '',
            $faculty->landline_no ?? '',
            $faculty->mobile_no ?? '',
            $faculty->email_id ?? '',
            $faculty->alternate_email_id ?? '',
            optional($faculty->countryMaster)->country_name,
            optional($faculty->stateMaster)->state_name,
            optional($faculty->districtMaster)->district_name,
            optional($faculty->cityMaster)->city_name,

            $degreeNames,
            $specializations,
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

            $sector,
            $expertiseAreas,
        ];
    }
}
