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
            'duplication' => EmployeeIDCardRequest::whereIn('request_for', ['Replacement', 'Duplication'])->latest(),
            'extension' => EmployeeIDCardRequest::where('request_for', 'Extension')->latest(),
            'all' => EmployeeIDCardRequest::withTrashed()->latest(),
            default => EmployeeIDCardRequest::latest(),
        };

        $data = $query->get();

        return $data->map(function ($record, $index) {
            return [
                $index + 1,
                $record->created_at ? $record->created_at->format('d/m/Y') : '--',
                $record->name ?? '--',
                $record->designation ?? '--',
                $record->card_type ?? '--',
                $record->request_for ?? '--',
                in_array($record->request_for, ['Replacement', 'Duplication']) ? ($record->duplication_reason ?? '--') : '--',
                $record->request_for === 'Extension' ? ($record->id_card_valid_upto ?? '--') : '--',
                $record->id_card_valid_upto ?? '--',
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
            'Card Type',
            'Request For',
            'Duplication',
            'Extension',
            'Valid Upto',
            'Status',
        ];
    }
}
