<?php

namespace App\Imports;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Validators\ValidationException;


class GroupMappingImport implements ToCollection, WithHeadingRow, WithStartRow
{

    public $failures = [];
    public function headingRow(): int
    {
        return 1; // Specify the row number of the heading
    }
    public function startRow(): int
    {
        return 2; // Specify the row number to start reading data
    }

    
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {
            
            $data = $row->toArray();

            $validator = Validator::make($data, [
                'group_name' => 'required|string|max:255',
                'group_code' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $this->failures[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->all(),
                ];
            }


        }
    }

    public function map($row): array
    {
        return [
            'name' => $row[0],
            'otCode' => $row[1],
            'groupName' => $row[2],
            'groupType' => $row[3]
        ];
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'otCode' => 'required|string|max:255',
            'groupName' => 'nullable|string|max:255',
            'groupType' => 'nullable|string|max:255',
        ];
    }
    public function customValidationMessages()
    {
        return [
            'name.required' => 'The group name is required.',
            'otCode.required' => 'The group code is required.',
            'groupName.string' => 'The group name must be a string.',
            'groupType.string' => 'The group type must be a string.',
        ];
    }
    public function customValidationAttributes()
    {
        return [
            'name' => 'Name',
            'otCode' => 'OT Code',
            'groupName' => 'Group Name',
            'groupType' => 'Group Type',
        ];
    }
    public function chunkSize(): int
    {
        return 1000; // Adjust the chunk size as needed
    }
    
}
