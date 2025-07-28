<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\FcJoiningDocument;
use Illuminate\Support\Str;

class FcJoiningDocumentController extends Controller
{
    protected $documents = [
        // Admin section
        'admin_family_details_form'       => ['title' => 'Family Details Form (Form - 3)', 'section' => 'admin'],
        'admin_close_relation_declaration' => ['title' => 'Nationality/Domicile Declaration', 'section' => 'admin'],
        'admin_dowry_declaration'         => ['title' => 'Dowry Declaration', 'section' => 'admin'],
        'admin_marital_status'            => ['title' => 'Marital Status Declaration', 'section' => 'admin'],
        'admin_home_town_declaration'     => ['title' => 'Home Town Declaration', 'section' => 'admin'],
        'admin_property_immovable'        => ['title' => '6-A: Immovable Property', 'section' => 'admin'],
        'admin_property_movable'          => ['title' => '6-B: Movable Property', 'section' => 'admin'],
        'admin_property_liabilities'      => ['title' => '6-C: Debts and Liabilities', 'section' => 'admin'],
        'admin_bond_ias_ips_ifos'         => ['title' => 'IAS/IPS/IFoS Bond', 'section' => 'admin'],
        'admin_bond_other_services'       => ['title' => 'Other Services Bond', 'section' => 'admin'],
        'admin_other_documents'           => ['title' => 'Other Documents', 'section' => 'admin'],
        'admin_oath_affirmation'          => ['title' => 'Form of OATH / Affirmation', 'section' => 'admin'],
        'admin_certificate_of_charge'     => ['title' => 'Certificate of Assumption of Charge', 'section' => 'admin'],

        // Accounts Section
        'accounts_nomination_form'        => ['title' => 'Form-7 (Unmarried) or Form-8 (Married)', 'section' => 'accounts'],
        'accounts_nps_registration'       => ['title' => 'NPS Registration Form', 'section' => 'accounts'],
        'accounts_employee_info_sheet'    => ['title' => 'Employee Information Sheet', 'section' => 'accounts'],
    ];

    public function create($formId)
    {
        $form = DB::table('local_form')->where('id', $formId)->first();
        if (!$form) abort(404, 'Form not found');

        // Determine parent form ID
        $parentFormId = ($form->parent_id && $form->parent_id != 0) ? $form->parent_id : $form->id;

        // Fetch children of the parent form
        $childForms = DB::table('local_form')
            ->where('parent_id', $parentFormId)
            ->where('visible', 1)
            ->orderBy('sortorder')
            ->get();

        $documents = FcJoiningDocument::where('user_id', auth()->id())->first();
        $userId = Auth::id();
        // dd($documents->admin_family_details_form);

        return view('admin.forms.joining_document', compact('documents', 'userId', 'form', 'childForms', 'formId'));
    }


    // public function store(Request $request)
    // {
    //     $userId = Auth::id();

    //     $dataToInsert = ['user_id' => $userId];

    //     foreach ($this->documents as $field => $meta) {
    //         if ($request->hasFile($field)) {
    //             $file = $request->file($field);
    //             $path = $file->store("fc_joining_documents/{$userId}", 'public');
    //             $dataToInsert[$field] = $path;
    //         }
    //     }

    //     // Insert or update based on existence
    //     DB::table('fc_joining_documents_user_uploads')->updateOrInsert(
    //         ['user_id' => $userId],
    //         $dataToInsert + ['updated_at' => now(), 'created_at' => now()]
    //     );

    //     return back()->with('success', 'Documents uploaded successfully.');
    // }

    public function store(Request $request)
    {
        $userId = Auth::id();
        // dd($userId);    

        // Build validation rules dynamically
        $rules = [];
        foreach ($this->documents as $field => $meta) {
            if ($request->hasFile($field)) {
                $rules[$field] = 'file|mimes:pdf|max:1024'; // 1024 KB = 1 MB
            }
        }

        // Validate request
        $validated = $request->validate($rules);

        // Proceed with storing
        $dataToInsert = ['user_id' => $userId];
        foreach ($this->documents as $field => $meta) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store("fc_joining_documents/{$userId}", 'public');
                $dataToInsert[$field] = $path;
            }
        }

        // Insert or update
        DB::table('fc_joining_documents_user_uploads')->updateOrInsert(
            ['user_id' => $userId],
            $dataToInsert + ['updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('success', 'Documents uploaded successfully.');
    }

    // Assuming student_master table has OT user info
    public function fc_report_index(Request $request, $formId)
    {
        // Define document fields with readable labels
        $fields = [
            'admin_family_details_form' => 'Family Details Declaration',
            'admin_close_relation_declaration' => 'Close Relation Declaration',
            'admin_dowry_declaration' => 'Dowry Declaration',
            'admin_marital_status' => 'Marital Declaration',
            'admin_home_town_declaration' => 'HomeTown Declaration',
            'admin_property_immovable' => 'Immovable Property',
            'admin_property_movable' => 'Movable Property',
            'admin_property_liabilities' => 'Debts And Liabilities',
            'admin_bond_ias_ips_ifos' => 'Surety Bond (IAS/IPS)',
            'admin_bond_other_services' => 'Surety Bond (Other services)',
            'admin_oath_affirmation' => 'Oath Affirmation',
            'admin_certificate_of_charge' => 'Certificate Assumption',
            'accounts_nomination_form' => 'Nomination Form',
            'accounts_nps_registration' => 'NPS Form',
            'accounts_employee_info_sheet' => 'Employee Information',
        ];



        $form = DB::table('local_form')->where('id', $formId)->first();
        if (!$form) abort(404, 'Form not found');

        // Determine parent form ID
        $parentFormId = ($form->parent_id && $form->parent_id != 0) ? $form->parent_id : $form->id;

        // Fetch children of the parent form
        $childForms = DB::table('local_form')
            ->where('parent_id', $parentFormId)
            ->where('visible', 1)
            ->orderBy('sortorder')
            ->get();
        // $query = DB::table('user_credentials')->select('pk', 'first_name', 'last_name');
        // $query = DB::table('users')->select('id', 'name', 'id');
        $query = DB::table('user_credentials')
            ->select('pk', DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))
            ->orderByRaw("CONCAT(first_name, ' ', last_name)");


        $search = $request->input('search');
        $status = $request->input('status');

        // if ($search) {
        //     $query->where('first_name', 'like', '%' . $search . '%');
        //     // $query->where('name', 'like', '%' . $search . '%');
        // }

        if ($search) {
            $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $search . '%');
        }


        // Get all uploads (needed before status filtering)
        $allUploads = DB::table('fc_joining_documents_user_uploads')->get()->keyBy('user_id');

        if ($request->filled('status')) {
            $fieldsKeys = array_keys($fields);

            $allStudentIds = $query->pluck('pk')->toArray();
            // $allStudentIds = $query->pluck('id')->toArray();

            $filteredUserIds = [];

            foreach ($allStudentIds as $studentId) {
                $upload = $allUploads->get($studentId);
                $allUploaded = $upload && collect($fieldsKeys)->every(fn($key) => !empty($upload->$key));

                if ($status == '1' && $allUploaded) {
                    $filteredUserIds[] = $studentId;
                } elseif ($status == '0' && !$allUploaded) {
                    $filteredUserIds[] = $studentId;
                }
            }

            $query->whereIn('pk', $filteredUserIds);
            // $query->whereIn('id', $filteredUserIds);
        }

        // Finally paginate
        $students = $query->paginate(20);
        // $students = $query->orderBy('name')->paginate(20);


        // Get uploads only for paginated students
        $studentIds = $students->pluck('pk')->toArray();
        // $studentIds = $students->pluck('id')->toArray();
        $uploads = $allUploads->only($studentIds);

        return view('admin.report.joining_documents_report', compact('fields', 'students', 'uploads', 'form', 'childForms', 'formId'))
            ->with('search', $search)
            ->with('status', $status);
    }


    // public function downloadAll($userId)
    // {
    //     $user = DB::table('fc_joining_documents_user_uploads')->where('user_id', $userId)->first();
    //     $student = DB::table('user_credentials')->where('pk', $userId)->first();

    //     // $user = DB::table('fc_joining_documents_user_uploads')->where('user_id', $userId)->first();
    //     // $student = DB::table('users')->where('id', $userId)->first();

    //     if (!$user || !$student) {
    //         return redirect()->back()->with('error', 'No uploaded documents found for this user.');
    //     }

    //     // Combine first and last name
    //     $fullName = trim($student->first_name . ' ' . $student->last_name);

    //     // Clean for filename usage
    //     $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName);
    //     // $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student->display_name);
    //     $zipFileName = $cleanName . '_joining_documents.zip';
    //     $tempFile = storage_path("app/temp/{$zipFileName}");

    //     $zip = new \ZipArchive;
    //     if ($zip->open($tempFile, \ZipArchive::CREATE) === TRUE) {
    //         $hasFiles = false;

    //         foreach ((array) $user as $key => $value) {
    //             if (!empty($value) && Storage::disk('public')->exists($value)) {
    //                 $zip->addFile(storage_path("app/public/{$value}"), basename($value));
    //                 $hasFiles = true;
    //             }
    //         }

    //         $zip->close();

    //         if (!$hasFiles) {
    //             return redirect()->back()->with('error', 'No documents available to download.');
    //         }

    //         return response()->download($tempFile, $zipFileName)->deleteFileAfterSend(true);
    //     }

    //     return redirect()->back()->with('error', 'Could not create ZIP file.');
    // }


    //finall

    public function downloadAll($userId)
    {
        $user = DB::table('fc_joining_documents_user_uploads')->where('user_id', $userId)->first();
        $student = DB::table('user_credentials')->where('pk', $userId)->first();

        if (!$user || !$student) {
            return redirect()->back()->with('error', 'No uploaded documents found for this user.');
        }

        $fields = [
            'admin_family_details_form' => 'Family Details',
            'admin_close_relation_declaration' => 'Close Relation Declaration',
            'admin_dowry_declaration' => 'Dowry Declaration',
            'admin_marital_status' => 'Marital Declaration',
            'admin_home_town_declaration' => 'Home Town Declaration',
            'admin_property_declaration' => 'Property Declaration',
            'accounts_bank_details' => 'Bank Details',
            'accounts_mobile_number_form' => 'Mobile Number Declaration',
            'accounts_pan_card' => 'PAN Card',
            'accounts_cancelled_cheque' => 'Cancelled Cheque',
        ];

        $fullName = trim($student->first_name . ' ' . $student->last_name);
        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName);
        $zipFileName = $cleanName . '_joining_documents.zip';
        $tempFile = storage_path("app/temp/{$zipFileName}");

        $zip = new \ZipArchive;
        if ($zip->open($tempFile, \ZipArchive::CREATE) === TRUE) {
            $hasFiles = false;

            foreach ((array) $user as $key => $value) {
                if (!empty($value) && Storage::disk('public')->exists($value)) {
                    $label = $fields[$key] ?? $key;
                    $extension = pathinfo($value, PATHINFO_EXTENSION);
                    $fileNameInZip = preg_replace('/[^A-Za-z0-9_\-]/', '_', $label) . '.' . $extension;

                    $zip->addFile(storage_path("app/public/{$value}"), $fileNameInZip);
                    $hasFiles = true;
                }
            }

            $zip->close();

            if (!$hasFiles) {
                return redirect()->back()->with('error', 'No documents available to download.');
            }

            return response()->download($tempFile, $zipFileName)->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Could not create ZIP file.');
    }


    // Function to save remarks for a user
    // This function allows the admin to save remarks for a specific user.
    public function saveRemark(Request $request, $user_id)
    {
        $request->validate([
            'remark' => 'nullable|string',
        ]);

        DB::table('fc_joining_documents_user_uploads')
            ->updateOrInsert(
                ['user_id' => $user_id],
                ['remark' => $request->remark, 'updated_at' => now()]
            );

        return back()->with('success', 'Remark saved successfully.');
    }
}
