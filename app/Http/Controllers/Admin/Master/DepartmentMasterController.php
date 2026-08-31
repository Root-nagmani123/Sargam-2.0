<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\ExportsBrandedGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\DepartmentMasterDataTable;
use App\Models\DepartmentMaster;
use Illuminate\Validation\Rule;


class DepartmentMasterController extends Controller
{
    use ExportsBrandedGrid;

    function index()
    {
        $departmentMaster = new DepartmentMasterDataTable;
        return $departmentMaster->render('admin.master.department.index');
        // return view('admin.master.department.index');
    }
    function create()
    {
        return view('admin.master.department.create');
    }
    function store(Request $request)
    {


        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'department_name' => [
                'required',
                'string',
                // The column is varchar(100); max:255 let MySQL truncate silently.
                'max:100',
                Rule::unique('department_master', 'department_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $department = $id ? DepartmentMaster::find($id) : new DepartmentMaster();

        if ($id && !$department) {
            return redirect()->back()->with('error', 'Department not found.');
        }

        $department->department_name = $request->department_name;
        $department->save();

        $message = $id ? 'Department updated successfully.' : 'Department created successfully.';

        // The listing's Add/Edit modal posts here over AJAX; a failed validation
        // already returns 422 JSON on its own, so success has to answer in kind.
        // The standalone create page still posts normally and gets the redirect.
        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.department.master.index')->with('success', $message);

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
            'department_name' => [
                'heading' => 'Department Name',
                'class' => 'col-name',
                'value' => fn ($row) => (string) ($row->department_name ?: '-'),
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Department Master → CSV / Excel / PDF / Print, all four off one query and
     * one column list via {@see ExportsBrandedGrid}. Honours the grid's search
     * box and Columns modal.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = trim((string) $request->query('q', ''));

        $rows = DepartmentMaster::query()
            ->when($search !== '', fn ($query) => $query->where('department_name', 'like', "%{$search}%"))
            ->orderBy('pk')
            ->get();

        return $this->brandedGridResponse(
            $format,
            'Department Master',
            'DepartmentMaster',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            $search !== '' ? 'Search: ' . $search : null,
            [
                'emptyText' => 'No departments to export',
                'centeredKeys' => ['sno', 'status'],
                'columnStyles' => '
        .col-sno    { width: 12%; text-align: center; }
        .col-name   { width: 68%; }
        .col-status { width: 20%; text-align: center; }',
            ]
        );
    }
    function edit($id)
    {
        try {
            $departmentMaster = DepartmentMaster::find(decrypt($id));
            return view('admin.master.department.create', compact('departmentMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit department: ' . $e->getMessage());
        }
    }
    function delete($id)
    {
        // Logic to delete department by ID
        return redirect()->route('master.department.index')->with('success', 'Department deleted successfully.');
    }
}
