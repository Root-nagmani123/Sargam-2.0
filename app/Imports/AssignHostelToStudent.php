<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use App\Models\OTHostelRoomDetails;
use App\Models\BuildingFloorRoomMapping;

class AssignHostelToStudent implements ToCollection, WithHeadingRow, WithStartRow
{
    public $failures = [];
    public $rows = [];
    public $preview = false;

    protected int $courseMasterPk;

    public function __construct(int $courseMasterPk, bool $preview = false)
    {
        $this->courseMasterPk = $courseMasterPk;
        $this->preview        = $preview;
    }

    public function headingRow(): int { return 1; }
    public function startRow(): int   { return 2; }

    public function collection(Collection $collection)
    {
        $existingUsernames = OTHostelRoomDetails::pluck('user_name')->toArray();
        $dataToInsert = [];

        foreach ($collection as $index => $row) {
            $rowNumber = $index + 2;
            $data      = array_map('trim', $row->toArray());
            $rowErrors = [];

            $validator = Validator::make($data, [
                'user_name'        => 'required|string|exists:user_credentials,user_name',
                'hostel_room_name' => 'required|string|exists:building_floor_room_mapping,room_name',
            ]);

            if ($validator->fails()) {
                $rowErrors = array_merge($rowErrors, $validator->errors()->all());
            }

            if (in_array($data['user_name'] ?? '', $existingUsernames)) {
                $rowErrors[] = "Username '{$data['user_name']}' already assigned to a hostel room";
            } elseif (in_array($data['user_name'] ?? '', array_column($dataToInsert, 'user_name'))) {
                $rowErrors[] = "Username '{$data['user_name']}' is duplicated in the Excel file";
            }

            $room = BuildingFloorRoomMapping::where('room_name', $data['hostel_room_name'] ?? '')->first();
            if ($room) {
                $allocatedCount = OTHostelRoomDetails::where('hostel_room_name', $data['hostel_room_name'])->count();
                if (($room->capacity - $allocatedCount) <= 0) {
                    $rowErrors[] = "Room '{$data['hostel_room_name']}' has no vacant slots";
                }
            }

            if (!empty($rowErrors)) {
                $this->failures[] = [
                    'row'              => $rowNumber,
                    'user_name'        => $data['user_name'] ?? '',
                    'hostel_room_name' => $data['hostel_room_name'] ?? '',
                    'errors'           => $rowErrors,
                ];
                continue;
            }

            $dataToInsert[] = [
                'course_master_pk' => $this->courseMasterPk,
                'user_name'        => $data['user_name'],
                'hostel_room_name' => $data['hostel_room_name'],
            ];
        }

        if (!empty($dataToInsert)) {
            foreach ($dataToInsert as $d) {
                $this->rows[] = [
                    'course_master_pk' => $d['course_master_pk'],
                    'user_name'        => $d['user_name'],
                    'hostel_room_name' => $d['hostel_room_name'],
                ];
            }
        }

        if (!$this->preview && empty($this->failures)) {
            OTHostelRoomDetails::insert($dataToInsert);
        }
    }
}
