<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class ExemptionDataExport implements FromArray, WithHeadings
{
    protected $records;
    protected $fields;
    protected $headings;

    public function __construct($records)
    {
        $this->records = $records;

        // Fields to extract from each record
        $this->fields = [
            'user_name',
            'contact_no',
            'web_auth',
            'Exemption_name',
            'medical_exemption_doc',
            'application_type',
            'created_date',
        ];

        // Column headings for Excel export
        $this->headings = [
            'User Name',
            'Contact No',
            'Web Code',
            'Exemption Category',
            'Medical Document',
            'Application Type',
            'Submitted On',
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->records as $record) {
            $row = [];

            foreach ($this->fields as $field) {
                if ($field === 'medical_exemption_doc') {
                    $value = !empty($record->$field) ? 'Available' : 'Not Available';

                } elseif ($field === 'created_date') {
                    $value = Carbon::parse($record->created_date)->format('d-m-Y');

                } elseif ($field === 'application_type') {
                    $value = match ((int) $record->application_type) {
                        1 => 'Registration',
                        2 => 'Exemption',
                        default => 'Unknown',
                    };

                } else {
                    $value = $record->$field ?? 'N/A';
                }

                $row[] = $value;
            }

            $data[] = $row;
        }

        return $data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
