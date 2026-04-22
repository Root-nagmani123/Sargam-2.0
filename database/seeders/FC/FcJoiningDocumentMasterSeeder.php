<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FcJoiningDocumentMasterSeeder extends Seeder
{
    public function run(): void
    {
        $docs = [
            ['document_name'=>'UPSC Appointment Letter','document_code'=>'UPSC_APPT','is_mandatory'=>1,'display_order'=>'1'],
            ['document_name'=>'Date of Birth Certificate / Class X Marksheet','document_code'=>'DOB_CERT','is_mandatory'=>1,'display_order'=>'2'],
            ['document_name'=>'Educational Qualification Certificates','document_code'=>'EDU_CERT','is_mandatory'=>1,'display_order'=>'3'],
            ['document_name'=>'Category Certificate (SC/ST/OBC/EWS if applicable)','document_code'=>'CAT_CERT','is_mandatory'=>0,'display_order'=>'4'],
            ['document_name'=>'Medical Fitness Certificate','document_code'=>'MED_CERT','is_mandatory'=>1,'display_order'=>'5'],
            ['document_name'=>'Passport Size Photographs (6 copies)','document_code'=>'PHOTO','is_mandatory'=>1,'display_order'=>'6'],
            ['document_name'=>'Aadhaar Card','document_code'=>'AADHAAR','is_mandatory'=>1,'display_order'=>'7'],
            ['document_name'=>'PAN Card','document_code'=>'PAN','is_mandatory'=>1,'display_order'=>'8'],
            ['document_name'=>'Bank Passbook / Cancelled Cheque','document_code'=>'BANK_DOC','is_mandatory'=>1,'display_order'=>'9'],
            ['document_name'=>'Character Certificate from Last Employer (if applicable)','document_code'=>'CHAR_CERT','is_mandatory'=>0,'display_order'=>'10'],
            ['document_name'=>'NOC from Last Employer (if applicable)','document_code'=>'NOC','is_mandatory'=>0,'display_order'=>'11'],
            ['document_name'=>'Property Return Statement','document_code'=>'PROP_RET','is_mandatory'=>1,'display_order'=>'12'],
            ['document_name'=>'Joining Report (Service Headquarters)','document_code'=>'JOIN_RPT','is_mandatory'=>1,'display_order'=>'13'],
            ['document_name'=>'COVID-19 Vaccination Certificate','document_code'=>'COVID_VAX','is_mandatory'=>0,'display_order'=>'14'],
        ];
        foreach ($docs as $doc) {
            DB::table('fc_joining_related_documents_masters')
                ->updateOrInsert(['document_code'=>$doc['document_code']], array_merge($doc, ['is_active'=>1]));
        }
    }
}
