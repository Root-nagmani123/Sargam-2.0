<?php

namespace App\Http\Controllers\Admin\Estate;

use App\DataTables\EstateElectricSlabDataTable;
use App\Exports\EstateElectricSlabExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Estate\Concerns\AuthorizesEstateMaster;
use App\Http\Controllers\Admin\Estate\Concerns\ExportsEstateGrid;
use App\Models\EstateElectricSlab;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class EstateElectricSlabController extends Controller
{
    use AuthorizesEstateMaster;
    use ExportsEstateGrid;

    /** The rules are identical for create and update — one definition. */
    private const RULES = [
        'start_unit_range' => 'required|integer|min:0',
        'end_unit_range' => 'required|integer|min:0|gte:start_unit_range',
        'rate_per_unit' => 'required|numeric|min:0',
        'estate_unit_type_master_pk' => 'required|integer|exists:estate_unit_type_master,pk',
    ];

    /** "Merge with House" options — the Unit Type master, the one source for this list. */
    private function unitTypes()
    {
        return UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
    }

    public function index(EstateElectricSlabDataTable $dataTable)
    {
        // Add / Edit is a modal on the index now, so the option list has to be
        // on this page rather than on a separate form view.
        return $dataTable->render('admin.estate.define_electric_slab.index', [
            'unitTypes' => $this->unitTypes(),
        ]);
    }

    public function create()
    {
        $item = null;

        return view('admin.estate.define_electric_slab.form', [
            'item' => $item,
            'unitTypes' => $this->unitTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(self::RULES);
        $validated['estate_unit_type_master_pk'] = (int) $request->input('estate_unit_type_master_pk');
        EstateElectricSlab::create($validated);

        $message = 'Electric slab added successfully.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', $message);
    }

    public function edit(string $id)
    {
        $item = EstateElectricSlab::findOrFail($id);

        return view('admin.estate.define_electric_slab.form', [
            'item' => $item,
            'unitTypes' => $this->unitTypes(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $item = EstateElectricSlab::findOrFail($id);
        $validated = $request->validate(self::RULES);
        $validated['estate_unit_type_master_pk'] = (int) $request->input('estate_unit_type_master_pk');
        $item->update($validated);

        $message = 'Electric slab updated successfully.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', $message);
    }

    public function destroy(Request $request, string $id)
    {
        EstateElectricSlab::findOrFail($id)->delete();

        $message = 'Electric slab deleted successfully.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.estate.define-electric-slab.index')->with('success', $message);
    }

    /**
     * Rows + heading line for the downloads, in the grid's own order.
     *
     * @return array{rows: Collection, cols: string[], filterLine: string, generatedAt: string}
     */
    private function exportPayload(Request $request): array
    {
        $search = trim($this->exportSearchTerm($request));
        $unitType = static::exportFilterText($request, 'unit_type_filter');

        // The grid's own query (same "Merge with House" filter) and its header sort.
        $grid = app(EstateElectricSlabDataTable::class);
        $rows = $grid->query(new EstateElectricSlab)
            ->tap(fn ($query) => $grid->sortQuery($query))
            ->get();

        if ($search !== '') {
            // The grid's rule (Yajra multi-term search): every word matches the start, the end, the
            // stored rate ("12.50") or the unit type — so grid and download list the same slabs (F-012).
            $terms = preg_split('/\s+/', mb_strtolower($search));
            $rows = $rows->filter(fn ($row) => collect($terms)->every(fn ($term) => collect([
                $row->start_unit_range, $row->end_unit_range, $row->getRawOriginal('rate_per_unit'), $row->unitType->unit_type ?? null,
            ])->contains(fn ($value) => str_contains(mb_strtolower((string) $value), $term))))->values();
        }

        $rows = $rows->map(function ($row) {
            return (object) [
                'unit_range' => $row->start_unit_range.'-'.$row->end_unit_range,
                'rate_per_unit' => (float) $row->rate_per_unit,
                'merge_with_house' => $row->unitType->unit_type ?? '-',
            ];
        });

        return [
            'rows' => $rows,
            'cols' => EstateElectricSlabExport::resolveCols($request->input('cols')),
            'filterLine' => implode('  |  ', array_filter([
                ctype_digit($unitType) ? 'Merge with House: '.(UnitType::where('pk', (int) $unitType)->value('unit_type') ?? $unitType) : null,
                $search !== '' ? 'Search: "'.$search.'"' : null,
            ])) ?: 'All slabs',
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];
    }

    /** Branded .xlsx of the slab list. */
    public function download(Request $request)
    {
        $payload = $this->exportPayload($request);

        // CSV / PDF: same rows, filter and columns as the Excel download below.
        if ($response = $this->estateCsvOrPdf($request, EstateElectricSlabExport::class, $payload, 'Define Electric Slab', 'estate-electric-slab', 'portrait')) {
            return $response;
        }

        return Excel::download(
            new EstateElectricSlabExport(
                $payload['rows'],
                $payload['filterLine'],
                $payload['generatedAt'],
                $payload['cols']
            ),
            'define-electric-slab-'.now()->format('Y-m-d_H-i-s').'.xlsx'
        );
    }

    /** Print-ready view — same header and columns as the Excel download. */
    public function print(Request $request)
    {
        return view('admin.estate.define_electric_slab.print', $this->exportPayload($request));
    }
}
