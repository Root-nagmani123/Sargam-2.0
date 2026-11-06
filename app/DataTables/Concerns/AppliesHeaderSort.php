<?php

namespace App\DataTables\Concerns;

/**
 * Header sort that REPLACES query()'s default ORDER BY. Yajra only appends the clicked
 * column after the query's own orderBy, so a grid whose query() ends in orderBy never
 * re-sorts. Use as ->order(fn ($query) => $this->applyHeaderSort($query, 'tbl.pk')).
 */
trait AppliesHeaderSort
{
    /**
     * @param  string  $tieBreak  unique column, so equal values page stably
     * @param  array<string, string>  $sqlFor  column data => ORDER BY column/alias where it is not the column's name()
     */
    protected function applyHeaderSort($query, string $tieBreak, array $sqlFor = []): void
    {
        // Only server-declared orderable columns are sortable: the request names a column, never SQL.
        $orderable = [];
        foreach ($this->getColumns() as $column) {
            if ($column->orderable) {
                $orderable[$column->data] = $sqlFor[$column->data] ?? ($column->name ?: $column->data);
            }
        }

        $requested = $this->request()->input('columns');
        $sort = [];
        foreach ((array) $this->request()->input('order') as $o) {
            $index = is_array($o) ? ($o['column'] ?? null) : null;
            $dir = is_array($o) && is_string($o['dir'] ?? null) ? strtolower($o['dir']) : '';
            $data = is_numeric($index) && is_array($requested) ? data_get($requested, (int) $index.'.data') : null;
            if (is_string($data) && isset($orderable[$data]) && in_array($dir, ['asc', 'desc'], true)) {
                $sort[] = [$orderable[$data], $dir];
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
