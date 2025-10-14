<?php

namespace App\Exports;

use App\Models\BuildingFloorRoomMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use \Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FloorRoomMappingExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    protected $filters;
    function __construct($filters)
    {
        $this->filters = $filters;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = BuildingFloorRoomMapping::with(['building', 'floor']);

        // Filter by building relationship
        $buildingId = $this->filters['building_id'] ?? null;
        if ($buildingId) {
            $query->whereHas('building', function ($q) use ($buildingId) {
                $q->where('pk', $buildingId);
            });
        }

        // Filter by room_type
        $roomType = $this->filters['room_type'] ?? null;
        if ($roomType) {
            $query->where('room_type', $roomType);
        }

        // Filter by status
        $status = $this->filters['status'] ?? null;
        if ($status !== null && $status !== '') {
            $query->where('active_inactive', $status);
        }

        $data = $query->get();

        return $data; // Or pass $data to export logic
    }

    public function headings(): array
    {
        return [
            'Building Name',
            'Floor',
            'Room Name',
            'Capacity',
            'Comment',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'],
                ],
            ],
        ];
    }

    public function map($mapping): array
    {
        return [
            optional($mapping->building)->building_name,
            optional($mapping->floor)->floor_name,
            $mapping->room_name,
            $mapping->capacity,
            $mapping->comment,
        ];
    }

}
