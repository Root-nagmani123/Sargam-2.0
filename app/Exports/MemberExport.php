<?php

namespace App\Exports;

use App\Models\{EmployeeMaster, City, State, Country};
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
class MemberExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $index = 0;
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

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
        return EmployeeMaster::get();
    }

    public function headings(): array
    {

        return [
            'ID',
            'Title',
            'First Name',
            'Middle Name',
            'Last Name',
            'Father Name',
            'DOB',
            'Gender',
            'Marital Status',
            'Nationality',
            'Height',
            'Email',
            'Mobile',
            'Alternate Mobile',
            'Landline Number',
            'Current Address',
            'Permanent Address',
            'City',
            'State',
            'Country',
            'Zipcode',
            'Employee ID',
            'Designation',
            'Department',
            'Reporting To',
            'Alternate Reporting To',
            'Experience',
            'Date of Joining',
            'Govt DOJ',
            'Initial Leaving Date',
            'Appraisal Date',
            'Payroll Date',
            'Payroll',
            'Employee Type',
            'Finance Book Code',
            'Official Email',
            'Aadhar No',
            'PAN No',
            'Passport No',
            'Employee Govt ID',
            'Employee Group',
            'Home Town Details',
            'Thumb Path',
            'Signature Path',
            'Status',
            'Created By',
            'Created Date',
            'Modified By',
            'Modified Date',
            // 'Roles'
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->index,
            $row->title && isset(EmployeeMaster::title[$row->title]) ? EmployeeMaster::title[$row->title] : '',
            $row->first_name ?? '',
            $row->middle_name ?? '',
            $row->last_name ?? '',
            $row->father_name ?? '',
            $this->date($row->dob),
            isset(EmployeeMaster::gender[$row->gender]) ? EmployeeMaster::gender[$row->gender] : '',
            isset(EmployeeMaster::maritalStatus[$row->marital_status]) ? EmployeeMaster::maritalStatus[$row->marital_status] : '',
            $row->nationality ?? '',
            $row->height ?? '',
            $row->email ?? '',
            $row->mobile ?? '',
            $row->emergency_contact_no ?? '',
            $row->landline_contact_no ?? '',
            $row->current_address ?? '',
            $row->permanent_address ?? '',
            optional(City::find($row->city))->city_name ?? '',
            optional(State::find($row->state_master_pk))->state_name ?? '',
            optional(Country::find($row->country_master_pk))->country_name ?? '',
            $row->zipcode ?? '',
            $row->emp_id ?? '',
            optional($row->designation)->designation_name ?? '',
            optional($row->department)->department_name ?? '',
            $row->reporting_to_employee_pk ?? '',
            $row->alternate_reporting_to_emp_pk ?? '',
            $row->experience ?? '',
            $this->date($row->doj),
            $this->date($row->govt_doj),
            $this->date($row->initial_leaving_date),
            $this->date($row->appraisal_date),
            $this->date($row->payroll_date),
            $row->payroll ?? '',
            optional($row->employeeType)->category_type_name ?? '',
            $row->finance_bookEntityCode ?? '',
            $row->officalemail ?? '',
            $row->aadar_no ?? '',
            $row->pan_no ?? '',
            $row->passport_no ?? '',
            $row->emp_gov_id ?? '',
            optional($row->employeeGroup)->emp_group_name ?? '',
            $row->home_town_details ?? '',
            $row->thumbPath ?? '',
            $row->sigPath ?? '',
            $row->status ?? '',
            $row->created_by ?? '',
            $this->date($row->created_date, 'd-m-Y H:i'),
            $row->modified_by ?? '',
            $this->date($row->modified_date, 'd-m-Y H:i'),
        ];

    }

    /**
     * A date column as DD-MM-YYYY (with the time for audit timestamps), the
     * format every member screen uses. Empty and zero dates stay blank, and a
     * value Carbon cannot read is passed through rather than failing the export.
     */
    private function date($value, string $format = 'd-m-Y'): string
    {
        if (blank($value) || str_starts_with((string) $value, '0000-00-00')) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
