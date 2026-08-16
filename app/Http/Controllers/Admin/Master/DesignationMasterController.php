<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\ExportsBrandedGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignationMaster;
use App\DataTables\Master\DesignationMasterDataTable;
use Illuminate\Validation\Rule; 
class DesignationMasterController extends Controller
{
    use ExportsBrandedGrid;

    function index()
    {
        $designationMaster = new DesignationMasterDataTable;
        return $designationMaster->render('admin.master.designation.index');
        // return view('admin.master.designation.index');
    }
    function create()
    {
        return view('admin.master.designation.create');
    }
    function store(Request $request)
    {


        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'designation_name' => [
                'required',
                'string',
                // The column is varchar(100); max:255 let MySQL truncate silently.
                'max:100',
                Rule::unique('designation_master', 'designation_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $designation = $id ? DesignationMaster::find($id) : new DesignationMaster();

        if ($id && !$designation) {
            return redirect()->back()->with('error', 'Designation not found.');
        }

        $designation->designation_name = $request->designation_name;
        $designation->save();

        $message = $id ? 'Designation updated successfully.' : 'Designation created successfully.';

        // The listing's Add/Edit modal posts here over AJAX; a failed validation
        // already returns 422 JSON on its own, so success has to answer in kind.
        // The standalone create page still posts normally and gets the redirect.
        if ($request->expectsJson()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->route('master.designation.index')->with('success', $message);

    }

    /**
     * The listing's export columns - the same three the grid shows (the Action
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
            'designation_name' => [
                'heading' => 'Designation Name',
                'class' => 'col-name',
                'value' => fn ($row) => (string) ($row->designation_name ?: '-'),
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Designation Master -> CSV / Excel / PDF / Print, all four off one query and
     * one column list via {@see ExportsBrandedGrid}.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = trim((string) $request->query('q', ''));

        $rows = DesignationMaster::query()
            ->when($search !== '', fn ($query) => $query->where('designation_name', 'like', "%{$search}%"))
            ->orderBy('pk')
            ->get();

        return $this->brandedGridResponse(
            $format,
            'Designation Master',
            'DesignationMaster',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            $search !== '' ? 'Search: ' . $search : null,
            [
                'emptyText' => 'No designations to export',
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
            $designationMaster = DesignationMaster::find(decrypt($id));
            return view('admin.master.designation.create', compact('designationMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit designation: ' . $e->getMessage());
        }
    }
    
}
