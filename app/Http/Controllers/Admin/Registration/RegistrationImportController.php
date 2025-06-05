<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\RegistrationImport;
use Maatwebsite\Excel\Facades\Excel;

class RegistrationImportController extends Controller
{
    public function showForm()
    {
        return view('admin.registration.fcregistration_import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls'
        ]);

        Excel::import(new RegistrationImport, $request->file('file'));

        return back()->with('success', 'Registrations imported successfully.');
    }
}
