<?php

namespace App\Imports;

use App\Models\FcRegistrationMaster;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RegistrationImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // return new FcRegistrationMaster([
        //     'first_name'   => $row['first_name'],
        //     'middle_name'  => $row['middle_name'],
        //     'last_name'    => $row['last_name'],
        //     'email'        => $row['email'],
        //     'contact_no'   => $row['contact_no'],
        //     'rank'         => $row['rank'],
        //     'web_auth'     => $row['web_auth'],
        // ]);
         // Check for duplicate by email or contact number
        // $exists = FcRegistrationMaster::where('email', $row['email'])
        //             ->orWhere('contact_no', $row['contact_no'])
        //             ->exists();

        $exists = FcRegistrationMaster::where('email', $row['email'])
          ->where('contact_no', $row['contact_no'])
          ->exists();


        if (!$exists) {
            // Skip this row (returning null means it won’t be imported)
             return new FcRegistrationMaster([
            'email'             => $row['email'],
            'contact_no'        => $row['contact_no'],
            'first_name'        => $row['first_name'],
            'middle_name'       => $row['middle_name'],
            'last_name'         => $row['last_name'],
            'rank'              => $row['rank'],
            'web_auth'          => $row['web_auth'],
            'service_master_pk' => 0, // default or required value
        ]);
        }

       
    }
}
