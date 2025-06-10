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
            'first_name', 'middle_name', 'last_name', 'email',
            'contact_no', 'rank', 'web_auth'
        )->get();
    }

    public function headings(): array
    {
        return [
            'First Name', 'Middle Name', 'Last Name',
            'Email', 'Contact No', 'Rank', 'Web Auth',
        ];
    }
}
