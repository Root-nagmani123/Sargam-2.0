<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\DataTables\AppellationMasterDataTable;
use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Models\AppellationMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AppellationMasterController extends Controller
{
    use ExportsMasterGrid;

    public function index(AppellationMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.appellation.index');
    }

    /* ====================================================================
     * Export - CSV | Excel | PDF | Print   (rendering lives in ExportsMasterGrid)
     * ================================================================= */

    /**
     * Canonical export columns, in display order.
     *
     * Keys must match APPL_EXPORT_COLUMN_KEYS in appellation/index.blade.php.
     * 'sno' is not a data column; it only drives the running serial.
     *
     * @return array<string, array<string, mixed>>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'width'   => '12%',
                'align'   => 'center',
                'value'   => fn ($row, int $index) => $index + 1,
            ],
            'appellation' => [
                'heading' => 'Appellation Name',
                'width'   => '68%',
                'align'   => 'left',
                'value'   => fn ($row) => $row->appettation_name ?: 'N/A',
            ],
            'status' => [
                'heading' => 'Status',
                'width'   => '20%',
                'align'   => 'center',
                // Inactive is stored as 0 (row switch) or 2 (form) - anything
                // that isn't 1 reads as Inactive, exactly like the grid.
                'value'   => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * The grid's own query, minus paging.
     *
     * Mirrors AppellationMasterDataTable::query() (same status filter, same
     * ordering) plus the search term the grid is showing, so the export is the
     * screen the user is looking at rather than the unfiltered table. Yajra only
     * searches the one Column::make() column, so matching on the name alone
     * reproduces the grid's own result set.
     */
    private function exportQuery(string $search, string $status): Builder
    {
        $query = AppellationMaster::query();

        if ($status === '1') {
            $query->where('active_inactive', 1);
        } elseif ($status === '0') {
            $query->where(function ($q) {
                $q->where('active_inactive', '!=', 1)->orWhereNull('active_inactive');
            });
        }

        if ($search !== '') {
            $query->where('appettation_name', 'like', '%' . $search . '%');
        }

        return $query->orderBy('pk', 'desc');
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status_filter', '');
        $rows   = $this->exportQuery($search, $status)->get();

        // One plain-text filter line, shared by all four headers.
        $filters = array_values(array_filter([
            $search !== '' ? 'Search: ' . $search : null,
            $status === '1' ? 'Status: Active' : ($status === '0' ? 'Status: Inactive' : null),
        ]));

        return $this->renderMasterExport(
            $format,
            $rows,
            $this->resolveExportColumns($request, $this->exportColumnDefs()),
            'Appellation Master',
            'AppellationMaster',
            $filters === [] ? null : implode('  |  ', $filters),
            'No appellations to export'
        );
    }

    public function create()
    {
        return view('admin.master.appellation.create_edit');
    }

    public function edit($id)
    {
        $appellation = AppellationMaster::findOrFail(decrypt($id));
        return view('admin.master.appellation.create_edit', compact('appellation'));
    }

    public function store(Request $request)
    {
        $pk = $request->id ? decrypt($request->id) : null;

        $request->validate([
            'appettation_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z\s\.]+$/',
                \Illuminate\Validation\Rule::unique('appellation_master', 'appettation_name')->ignore($pk, 'pk'),
            ],
            'active_inactive'  => 'required|in:1,2',
        ], [
            'appettation_name.required' => 'Appellation name is required.',
            'appettation_name.regex'    => 'Appellation name must contain only letters and spaces.',
            'appettation_name.max'      => 'Appellation name must not exceed 50 characters.',
            'appettation_name.unique'   => 'This appellation name already exists.',
            'active_inactive.required'  => 'Status is required.',
            'active_inactive.in'        => 'Invalid status selected.',
        ]);

        AppellationMaster::updateOrCreate(
            ['pk' => $pk],
            [
                'appettation_name' => $request->appettation_name,
                'active_inactive'  => $request->active_inactive,
            ]
        );

        return redirect()->route('master.appellation.index')
            ->with('success', 'Appellation saved successfully.');
    }

    public function destroy($id)
    {
        try {
            $appellation = AppellationMaster::where('pk', decrypt($id))->firstOrFail();

            if ($appellation->active_inactive == 1) {
                return redirect()->back()->with('error', 'Active records cannot be deleted. Please deactivate it first.');
            }

            $appellation->delete();

            return redirect()->route('master.appellation.index')
                ->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
