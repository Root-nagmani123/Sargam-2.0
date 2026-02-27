<?php

namespace App\Exports;

use App\Models\FamilyIDCardRequest;
use App\Models\SecurityFamilyIdApply;
use App\Support\IdCardSecurityMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FamilyIDCardExport implements FromCollection, WithHeadings
{
    protected string $tab;
    protected bool $useSecurity;
    protected string $search;
    protected string $cardType;

    public function __construct(string $tab = 'active', bool $useSecurity = false, string $search = '', string $cardType = '')
    {
        $this->tab = $tab;
        $this->useSecurity = $useSecurity;
        $this->search = $search;
        $this->cardType = $cardType;
    }

    public function collection(): Collection
    {
        if ($this->useSecurity) {
            $query = match ($this->tab) {
                'archive' => SecurityFamilyIdApply::whereIn('id_status', [2, 3]),
                'all' => SecurityFamilyIdApply::query(),
                default => SecurityFamilyIdApply::where('id_status', 1),
            };
            
            // Filter by current user
            $query->where('created_by', Auth::user()->user_id);
            
            // Apply search filter
            if (!empty($this->search)) {
                $query->where(function ($q) {
                    $q->where('emp_id_apply', 'LIKE', "%{$this->search}%")
                      ->orWhere('family_name', 'LIKE', "%{$this->search}%")
                      ->orWhere('family_relation', 'LIKE', "%{$this->search}%");
                });
            }
            
            // Apply card type filter
            if (!empty($this->cardType)) {
                $query->where('card_type', $this->cardType);
            }
            
            $query->orderBy('created_date', 'desc');
            $data = $query->get()->map(fn ($r) => IdCardSecurityMapper::toFamilyRequestDto($r));
        } else {
            $query = match ($this->tab) {
                'archive' => FamilyIDCardRequest::onlyTrashed()->latest(),
                'all' => FamilyIDCardRequest::withTrashed()->latest(),
                default => FamilyIDCardRequest::latest(),
            };
            $data = $query->get();
        }

        return $data->map(function ($record, $index) {
            $createdAt = $record->created_at ?? null;
            $createdStr = $createdAt && is_object($createdAt) && method_exists($createdAt, 'format') ? $createdAt->format('d/m/Y') : '--';
            return [
                $index + 1,
                $createdStr,
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
