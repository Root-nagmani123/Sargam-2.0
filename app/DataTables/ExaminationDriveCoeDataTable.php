<?php

namespace App\DataTables;

use App\Models\ExaminationDrive;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * COE → Examination Management → Examination Drive (COE) grid.
 *
 * Same drives and columns as the faculty grid (non-Draft, Active / Archived
 * tabs) without the Action column; only the status shown and the table id differ.
 */
class ExaminationDriveCoeDataTable extends ExaminationDriveFacultyDataTable
{
    public function html()
    {
        return $this->builder()->setTableId('cedcTable')->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ExaminationDriveCoe_' . date('YmdHis');
    }

    /** No Action column: COE only reviews the list, so no faculty "Complete Setup" link either. */
    public function dataTable(Builder $query): EloquentDataTable
    {
        return parent::dataTable($query)->removeColumn('actions');
    }

    public function getColumns(): array
    {
        return array_values(array_filter(parent::getColumns(), fn ($column) => $column->name !== 'actions'));
    }
}
