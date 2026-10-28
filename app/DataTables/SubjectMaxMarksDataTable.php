<?php

namespace App\DataTables;

use App\Models\SubjectMaster;
use App\Models\SubjectMaxMarksMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Subject Max Marks, server-side via Yajra.
 * Same shape as ComponentMasterDataTable; the Subject column shows the name,
 * and data-coe-val-subject carries the pk for the Edit dialog's select.
 */
class SubjectMaxMarksDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('subject_master_pk', fn ($row) => trim($row->subject->subject_name ?? '-'))
            ->editColumn('max_marks', fn ($row) => self::marks($row->max_marks))
            ->addColumn('status', fn ($row) => $this->statusCell((bool) $row->active_inactive))
            ->addColumn('actions', fn ($row) => $this->actionsCell((bool) $row->active_inactive))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => $row->active_inactive ? '1' : '0',
                'data-coe-val-subject' => fn ($row) => $row->subject_master_pk,
            ])
            ->filterColumn('subject_master_pk', fn ($q, $kw) => $q->whereHas('subject', fn ($qq) => $qq->where('subject_name', 'like', '%' . $kw . '%')))
            ->orderColumn('subject_master_pk', fn ($q, $dir) => $q->orderBy(
                SubjectMaster::select('subject_name')->whereColumn('subject_master.pk', 'subject_max_marks_master.subject_master_pk'),
                $dir
            ))
            ->rawColumns(['status', 'actions']);
    }

    public function query(SubjectMaxMarksMaster $model): Builder
    {
        $query = $model->newQuery()->with('subject');

        $status = request()->query('status_filter');
        if ($status !== null && $status !== '') {
            $query->where('active_inactive', $status);
        }

        return $query->orderByDesc('pk');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('subject_master_pk')->title('Subject'),
            Column::make('max_marks')->title('Max Marks'),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('smmTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'SubjectMaxMarks_' . date('YmdHis');
    }

    /** 80.00 -> "80", 72.50 -> "72.5". */
    public static function marks($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function statusCell(bool $active): string
    {
        return '<span class="coe-state coe-state--' . ($active ? 'active' : 'inactive') . '">'
            . ($active ? 'Active' : 'Inactive') . '</span>';
    }

    private function actionsCell(bool $active): string
    {
        return '<div class="coe-act-group" role="group" aria-label="Row actions">'
            . '<button type="button" class="coe-act coe-act--edit">'
            . '<span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Edit</span></button>'
            . '<label class="coe-act coe-act--toggle ' . ($active ? 'is-on' : 'is-off') . '">'
            . '<span class="coe-act__icon"><input type="checkbox" role="switch" '
            . 'class="form-check-input plain-status-toggle coe-switch"' . ($active ? ' checked' : '')
            . ' aria-label="' . ($active ? 'Deactivate' : 'Activate') . '"></span>'
            . '<span class="coe-act__label">' . ($active ? 'Deactivate' : 'Activate') . '</span></label>'
            . '<button type="button" class="coe-act coe-act--del">'
            . '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Delete</span></button>'
            . '</div>';
    }
}
