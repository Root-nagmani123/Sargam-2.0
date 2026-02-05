<?php

namespace App\Exports;

use App\Models\VehiclePassTWApply;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiclePassExport implements FromCollection, WithHeadings
{
    protected string $tab;

    protected ?int $employeePk;

    public function __construct(string $tab = 'active', ?int $employeePk = null)
    {
        $this->tab = $tab;
        $this->employeePk = $employeePk;
    }

    public function collection(): Collection
    {
        $query = VehiclePassTWApply::with(['vehicleType', 'employee'])
            ->where('veh_created_by', $this->employeePk)
            ->orderBy('created_date', 'desc');

        $query = match ($this->tab) {
            'archive' => $query->whereIn('vech_card_status', [2, 3]),
            'all' => $query,
            default => $query->where('vech_card_status', 1),
        };

        $data = $query->get();

        return $data->map(function ($record, $index) {
            $status = match ($record->vech_card_status) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => '--',
            };

            return [
                $index + 1,
                $record->display_name,
                $record->vehicle_req_id ?? '--',
                $record->vehicleType->vehicle_type ?? '--',
                $record->vehicle_no ?? '--',
                $record->created_date ? $record->created_date->format('d/m/Y H:i') : '--',
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
