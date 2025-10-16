<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PeerEvaluationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $members;
    protected $columns;
    protected $scores;
    protected $groupName;

    public function __construct($members, $columns, $scores, $groupName)
    {
        $this->members = $members;
        $this->columns = $columns;
        $this->scores = $scores;
        $this->groupName = $groupName;
    }

    public function collection()
{
    $data = collect();

    foreach ($this->members as $member) {
        $row = [
            'Member Name' => $member->first_name,
            'User ID' => $member->user_id ?? '-',
            'OT Code' => $member->ot_code ?? '-',
            'Group' => $this->groupName,
        ];

        foreach ($this->columns as $column) {
            $key = $member->id . '-' . $column->id;
            $row[$column->column_name] = $this->scores[$key]->score ?? '-';
        }

        $data->push($row);
    }

    return $data;
}

    public function headings(): array
    {
        $headings = ['Member Name', 'User ID', 'OT Code', 'Group'];
        foreach ($this->columns as $column) {
            $headings[] = $column->column_name;
        }
        return $headings;
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
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'],
                ],
            ],
        ];
    }
}
