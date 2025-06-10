<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PreviewImport;
use App\Models\FcRegistrationMaster;
use Illuminate\Support\Facades\Session;
use App\Exports\FcRegistrationExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\FcRegistrationExportMaster as ModelsFcRegistrationExportMaster;

class RegistrationImportController extends Controller
{
    public function showForm()
    {
        return view('admin.registration.fcregistration_import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $path = $request->file('file')->store('temp');
        $data = Excel::toArray(new PreviewImport, storage_path('app/' . $path));

        Session::put('import_data', $data[0]);

        return view('admin.registration.fcregistration_preview', ['rows' => $data[0]]);
    }

    public function importConfirmed()
    {
        $importData = Session::get('import_data');

        if (!$importData) {
            return redirect()->back()->with('error', 'No data to import.');
        }

        foreach ($importData as $row) {
            FcRegistrationMaster::updateOrCreate(
                ['email' => $row['email']],
                [
                    'contact_no'        => $row['contact_no'],
                    'first_name'        => $row['first_name'],
                    'middle_name'       => $row['middle_name'],
                    'last_name'         => $row['last_name'],
                    'rank'              => $row['rank'],
                    'web_auth'          => $row['web_auth'],
                    'service_master_pk' => 0,
                ]
            );
        }

        Session::forget('import_data');

        return redirect()->route('admin.registration.index')->with('success', 'Data imported successfully.');
    }

    public function fc_masterindex()
    {
        $registrations = FcRegistrationMaster::select('pk', 'email', 'contact_no', 'first_name', 'middle_name', 'last_name', 'rank', 'exam_year', 'web_auth', 'dob')->get();
        return view('admin.registration.fcregistrationmaster_list', compact('registrations'));
    }
    public function fc_masteredit($id)
    {
        $registration = FcRegistrationMaster::findOrFail($id);
        return view('admin.registration.fcregistrationmaster_edit', compact('registration'));
    }


    public function fc_masterupdate(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
            'contact_no' => 'required',
            'first_name' => 'required',
            'dob' => 'nullable|date',
        ]);

        $record = FcRegistrationMaster::findOrFail($id);
        $record->update($request->only(['email', 'contact_no', 'first_name', 'middle_name', 'last_name', 'rank', 'exam_year', 'web_auth', 'dob']));

        return redirect()->route('admin.registration.index')->with('success', 'Record updated successfully.');
    }

    public function fc_masterdestroy($id)
    {
        FcRegistrationMaster::destroy($id);
        return back()->with('success', 'Record deleted.');
    }

    // export fc master

    public function export(Request $request)
    {
        $format = $request->get('format');

        if ($format === 'xlsx') {
            return Excel::download(new FcRegistrationExport(), 'fc-registrations.xlsx');
        } elseif ($format === 'csv') {
            return Excel::download(new FcRegistrationExport(), 'fc-registrations.csv');
        } elseif ($format === 'pdf') {
            $registrations = ModelsFcRegistrationExportMaster::all();
            $pdf = Pdf::loadView('admin.forms.export.fcregistrationmaster_pdf', compact('registrations'));
            return $pdf->download('fc-registrations.pdf');
        } else {
            return redirect()->back()->with('error', 'Invalid format selected.');
        }
    }
}
