<?php

// namespace App\Exports;

// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;

// class PeerEvaluationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
// {
//     protected $members;
//     protected $columns;
//     protected $scores;
//     protected $groupName;

//     public function __construct($members, $columns, $scores, $groupName)
//     {
//         $this->members = $members;
//         $this->columns = $columns;
//         $this->scores = $scores;
//         $this->groupName = $groupName;
//     }

//     public function collection()
// {
//     $data = collect();

//     foreach ($this->members as $member) {
//         $row = [
//             'Member Name' => $member->first_name,
//             'User ID' => $member->user_id ?? '-',
//             'OT Code' => $member->ot_code ?? '-',
//             'Group' => $this->groupName,
//         ];

//         foreach ($this->columns as $column) {
//             $key = $member->id . '-' . $column->id;
//             $row[$column->column_name] = $this->scores[$key]->score ?? '-';
//         }

//         $data->push($row);
//     }

//     return $data;
// }

//     public function headings(): array
//     {
//         $headings = ['Member Name', 'User ID', 'OT Code', 'Group'];
//         foreach ($this->columns as $column) {
//             $headings[] = $column->column_name;
//         }
//         return $headings;
//     }

//      public function styles(Worksheet $sheet)
//     {
//         $lastRow = $sheet->getHighestRow();
//         $lastColumn = $sheet->getHighestColumn();

//         $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
//             ->applyFromArray([
//                 'borders' => [
//                     'allBorders' => [
//                         'borderStyle' => Border::BORDER_THIN,
//                         'color' => ['argb' => 'FF000000'],
//                     ],
//                 ],
//                 'alignment' => [
//                     'horizontal' => Alignment::HORIZONTAL_CENTER,
//                     'vertical'   => Alignment::VERTICAL_CENTER,
//                 ],
//             ]);

//         return [
//             1 => [
//                 'font' => ['bold' => true],
//                 'fill' => [
//                     'fillType'   => Fill::FILL_SOLID,
//                     'startColor' => ['rgb' => 'FFCC00'],
//                 ],
//             ],
//         ];
//     }
// }



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
    protected $reflectionFields;
    protected $reflectionResponses;

    public function __construct($members, $columns, $scores, $groupName, $reflectionFields, $reflectionResponses)
    {
        $this->members = $members;
        $this->columns = $columns;
        $this->scores = $scores;
        $this->groupName = $groupName;
        $this->reflectionFields = $reflectionFields;
        $this->reflectionResponses = $reflectionResponses;
    }

    public function collection()
    {
        $data = collect();
        $rowCounter = 1;

        foreach ($this->members as $member) {
            // Get all evaluators for this member
            $evaluators = $this->scores->where('member_id', $member->id)->pluck('evaluator_id')->unique();
            
            foreach ($evaluators as $evaluatorId) {
                $row = [
                    'Sr.No' => $rowCounter++,
                    'Member Name' => $member->first_name,
                    'User ID' => $member->user_id ?? '-',
                    'OT Code' => $member->ot_code ?? '-',
                    'Group' => $this->groupName,
                    'Evaluator ID' => $evaluatorId,
                ];

                // Add evaluation scores
                foreach ($this->columns as $column) {
                    $score = $this->scores
                        ->where('member_id', $member->id)
                        ->where('column_id', $column->id)
                        ->where('evaluator_id', $evaluatorId)
                        ->first();
                    $row[$column->column_name] = $score->score ?? '-';
                }

                // Add reflection responses
                foreach ($this->reflectionFields as $field) {
                    $response = $this->reflectionResponses->get($evaluatorId . '-' . $field->id);
                    $row[$field->field_label] = $response->description ?? '-';
                }

                $data->push($row);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        $headings = ['Sr.No', 'Member Name', 'User ID', 'OT Code', 'Group', 'Evaluator ID'];
        
        foreach ($this->columns as $column) {
            $headings[] = $column->column_name;
        }
        
        foreach ($this->reflectionFields as $field) {
            $headings[] = $field->field_label;
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
