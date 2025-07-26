<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;



class FcformListExport implements FromArray, WithHeadings
{
    protected $records;
    protected $fields;

    public function __construct($records, $fields)
    {
        $this->records = $records;
        $this->fields = $fields;
    }

    // Return the actual data rows
    // public function array(): array
    // {
    //     $data = [];

    //     foreach ($this->records as $record) {
    //         $row = [];
    //         foreach ($this->fields as $field) {
    //             $row[] = $record->$field ?? '';
    //         }
    //         $data[] = $row;
    //     }

    //     return $data;
    // }

      public function array(): array
    {
        $data = [];

        foreach ($this->records as $record) {
            $row = [];

            foreach ($this->fields as $field) {
                // Use property_exists or check for object/array compatibility
                if (is_object($record)) {
                    $row[] = $record->{$field} ?? '';
                } elseif (is_array($record)) {
                    $row[] = $record[$field] ?? '';
                } else {
                    $row[] = '';
                }
            }

            $data[] = $row;
        }

        return $data;
    }

    // Return the column headers
    public function headings(): array
    {
        return $this->fields;
    }
}
