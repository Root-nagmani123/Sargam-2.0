<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use App\Models\OTHostelRoomDetails;
use App\Models\BuildingFloorRoomMapping;
use App\Models\CourseMaster;

class AssignHostelToStudent implements ToCollection, WithHeadingRow, WithStartRow
{
    public $failures = [];

    /** Valid, ready-to-insert rows (with display labels) — used for the preview step. */
    public $rows = [];

    /** When true, the file is only parsed/validated (no DB insert). */
    public $preview = false;

    public function __construct(bool $preview = false)
    {
        $this->preview = $preview;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $collection)
    {
        $existingUsernames = OTHostelRoomDetails::pluck('user_name')->toArray();
        $dataToInsert = [];

        foreach ($collection as $index => $row) {
            $rowNumber = $index + 2;
            $data = array_map('trim', $row->toArray());
            $rowErrors = [];

            // Validation
            $validator = Validator::make($data, [
                'course_master_pk' => 'required|integer|exists:course_master,pk',
                'user_name'        => 'required|string|exists:user_credentials,user_name',
                'hostel_room_name' => 'required|string|exists:building_floor_room_mapping,room_name',
            ]);

            if ($validator->fails()) {
                $rowErrors = array_merge($rowErrors, $validator->errors()->all());
            }

            // Check for duplicates in DB or in the current Excel
            if (in_array($data['user_name'], $existingUsernames)) {
                $rowErrors[] = "Username '{$data['user_name']}' already exists";
            } elseif (in_array($data['user_name'], array_column($dataToInsert, 'user_name'))) {
                $rowErrors[] = "Username '{$data['user_name']}' is duplicated in the Excel file";
            }

            // Check if the room has vacant slots
            $room = BuildingFloorRoomMapping::where('room_name', $data['hostel_room_name'])->first();
            if ($room) {
                $allocatedCount = OTHostelRoomDetails::where('hostel_room_name', $data['hostel_room_name'])->count();
                $vacantSlots = $room->capacity - $allocatedCount;

                if ($vacantSlots <= 0) {
                    $rowErrors[] = "Room '{$data['hostel_room_name']}' has no vacant slots";
                }
            }

            if (!empty($rowErrors)) {
                $this->failures[] = [
                    'row' => $rowNumber,
                    'user_name' => $data['user_name'] ?? '',
                    'hostel_room_name' => $data['hostel_room_name'] ?? '',
                    'course_master_pk' => $data['course_master_pk'] ?? '',
                    'errors' => $rowErrors,
                ];
                continue; // Skip inserting this row
            }

            $dataToInsert[] = [
                'course_master_pk' => $data['course_master_pk'],
                'user_name'        => $data['user_name'],
                'hostel_room_name' => $data['hostel_room_name'],
            ];
        }

        // Build display rows (with course name resolved) for the preview step.
        if (!empty($dataToInsert)) {
            $courseNames = CourseMaster::whereIn('pk', array_column($dataToInsert, 'course_master_pk'))
                ->pluck('course_name', 'pk');

            foreach ($dataToInsert as $d) {
                $this->rows[] = [
                    'course_name'      => $courseNames[$d['course_master_pk']] ?? $d['course_master_pk'],
                    'user_name'        => $d['user_name'],
                    'hostel_room_name' => $d['hostel_room_name'],
                ];
            }
        }

        // Only insert when committing (not previewing) and there are no failures.
        if (!$this->preview && empty($this->failures)) {
            OTHostelRoomDetails::insert($dataToInsert);
        }
    }
}

