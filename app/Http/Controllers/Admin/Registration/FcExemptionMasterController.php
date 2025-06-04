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
            'Exemption_short_name' => 'required|string|max:100',
        ]);

        // Insert data into the table
        DB::table('fc_exemption_master')->insert([
            'Exemption_name' => $request->input('Exemption_name'),
            'Exemption_short_name' => $request->input('Exemption_short_name'),
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
            'Exemption_short_name' => 'required|string|max:100',
        ]);

        $exemption = FcExemptionMaster::findOrFail($id);
        $exemption->update([
            'Exemption_name' => $request->Exemption_name,
            'Exemption_short_name' => $request->Exemption_short_name,
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
    // Validation rules
    $rules = [
        'ex_mobile' => 'required|digits_between:7,15',
        'reg_web_code' => 'required|string',
        'exemption_category' => 'required|exists:fc_exemption_master,Pk',
        'captcha' => 'required|captcha',
    ];

    // Check if medical doc upload is required based on exemption short name
    $exemption = DB::table('fc_exemption_master')
        ->where('Pk', $request->exemption_category)
        ->first();

    if ($exemption && strtolower($exemption->Exemption_short_name) === 'medical') {
        $rules['medical_doc'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
    }

    // Validate input
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput()
            ->with('captcha_refresh', true);
    }

    // ✅ Check if the mobile number and web code exist in fc_registration_master
    $registration = DB::table('fc_registration_master')
        ->where('contact_no', $request->ex_mobile)
        ->where('web_auth', $request->reg_web_code)
        ->first();

    if (!$registration) {
        return redirect()->back()
            ->withErrors(['reg_web_code' => 'Invalid Web Code or Mobile Number.'])
            ->withInput();
    }

    // Handle file upload if present and valid
    $medicalDocPath = null;
    if ($request->hasFile('medical_doc') && $request->file('medical_doc')->isValid()) {
        $medicalDocPath = $request->file('medical_doc')->store('medical_docs', 'public');
    }

    // Insert into database
    DB::table('exemption_data')->insert([
        'contact_no' => $request->ex_mobile,
        'user_id' => Auth::id(),
        'web_auth' => $request->reg_web_code,
        'fc_exemption_master_pk' => $request->exemption_category,
        'medical_exemption_doc' => $medicalDocPath,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return redirect()->route('exemptions.datalist')->with('success', 'Exemption form submitted successfully.');
}



    //validates user exists 

    public function verify(Request $request)
    {
        // Validate input including captcha
        $request->validate([
            'reg_mobile' => 'required',
            'reg_web_code' => 'required|string',
            'captcha' => 'required|captcha',  // Add captcha validation rule

        ], [
            'captcha.captcha' => 'Invalid captcha.',
        ]);

        // Check in fc_registration_master table
        $registration = DB::table('fc_registration_master')
            ->where('contact_no', $request->reg_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->first();

        if ($registration) {
            // Get the first visible form ID from local_form
            $form = DB::table('local_form')
                ->where('visible', 1)
                ->orderBy('id')
                ->first();

            if ($form) {
                // Redirect using formid from local_form
                return redirect()->route('forms.show', $form->id);
            } else {
                return redirect()->back()
                    ->withErrors(['formid' => 'No visible form found.']);
            }
        } else {
            return redirect()->back()
                ->withErrors(['web_auth' => 'Invalid contact number or web auth code.'])
                ->withInput();
        }
    }



    // public function verify(Request $request)
    // {
    //     // Validate input
    //     $request->validate([
    //         // 'reg_mobile' => 'required|digits_between:7,15',
    //         'reg_mobile' => 'required',
    //         'reg_web_code' => 'required|string',
    //     ]);

    //     // Check in fc_registration_master table
    //     $registration = DB::table('fc_registration_master')
    //         ->where('contact_no', $request->reg_mobile)
    //         ->where('web_auth', $request->reg_web_code)
    //         ->first();

    //     if ($registration) {
    //         // Get the first visible form ID from local_form
    //         $form = DB::table('local_form')
    //             ->where('visible', 1)
    //             ->orderBy('id') // Optional: ensure consistent ordering
    //             ->first();

    //         if ($form) {
    //             // Redirect using formid from local_form
    //             return redirect()->route('forms.show', $form->id);
    //         } else {
    //             return redirect()->back()
    //                 ->withErrors(['formid' => 'No visible form found.']);
    //         }
    //     } else {
    //         return redirect()->back()
    //             ->withErrors(['web_auth' => 'Invalid contact number or web auth code.'])
    //             ->withInput();
    //     }
    // }

    // Exemption listing
    public function exemption_list()
    {
        $submissions = DB::table('exemption_data as d') // change table name as fc_registration_master afterwards
            ->leftJoin('fc_exemption_master as e', 'd.fc_exemption_master_pk', '=', 'e.Pk')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id') // join users table
            ->select(
                'd.id',
                'd.contact_no',
                'd.web_auth',
                'd.medical_exemption_doc',
                'd.created_at',
                'e.Exemption_name',
                'e.Exemption_short_name',
                'u.name as user_name' // Assuming 'name' is the column in users table

            )
            // ->orderByDesc('d.created_at')
            ->get();

        return view('admin.forms.exemption_datalist', compact('submissions'));
    }

    // Exemption export
    public function exemptionexport(Request $request)
    {
        $format = $request->get('format');

        // Fetch data
        $submissions = DB::table('exemption_data as d') // change table name as fc_registration_master afterwards
            ->leftJoin('fc_exemption_master as e', 'd.fc_exemption_master_pk', '=', 'e.Pk')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->select(
                'd.contact_no',
                'd.web_auth',
                'e.Exemption_short_name',
                'd.medical_exemption_doc',
                'd.created_at',
                'u.name as user_name'
            )
            // ->orderByDesc('d.created_at')
            ->get();

        if ($format === 'xlsx' || $format === 'csv') {
            return Excel::download(new ExemptionDataExport($submissions), "exemption_data.$format");
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.forms.export.exemption_pdf', compact('submissions'));
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
