<?php

namespace App\Http\Controllers\Mess\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait SortsMessReportData
{
    protected function messReportPerPage(Request $request, int $default = 25): int
    {
        $allowed = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }

    protected function messReportSortField(Request $request, array $allowed, string $default): string
    {
        $sort = (string) $request->input('sort', $default);

        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    protected function messReportSortDirection(Request $request, string $default = 'asc'): string
    {
        if (! $request->filled('sort_dir')) {
            return strtolower($default) === 'desc' ? 'desc' : 'asc';
        }

        return strtolower((string) $request->input('sort_dir')) === 'desc' ? 'desc' : 'asc';
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $sortMap
     * @return array<int, array<string, mixed>>
     */
    protected function sortMessReportRows($rows, Request $request, array $sortMap, string $defaultField = 'item_name', string $defaultDir = 'asc'): array
    {
        $collection = $rows instanceof Collection ? $rows : collect($rows);
        $field = $this->messReportSortField($request, array_keys($sortMap), $defaultField);
        $dir = $this->messReportSortDirection($request, $defaultDir);
        $key = $sortMap[$field] ?? $field;

        $callback = function ($row) use ($key) {
            $val = is_array($row)
                ? ($row[$key] ?? null)
                : (is_object($row) ? ($row->{$key} ?? null) : null);

            if (is_numeric($val)) {
                return (float) $val;
            }

            return mb_strtolower((string) ($val ?? ''));
        };

        $sorted = $dir === 'desc'
            ? $collection->sortByDesc($callback, SORT_NATURAL)
            : $collection->sortBy($callback, SORT_NATURAL);

        return $sorted->values()->all();
    }

    /**
     * @param  array<string, string>  $columnMap
     */
    protected function applyMessReportQuerySort(Builder $query, Request $request, array $columnMap, string $defaultField = 'item_name', string $defaultDir = 'asc'): Builder
    {
        $field = $this->messReportSortField($request, array_keys($columnMap), $defaultField);
        $dir = $this->messReportSortDirection($request, $defaultDir);
        $column = $columnMap[$field] ?? $columnMap[$defaultField];

        return $query->orderBy($column, $dir);
    }
}
