<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\ExportsBrandedGrid;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\CasteCategoryMasterDataTable;
use App\Models\CasteCategoryMaster;
use Illuminate\Validation\Rule;

class CasteCategoryMasterController extends Controller
{
    use ExportsBrandedGrid;

    public function index()
    {
        return (new CasteCategoryMasterDataTable())->render('admin.master.caste_category.index');
    }
    public function create()
    {
        return view('admin.master.caste_category.create');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'Seat_name' => [
                'required',
                'string',
                // Both columns are varchar(30); max:255 let MySQL truncate silently.
                'max:30',
                Rule::unique('caste_category_master', 'Seat_name')->ignore($id, 'pk'),
            ],
            'Seat_name_hindi' => [
                'required',
                'string',
                'max:30',
                Rule::unique('caste_category_master', 'Seat_name_hindi')->ignore($id, 'pk'),
            ]
        ];

        $request->validate($rules);

        $casteCategory = $id ? CasteCategoryMaster::find($id) : new CasteCategoryMaster();

        if ($id && !$casteCategory) {
            return redirect()->back()->with('error', 'Caste Category not found.');
        }

        $casteCategory->Seat_name = $request->Seat_name;
        $casteCategory->Seat_name_hindi = $request->Seat_name_hindi;
        $casteCategory->save();

        $message = $id ? 'Caste Category updated successfully.' : 'Caste Category created successfully.';

        // The listing's Add/Edit modal posts here over AJAX; a failed validation
        // already returns 422 JSON on its own, so success has to answer in kind.
        // The standalone create page still posts normally and gets the redirect.
        if ($request->expectsJson()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->route('master.caste.category.index')->with('success', $message);
    }

    /**
     * The listing's export columns - the same four the grid shows (the Action
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
            'seat_name' => [
                'heading' => 'Category/Caste Name',
                'class' => 'col-name',
                'value' => fn ($row) => (string) ($row->Seat_name ?: '-'),
            ],
            'seat_name_hindi' => [
                'heading' => 'Category/Caste Name (Hindi)',
                'class' => 'col-name-hi',
                'value' => fn ($row) => (string) ($row->Seat_name_hindi ?: '-'),
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Caste Master -> CSV / Excel / PDF / Print, all four off one query and one
     * column list via {@see ExportsBrandedGrid}. The search matches either name,
     * exactly like the grid's own filter.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = trim((string) $request->query('q', ''));

        $rows = CasteCategoryMaster::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('Seat_name', 'like', "%{$search}%")
                        ->orWhere('Seat_name_hindi', 'like', "%{$search}%");
                });
            })
            ->orderBy('pk')
            ->get();

        return $this->brandedGridResponse(
            $format,
            'Caste Master',
            'CasteMaster',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            $search !== '' ? 'Search: ' . $search : null,
            [
                'emptyText' => 'No caste categories to export',
                'centeredKeys' => ['sno', 'status'],
                // The Hindi column is Devanagari, which DomPDF draws unshaped —
                // matras land on the wrong side of their consonant and viramas
                // drop, so "अन्य पिछड़ा वर्ग" prints as "अनय पछिडा वरग". mPDF
                // shapes it correctly (see ExportsBrandedGrid::brandedGridMpdf).
                'pdfEngine' => 'mpdf',
                'columnStyles' => '
        .col-sno     { width: 10%; text-align: center; }
        .col-name    { width: 37%; }
        .col-name-hi { width: 37%; }
        .col-status  { width: 16%; text-align: center; }',
            ]
        );
    }
    public function edit($id)
    {
        $casteCategory = CasteCategoryMaster::findOrFail(decrypt($id));
        return view('admin.master.caste_category.create', compact('casteCategory'));
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
