<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use App\Models\OTHostelRoomDetails;

class AssignHostelToStudent implements ToCollection, WithHeadingRow, WithStartRow
{
    public $failures = [];

    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $dataToInsert = [];

        foreach ($collection as $index => $row) {
            $rowNumber = $index + 2;
            $data = array_map('trim', $row->toArray());

            $validator = Validator::make($data, [
                'user_name'        => 'required|string|exists:user_credentials,user_name',
                'hostel_room_name' => 'required|string|exists:hostel_room_master,hostel_room_name',
            ]);

            if ($validator->fails()) {
                $this->addFailure($rowNumber, $validator->errors()->all());
                continue;
            }

            $dataToInsert[] = [
                'user_name'        => $data['user_name'],
                'hostel_room_name' => $data['hostel_room_name'],
            ];
        }

        OTHostelRoomDetails::insert($dataToInsert);

    }

    private function addFailure($rowNumber, array $errors)
    {
        $this->failures[] = [
            'row'    => $rowNumber,
            'errors' => $errors,
        ];
    }
}
