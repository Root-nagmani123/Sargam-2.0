<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecVisitorCardGenerated;
use App\Models\SecVisitorName;
use App\Models\EmployeeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class VisitorPassController extends Controller
{
    public function index()
    {
        $visitorPasses = SecVisitorCardGenerated::with(['employee', 'visitorNames', 'createdBy'])
            ->orderBy('created_date', 'desc')
            ->paginate(10);

        return view('admin.security.visitor_pass.index', compact('visitorPasses'));
    }

    public function create()
    {
        $employees = EmployeeMaster::where('status', 1)->get();
        return view('admin.security.visitor_pass.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_names' => ['required', 'array', 'min:1'],
            'visitor_names.*' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'employee_master_pk' => ['required', 'exists:employee_master,pk'],
            'purpose' => ['required', 'string'],
            'in_time' => ['required', 'date'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'identity_card' => ['nullable', 'string', 'max:100'],
            'id_no' => ['nullable', 'string', 'max:50'],
            'valid_for_days' => ['required', 'integer', 'min:1', 'max:30'],
            'upload_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $employeePk = $user->user_id ?? null;

            // Handle file upload
            $uploadPath = null;
            if ($request->hasFile('upload_path')) {
                $uploadPath = $request->file('upload_path')->store('visitor_documents', 'public');
            }

            // Generate pass number
            $passNumber = $this->generatePassNumber();

            $visitorPass = new SecVisitorCardGenerated();
            $visitorPass->pass_number = $passNumber;
            $visitorPass->vehicle_number = $validated['vehicle_number'];
            $visitorPass->company = $validated['company'];
            $visitorPass->address = $validated['address'];
            $visitorPass->employee_master_pk = $validated['employee_master_pk'];
            $visitorPass->purpose = $validated['purpose'];
            $visitorPass->in_time = $validated['in_time'];
            $visitorPass->mobile_number = $validated['mobile_number'];
            $visitorPass->identity_card = $validated['identity_card'];
            $visitorPass->id_no = $validated['id_no'];
            $visitorPass->valid_for_days = $validated['valid_for_days'];
            $visitorPass->issued_date = now()->toDateString();
            $visitorPass->upload_path = $uploadPath;
            $visitorPass->created_by = $employeePk;
            $visitorPass->created_date = now();
            $visitorPass->save();

            // Save visitor names
            foreach ($validated['visitor_names'] as $name) {
                if (!empty(trim($name))) {
                    $visitorName = new SecVisitorName();
                    $visitorName->sec_visitor_card_generated_pk = $visitorPass->pk;
                    $visitorName->visitor_name = trim($name);
                    $visitorName->created_date = now();
                    $visitorName->save();
                }
            }

            DB::commit();

            return redirect()->route('admin.security.visitor_pass.index')->with('success', 'Visitor Pass created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating visitor pass: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $visitorPass = SecVisitorCardGenerated::with(['employee', 'visitorNames', 'createdBy'])
            ->findOrFail($pk);

        return view('admin.security.visitor_pass.show', compact('visitorPass'));
    }

    public function edit($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $visitorPass = SecVisitorCardGenerated::with('visitorNames')->findOrFail($pk);
        $employees = EmployeeMaster::where('status', 1)->get();

        return view('admin.security.visitor_pass.edit', compact('visitorPass', 'employees'));
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $visitorPass = SecVisitorCardGenerated::findOrFail($pk);

        $validated = $request->validate([
            'visitor_names' => ['required', 'array', 'min:1'],
            'visitor_names.*' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'employee_master_pk' => ['required', 'exists:employee_master,pk'],
            'purpose' => ['required', 'string'],
            'out_time' => ['nullable', 'date'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'identity_card' => ['nullable', 'string', 'max:100'],
            'id_no' => ['nullable', 'string', 'max:50'],
            'valid_for_days' => ['required', 'integer', 'min:1', 'max:30'],
            'upload_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        DB::beginTransaction();
        try {
            // Handle file upload
            if ($request->hasFile('upload_path')) {
                // Delete old file
                if ($visitorPass->upload_path) {
                    Storage::disk('public')->delete($visitorPass->upload_path);
                }
                $visitorPass->upload_path = $request->file('upload_path')->store('visitor_documents', 'public');
            }

            $visitorPass->vehicle_number = $validated['vehicle_number'];
            $visitorPass->company = $validated['company'];
            $visitorPass->address = $validated['address'];
            $visitorPass->employee_master_pk = $validated['employee_master_pk'];
            $visitorPass->purpose = $validated['purpose'];
            $visitorPass->out_time = $validated['out_time'];
            $visitorPass->mobile_number = $validated['mobile_number'];
            $visitorPass->identity_card = $validated['identity_card'];
            $visitorPass->id_no = $validated['id_no'];
            $visitorPass->valid_for_days = $validated['valid_for_days'];
            $visitorPass->modified_date = now();
            $visitorPass->save();

            // Update visitor names
            // Delete existing names
            SecVisitorName::where('sec_visitor_card_generated_pk', $visitorPass->pk)->delete();

            // Add new names
            foreach ($validated['visitor_names'] as $name) {
                if (!empty(trim($name))) {
                    $visitorName = new SecVisitorName();
                    $visitorName->sec_visitor_card_generated_pk = $visitorPass->pk;
                    $visitorName->visitor_name = trim($name);
                    $visitorName->created_date = now();
                    $visitorName->save();
                }
            }

            DB::commit();

            return redirect()->route('admin.security.visitor_pass.index')->with('success', 'Visitor Pass updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating visitor pass: ' . $e->getMessage())->withInput();
        }
    }

    public function delete($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $visitorPass = SecVisitorCardGenerated::findOrFail($pk);

        // Delete uploaded document
        if ($visitorPass->upload_path) {
            Storage::disk('public')->delete($visitorPass->upload_path);
        }

        $visitorPass->delete();

        return redirect()->route('admin.security.visitor_pass.index')->with('success', 'Visitor Pass deleted successfully');
    }

    public function checkOut(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $visitorPass = SecVisitorCardGenerated::findOrFail($pk);
        $visitorPass->out_time = now();
        $visitorPass->modified_date = now();
        $visitorPass->save();

        return redirect()->route('admin.security.visitor_pass.index')->with('success', 'Visitor checked out successfully');
    }

    private function generatePassNumber()
    {
        $lastPass = SecVisitorCardGenerated::orderBy('pk', 'desc')->first();
        $lastNumber = $lastPass ? $lastPass->pass_number : 0;
        return $lastNumber + 1;
    }
}
