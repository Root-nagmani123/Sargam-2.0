<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FormEditController extends Controller
{
    // public function fc_edit($form_id)
    // {
    //     // Retrieve form sections and fields from the database
    //     $sections = DB::table('form_sections')
    //         ->where('formid', $form_id)
    //         ->orderBy('sort_order')
    //         ->get();

    //     $fields = DB::table('form_data')
    //         ->where('formid', $form_id)
    //         ->get();

    //     return view('admin.registration.fc_edit', compact('form_id', 'sections', 'fields'));
    // }

}
