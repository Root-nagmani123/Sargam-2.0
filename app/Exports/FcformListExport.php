<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class FcformListExport implements FromCollection
{
    protected $records, $fields;

    public function __construct($records, $fields)
    {
        $this->records = $records;
        $this->fields = $fields;
    }

    public function collection()
    {
        return collect($this->records)->map(function ($record) {
            return collect($this->fields)->mapWithKeys(function ($field) use ($record) {
                return [$field => $record->$field ?? ''];
            });
        });
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
