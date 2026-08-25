<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PeerEvaluationReportDataTable;
use App\Exports\PeerEvaluationReportExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\PeerEvent;
use App\Models\PeerGroup;
use App\Models\PeerGroupMember;
use App\Support\PeerCourseStatusScope;
use App\Support\PeerGroupSource;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Peer Evaluation -> Evaluation Reports.
 *
 * Read-only reporting over peer_scores. Two views:
 *   index() - one row per evaluated OT, with their AVERAGE score per criterion
 *   show()  - one OT's report: every evaluator's scores and remark, plus averages
 *
 * Every number is a mean, never a sum: scores are ints 1..10, so a sum would grow
 * with the number of evaluators instead of measuring performance.
 *
 * Courses come from course_master and the Active/Archived pills use the same rule
 * as Course Master (App\Support\PeerCourseStatusScope).
 */
class PeerEvaluationReportController extends Controller
{
    public function index(PeerEvaluationReportDataTable $dataTable)
    {
        $status = PeerCourseStatusScope::normalise(request('status_filter', 'active'));
        $filters = $this->filters($status);

        return $dataTable->render('admin.forms.peer_evaluation.reports.index', [
            'courses' => $this->courseOptions($status),
            'events' => $this->eventOptions(request('course_filter'), $status),
            'groups' => $this->groupOptions(request('course_filter'), request('event_filter')),
            'criteria' => PeerEvaluationReportDataTable::criteria(),
            // Positional grid-column -> export-column key map for the page script.
            // Built here rather than in the view: @json() can't parse a multi-line
            // array_merge with an arrow fn inside it and mangles the expression.
            'exportColumnKeys' => array_merge(
                ['sno', 'ot_name', 'ot_code', 'course_name', 'group_name', 'evaluators'],
                PeerEvaluationReportDataTable::criteria()->map(fn ($c) => 'crit_' . $c->id)->all(),
                ['status', 'overall']
            ),
            'stats' => $this->stats($filters),
            'courseFilter' => (string) request('course_filter', ''),
            'eventFilter' => (string) request('event_filter', ''),
            'groupFilter' => (string) request('group_filter', ''),
            'submissionFilter' => (string) request('submission_filter', ''),
            'statusFilter' => $status,
        ]);
    }

    /**
     * The five headline tiles.
     *
     * Computed by wrapping the grid's own query as a subquery, so a tile can never
     * disagree with the rows underneath it - including under every filter.
     *
     * @return array<string, mixed>
     */
    private function stats(array $filters): array
    {
        $inner = PeerEvaluationReportDataTable::baseQuery(new PeerGroupMember(), $filters);

        $agg = DB::query()
            ->fromSub($inner, 'r')
            ->selectRaw('COUNT(*) as total_ots')
            ->selectRaw('SUM(CASE WHEN r.has_submitted = 1 THEN 1 ELSE 0 END) as given')
            ->selectRaw('AVG(r.overall_score) as avg_score')
            ->first();

        $totalOts = (int) ($agg->total_ots ?? 0);
        $given = (int) ($agg->given ?? 0);

        // A "peer evaluation" is one evaluator scoring one OT, however many
        // criteria that covered - so count the distinct triples, not score rows.
        $totalEvaluations = (int) DB::query()
            ->fromSub(
                DB::table('peer_scores')
                    ->select('evaluator_id', 'member_id', 'group_id')
                    ->whereIn('member_id', (clone $inner)->select('peer_group_members.id'))
                    ->distinct(),
                'e'
            )
            ->count();

        return [
            'total_ots' => $totalOts,
            'given' => $given,
            'not_given' => max(0, $totalOts - $given),
            'total_evaluations' => $totalEvaluations,
            'avg_score' => $agg->avg_score === null ? null : (float) $agg->avg_score,
        ];
    }

    /** @return array<string, mixed> */
    private function filters(string $status): array
    {
        return [
            'course' => request('course_filter'),
            'event' => request('event_filter'),
            'group' => request('group_filter'),
            'submission' => request('submission_filter'),
            'status' => $status,
        ];
    }

    // ==================== FILTER OPTIONS ====================

    private function courseOptions(string $status)
    {
        $query = CourseMaster::query();

        PeerCourseStatusScope::forCourses($query, $status);
        $this->applyRoleScope($query);

        return $query->orderBy('course_name')->pluck('course_name', 'pk');
    }

    private function eventOptions($courseId = null, ?string $status = null)
    {
        $query = PeerEvent::query();

        if (filled($courseId)) {
            $query->where('course_id', $courseId);
        }
        if ($status !== null) {
            PeerCourseStatusScope::forRelated($query, $status, 'peer_events.course_id');
        }

        return $query->orderBy('event_name')->pluck('event_name', 'id');
    }

    /**
     * Groups come from Course Group Mapping, keyed by that table's pk - so the
     * filter offers the same list every other group picker in the module does.
     * baseQuery() matches it against peer_groups.group_map_pk.
     */
    private function groupOptions($courseId = null, $eventId = null)
    {
        // An event already belongs to a course, so narrow by the event's course
        // when one is picked - the mapping table knows nothing about events.
        if (filled($eventId)) {
            $courseId = PeerEvent::whereKey($eventId)->value('course_id') ?: $courseId;
        }

        return PeerGroupSource::options($courseId)->pluck('label', 'pk');
    }

    /**
     * get_Role_by_course() returns [] for Admin/Super Admin/PA ("no restriction")
     * and [-1] for a non-admin with no roles ("nothing"), so an empty array must
     * NOT be fed to whereIn.
     */
    private function applyRoleScope($query): void
    {
        $allowed = get_Role_by_course();

        if (! empty($allowed)) {
            $query->whereIn('course_master.pk', $allowed);
        }
    }

    /**
     * Dependent-dropdown feed. Ordered LISTS of {id, name}, never id => name maps:
     * JavaScript reorders numeric-looking object keys, so a map comes back sorted
     * by id and the dropdown goes alphabetical-then-not after a rebuild.
     */
    public function options(Request $request)
    {
        $courseId = $request->query('course_id');
        $eventId = $request->query('event_id');
        $status = $request->query('status');
        $status = $status === null ? null : PeerCourseStatusScope::normalise($status);

        $shape = fn ($collection) => $collection
            ->map(fn ($name, $id) => ['id' => (string) $id, 'name' => $name])
            ->values();

        $payload = [
            'success' => true,
            'events' => $shape($this->eventOptions($courseId, $status)),
            'groups' => $shape($this->groupOptions($courseId, $eventId)),
        ];

        if ($status !== null) {
            $payload['courses'] = $shape($this->courseOptions($status));
        }

        return response()->json($payload);
    }

    // ==================== DETAIL REPORT ====================

    /**
     * One OT's report: every evaluator who scored them, that evaluator's score per
     * criterion and their remark, then a per-criterion average row.
     */
    public function show($member)
    {
        $report = $this->buildReport($member);

        return view('admin.forms.peer_evaluation.reports.show', $report);
    }

    /**
     * Assemble one OT's report. Shared by show() and its exports so the screen and
     * the download can't disagree.
     *
     * @return array<string, mixed>
     */
    private function buildReport($memberId): array
    {
        $member = PeerGroupMember::query()
            ->join('peer_groups', 'peer_groups.id', '=', 'peer_group_members.group_id')
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_groups.course_id')
            ->leftJoin('peer_events', 'peer_events.id', '=', 'peer_groups.event_id')
            ->select([
                'peer_group_members.id',
                'peer_group_members.user_name',
                'peer_group_members.ot_code',
                'peer_group_members.user_id',
                'peer_group_members.group_id',
                'peer_groups.group_name',
                'course_master.course_name as course_name',
                'peer_events.event_name as event_name',
            ])
            ->where('peer_group_members.id', $memberId)
            ->firstOrFail();

        $criteria = PeerEvaluationReportDataTable::criteria();

        // Every score this OT received, keyed evaluator -> criterion.
        $scores = DB::table('peer_scores')
            ->where('member_id', $member->id)
            ->where('group_id', $member->group_id)
            ->get();

        $remarks = DB::table('peer_evaluation_remarks')
            ->where('member_id', $member->id)
            ->where('group_id', $member->group_id)
            ->pluck('remarks', 'evaluator_id');

        $evaluatorIds = $scores->pluck('evaluator_id')->unique()->filter()->values();

        // Evaluator display name + code. peer_scores stores user_credentials.pk;
        // the OT-facing code lives on peer_group_members, matched back through
        // user_credentials.user_id (the module's own linkage).
        $people = DB::table('user_credentials')
            ->whereIn('pk', $evaluatorIds)
            ->get(['pk', 'user_id', 'first_name', 'last_name', 'user_name'])
            ->keyBy('pk');

        $codes = DB::table('peer_group_members')
            ->where('group_id', $member->group_id)
            ->whereNotNull('user_id')
            ->where('user_id', '<>', '')
            ->pluck('ot_code', 'user_id');

        $rows = [];
        foreach ($evaluatorIds as $evaluatorId) {
            $person = $people->get($evaluatorId);
            $name = $person
                ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: ($person->user_name ?? 'Unknown')
                : 'Unknown';

            $mine = $scores->where('evaluator_id', $evaluatorId);
            $perCriterion = [];
            foreach ($criteria as $criterion) {
                $hit = $mine->firstWhere('column_id', $criterion->id);
                $perCriterion[$criterion->id] = $hit ? (float) $hit->score : null;
            }

            $given = array_filter($perCriterion, fn ($v) => $v !== null);

            $rows[] = [
                'evaluator_id' => $evaluatorId,
                'name' => $name,
                'code' => $person && isset($codes[(string) $person->user_id]) ? $codes[(string) $person->user_id] : null,
                'scores' => $perCriterion,
                'overall' => $given === [] ? null : array_sum($given) / count($given),
                'remarks' => $remarks[$evaluatorId] ?? null,
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        // Per-criterion averages across evaluators, plus the headline peer score.
        $averages = [];
        foreach ($criteria as $criterion) {
            $values = array_filter(
                array_column($rows, 'scores'),
                fn ($s) => ($s[$criterion->id] ?? null) !== null
            );
            $values = array_map(fn ($s) => $s[$criterion->id], $values);
            $averages[$criterion->id] = $values === [] ? null : array_sum($values) / count($values);
        }

        $allOveralls = array_filter(array_column($rows, 'overall'), fn ($v) => $v !== null);

        return [
            'member' => $member,
            'criteria' => $criteria,
            'rows' => $rows,
            'averages' => $averages,
            'overallScore' => $allOveralls === [] ? null : array_sum($allOveralls) / count($allOveralls),
            'submitted' => $rows !== [],
        ];
    }

    // ==================== EXPORTS ====================

    /**
     * Canonical grid columns. CSV, Excel, PDF and print all render from this one
     * array, so hiding a column in the grid's Columns modal drops it from every
     * format and the four can never drift apart.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        $defs = [
            'sno' => ['heading' => 'S. No.', 'class' => 'col-sno', 'value' => fn ($r, int $i) => $i + 1],
            'ot_name' => ['heading' => "OT's", 'class' => 'col-name', 'value' => fn ($r) => $r->user_name ?: '-'],
            'ot_code' => ['heading' => 'OT Code', 'class' => 'col-code', 'value' => fn ($r) => $r->ot_code ?: '-'],
            'course_name' => ['heading' => 'Course Name', 'class' => 'col-course', 'value' => fn ($r) => $r->course_name ?: '-'],
            'group_name' => ['heading' => 'Group Name', 'class' => 'col-group', 'value' => fn ($r) => $r->group_name ?: '-'],
            'evaluators' => ['heading' => 'Evaluators', 'class' => 'col-num', 'value' => fn ($r) => (int) ($r->evaluators_count ?? 0)],
        ];

        foreach (PeerEvaluationReportDataTable::criteria() as $criterion) {
            $key = 'crit_' . $criterion->id;
            $defs[$key] = [
                'heading' => $criterion->column_name,
                'class' => 'col-num',
                'value' => fn ($r) => $r->{$key} === null ? '-' : number_format((float) $r->{$key}, 2),
            ];
        }

        $defs['status'] = [
            'heading' => 'Status',
            'class' => 'col-status',
            'value' => fn ($r) => ((int) ($r->has_submitted ?? 0) === 1) ? 'Submitted' : 'Pending',
        ];
        $defs['overall'] = [
            'heading' => 'Overall',
            'class' => 'col-num',
            'value' => fn ($r) => $r->overall_score === null ? '-' : number_format((float) $r->overall_score, 2),
        ];

        return $defs;
    }

    private function resolveExportColumns(Request $request): array
    {
        $defs = $this->exportColumnDefs();
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $status = PeerCourseStatusScope::normalise($request->query('status_filter', 'active'));
        $search = trim((string) $request->query('q', ''));

        $query = PeerEvaluationReportDataTable::baseQuery(new PeerGroupMember(), [
            'course' => $request->query('course_filter'),
            'event' => $request->query('event_filter'),
            'group' => $request->query('group_filter'),
            'submission' => $request->query('submission_filter'),
            'status' => $status,
        ]);

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('peer_group_members.user_name', 'like', "%{$search}%")
                    ->orWhere('peer_group_members.ot_code', 'like', "%{$search}%")
                    ->orWhere('course_master.course_name', 'like', "%{$search}%")
                    ->orWhere('peer_groups.group_name', 'like', "%{$search}%");
            });
        }

        $rows = $query->get();
        $columns = $this->resolveExportColumns($request);
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        $filterBits = ['Status: ' . ($status === 'archive' ? 'Archived' : 'Active')];
        foreach ([
            ['course_filter', CourseMaster::class, 'pk', 'course_name', 'Course'],
            ['event_filter', PeerEvent::class, 'id', 'event_name', 'Event'],
            ['group_filter', PeerGroup::class, 'id', 'group_name', 'Group'],
        ] as [$param, $model, $key, $label, $prefix]) {
            $value = $request->query($param);
            if (filled($value)) {
                $name = $model::where($key, $value)->value($label);
                if ($name) {
                    $filterBits[] = $prefix . ': ' . $name;
                }
            }
        }
        if (filled($request->query('submission_filter'))) {
            $filterBits[] = 'Submission: ' . ucfirst((string) $request->query('submission_filter'));
        }
        if ($search !== '') {
            $filterBits[] = 'Search: ' . $search;
        }
        $filterText = implode('  |  ', $filterBits);

        return $this->renderExport(
            $format,
            'admin.forms.peer_evaluation.reports',
            compact('columns', 'rows', 'filterText', 'exportDate'),
            new PeerEvaluationReportExport($rows, $columns, $exportDate, $filterText),
            'EvaluationReports_' . $stamp
        );
    }

    /**
     * One OT's report, in the same four formats as the grid.
     */
    public function exportDetail(Request $request, $member, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $report = $this->buildReport($member);
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        // Flatten to the same {heading, value} shape the grid exports use, so both
        // reports go through one rendering path.
        $columns = [
            'sno' => ['heading' => 'S. No.', 'class' => 'col-sno', 'value' => fn ($r, int $i) => $i + 1],
            'evaluator' => [
                'heading' => 'Evaluator and Code',
                'class' => 'col-name',
                'value' => fn ($r) => $r['name'] . ($r['code'] ? ' - ' . $r['code'] : ''),
            ],
        ];
        foreach ($report['criteria'] as $criterion) {
            $id = $criterion->id;
            $columns['crit_' . $id] = [
                'heading' => $criterion->column_name,
                'class' => 'col-num',
                'value' => fn ($r) => $r['scores'][$id] === null ? '-' : number_format($r['scores'][$id], 2),
            ];
        }
        $columns['overall'] = [
            'heading' => 'Overall',
            'class' => 'col-num',
            'value' => fn ($r) => $r['overall'] === null ? '-' : number_format($r['overall'], 2),
        ];
        $columns['remarks'] = [
            'heading' => 'Remarks',
            'class' => 'col-remarks',
            'value' => fn ($r) => $r['remarks'] ?: '-',
        ];

        $rows = collect($report['rows']);
        $member = $report['member'];
        $filterText = implode('  |  ', array_filter([
            'Course: ' . ($member->course_name ?: '-'),
            'Event: ' . ($member->event_name ?: '-'),
            'Group: ' . ($member->group_name ?: '-'),
            'OT Code: ' . ($member->ot_code ?: '-'),
        ]));

        $title = ($member->user_name ?: 'Officer Trainee') . ' - Evaluation Report';

        return $this->renderExport(
            $format,
            'admin.forms.peer_evaluation.reports.detail',
            compact('columns', 'rows', 'filterText', 'exportDate') + [
                'reportTitle' => $title,
                'averages' => $report['averages'],
                'criteria' => $report['criteria'],
            ],
            new PeerEvaluationReportExport($rows, $columns, $exportDate, $filterText, $title),
            'EvaluationReport_' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) $member->user_name) . '_' . $stamp,
            $title
        );
    }

    /**
     * The one place the four export formats are produced.
     *
     * $viewBase resolves to "<base>.export_print" / "<base>.export_pdf".
     */
    private function renderExport(
        string $format,
        string $viewBase,
        array $data,
        $excel,
        string $filename,
        ?string $title = null
    ) {
        if ($format === 'print') {
            return view($viewBase . '.export_print', $data);
        }

        if ($format === 'excel') {
            return Excel::download($excel, $filename . '.xlsx');
        }

        if ($format === 'pdf') {
            return Pdf::loadView($viewBase . '.export_pdf', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script at the end of the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download($filename . '.pdf');
        }

        $columns = $data['columns'];
        $rows = $data['rows'];
        $csvBand = \App\Support\ExportCsvHeader::rows(
            $title ?: 'Evaluation Reports',
            $data['filterText'] !== '' ? $data['filterText'] : null,
            $data['exportDate'],
            is_countable($rows) ? count($rows) : $rows->count()
        );

        return response()->streamDownload(function () use ($columns, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, array_values(array_map(fn ($c) => $c['heading'], $columns)));

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_values(array_map(fn ($c) => $c['value']($row, $index), $columns)));
            }

            fclose($handle);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
