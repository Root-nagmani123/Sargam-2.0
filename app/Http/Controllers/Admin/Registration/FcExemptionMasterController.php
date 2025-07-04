<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Models\FcExemptionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Assuming you have a User model for user relationships
use Illuminate\Support\Facades\DB; // For database operations if needed
use App\Exports\ExemptionDataExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf; // For PDF generation

class FcExemptionMasterController extends Controller
{
    public function index()
    {
        // $exemptions = FcExemptionMaster::all();
        // return view('admin.forms.exemption.index', compact('exemptions'));

        $exemptions = FcExemptionMaster::with(['createdByUser', 'modifiedByUser'])->get();
        return view('admin.forms.exemption.index', compact('exemptions'));
    }

    public function create()
    {
        return view('admin.forms.exemption.create');
    }

    //exemption store
    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'Exemption_name' => 'required|string|max:500',
            'description' => 'required|string',
        ]);

        // Insert data into the table
        DB::table('fc_exemption_master')->insert([
            'Exemption_name' => $request->input('Exemption_name'),
            'description' => $request->input('description'),
            'Created_by' => Auth::id(), // Assumes authentication is used
            'Created_date' => now(),
        ]);

        // Redirect back with success message
        return redirect()->route('admin.fc_exemption.index')->with('success', 'Exemption form submitted successfully.');
    }


    public function edit($id)
    {
        $exemption = FcExemptionMaster::findOrFail($id);
        return view('admin.forms.exemption.edit', compact('exemption'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Exemption_name' => 'required|string|max:500',
            'description' => 'required|string|',
        ]);

        $exemption = FcExemptionMaster::findOrFail($id);
        $exemption->update([
            'Exemption_name' => $request->Exemption_name,
            'description' => $request->description,
            'Modified_by' => Auth::id(),
            'Modified_date' => now(),
        ]);

        return redirect()->route('admin.fc_exemption.index')->with('success', 'Exemption updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $exemption = FcExemptionMaster::findOrFail($id);
            $exemption->delete();

            return redirect()->route('admin.fc_exemption.index')->with('success', 'Exemption deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.fc_exemption.index')->with('error', 'Failed to delete exemption.');
        }
    }

    public function exemptioncreate()
    {
        // Controller method
        $exemptions = FcExemptionMaster::where('visible', 1)->get();  // Assuming your model name
        return view('admin.forms.exemption_index', compact('exemptions'));
    }


    //exemptionstore
    public function exemptionstore(Request $request)
    {
        $rules = [
            'ex_mobile' => 'required|digits_between:7,15',
            'reg_web_code' => 'required|string',
            'exemption_category' => 'required|exists:fc_exemption_master,Pk',
            'captcha' => 'required|captcha',
        ];

        $exemption = DB::table('fc_exemption_master')
            ->where('Pk', $request->exemption_category)
            ->first();

        if ($exemption && strtolower($exemption->Exemption_name) === 'medical') {
            $rules['medical_doc'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('captcha_refresh', true);
        }

        // Check against fc_registration_master
        $registration = DB::table('fc_registration_master')
            ->where('contact_no', $request->ex_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->first();

        if (!$registration) {
            return redirect()->back()
                ->withErrors(['reg_web_code' => 'Invalid Web Code or Mobile Number.'])
                ->withInput();
        }

        $username = $registration->user_id ?? null;

        $medicalDocPath = null;
        if ($request->hasFile('medical_doc') && $request->file('medical_doc')->isValid()) {
            $medicalDocPath = $request->file('medical_doc')->store('medical_docs', 'public');
        }

        //  Update if exists, otherwise insert
        DB::table('fc_registration_master')->updateOrInsert(
            [
                'contact_no' => $request->ex_mobile,
                'web_auth' => $request->reg_web_code
            ],
            [
                'user_id' => $username,
                'fc_exemption_master_pk' => $request->exemption_category,
                'medical_exemption_doc' => $medicalDocPath,
                // 'updated_at' => now(),
                // 'created_at' => now(), // This won't update if already exists
            ]
        );

        return redirect()->route('exemptions.datalist')->with('success', 'Exemption form submitted successfully.');
    }


    public function exemption_list(Request $request)
    {
        $filter = $request->get('exemption_category'); // Could be short name or empty
        $typeFilter = $request->get('application_type'); // 1 = Registration, 2 = Exemption


        // Fetch unique exemption short names for the dropdown
        $categories = DB::table('fc_exemption_master')
            ->select('Exemption_name')
            ->where('visible', 1)
            ->where('is_notice', 0)
            ->distinct()
            ->orderBy('Exemption_name')
            ->get();

        // Base query
        $query = DB::table('fc_registration_master as r')
            ->leftJoin('fc_exemption_master as e', 'r.fc_exemption_master_pk', '=', 'e.Pk')
            ->select(
                'r.pk',
                'r.contact_no',
                'r.web_auth',
                'r.medical_exemption_doc',
                'r.created_date',
                'r.first_name',
                'r.middle_name',
                'r.last_name',
                'e.Exemption_name',
                'r.application_type',
                'e.Exemption_name',
                'r.exemption_count'
            )
            ->orderBy('r.created_date')
            ->whereIn('r.application_type', [1, 2]);  // Only show records with either registration or exemption
        // ->where('e.visible', 1);
        // ->where('r.fc_exemption_master_pk', '!=', 0);

        // Apply filter only if a category is selected
        if (!empty($filter)) {
            $query->where('e.Exemption_name', $filter);
        }

        // Type Filter Logic
        if (!empty($typeFilter)) {
            $query->where('r.application_type', intval($typeFilter));

            if ($typeFilter == 2) {
                $query->where('e.visible', 1)
                    ->where('r.fc_exemption_master_pk', '!=', 0);
            }
        }

        if (!empty($typeFilter)) {
            $query->where('r.application_type', intval($typeFilter));
        }

        $submissions = $query->get();

        return view('admin.forms.exemption_datalist', compact('submissions', 'categories', 'filter', 'typeFilter'));
    }



    public function exemptionexport(Request $request)
    {
        $format = $request->get('format');
        $filter = $request->get('exemption_category');
        $appType = $request->get('application_type'); // ✅ new input

        // Fetch data from registration with optional filters
        $submissions = DB::table('fc_registration_master as d')
            ->leftJoin('fc_exemption_master as e', 'd.fc_exemption_master_pk', '=', 'e.Pk')
            ->select(
                'd.contact_no',
                'd.web_auth',
                'e.Exemption_name',
                'd.medical_exemption_doc',
                'd.created_date',
                'd.application_type',
                'd.exemption_count',
                DB::raw("COALESCE(CONCAT_WS(' ', d.first_name, d.middle_name, d.last_name)) as user_name")
            )
            // ->where('d.fc_exemption_master_pk', '!=', 0)
            ->whereIn('d.application_type', [1, 2]) // Always restrict to valid types
            ->when($filter, function ($query, $filter) {
                return $query->where('e.Exemption_name', $filter);
            })
            ->when($appType, function ($query, $appType) {
                return $query->where('d.application_type', $appType);
            })
            ->get();


        if ($format === 'xlsx' || $format === 'csv') {
            return Excel::download(new ExemptionDataExport($submissions), "exemption_data.$format");
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.forms.export.exemption_pdf', compact('submissions', 'filter', 'appType'));
            $pdf->setPaper('a4', 'landscape');

            return $pdf->download('exemption_data.pdf');
        } else {
            return back()->with('error', 'Invalid export format selected.');
        }
    }



    //captcha refresh
    public function reloadCaptcha()
    {
        return response()->json(['captcha' => captcha_img()]);
    }
}
