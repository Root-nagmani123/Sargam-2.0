<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExemptionDataExport implements FromArray, WithHeadings
{
    protected $records;
    protected $fields;
    protected $headings;

    public function __construct($records)
    {
        $this->records = $records;

        // Set the fields you want to export (these should match your DB column names or aliases)
        $this->fields = [
            'user_name',
            'contact_no',
            'web_auth',
            'Exemption_short_name',
            'medical_exemption_doc',
            'created_at',
        ];

        // Optional: Set readable column headings
        $this->headings = [
            'User Name',
            'Contact No',
            'Web Code',
            'Exemption Category',
            'Medical Document',
            'Submitted On',
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->records as $record) {
            $row = [];
            foreach ($this->fields as $field) {
                $value = $record->$field ?? '';
                $row[] = !empty($value) ? $value : 'N/A';
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
