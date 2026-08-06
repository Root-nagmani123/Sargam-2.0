<?php

namespace App\DataTables\FC;

use App\Services\FC\FcDescriptiveDataChildLoader;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Yajra\DataTables\QueryDataTable;

/**
 * A QueryDataTable that fills in the Descriptive Data report's repeating sections after the
 * page has been read.
 *
 * results() is the one point where Yajra hands over the paged rows before it renders them —
 * hooking it means the child sections are loaded in one batched query per table for the whole
 * page, instead of one query per row inside an editColumn() closure.
 *
 * It also lets the row count be counted cheaply — see prepareCountQuery().
 */
class FcChildHydratingQueryDataTable extends QueryDataTable
{
    /** @var array<string,array<string,mixed>> */
    protected array $fcFields = [];

    /** @param array<string,array<string,mixed>> $fields */
    public function fcHydrateChildren(array $fields): static
    {
        $this->fcFields = $fields;

        return $this;
    }

    /** @var (\Closure(): QueryBuilder)|null */
    protected $fcCountQuery = null;

    /**
     * Count the rows with a cheaper query than the one that fetches them.
     *
     * Yajra counts by cloning the data query and swapping its SELECT, which keeps all ~30
     * lookup joins — every one of them a per-row index dive that cannot change the answer.
     * Each lookup is a LEFT JOIN on a uniquely-indexed column (enforced by
     * FcDescriptiveDataFieldResolver::columnIsUniquelyIndexed), so dropping them from the
     * count is exact, not an approximation: verified identical across every course and filter
     * combination the report offers.
     *
     * @param  \Closure(): QueryBuilder  $factory  the same rows, scoped and filtered, no lookups
     */
    public function fcCountQueryUsing(\Closure $factory): static
    {
        $this->fcCountQuery = $factory;

        return $this;
    }

    public function prepareCountQuery(): QueryBuilder
    {
        return $this->fcCountQuery !== null
            ? ($this->fcCountQuery)()
            : parent::prepareCountQuery();
    }

    public function results(): Collection
    {
        $results = parent::results();

        if ($this->fcFields !== []) {
            app(FcDescriptiveDataChildLoader::class)->hydrate($results, $this->fcFields);
        }

        return $results;
    }
}
