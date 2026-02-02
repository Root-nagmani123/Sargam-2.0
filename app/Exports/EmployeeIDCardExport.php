<?php

namespace App\Exports;

use App\Models\EmployeeIDCardRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeIDCardExport implements FromCollection, WithHeadings
{
    protected string $tab;

    public function __construct(string $tab = 'active')
    {
        $this->tab = $tab;
    }

    public function collection(): Collection
    {
        $query = match ($this->tab) {
            'archive' => EmployeeIDCardRequest::onlyTrashed()->latest(),
            'all' => EmployeeIDCardRequest::withTrashed()->latest(),
            default => EmployeeIDCardRequest::latest(),
        };

        $data = $query->get();

        return $data->map(function ($record, $index) {
            $validFrom = optional($record->valid_from)->format('d/m/Y') ?? '--';
            $validTo = optional($record->valid_to)->format('d/m/Y') ?? $record->id_card_valid_upto ?? '--';
            return [
                $index + 1,
                $record->created_at ? $record->created_at->format('d/m/Y') : '--',
                $record->name ?? '--',
                $record->designation ?? '--',
                $record->id_card_no ?? $record->id ?? '--',
                $record->card_type ?? '--',
                $record->complete ?? '--',
                $validFrom . ' - ' . $validTo,
                $record->status ?? '--',
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
            'ID Card No.',
            'ID Type',
            'Complete',
            'Valid From - To',
            'Status',
        ];
    }
}
