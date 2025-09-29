<?php

namespace App\Imports;

use App\Models\FcRegistrationMaster;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class FcRegistrationImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'uid'               => 0, // fixed default because Excel doesn't have it
                'display_name'      => $row['display_name'] ?? null,
                'contact_no'        => $row['contact_no'] ?? null,
                'rank'              => $row['rank'] ?? null,
                'generated_OT_code' => $row['generated_ot_code'] ?? null,
                'service_master_pk' => $row['service_master_pk'] ?? null,
                'cadre_master_pk'   => $row['cadre_master_pk'] ?? null,
                'created_date'      => now(),
            ];
        }

        // Perform bulk upsert
        FcRegistrationMaster::upsert(
            $data,
            ['display_name', 'rank', 'generated_OT_code'], // Unique keys
            ['contact_no', 'service_master_pk', 'cadre_master_pk'] // Columns to update
        );
    }
}
