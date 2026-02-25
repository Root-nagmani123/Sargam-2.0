<?php

namespace App\Exports;

use App\Models\EmployeeIDCardRequest;
use App\Models\SecurityParmIdApply;
use App\Support\IdCardSecurityMapper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeIDCardExport implements FromCollection, WithHeadings
{
    protected string $tab;
    protected bool $useSecurity;

    public function __construct(string $tab = 'active', bool $useSecurity = false)
    {
        $this->tab = $tab;
        $this->useSecurity = $useSecurity;
    }

    public function collection(): Collection
    {
        if ($this->useSecurity) {
            $query = match ($this->tab) {
                'archive' => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED])->orderBy('pk', 'desc'),
                'duplication', 'extension' => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->orderBy('pk', 'desc'),
                'all' => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->orderBy('pk', 'desc'),
                default => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->orderBy('pk', 'desc'),
            };
            $data = $query->get()->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        } else {
            $query = match ($this->tab) {
                'archive' => EmployeeIDCardRequest::onlyTrashed()->latest(),
                'duplication' => EmployeeIDCardRequest::whereIn('request_for', ['Replacement', 'Duplication'])->latest(),
                'extension' => EmployeeIDCardRequest::where('request_for', 'Extension')->latest(),
                'all' => EmployeeIDCardRequest::withTrashed()->latest(),
                default => EmployeeIDCardRequest::latest(),
            };
            $data = $query->get();
        }

        return $data->map(function ($record, $index) {
            $createdAt = $record->created_at ?? null;
            if ($createdAt && is_object($createdAt) && method_exists($createdAt, 'format')) {
                $createdAt = $createdAt->format('d/m/Y');
            } elseif ($createdAt) {
                $createdAt = '--';
            } else {
                $createdAt = '--';
            }
            return [
                $index + 1,
                $createdAt,
                $record->name ?? '--',
                $record->designation ?? '--',
                $record->card_type ?? '--',
                $record->request_for ?? '--',
                in_array($record->request_for ?? '', ['Replacement', 'Duplication']) ? ($record->duplication_reason ?? '--') : '--',
                ($record->request_for ?? '') === 'Extension' ? ($record->id_card_valid_upto ?? '--') : '--',
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
