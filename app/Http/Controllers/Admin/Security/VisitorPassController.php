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
    public function index(Request $request)
    {
        // Grid is server-side (components.mess-master-datatables): rows are fetched per page.
        if ($request->ajax() && $request->has('draw')) {
            return $this->visitorPassDatatable($request);
        }

        return view('admin.security.visitor_pass.index');
    }

    /**
     * Server-side rows for the visitor pass grid: search, sort and paging run in SQL.
     * Each row is a positional array of cell HTML, matching the grid's column order.
     */
    protected function visitorPassDatatable(Request $request)
    {
        $base = SecVisitorCardGenerated::with(['employee', 'visitorNames', 'createdBy']);

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $recordsTotal = (clone $base)->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $base->where(function ($q) use ($like) {
                $q->where('pass_number', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('purpose', 'like', $like)
                    ->orWhereHas('visitorNames', fn ($v) => $v->where('visitor_name', 'like', $like))
                    ->orWhereHas('employee', function ($e) use ($like) {
                        $e->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like);
                    });
            });
        }

        $recordsFiltered = (clone $base)->count();

        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = [
            0 => 'pass_number',
            2 => 'company',
            3 => 'purpose',
            5 => 'in_time',
            6 => 'out_time',
        ];

        $paged = clone $base;
        if (isset($sortable[$orderColumn])) {
            $paged->orderBy($sortable[$orderColumn], $orderDir);
        } else {
            $paged->orderBy('created_date', 'desc');
        }

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $data = $paged->get()->map(function (SecVisitorCardGenerated $pass) {
            $names = $pass->visitorNames;
            if ($names->count() > 0) {
                $visitorCell = $names->take(2)->pluck('visitor_name')->map(fn ($n) => e($n))->implode(', ');
                if ($names->count() > 2) {
                    $visitorCell .= ' <small class="text-muted">(+'.($names->count() - 2).' more)</small>';
                }
            } else {
                $visitorCell = '--';
            }

            $actions = '<div class="d-flex gap-2">'
                .'<a href="'.route('admin.security.visitor_pass.show', encrypt($pass->pk)).'" class="text-info" title="View">'
                .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i></a>';

            if (! $pass->out_time) {
                $actions .= '<form action="'.route('admin.security.visitor_pass.checkout', encrypt($pass->pk)).'" method="POST" onsubmit="return confirm(\'Mark visitor as checked out?\')">'
                    .csrf_field()
                    .'<button type="submit" class="btn btn-link p-0 text-warning" title="Check Out">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">logout</i></button></form>'
                    .'<a href="'.route('admin.security.visitor_pass.edit', encrypt($pass->pk)).'" class="text-success" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>';
            }

            $actions .= '<form action="'.route('admin.security.visitor_pass.delete', encrypt($pass->pk)).'" method="POST" onsubmit="return confirm(\'Delete this visitor pass?\')">'
                .csrf_field().method_field('DELETE')
                .'<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button></form></div>';

            return [
                e($pass->pass_number),
                $visitorCell,
                e($pass->company ?? '--'),
                e(\Str::limit($pass->purpose, 30)),
                e($pass->employee ? $pass->employee->first_name.' '.$pass->employee->last_name : '--'),
                $pass->in_time ? e($pass->in_time->format('d-m-Y H:i')) : '--',
                $pass->out_time ? e($pass->out_time->format('d-m-Y H:i')) : '<span class="badge bg-success">Active</span>',
                $actions,
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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
