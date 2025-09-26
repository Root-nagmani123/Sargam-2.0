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
use App\DataTables\FC\FcRegistrationMasterListDaTable;
use App\Models\FcRegistrationExportMaster as ModelsFcRegistrationExportMaster;
use Illuminate\Support\Facades\DB;

class RegistrationImportController extends Controller
{
    public function showForm()
    {
        return view('admin.registration.fcregistration_import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:,xls,csv,xlsx'
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
                    'contact_no'        => $row['contact_no'] ?? null,
                    'display_name'      => $row['display_name'] ?? null,
                    'schema_id'         => $row['schema_id'] ?? null,
                    'first_name'        => $row['first_name'] ?? null,
                    'middle_name'       => $row['middle_name'] ?? null,
                    'last_name'         => $row['last_name'] ?? null,
                    'rank'              => $row['rank'] ?? null,
                    'exam_year'         => $row['exam_year'] ?? null,
                    'service_master_pk' => $row['service_master_pk'] ?? 0,
                    'web_auth'          => $row['web_auth'] ?? null,
                ]
            );
        }

        Session::forget('import_data');

        return redirect()->route('admin.registration.index')->with('success', 'Data imported successfully.');
    }

    public function fc_masterindex(FcRegistrationMasterListDaTable $dataTable)
    {
        $courses = DB::table('local_form')->where('visible', 1)->where('parent_id', '=', null)->pluck('name', 'id');
        $exemptionCategories = DB::table('fc_exemption_master')->pluck('Exemption_name', 'Pk');
        $applicationTypes = [1 => 'Registration', 2 => 'Exemption'];
        $serviceMasters = DB::table('service_master')->pluck('service_name', 'pk');

        return $dataTable->render('admin.registration.fcregistrationmaster_list', [
            'courses' => $courses,
            'exemptionCategories' => $exemptionCategories,
            'applicationTypes' => $applicationTypes,
            'serviceMasters' => $serviceMasters
        ]);
    }


    // {
    //     $registrations = FcRegistrationMaster::select('pk', 'email', 'contact_no','display_name','schema_id','first_name', 'middle_name', 'last_name', 'rank', 'exam_year','service_master_pk', 'web_auth', 'dob')->get();
    //     return view('admin.registration.fcregistrationmaster_list', compact('registrations'));
    // }
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
            'display_name' => 'nullable|string|max:255',
            'schema_id' => 'nullable|string|max:255',
            'service_master_pk' => 'nullable|string|max:255',
            'exam_year' => 'nullable|string|max:255',
        ]);

        $record = FcRegistrationMaster::findOrFail($id);
        $record->update($request->only([
            'email',
            'contact_no',
            'first_name',
            'middle_name',
            'last_name',
            'rank',
            'exam_year',
            'web_auth',
            'dob',
            'display_name',
            'schema_id',
            'service_master_pk'
        ]));

        return redirect()->route('admin.registration.index')->with('success', 'Record updated successfully.');
    }


    public function fc_masterdestroy($id)
    {
        FcRegistrationMaster::destroy($id);
        return back()->with('success', 'Record deleted.');
    }

    // export fc master

    // public function export(Request $request)
    // {
    //     $format = $request->get('format');

    //     if ($format === 'xlsx') {
    //         return Excel::download(new FcRegistrationExport(), 'fc-registrations.xlsx');
    //     } elseif ($format === 'csv') {
    //         return Excel::download(new FcRegistrationExport(), 'fc-registrations.csv');
    //     } elseif ($format === 'pdf') {
    //         $registrations = ModelsFcRegistrationExportMaster::all();
    //         $pdf = Pdf::loadView('admin.forms.export.fcregistrationmaster_pdf', compact('registrations'))->setPaper('a4', 'landscape');
    //         return $pdf->download('fc-registrations.pdf');
    //     } else {
    //         return redirect()->back()->with('error', 'Invalid format selected.');
    //     }
    // }

    public function export(Request $request)
{
    $query = FcRegistrationMaster::query()
        ->leftJoin('service_master as s', 'fc_registration_master.service_master_pk', '=', 's.pk')
        ->leftJoin('fc_exemption_master as e', 'fc_registration_master.fc_exemption_master_pk', '=', 'e.Pk')
        ->select(
            'fc_registration_master.formid as course_master_pk',
            'fc_registration_master.application_type',
            'fc_registration_master.fc_exemption_master_pk',
              's.service_short_name',     // optional, if you want short name
            'fc_registration_master.schema_id',
            'fc_registration_master.display_name',
            'fc_registration_master.first_name',
            'fc_registration_master.middle_name',
            'fc_registration_master.last_name',
            'fc_registration_master.email',
            'fc_registration_master.contact_no',
            'fc_registration_master.rank',
            'fc_registration_master.dob',
            'fc_registration_master.web_auth',
            'fc_registration_master.exam_year',
            'e.Exemption_name as exemption_name'
        );

    // Apply filters
    if ($course = $request->course_name) {
        $query->where('fc_registration_master.formid', $course);
    }

    if ($exemption = $request->exemption_category) {
        $query->where('e.Exemption_name', $exemption);
    }

    if ($type = $request->application_type) {
        $query->where('fc_registration_master.application_type', $type);
    }

    if ($service = $request->service_master) {
        $query->where('fc_registration_master.service_master_pk', $service);
    }

    $registrations = $query->get();

    $format = $request->format;

    if ($format === 'xlsx') {
        return Excel::download(new FcRegistrationExport($registrations), 'fc-registrations.xlsx');
    } elseif ($format === 'csv') {
        return Excel::download(new FcRegistrationExport($registrations), 'fc-registrations.csv');
    } elseif ($format === 'pdf') {
        $pdf = Pdf::loadView('admin.forms.export.fcregistrationmaster_pdf', compact('registrations'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('fc-registrations.pdf');
    }

    return redirect()->back()->with('error', 'Invalid format selected.');
}

}
