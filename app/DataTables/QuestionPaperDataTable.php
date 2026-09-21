<?php

namespace App\DataTables;

use App\Models\QuestionPaper;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE Examination - the Examination Section's view of a drive's papers.
 *
 * Scoped to one drive: the section works a drive at a time, and listing every
 * paper in the system at once would put unrelated subjects on screen together.
 */
class QuestionPaperDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->addColumn('subject', function ($row) {
                return optional(optional($row->componentMap)->subject)->subject_name ?? 'N/A';
            })

            ->addColumn('component', function ($row) {
                return optional(optional($row->componentMap)->component)->component_name ?? 'N/A';
            })

            ->addColumn('max_marks', function ($row) {
                $marks = optional($row->componentMap)->max_marks;

                // Marks are stored as decimal(6,2); whole numbers read better
                // unqualified on a listing.
                return $marks === null ? '-' : rtrim(rtrim(number_format((float) $marks, 2), '0'), '.');
            })

            ->addColumn('faculty', function ($row) {
                return optional($row->faculty)->full_name ?? 'Not assigned';
            })

            ->editColumn('deadline', function ($row) {
                return $row->deadline ? $row->deadline->format('d-m-Y') : '-';
            })

            ->addColumn('status_badge', function ($row) {
                $label = e(QuestionPaperStatus::label($row->status));
                $badge = QuestionPaperStatus::badge($row->status);

                $html = '<span class="badge bg-' . $badge . '">' . $label . '</span>';

                // A pending unfreeze request is the one thing the section has to
                // act on, so it is flagged on the row rather than only inside.
                if ($row->pendingUnfreezeRequest) {
                    $html .= ' <span class="badge bg-warning" title="Unfreeze request pending">Unfreeze&nbsp;?</span>';
                }

                return $html;
            })

            ->addColumn('files', function ($row) {
                $count = $row->currentFiles->count();

                return $count > 0 ? $count . ' file(s)' : '<span class="text-muted">None</span>';
            })

            ->addColumn('actions', function ($row) {
                $view = route('coe.question_paper.show', encrypt($row->id));

                return '<a href="' . $view . '" title="View"><i class="material-icons">visibility</i></a>';
            })

            ->rawColumns(['status_badge', 'files', 'actions']);
    }

    public function query(QuestionPaper $model): Builder
    {
        $driveId = (int) request('examination_drive_id', 0);

        return $model->newQuery()
            ->with([
                'componentMap.subject',
                'componentMap.component',
                'faculty',
                'currentFiles',
                'pendingUnfreezeRequest',
            ])
            ->where('examination_drive_id', $driveId)
            ->orderBy('question_papers.id');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No')->width(60),
            Column::computed('subject')->title('Subject'),
            Column::computed('component')->title('Component'),
            Column::computed('max_marks')->title('Max Marks')->width(90),
            Column::computed('faculty')->title('Paper Setter'),
            Column::make('deadline')->title('Deadline')->width(110),
            Column::computed('status_badge')->title('Status'),
            Column::computed('files')->title('Files')->width(90),
            Column::computed('actions')->title('Action')->width(80),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('question-paper-table')
            ->columns($this->getColumns())
            // The drive is chosen on the page, so it has to ride along with the
            // ajax call or the table would reload unfiltered.
            ->minifiedAjax('', null, ['examination_drive_id' => '$("#examination_drive_id").val()'])
            ->pageLength(25);
    }
}
