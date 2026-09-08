<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\ExportsBrandedGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\Models\EmployeeTypeMaster;
use Illuminate\Validation\Rule;

class EmployeeTypeMasterController extends Controller
{
    use ExportsBrandedGrid;

    function index()
    {
        $employeeTypeMaster = new EmployeeTypeMasterDataTable;
        return $employeeTypeMaster->render('admin.master.employee_type.index');
        // return view('admin.master.employee_type.index');
    }
    function create()
    {
        return view('admin.master.employee_type.create');
    }
    function store(Request $request)
    {

        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'employee_type_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_type_master', 'category_type_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $employeeType = $id ? EmployeeTypeMaster::find($id) : new EmployeeTypeMaster();

        if ($id && !$employeeType) {
            return redirect()->back()->with('error', 'Employee Type not found.');
        }
        
        $employeeType->category_type_name = $request->employee_type_name;
        $employeeType->save();

        $message = $id ? 'Employee Type updated successfully.' : 'Employee Type created successfully.';

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        // The listing's Add/Edit modal posts here over AJAX; a failed validation
        // already returns 422 JSON on its own, so success has to answer in kind.
        // The standalone create page still posts normally and gets the redirect.
        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.employee.type.index')->with('success', $message);

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
            'category_type_name' => [
                'heading' => 'Category Type Name',
                'class' => 'col-name',
                'value' => fn ($row) => (string) ($row->category_type_name ?: '-'),
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Employee Type Master → CSV / Excel / PDF / Print, all four off one query
     * and one column list via {@see ExportsBrandedGrid}. Honours the grid's
     * search box and Columns modal.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = trim((string) $request->query('q', ''));

        $rows = EmployeeTypeMaster::query()
            ->when($search !== '', fn ($query) => $query->where('category_type_name', 'like', "%{$search}%"))
            ->orderBy('pk')
            ->get();

        return $this->brandedGridResponse(
            $format,
            'Employee Type Master',
            'EmployeeTypeMaster',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            $search !== '' ? 'Search: ' . $search : null,
            [
                'emptyText' => 'No employee types to export',
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
            $employeeTypeMaster = EmployeeTypeMaster::find(decrypt($id));
            
            return view('admin.master.employee_type.create', compact('employeeTypeMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit employee type: ' . $e->getMessage());
        }
    }
    // function delete($id)
    // {
    //     // Logic to delete department by ID
    //     return redirect()->route('master.department.index')->with('success', 'Department deleted successfully.');
    // }
}
