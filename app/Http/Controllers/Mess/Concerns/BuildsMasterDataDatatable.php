<?php

namespace App\Http\Controllers\Mess\Concerns;

use App\Support\DataTableSearchHelper;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared draw/start/length/search/order/paginate/JSON-envelope pipeline for the module's
 * simple master-data DataTables listings (ClientType, SubStore, Store, Vendor). Extracted
 * because these four controllers had byte-identical copies of this pipeline; row rendering
 * (HTML, per-model fields) stays in each controller — only the query/pagination shell moves.
 */
trait BuildsMasterDataDatatable
{
    /**
     * @param  EloquentBuilder  $query  Base query (filters already applied by the caller)
     * @param  string[]  $searchColumns  Plain columns matched against each search token via LIKE
     * @param  array<int, string>  $orderColumnMap  DataTables column index => query column name
     * @param  string  $defaultOrderDirection  'asc' or 'desc', used when no explicit order column matches
     * @param  callable(\Illuminate\Database\Eloquent\Collection): array<int, array<int, string>>  $buildRows
     *         Maps the paged Eloquent collection to DataTables row arrays (identical to each
     *         controller's own row-mapping call, just passed in instead of inlined).
     * @param  bool  $appendIdDescTiebreak  When true, always sorts by id desc after the chosen
     *         column (matches ClientType/SubStore/Vendor). When false, no secondary sort is
     *         added (matches Store, which has no tiebreak). Must match the original controller
     *         exactly — this affects the order of rows with equal sort-column values.
     */
    protected function buildMasterDataDatatableResponse(
        Request $request,
        EloquentBuilder $query,
        array $searchColumns,
        array $orderColumnMap,
        string $defaultOrderDirection,
        callable $buildRows,
        bool $appendIdDescTiebreak = true
    ): JsonResponse {
        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchTokens = DataTableSearchHelper::tokens((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count();

        if ($searchTokens !== [] && $searchColumns !== []) {
            $query->where(function ($q) use ($searchTokens, $searchColumns) {
                foreach ($searchTokens as $token) {
                    $like = DataTableSearchHelper::likePattern($token);
                    $q->where(function ($inner) use ($like, $searchColumns) {
                        foreach ($searchColumns as $i => $column) {
                            if ($i === 0) {
                                $inner->where($column, 'like', $like);
                            } else {
                                $inner->orWhere($column, 'like', $like);
                            }
                        }
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 0);
        $orderDir = DataTableSearchHelper::orderDirection($request, $defaultOrderDirection);

        if (isset($orderColumnMap[$orderCol])) {
            $paged->orderBy($orderColumnMap[$orderCol], $orderDir);
            if ($appendIdDescTiebreak) {
                $paged->orderByDesc('id');
            }
        } else {
            $paged->orderByDesc('id');
        }

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $data = $buildRows($rows);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
