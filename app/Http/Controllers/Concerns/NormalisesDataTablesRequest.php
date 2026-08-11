<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Translates a DataTables server-side request into the ?q / ?sort / ?dir /
 * page convention the Centcom controllers already resolve for their exports.
 *
 * Doing the translation here (rather than teaching every controller to read
 * DataTables' own parameter names) means one grid feed and one export read the
 * SAME filters — a download can never disagree with what is on screen.
 *
 * Mirrors IssueManagementController::normaliseDataTablesRequest(), which pre-dates
 * this trait and stays private to that class because it also carries the Centcom
 * grid's own filter set.
 */
trait NormalisesDataTablesRequest
{
    /**
     * Merge DataTables' parameters back onto the request as q/sort/dir and
     * return the page window to slice.
     *
     * @param  array<int, int>  $perPageOptions  Must match the lengthMenu that
     *                                           datatable-global-ui.js installs,
     *                                           or a page size the user picked
     *                                           silently falls back to the default.
     * @return array{page: int, perPage: int, start: int}
     */
    protected function normaliseDataTablesRequest(Request $request, int $defaultPerPage, array $perPageOptions): array
    {
        // DataTables posts the term as search[value]; `search` is an array at
        // this point, so it can never be cast to string directly.
        $rawSearch = $request->input('search');
        $searchTerm = is_array($rawSearch) ? ($rawSearch['value'] ?? '') : $rawSearch;
        $merge = ['q' => trim((string) $searchTerm)];

        // order[0] points at a column by INDEX — resolve it back to a sort key
        // through the columns[] the front end declared. Columns that are not
        // sortable server-side declare an empty name, which resolveSort() then
        // rejects in favour of the page default.
        $orderColumn = $request->input('order.0.column');
        if ($orderColumn !== null) {
            $merge['sort'] = (string) $request->input('columns.' . (int) $orderColumn . '.name', '');
            $merge['dir'] = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        }

        // On a GET the input source IS the query bag, so the controllers'
        // existing $request->query('q') resolvers see these.
        $request->merge($merge);

        $length = (int) $request->input('length', $defaultPerPage);
        $perPage = in_array($length, $perPageOptions, true) ? $length : $defaultPerPage;
        $start = max(0, (int) $request->input('start', 0));

        return [
            'page' => (int) floor($start / $perPage) + 1,
            'perPage' => $perPage,
            'start' => $start,
        ];
    }
}
