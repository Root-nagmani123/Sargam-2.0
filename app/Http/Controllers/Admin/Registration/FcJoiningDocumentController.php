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

    public function create()
    {
        $documents = FcJoiningDocument::where('user_id', auth()->id())->first();
        $userId = Auth::id();

        return view('admin.forms.joining_document', compact('documents', 'userId'));
    }


    public function store(Request $request)
    {
        $userId = Auth::id();

        $dataToInsert = ['user_id' => $userId];

        foreach ($this->documents as $field => $meta) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store("fc_joining_documents/{$userId}", 'public');
                $dataToInsert[$field] = $path;
            }
        }

        // Insert or update based on existence
        DB::table('fc_joining_documents_user_uploads')->updateOrInsert(
            ['user_id' => $userId],
            $dataToInsert + ['updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('success', 'Documents uploaded successfully.');
    }

    // Function to display the joining document report index
    // public function fc_report_index(Request $request)
    // {
    // $query = DB::table('fc_joining_documents_user_uploads as uploads')
    //     ->join('student_master as students', 'uploads.user_id', '=', 'students.pk')
    //     ->select('uploads.*', 'students.display_name');

    // // Search by OT name
    // if ($request->filled('search')) {
    //     $query->where('students.display_name', 'like', '%' . $request->search . '%');
    // }

    // // Filter by status (complete if all filled, pending otherwise)
    // if ($request->filled('status')) {
    //     $query->where(function ($q) use ($request) {
    //         if ($request->status === 'complete') {
    //             $q->whereNotNull('uploads.admin_family_details_form')
    //                 ->whereNotNull('uploads.admin_close_relation_declaration')
    //                 ->whereNotNull('uploads.admin_dowry_declaration');
    //             // ... Add all other fields
    //         } else {
    //             $q->orWhereNull('uploads.admin_family_details_form')
    //                 ->orWhereNull('uploads.admin_close_relation_declaration')
    //                 ->orWhereNull('uploads.admin_dowry_declaration');
    //             // ... Add all other fields
    //         }
    //     });
    // }

    // $reports = $query->get();

    // // @dd($reports);
    // return view('admin.report.joining_documents_report', compact('reports'));
    //  {
    // Assuming student_master table has OT user info
    public function fc_report_index(Request $request)
    {
        // Define document fields with readable labels
        $fields = [
            'admin_family_details_form' => 'Family Details Doc',
            'admin_close_relation_declaration' => 'Close Relation Doc',
            'admin_dowry_declaration' => 'Dowry Declaration',
            'admin_marital_status' => 'Marital Declaration',
            'admin_home_town_declaration' => 'HomeTown Doc',
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

        // // Get all students
        // $students = DB::table('student_master')
        //     ->select('pk', 'display_name', 'schema_id')
        //     ->paginate(10);

        // // Get uploads
        // $uploads = DB::table('fc_joining_documents_user_uploads')->get()->keyBy('user_id');

        // return view('admin.report.joining_documents_report', compact('fields', 'students', 'uploads'));



        // Base query
        // $query = DB::table('student_master')
        //     ->select('pk', 'display_name', 'schema_id');

        // // Search filter
        // if ($request->filled('search')) {
        //     $query->where('display_name', 'like', '%' . $request->search . '%');
        // }

        // // Apply status filter AFTER retrieving uploads
        // $uploads = DB::table('fc_joining_documents_user_uploads')->get()->keyBy('user_id');
        //     // @dd($query->toSql());

        // if ($request->filled('status')) {
        //     $fieldsKeys = array_keys($fields);

        //     // Filter by user_id based on whether all documents are uploaded
        //     $filteredUserIds = collect($uploads)->filter(function ($upload) use ($fieldsKeys, $request) {
        //         $allUploaded = collect($fieldsKeys)->every(fn($key) => !empty($upload->$key));
        //         return $request->status == '1' ? $allUploaded : !$allUploaded;
        //     })->keys();

        //     $query->whereIn('pk', $filteredUserIds);
        //     // @dd($request->status, $filteredUserIds);;
        // }

        // // Paginate after filters
        // $students = $query->orderBy('display_name')->paginate(10)->appends($request->query());

        // // Return view with students and uploads
        // return view('admin.report.joining_documents_report', compact('fields', 'students', 'uploads'));

        $query = DB::table('student_master')->select('pk', 'display_name', 'schema_id');

        $search = $request->input('search');
        $status = $request->input('status');

        if ($search) {
            $query->where('display_name', 'like', '%' . $search . '%');
        }

        // Get all uploads (needed before status filtering)
        $allUploads = DB::table('fc_joining_documents_user_uploads')->get()->keyBy('user_id');

        if ($request->filled('status')) {
            $fieldsKeys = array_keys($fields);

            $allStudentIds = $query->pluck('pk')->toArray();

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
        }

        // Finally paginate
        $students = $query->orderBy('display_name')->paginate(20);

        // Get uploads only for paginated students
        $studentIds = $students->pluck('pk')->toArray();
        $uploads = $allUploads->only($studentIds);

        return view('admin.report.joining_documents_report', compact('fields', 'students', 'uploads'));
    }

    // Download all documents for a specific user
    // public function downloadAll($userId)
    // {
    //     $user = DB::table('fc_joining_documents_user_uploads')->where('user_id', $userId)->first();

    //     if (!$user) abort(404);

    //     $zipFileName = 'user_docs_' . $userId . '.zip';
    //     $zip = new \ZipArchive;
    //     $tempFile = storage_path("app/temp/{$zipFileName}");

    //     if ($zip->open($tempFile, \ZipArchive::CREATE) === TRUE) {
    //         foreach ((array) $user as $key => $value) {
    //             if (Str::endsWith($key, '_form') || Str::endsWith($key, '_declaration') || Str::endsWith($key, '_doc') || Str::endsWith($key, '_bond') || Str::endsWith($key, '_sheet')) {
    //                 if (!empty($value) && Storage::disk('public')->exists($value)) {
    //                     $zip->addFile(storage_path("app/public/{$value}"), basename($value));
    //                 }
    //             }
    //         }
    //         $zip->close();

    //         return response()->download($tempFile)->deleteFileAfterSend(true);
    //     }

    //     return back()->with('error', 'Could not create zip.');
    // }

    //     public function downloadAll($userId)
    // {
    //     $user = DB::table('fc_joining_documents_user_uploads')->where('user_id', $userId)->first();
    //     $student = DB::table('student_master')->where('pk', $userId)->first();

    //     if (!$user || !$student) abort(404);

    //     $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student->display_name);
    //     $zipFileName = $cleanName . '_joining_documents.zip';
    //     $tempFile = storage_path("app/temp/{$zipFileName}");

    //     $zip = new \ZipArchive;
    //     if ($zip->open($tempFile, \ZipArchive::CREATE) === TRUE) {
    //         foreach ((array) $user as $key => $value) {
    //             if (
    //                 Str::endsWith($key, '_form') ||
    //                 Str::endsWith($key, '_declaration') ||
    //                 Str::endsWith($key, '_doc') ||
    //                 Str::endsWith($key, '_bond') ||
    //                 Str::endsWith($key, '_sheet')
    //             ) {
    //                 if (!empty($value) && Storage::disk('public')->exists($value)) {
    //                     $zip->addFile(storage_path("app/public/{$value}"), basename($value));
    //                 }
    //             }
    //         }

    //         $zip->close();
    //         return response()->download($tempFile, $zipFileName)->deleteFileAfterSend(true);
    //     }

    //     return back()->with('error', 'Could not create zip.');
    // }
    public function downloadAll($userId)
    {
        $user = DB::table('fc_joining_documents_user_uploads')->where('user_id', $userId)->first();
        $student = DB::table('student_master')->where('pk', $userId)->first();

        if (!$user || !$student) {
            return redirect()->back()->with('error', 'No uploaded documents found for this user.');
        }

        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student->display_name);
        $zipFileName = $cleanName . '_joining_documents.zip';
        $tempFile = storage_path("app/temp/{$zipFileName}");

        $zip = new \ZipArchive;
        if ($zip->open($tempFile, \ZipArchive::CREATE) === TRUE) {
            $hasFiles = false;

            foreach ((array) $user as $key => $value) {
                if (!empty($value) && Storage::disk('public')->exists($value)) {
                    $zip->addFile(storage_path("app/public/{$value}"), basename($value));
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
