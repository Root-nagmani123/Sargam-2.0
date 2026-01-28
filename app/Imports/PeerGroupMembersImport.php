<?php

// namespace App\Imports;

// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\ToCollection;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Illuminate\Support\Facades\DB;

// class PeerGroupMembersImport implements ToCollection, WithHeadingRow
// {
//     protected $groupId;

//     public function __construct($groupId)
//     {
//         $this->groupId = $groupId;
//     }

//     public function collection(Collection $rows)
//     {
//         // dd($rows);
//         foreach ($rows as $row) {
//             // // Skip empty rows
//             // if (empty($row['course_name']) && empty($row['user_id'])) {
//             //     continue;
//             // }

//             // // Find member_pk from fc_registration_master based on user_id or user_name
//             // $memberPk = $this->findMemberPk($row);
//             // Skip empty rows - use numeric indexes
//             if (empty($row[0]) && empty($row[2])) { 
//                 dd("wwswss"); // Course Name (0) and User ID (2)
//                 continue;
//             }

//             // Debug the row data
//             // \Log::info('Processing row:', $row->toArray());

//             // Find member_pk from fc_registration_master based on user_id or user_name
//             $memberPk = $this->findMemberPk($row);
//             dd($memberPk);

//             if ($memberPk) {
//                 DB::table('peer_group_members')->updateOrInsert(
//                     [
//                         'group_id' => $this->groupId,
//                         'member_pk' => $memberPk
//                     ],
//                     [
//                         'course_name' => $row['course_name'] ?? null,
//                         'event_name' => $row['event_name'] ?? null,
//                         'user_id' => $row['user_id'] ?? null,
//                         'user_name' => $row['user_name'] ?? null,
//                         'ot_code' => $row['ot_code'] ?? null,
//                         'created_at' => now(),
//                         'updated_at' => now()
//                     ]
//                 );
//             }
//         }
//     }

//     private function findMemberPk($row)
//     {
//         // First try to find by user_id
//         if (!empty($row['user_id'])) {
//             $member = DB::table('fc_registration_master')
//                 ->where('pk', $row['user_id'])
//                 ->orWhere('user_id', $row['user_id'])
//                 ->first();

//             if ($member) {
//                 return $member->pk;
//             }
//         }

//         // Then try by user_name
//         if (!empty($row['user_name'])) {
//             $member = DB::table('fc_registration_master')
//                 ->where('first_name', 'like', '%' . $row['user_name'] . '%')
//                 ->orWhere('username', 'like', '%' . $row['user_name'] . '%')
//                 ->first();

//             if ($member) {
//                 return $member->pk;
//             }
//         }

//         // If not found, you can create a new entry or return null
//         return null;
//     }

//     public function headingRow(): int
//     {
//         return 1; // Excel header row
//     }
// }


namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class PeerGroupMembersImport implements ToCollection, WithStartRow
{
    protected $groupId;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    public function collection(Collection $rows)
    {
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $rowIndex => $row) {
            // Skip completely empty rows
            if (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3]) && empty($row[4])) {
                $skippedCount++;
                continue;
            }
        

            try {
                DB::table('peer_group_members')->insert([
                    'group_id' => $this->groupId,
                    'member_pk' => $row[0] ?? $this->generateMemberPk(), // Use User ID as member_pk
                    'course_name' => $row[3] ?? null,
                    'event_name' => $row[4] ?? null,
                    'user_id' => $row[0] ?? null,
                    'user_name' => $row[1] ?? null,
                    'ot_code' => $row[2] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $importedCount++;
                
            } catch (\Exception $e) {
                // Log error but continue with other rows
                \Log::error("Error importing row {$rowIndex}: " . $e->getMessage());
                $skippedCount++;
                continue;
            }
        }

        // \Log::info("Import completed: {$importedCount} records imported, {$skippedCount} skipped for group {$this->groupId}");
    }

    private function generateMemberPk()
    {
        // Generate a unique member_pk if User ID is not provided
        return time() . rand(1000, 9999);
    }

    public function startRow(): int
    {
        return 2; // Skip header row
    }
}
