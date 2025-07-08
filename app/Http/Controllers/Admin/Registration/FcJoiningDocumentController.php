<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\FcJoiningDocument;

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
}
