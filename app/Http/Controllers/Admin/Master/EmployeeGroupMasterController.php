<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\ExportsBrandedGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeGroupMasterDataTable;
use App\Models\EmployeeGroupMaster;
use Illuminate\Validation\Rule;

class EmployeeGroupMasterController extends Controller
{
    use ExportsBrandedGrid;

    public function index()
    {
        return (new EmployeeGroupMasterDataTable())->render('admin.master.employee_group.index');
    }
    public function create()
    {
        return view('admin.master.employee_group.create');
    }

    /**
     * Create / update an employee group.
     *
     * ⚠️ The field is `emp_group_name`. This method previously validated, uniqued
     * and wrote `group_name`, which is not a column on employee_group_master —
     * every save died with "Unknown column 'group_name' in 'field list'", so
     * Add/Edit on this master never worked. The column name is now used
     * throughout; the max length matches the schema's varchar(30).
     */
    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $request->validate([
            'emp_group_name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('employee_group_master', 'emp_group_name')->ignore($id, 'pk'),
            ],
        ], [], ['emp_group_name' => 'employee group name']);

        $employeeGroup = $id ? EmployeeGroupMaster::find($id) : new EmployeeGroupMaster();

        if ($id && ! $employeeGroup) {
            return redirect()->back()->with('error', 'Employee Group not found.');
        }

        $employeeGroup->emp_group_name = $request->emp_group_name;
        $employeeGroup->save();

        $message = $id ? 'Employee Group updated successfully.' : 'Employee Group created successfully.';

        // The listing's Add/Edit modal posts here over AJAX; a failed validation
        // already returns 422 JSON on its own, so success has to answer in kind.
        // The standalone create page still posts normally and gets the redirect.
        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.employee.group.index')->with('success', $message);
    }

    /**
     * The listing's export columns — the same three the grid shows (the Action
     * cell has no export column), so a download reconciles against the screen.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn ($row, int $index) => $index + 1,
            ],
            'emp_group_name' => [
                'heading' => 'Employee Group Name',
                'class' => 'col-name',
                'value' => fn ($row) => (string) ($row->emp_group_name ?: '-'),
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Employee Group Master → CSV / Excel / PDF / Print, all four off one query
     * and one column list via {@see ExportsBrandedGrid}. Honours the grid's
     * search box and Columns modal.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = trim((string) $request->query('q', ''));

        $rows = EmployeeGroupMaster::query()
            ->when($search !== '', fn ($query) => $query->where('emp_group_name', 'like', "%{$search}%"))
            ->orderBy('pk')
            ->get();

        return $this->brandedGridResponse(
            $format,
            'Employee Group Master',
            'EmployeeGroupMaster',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            $search !== '' ? 'Search: ' . $search : null,
            [
                'emptyText' => 'No employee groups to export',
                'centeredKeys' => ['sno', 'status'],
                'columnStyles' => '
        .col-sno    { width: 12%; text-align: center; }
        .col-name   { width: 68%; }
        .col-status { width: 20%; text-align: center; }',
            ]
        );
    }
    public function edit($id)
    {
        $employeeGroupMaster = EmployeeGroupMaster::findOrFail(decrypt($id));
        // dd($employeeGroupMaster);
        return view('admin.master.employee_group.create', compact('employeeGroupMaster'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'group_name' => 'required|string|max:255',
        ]);

        $employeeGroup = \App\Models\EmployeeGroupMaster::findOrFail($id);
        $employeeGroup->update($data);
        return redirect()->route('admin.master.employee_group_master.index')->with('success', 'Employee Group updated successfully.');
    }


}
