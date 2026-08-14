<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Http\Request;

/**
 * Server-side listing helpers for the "new design" index pages.
 *
 * The programme-dt footer (see new-design-index-page.md §4B) offers a
 * rows-per-page dropdown and a search box. Both arrive as query parameters, and
 * both have to be handled server-side — §8.8 requires the per-page value to be
 * whitelisted so ?per_page= can't be used to request an arbitrarily large page.
 *
 * Every master/setup grid needs the identical few lines, so they live here
 * rather than being pasted into a dozen controllers.
 *
 * @see \Illuminate\Pagination\LengthAwarePaginator
 */
trait PaginatesListings
{
    /** Values the footer dropdown offers. Mirrored in the Blade component. */
    protected array $perPageOptions = ['10', '25', '50', '100', '200', 'all'];

    /**
     * Resolve ?per_page= to a row count paginate() can use.
     *
     * "all" stays in the URL as-is so the dropdown can show it selected; the
     * query gets a high cap instead of a real "no limit", which is safe because
     * paginate() runs its own COUNT and so never returns more rows than match.
     */
    protected function resolvePerPage(Request $request, string $default = '10'): int
    {
        $perPage = (string) $request->input('per_page', $default);

        if (!in_array($perPage, $this->perPageOptions, true)) {
            $perPage = $default;
        }

        return $perPage === 'all' ? 100000 : (int) $perPage;
    }

    /** The trimmed ?search= term, or '' when absent. */
    protected function searchTerm(Request $request, string $key = 'search'): string
    {
        return trim((string) $request->input($key, ''));
    }

    /**
     * Apply a LIKE filter across the given columns, OR'd together.
     *
     * The columns are grouped in a nested where() so the search cannot leak past
     * an existing filter — without the closure, `->where('active', 1)` followed by
     * a bare `orWhere('name', 'like', …)` matches inactive rows too.
     *
     * @param  array<int, string>  $columns
     */
    protected function applySearch(BuilderContract $query, string $term, array $columns): BuilderContract
    {
        if ($term === '' || $columns === []) {
            return $query;
        }

        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($columns, $like) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', $like);
            }
        });
    }
}
