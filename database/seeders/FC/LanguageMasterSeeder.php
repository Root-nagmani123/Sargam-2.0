<?php

namespace Database\Seeders\FC;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LanguageMasterSeeder extends Seeder
{
    public function run(): void
    {
        $langs = [
            'Hindi','English','Bengali','Telugu','Marathi','Tamil','Gujarati','Urdu','Kannada',
            'Odia','Malayalam','Punjabi','Assamese','Maithili','Sanskrit','Sindhi','Konkani',
            'Manipuri','Nepali','Bodo','Santhali','Kashmiri','French','German','Spanish',
            'Chinese (Mandarin)','Japanese','Arabic','Russian','Portuguese','Other',
        ];
        foreach ($langs as $l) {
            DB::table('language_master')->updateOrInsert(['language_name'=>$l],['language_name'=>$l]);
        }
    }
}
