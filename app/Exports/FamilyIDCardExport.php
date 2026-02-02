<?php

namespace App\Exports;

use App\Models\FamilyIDCardRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FamilyIDCardExport implements FromCollection, WithHeadings
{
    protected string $tab;

    public function __construct(string $tab = 'active')
    {
        $this->tab = $tab;
    }

    public function collection(): Collection
    {
        $query = match ($this->tab) {
            'archive' => FamilyIDCardRequest::onlyTrashed()->latest(),
            'all' => FamilyIDCardRequest::withTrashed()->latest(),
            default => FamilyIDCardRequest::latest(),
        };

        $data = $query->get();

        return $data->map(function ($record, $index) {
            return [
                $index + 1,
                $record->created_at ? $record->created_at->format('d/m/Y') : '--',
                $record->employee_name ?? $record->employee_id ?? '--',
                $record->designation ?? '--',
                $record->section ?? '--',
                $record->name ?? '--',
                1,
                $record->card_type ?? '--',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'S.No.',
            'Request Date',
            'Employee Name',
            'Designation',
            'Department',
            'Member Name',
            'No. of Member ID Card',
            'ID Type',
        ];
    }
}
