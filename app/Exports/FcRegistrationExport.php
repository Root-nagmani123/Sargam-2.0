<?php

namespace App\Exports;

use App\Models\FcRegistrationMaster;  // Model
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FcRegistrationExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return FcRegistrationMaster::select(
          'service_master_pk','schema_id', 'display_name', 'first_name', 'middle_name', 'last_name', 'email',
            'contact_no', 'rank', 'web_auth', 'exam_year',
        )->get();
    }

    public function headings(): array
    {
        return [
            'Service Master PK', 'Schema ID', 'Display Name', 'First Name', 'Middle Name', 'Last Name',
            'Email', 'Contact No', 'Rank', 'Web Auth', 'Exam Year',
        ];
    }
}
