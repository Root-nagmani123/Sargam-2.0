<?php

namespace App\DataTables;

use App\Models\{StudentCourseGroupMap, GroupTypeMasterCourseMasterMap, MDOEscotDutyMap, CourseStudentAttendance, StudentMedicalExemption};
use App\Models\Timetable;
use App\Services\Attendance\OtExemptionResolver;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StudentAttendanceListDataTable extends DataTable
{
    protected ?OtExemptionResolver $exemptions = null;

    public function __construct(
        protected int $group_pk,
        protected int $course_pk,
        protected int $timetable_pk
    ) {}

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('student_code', fn($row) => e($row->studentsMaster->generated_OT_code ?? 'N/A'))
            ->addColumn('student_name', fn($row) => e($row->studentsMaster->display_name ?? 'N/A'))
            ->addColumn('attendance_status', fn($row) => $this->renderStatusBadge($row))
            ->addColumn('update_status', fn($row) => $this->renderRadioGroup($row, 'status', [1 => 'Present', 2 => 'Late', 3 => 'Absent']))
            ->addColumn('mdo_duty', fn($row) => $this->renderMdoCell($row))
            ->addColumn('escort_duty', fn($row) => $this->renderEscortCell($row))
            ->addColumn('other_exemption', fn($row) => $this->renderOtherExemptionCell($row))
            ->addColumn('action', fn($row) => $this->renderActionCell($row))
            ->filterColumn('student_name', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('display_name', 'like', "%{$keyword}%")))
            ->filterColumn('student_code', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('generated_OT_code', 'like', "%{$keyword}%")))
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('studentsMaster', function ($studentQuery) use ($searchValue) {
                            $studentQuery->where('display_name', 'like', "%{$searchValue}%")
                                ->orWhere('generated_OT_code', 'like', "%{$searchValue}%")
                                ->orWhere('user_id', 'like', "%{$searchValue}%");
                        });
                    });
                }
            }, true)
            ->rawColumns(['attendance_status', 'update_status', 'mdo_duty', 'escort_duty', 'other_exemption', 'action']);
    }

    public function query(): QueryBuilder
    {
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::where('pk', $this->group_pk)
            ->where('course_name', $this->course_pk)
            ->first();

        if (!$groupTypeMaster) {
            // Return an empty query to avoid throwing ModelNotFoundException
            return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,user_id,cadre_master_pk,pk',
                'studentsMaster.cadre:pk,cadre_name',
                'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)
                                          ->where('group_type_master_course_master_map_pk', $this->group_pk)
            ])->whereRaw('1=0');
        }

        return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,user_id,cadre_master_pk,pk',
                'studentsMaster.cadre:pk,cadre_name',
                'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)
                                          ->where('group_type_master_course_master_map_pk', $this->group_pk)
            ])
            ->where('group_type_master_course_master_map_pk', $groupTypeMaster->pk);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('studentAttendanceTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'responsive' => false,
                'scrollX' => false,
                'autoWidth' => false,
                'language' => [
                    'emptyTable' => 'No officer trainees found.',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false),
            Column::make('student_code')->title('OT Code')->orderable(false)->searchable(true),
            Column::make('student_name')->title('OT Name')->orderable(false)->searchable(true),
            Column::make('attendance_status')->title('Current Attendance Status')->orderable(false)->searchable(true),
            Column::make('update_status')->title('Update Attendance Status')->orderable(false)->searchable(true),
            Column::make('mdo_duty')->title('MDO Duty')->orderable(false)->searchable(true),
            Column::make('escort_duty')->title('Escort/ Modular Duty')->orderable(false)->searchable(true),
            Column::make('other_exemption')->title('Other Exemption')->orderable(false)->searchable(false),
            Column::computed('action')->title('Action')->orderable(false)->searchable(true),
        ];
    }

    /** The saved attendance row for this OT/session, resolved once and cached. */
    protected function getSavedRecord(int $studentId): ?CourseStudentAttendance
    {
        static $cache = [];
        if (!array_key_exists($studentId, $cache)) {
            $cache[$studentId] = CourseStudentAttendance::where([
                ['Student_master_pk', '=', $studentId],
                ['Course_master_pk', '=', $this->course_pk],
                ['group_type_master_course_master_map_pk', '=', $this->group_pk],
                ['timetable_pk', '=', $this->timetable_pk],
            ])->first();
        }
        return $cache[$studentId];
    }

    protected function getSavedStatus(int $studentId): int
    {
        $rec = $this->getSavedRecord($studentId);
        return $rec ? (int) $rec->status : 0;
    }

    /**
     * Which status radio (Present=1 / Late=2 / Absent=3) shows as selected for
     * this OT. Unlocked OTs with no record default to Present — unchanged from the
     * original behaviour.
     */
    protected function statusRadioSelected(int $studentId): int
    {
        // Locked by an MDO / Escort / Medical exemption → always Present.
        if ($this->exemptions()->reasonFor($studentId) !== null) {
            return 1;
        }

        $rec = $this->getSavedRecord($studentId);
        if (!$rec) {
            return 1; // No record yet → default Present.
        }

        $status = (int) $rec->status;

        // Duty/exemption statuses (4=MDO, 5=Escort, 6=Medical, 7=Other) have no
        // radio of their own here, so show Present.
        if (in_array($status, [4, 5, 6, 7], true)) {
            return 1;
        }

        return $status; // 1/2/3, or 0 (nothing checked)
    }

    /**
     * True when this OT was saved as an Other Exemption: status Absent (3) with a
     * reason on record. The Other Exemption radio is its own control (not part of
     * the status group), so such a row shows BOTH the Absent radio and the Other
     * Exemption radio checked, with the reason filled in.
     */
    protected function isOtherExemption(int $studentId): bool
    {
        if ($this->exemptions()->reasonFor($studentId) !== null) {
            return false;
        }

        $rec = $this->getSavedRecord($studentId);

        return $rec
            && (int) $rec->status === 3
            && trim((string) ($rec->other_exemption_comments ?? '')) !== '';
    }

    protected function statusLabelClass(int $status): array
    {
        return match ($status) {
            1 => ['Present', 'att-present'],
            2 => ['Late', 'att-late'],
            3 => ['Absent', 'att-absent'],
            4 => ['MDO', 'att-duty'],
            5 => ['Escort', 'att-duty'],
            6 => ['Medical', 'att-exempt'],
            7 => ['Other', 'att-exempt'],
            default => ['Not Marked', 'att-nm'],
        };
    }

    protected function renderStatusBadge($row): string
    {
        $pk = $row->studentsMaster->pk;
        $status = $this->getSavedStatus($pk);
        [$label, $cls] = $this->statusLabelClass($status);

        // Display-only pill of the SAVED status. The editable field (name="student[pk]")
        // is now owned by the inline "Update Attendance Status" radios so there is no
        // duplicate form field.
        return '<span class="att-badge ' . $cls . '" data-ot="' . $pk . '">' . $label . '</span>';
    }

    protected function renderMdoCell($row): string
    {
        $pk = $row->studentsMaster->pk;
        if ($this->hasMdoDuty($pk)) {
            // Mirrors the escort column, which names the duty rather than saying "Yes".
            return '<span class="text-info fw-semibold">MDO Duty</span>';
        }

        // An MDO record exists for the session date but doesn't overlap its time → "No".
        $timetable = Timetable::select('START_DATE')->where('pk', $this->timetable_pk)->first();
        $mdoTypes = MDOEscotDutyMap::getMdoDutyTypes();
        if ($timetable && !empty($mdoTypes['mdo'])) {
            $exists = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', $mdoTypes['mdo']],
                ['selected_student_list', '=', $pk],
            ])->whereDate('mdo_date', '=', $timetable->START_DATE)->exists();
            if ($exists) {
                return '<span class="text-muted">No</span>';
            }
        }

        return '<span class="text-muted">NA</span>';
    }

    protected function renderEscortCell($row): string
    {
        $pk = $row->studentsMaster->pk;
        return $this->hasEscortDuty($pk)
            ? '<span class="text-info fw-semibold">Escort/Moderator</span>'
            : '<span class="text-muted">NA</span>';
    }

    protected function renderActionCell($row): string
    {
        $pk = $row->studentsMaster->pk;
        $status = $this->getSavedStatus($pk);
        $name = e($row->studentsMaster->display_name ?? '');

        return '<button type="button" class="att-action-icon js-mark-ot" '
            . 'data-ot="' . $pk . '" data-status="' . $status . '" data-name="' . $name . '" '
            . 'title="Update Attendance" aria-label="Update Attendance">'
            . '<i class="bi bi-clipboard-check"></i><span class="att-action-label">Update Attendance</span></button>';
    }

    protected function renderRadioGroup($row, string $field, array $options): string
    {
        $studentId = $row->studentsMaster->pk;

        // Any duty or exemption overlapping this session (MDO, Escort/Moderator,
        // Other, Medical) means the OT must never be marked absent: Present is
        // forced and the status is locked. AttendanceController::save applies the
        // same rule — the lock here is the visible half of it, not the rule.
        $lockReason = $this->exemptions()->reasonFor($studentId);

        // Which status radio is checked. Unlocked OTs with no record default to
        // Present (original, unchanged behaviour).
        $selected = $this->statusRadioSelected($studentId);

        // Fallback for a browser that submits no radio. Present when the OT is
        // locked, so a locked row can never post back "Not Marked".
        $fallback = $lockReason !== null ? 1 : 0;
        $html = "<input type='hidden' name='student[{$studentId}]' value='{$fallback}'>";

        foreach ($options as $value => $label) {
            $labelClass = match ($value) {
                1 => 'text-success',
                2 => 'text-warning',
                3 => 'text-danger',
                default => 'text-dark',
            };

            $checked = ($selected == $value) ? 'checked' : '';

            // Left enabled deliberately: a disabled radio fires no event, so the
            // marker would get no explanation for why the click did nothing. The
            // page reverts the choice and names the duty instead.
            $lockAttr = $lockReason !== null
                ? " data-att-lock=\"" . e($lockReason) . "\" data-att-ot=\"{$studentId}\""
                : '';

            $html .= "<div class='form-check form-check-inline'>
                        <input class='form-check-input js-att-status' type='radio' name='student[{$studentId}]' value='{$value}' {$checked} id='student[{$studentId}][{$value}]'{$lockAttr}>
                        <label class='form-check-label {$labelClass}' for='student[{$studentId}][{$value}]'>{$label}</label>
                    </div>";
        }

        if ($lockReason !== null) {
            $html .= "<div class='att-lock-note'><i class='bi bi-lock-fill' aria-hidden='true'></i> "
                . e($lockReason) . "</div>";
        }

        return $html;
    }

    /**
     * "Other Exemption" column: a radio in the SAME group as Present/Late/Absent
     * (so picking it deselects them) plus the free-text reason. On save the
     * status is stored as Absent (3) and the reason as other_exemption_comments;
     * the text field is editable only while this radio is the chosen one.
     */
    protected function renderOtherExemptionCell($row): string
    {
        $studentId = $row->studentsMaster->pk;

        $lockReason = $this->exemptions()->reasonFor($studentId);
        $isOther = $this->isOtherExemption($studentId);

        $rec = $this->getSavedRecord($studentId);
        $comment = $rec ? (string) ($rec->other_exemption_comments ?? '') : '';

        $checked = $isOther ? 'checked' : '';

        // A locked OT (MDO/Escort/Medical) is forced Present and can never be an
        // Other Exemption, so its control is disabled.
        $radioDisabled = $lockReason !== null ? 'disabled' : '';

        // Reason is editable only while Other Exemption is on for an unlocked OT.
        $textDisabled = ($isOther && $lockReason === null) ? '' : 'disabled';

        return "<div class='form-check mb-1'>
                    <input class='form-check-input js-att-other' type='radio' name='other_exemption[{$studentId}]' value='1' {$checked} {$radioDisabled} id='other_exemption[{$studentId}]'>
                    <label class='form-check-label text-secondary' for='other_exemption[{$studentId}]'>Other Exemption</label>
                </div>
                <input type='text' class='form-control form-control-sm att-other-reason' name='other_exemption_comments[{$studentId}]' data-att-ot='{$studentId}' value='" . e($comment) . "' placeholder='Enter exemption reason' {$textDisabled}>";
    }

    /** Shared with AttendanceController::save, so the lock and its enforcement agree. */
    protected function exemptions(): OtExemptionResolver
    {
        return $this->exemptions ??= new OtExemptionResolver($this->course_pk, $this->timetable_pk);
    }

    /**
     * MDO duty overlapping the current class session. An MDO-duty OT is on duty
     * but still counted as attending, so the Attendance column shows Present.
     */
    protected function hasMdoDuty(int $studentId): bool
    {
        return $this->exemptions()->hasMdo($studentId);
    }

    /** Escort/Moderator duty overlapping the current class session — also attending. */
    protected function hasEscortDuty(int $studentId): bool
    {
        return $this->exemptions()->hasEscort($studentId);
    }


    protected function filename(): string
    {
        return 'StudentAttendanceList_' . date('YmdHis');
    }
}
