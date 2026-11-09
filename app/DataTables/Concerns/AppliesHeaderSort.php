<?php

namespace App\DataTables\Concerns;

use Illuminate\Database\Query\Expression;

/**
 * Header sort that REPLACES query()'s default ORDER BY. Yajra only appends the clicked
 * column after the query's own orderBy, so a grid whose query() ends in orderBy never
 * re-sorts. Use as ->order(fn ($query) => $this->applyHeaderSort($query, 'tbl.pk')).
 *
 * Download / Print send only order[i][column] + dir (no columns[]); the index is then read
 * against getColumns(), which is the grid's own column order, so an export sorts like the grid.
 */
trait AppliesHeaderSort
{
    /**
     * @param  string  $tieBreak  unique column, so equal values page stably
     * @param  array<string, string|Expression|list<string|Expression>>  $sqlFor  column data => ORDER BY column(s), where not its name()
     */
    protected function applyHeaderSort($query, string $tieBreak, array $sqlFor = []): void
    {
        // Only server-declared orderable columns are sortable: the request names a column, never SQL.
        $declared = [];
        $orderable = [];
        foreach ($this->getColumns() as $column) {
            $declared[] = $column->data;
            if ($column->orderable) {
                $by = $sqlFor[$column->data] ?? ($column->name ?: $column->data);
                $orderable[$column->data] = is_array($by) ? $by : [$by];
            }
        }

        $requested = $this->request()->input('columns');
        $sort = [];
        foreach ((array) $this->request()->input('order') as $o) {
            $index = is_array($o) ? ($o['column'] ?? null) : null;
            $dir = is_array($o) && is_string($o['dir'] ?? null) ? strtolower($o['dir']) : '';
            if (! is_numeric($index)) {
                continue;
            }
            $data = is_array($requested) ? data_get($requested, (int) $index.'.data') : ($declared[(int) $index] ?? null);
            if (is_string($data) && isset($orderable[$data]) && in_array($dir, ['asc', 'desc'], true)) {
                foreach ($orderable[$data] as $by) {
                    $sort[] = [$by, $dir];
                }
            }
        }

        if ($sort === []) {
            return; // no header sort: keep query()'s default order
        }

        $query->reorder();
        foreach ($sort as [$column, $dir]) {
            $query->orderBy($column, $dir);
        }
        $query->orderBy($tieBreak, $sort[0][1]);
    }
}
