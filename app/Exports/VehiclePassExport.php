<?php

namespace App\Exports;

use App\Models\VehiclePassTWApply;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiclePassExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected string $tab,
        protected mixed $employeePk,
        protected mixed $pkOld = null
    ) {
    }

    public function collection(): Collection
    {
        if ($this->employeePk === null || $this->employeePk === '') {
            return collect();
        }

        $query = VehiclePassTWApply::with(['vehicleType', 'employee'])
            ->where(function ($q) {
                $q->where('veh_created_by', $this->employeePk);
                if ($this->pkOld) {
                    $q->orWhere('veh_created_by', $this->pkOld);
                }
            })
            ->orderBy('created_date', 'desc');

        if ($this->tab === 'archive') {
            $query->whereIn('vech_card_status', [2, 3]);
        } elseif ($this->tab !== 'all') {
            $query->where('vech_card_status', 1);
        }

        return $query->get()->values()->map(function (VehiclePassTWApply $record, int $index) {
            $status = match ((int) $record->vech_card_status) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => '--',
            };
            $created = $record->created_date ? $record->created_date->format('d/m/Y H:i') : '--';

            return [
                $index + 1,
                $record->display_name,
                $record->vehicle_req_id ?? '--',
                $record->vehicleType->vehicle_type ?? '--',
                $record->vehicle_no ?? '--',
                $created,
                $status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'S.No.',
            'Employee Name',
            'Vehicle Pass No.',
            'Vehicle Type',
            'Vehicle Number',
            'Requested Date',
            'Status',
        ];
    }
}
