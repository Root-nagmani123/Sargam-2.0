<?php

/**
 * Feedback report snapshot harness.
 *
 * Captures the data payload of every feedback report across a matrix of filter
 * combinations, normalises it, and writes one file per case plus a manifest of
 * hashes. Run it before and after a query optimisation and diff the two output
 * directories: identical hashes prove the reports still return the same data.
 *
 *   php scripts/feedback_report_snapshot.php <output-dir> [--timing]
 *
 * Normalisation notes:
 *   - Rows are sorted, so a changed ORDER BY tie-break does not register as a diff.
 *     Ordering is asserted separately (see ORDER-SENSITIVE cases below).
 *   - GROUP_CONCAT(DISTINCT ...) under utf8mb4_general_ci keeps an arbitrary
 *     case-variant ("gOOD" vs "Good"). Remark bundles are therefore compared as a
 *     case-folded sorted set. The raw value is also hashed separately so a genuine
 *     content change is still caught.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\FeedbackController;
use App\Services\FacultyFeedbackReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$outDir = $argv[1] ?? null;
if (! $outDir) {
    fwrite(STDERR, "usage: php scripts/feedback_report_snapshot.php <output-dir> [--timing]\n");
    exit(1);
}
$withTiming = in_array('--timing', $argv, true);

@mkdir($outDir, 0777, true);
array_map('unlink', glob("$outDir/*.json") ?: []);

// Several reports are cached; start from cold so baseline and optimised runs are
// comparing freshly computed data rather than a stale entry from the other run.
Illuminate\Support\Facades\Cache::flush();
App\Support\RedisBackedCache::repositoryForStore(
    App\Support\RedisBackedCache::projectDefaultStoreName()
)->clear();

/** Super Admin => no course scoping => widest possible result set. */
$admin = App\Models\User::query()
    ->whereIn('pk', function ($q) {
        $q->select('model_has_roles.model_id')
            ->from('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Super Admin');
    })
    ->first();

if (! $admin) {
    fwrite(STDERR, "No Super Admin user found; the snapshot needs one to see unscoped data.\n");
    exit(1);
}
Auth::login($admin);

/** Keys whose value is a GROUP_CONCAT bundle of student remarks. */
const REMARK_KEYS = ['all_comments', 'comments', 'remarks', 'remark', 'suggestions'];

/** Keys that carry no report meaning: per-call encryption, positional numbering, wall clock. */
const VOLATILE_KEYS = [
    'faculty_enc_id', 'row_num', 's_no', '_token',
    'export_date', 'generated_at', 'generated_on', 'printed_on', 'print_date',
    'refreshTime', 'refresh_time', 'currentDateTime', 'current_date_time',
];

/**
 * Recursively normalise a payload into something order-insensitively comparable.
 *
 * $key is the key this value was stored under, so remark bundles can be recognised by
 * name rather than by sniffing for a separator — addresses also contain newlines, and
 * case-folding those would mask a genuine difference.
 */
function normalise($value, ?string $key = null)
{
    if ($value instanceof \Illuminate\Support\Collection) {
        $value = $value->all();
    }
    if (is_object($value)) {
        $value = get_object_vars($value);
    }
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            if (in_array($k, VOLATILE_KEYS, true)) {
                continue;
            }
            $out[$k] = normalise($v, is_string($k) ? $k : $key);
        }
        // Sort list-shaped arrays (report rows) so ordering does not affect the hash.
        // Children are normalised first, so the sort key is already canonical.
        if (array_is_list($out)) {
            usort($out, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
        } else {
            ksort($out);
        }

        return $out;
    }
    if (is_string($value) && $key !== null && in_array($key, REMARK_KEYS, true)) {
        return normalise_remarks($value);
    }
    if (is_float($value)) {
        return round($value, 6);
    }

    return $value;
}

/**
 * A GROUP_CONCAT(DISTINCT ...) bundle is a SET, not a sequence, and under
 * utf8mb4_general_ci "Good" and "gOOD" are the same element — which of the two MySQL
 * keeps depends on scan order and is already non-deterministic in the current code.
 * Compare such bundles as a case-folded sorted set so that arbitrary choice does not
 * read as a data change; a genuinely added or removed remark still shows up.
 */
function normalise_remarks(string $s): string
{
    foreach ([' | ', '|||', "\n"] as $sep) {
        if (str_contains($s, $sep)) {
            $parts = array_map(fn ($p) => mb_strtolower(trim($p)), explode($sep, $s));
            $parts = array_values(array_unique(array_filter($parts, fn ($p) => $p !== '')));
            sort($parts);

            return implode(' ~ ', $parts);
        }
    }

    return mb_strtolower(trim($s));
}

/** Pull the comparable data out of whatever a controller action returned. */
function payloadOf($result)
{
    if ($result instanceof \Illuminate\Http\JsonResponse) {
        return json_decode($result->getContent(), true);
    }
    if ($result instanceof \Illuminate\View\View) {
        $data = $result->getData();
        unset($data['__env'], $data['app'], $data['errors']);

        return $data;
    }
    if ($result instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
        return ['__binary_export' => true];
    }
    if ($result instanceof \Illuminate\Http\RedirectResponse) {
        return ['__redirect' => $result->getTargetUrl()];
    }
    if (is_object($result) && method_exists($result, 'getContent')) {
        return ['__content_sha' => sha1((string) $result->getContent())];
    }

    return $result;
}

$manifest = [];

function capture(string $case, callable $fn) {
    global $manifest, $outDir, $withTiming;

    $t0 = microtime(true);
    try {
        $payload = payloadOf($fn());
    } catch (\Throwable $e) {
        $payload = ['__error' => get_class($e) . ': ' . $e->getMessage()];
    }
    $ms = (microtime(true) - $t0) * 1000;

    $norm = normalise($payload);
    $json = json_encode($norm, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents("$outDir/" . preg_replace('/[^a-z0-9_.-]/i', '_', $case) . '.json', $json);

    $manifest[$case] = ['sha1' => sha1($json), 'bytes' => strlen($json)];
    if ($withTiming) {
        $manifest[$case]['ms'] = round($ms, 1);
    }
    printf("  %-58s %8.1fms  %s\n", $case, $ms, substr(sha1($json), 0, 12));
}

/** Build a Request and make it the container's current request (controllers call request()). */
function req(array $params = [], string $method = 'GET'): Request {
    $r = Request::create('/harness', $method, $params);
    $r->setLaravelSession(app('session.store'));
    app()->instance('request', $r);
    Illuminate\Support\Facades\Facade::clearResolvedInstance('request');
    app()->instance('request', $r);

    return $r;
}

/* --------------------------------------------------------------------------
 | Filter matrix — real ids drawn from the database so the cases hit data.
 * ------------------------------------------------------------------------ */
// Busiest courses first — these exercise the widest code paths.
$courseIds = DB::table('topic_feedback as tf')
    ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
    ->groupBy('t.course_master_pk')
    ->orderByRaw('COUNT(*) DESC')
    ->limit(4)
    ->pluck('t.course_master_pk')
    ->all();

$facultyIds = DB::table('topic_feedback')
    ->groupBy('faculty_pk')
    ->orderByRaw('COUNT(*) DESC')
    ->limit(4)
    ->pluck('faculty_pk')
    ->all();

// Courses that actually have pending-feedback rows, so those cases are not vacuous.
$pendingCourseIds = DB::table('course_student_attendance as csa')
    ->join('timetable as t', 'csa.timetable_pk', '=', 't.pk')
    ->where('csa.status', '1')
    ->groupBy('t.course_master_pk')
    ->orderByRaw('COUNT(*) DESC')
    ->limit(3)
    ->pluck('t.course_master_pk')
    ->all();

$topic = DB::table('timetable')->whereNotNull('subject_topic')
    ->where('subject_topic', '<>', '')->value('subject_topic');
$topicFragment = $topic ? mb_substr($topic, 0, 8) : 'a';

$facultyName = DB::table('faculty_master')->whereNotNull('full_name')
    ->where('full_name', '<>', '')->value('full_name');

echo "Snapshotting to $outDir\n";
echo "  courses=" . json_encode($courseIds) . " faculties=" . json_encode($facultyIds) . "\n\n";

$c = new FeedbackController();
$ref = new ReflectionClass($c);
$priv = function (string $m, ...$args) use ($c, $ref) {
    $x = $ref->getMethod($m);
    $x->setAccessible(true);

    return $x->invoke($c, ...$args);
};

/* ---------------- Feedback Database report ---------------- */
echo "[ Feedback Database ]\n";
capture('database.data.nofilter', fn () => $c->getDatabaseData(req(['per_page' => 25, 'page' => 1])));
capture('database.data.page2', fn () => $c->getDatabaseData(req(['per_page' => 25, 'page' => 2])));
capture('database.data.page3.small', fn () => $c->getDatabaseData(req(['per_page' => 7, 'page' => 3])));
foreach ($courseIds as $i => $cid) {
    capture("database.data.course$i", fn () => $c->getDatabaseData(req(['course_id' => $cid, 'per_page' => 50])));
}
foreach ($facultyIds as $i => $fid) {
    capture("database.data.faculty$i", fn () => $c->getDatabaseData(req(['faculty_id' => $fid, 'per_page' => 50])));
}
capture('database.data.topic', fn () => $c->getDatabaseData(req(['topic_value' => $topicFragment, 'per_page' => 50])));
capture('database.data.search', fn () => $c->getDatabaseData(req(['search_term' => 'a', 'per_page' => 50])));
capture('database.data.cond.ge80', fn () => $c->getDatabaseData(req(['cond_field' => 'content', 'cond_operator' => '>=', 'cond_value' => 80, 'per_page' => 50])));
capture('database.data.cond.lt50', fn () => $c->getDatabaseData(req(['cond_field' => 'presentation', 'cond_operator' => '<', 'cond_value' => 50, 'per_page' => 50])));
capture('database.data.combo', fn () => $c->getDatabaseData(req(['course_id' => $courseIds[0] ?? null, 'search_term' => 'a', 'per_page' => 50])));
capture('database.faculties.all', fn () => $c->getDatabaseFaculties(req()));
foreach ($courseIds as $i => $cid) {
    capture("database.faculties.course$i", fn () => $c->getDatabaseFaculties(req(['course_id' => $cid])));
}
foreach ($courseIds as $i => $cid) {
    capture("database.topics.course$i", fn () => $c->getTopicsForCourse(req(['course_id' => $cid])));
}
capture('database.courses.current', fn () => $c->getDatabaseCourses(req(['course_type' => 'current'])));
capture('database.courses.archived', fn () => $c->getDatabaseCourses(req(['course_type' => 'archived'])));
// Invalid input must keep failing validation, not silently start returning rows.
capture('database.courses.invalid', fn () => $c->getDatabaseCourses(req(['course_type' => 'bogus'])));

// Whole result set, unpaginated: the real "same data" assertion for the grid.
// Independent of ordering and of how rows fall across page boundaries.
capture('database.data.FULLSET', fn () => $priv('baseDatabaseQuery', req())->get());
foreach ($courseIds as $i => $cid) {
    capture("database.data.FULLSET.course$i", fn () => $priv('baseDatabaseQuery', req(['course_id' => $cid]))->get());
}

/*
 * Paging integrity: walking every page must yield each row exactly once and reproduce
 * the full result set. This holds only if ORDER BY is a total order — with ties, OFFSET
 * can repeat or skip rows between pages. Reported as a verdict rather than compared to
 * the baseline, because the baseline is expected to fail it.
 */
capture('database.data.PAGING_INTEGRITY', function () use ($c, $priv) {
    $perPage = 25;
    $total = $priv('baseDatabaseQuery', req())->get()->count();
    $pages = (int) ceil($total / $perPage);

    $seen = [];
    $dupes = 0;
    for ($p = 1; $p <= $pages; $p++) {
        $body = json_decode($c->getDatabaseData(req(['per_page' => $perPage, 'page' => $p]))->getContent(), true);
        foreach ($body['data'] ?? [] as $row) {
            $key = ($row['timetable_pk'] ?? '') . ':' . ($row['faculty_id'] ?? '');
            if (isset($seen[$key])) {
                $dupes++;
            }
            $seen[$key] = true;
        }
    }

    return [
        'distinct_rows_expected' => $total,
        'distinct_rows_walked' => count($seen),
        'duplicate_rows_across_pages' => $dupes,
        'verdict' => ($dupes === 0 && count($seen) === $total) ? 'PASS' : 'FAIL',
    ];
});

/* ---------------- Faculty Average report ---------------- */
echo "[ Faculty Average ]\n";
capture('avg.nofilter', fn () => $c->showFacultyAverage(req()));
foreach ($courseIds as $i => $cid) {
    capture("avg.program$i", fn () => $c->showFacultyAverage(req(['program_name' => $cid])));
}

/* ---------------- Faculty View report ---------------- */
echo "[ Faculty View ]\n";
capture('view.nofilter', fn () => $c->facultyView(req([], 'POST')));
foreach ($courseIds as $i => $cid) {
    capture("view.program$i", fn () => $c->facultyView(req(['program_id' => $cid], 'POST')));
}
capture('view.type1', fn () => $c->facultyView(req(['faculty_type' => ['1']], 'POST')));
capture('view.type2', fn () => $c->facultyView(req(['faculty_type' => ['2']], 'POST')));
capture('view.type12', fn () => $c->facultyView(req(['faculty_type' => ['1', '2']], 'POST')));
if ($facultyName) {
    capture('view.facultyname', fn () => $c->facultyView(req(['faculty_name' => $facultyName], 'POST')));
}
capture('view.suggestions.all', fn () => $c->getFacultySuggestions(req()));
capture('view.suggestions.type1', fn () => $c->getFacultySuggestions(req(['faculty_type' => '1'])));
capture('view.suggestions.search', fn () => $c->getFacultySuggestions(req(['faculty_name' => 'a'])));

/* ---------------- Feedback Details report ---------------- */
echo "[ Feedback Details ]\n";
capture('details.nofilter', fn () => $c->feedbackDetails(req()));
foreach ($courseIds as $i => $cid) {
    capture("details.program$i", fn () => $c->feedbackDetails(req(['program_id' => $cid])));
}

/*
 * Archived cases matter far more than they look. Every course carrying feedback in this
 * database has already ended, so a request without course_type=archived reports on the
 * "current" set and comes back EMPTY — the cases above compare nothing but chrome. These
 * drive the same reports over data that actually exists.
 */
echo "[ Feedback Details / Average / View — archived (rows actually present) ]\n";
$ARCH = ['course_type' => 'archived'];

capture('details.archived.all', fn () => $c->feedbackDetails(req($ARCH)));
foreach ($courseIds as $i => $cid) {
    capture("details.archived.program$i", fn () => $c->feedbackDetails(req($ARCH + ['program_id' => $cid])));
}
capture('details.archived.type1', fn () => $c->feedbackDetails(req($ARCH + ['faculty_type' => ['1']])));
capture('details.archived.type2', fn () => $c->feedbackDetails(req($ARCH + ['faculty_type' => ['2']])));
capture('details.archived.page3', fn () => $c->feedbackDetails(req($ARCH + ['program_id' => $courseIds[0] ?? null, 'page' => 3])));
capture('details.archived.page7', fn () => $c->feedbackDetails(req($ARCH + ['program_id' => $courseIds[0] ?? null, 'page' => 7])));
if ($facultyName) {
    capture('details.archived.facultyname', fn () => $c->feedbackDetails(req($ARCH + ['faculty_name' => $facultyName])));
}

capture('avg.archived.all', fn () => $c->showFacultyAverage(req($ARCH)));
foreach ($courseIds as $i => $cid) {
    capture("avg.archived.program$i", fn () => $c->showFacultyAverage(req($ARCH + ['program_name' => $cid])));
}

capture('view.archived.all', fn () => $c->facultyView(req($ARCH, 'POST')));
foreach ($courseIds as $i => $cid) {
    capture("view.archived.program$i", fn () => $c->facultyView(req($ARCH + ['program_id' => $cid], 'POST')));
}
capture('view.archived.type1', fn () => $c->facultyView(req($ARCH + ['faculty_type' => ['1']], 'POST')));
capture('view.archived.type2', fn () => $c->facultyView(req($ARCH + ['faculty_type' => ['2']], 'POST')));

/*
 * Every feedback row the Details report can reach for one course, collected by walking all
 * its pages. This is the real "no data lost" assertion: it compares the whole reachable set,
 * independent of which page a row happens to land on.
 */
capture('details.FULLSET.walk', function () use ($c, $ARCH, $courseIds) {
    $params = $ARCH + ['program_id' => $courseIds[0] ?? null];
    $first = $c->feedbackDetails(req($params + ['page' => 1]))->getData();
    $total = (int) ($first['totalRecords'] ?? 0);
    $perPage = (int) ($first['perPage'] ?? 10);

    $ids = [];
    $duplicates = 0;
    for ($p = 1; $p <= (int) ceil($total / max($perPage, 1)); $p++) {
        $data = $c->feedbackDetails(req($params + ['page' => $p]))->getData();
        foreach ($data['groupedData'] ?? [] as $group) {
            foreach ($group as $row) {
                $id = $row['feedback_id'] ?? null;
                if ($id === null) {
                    continue;
                }
                if (isset($ids[$id])) {
                    $duplicates++;
                }
                $ids[$id] = true;
            }
        }
    }
    ksort($ids);

    return [
        'total_reported' => $total,
        'distinct_reached' => count($ids),
        'duplicates_across_pages' => $duplicates,
        'verdict' => ($duplicates === 0 && count($ids) === $total) ? 'PASS' : 'FAIL',
        'feedback_ids' => array_keys($ids),
    ];
});

/* ---------------- Pending feedback ---------------- */
echo "[ Pending Feedback ]\n";
capture('pending.grouped.p1', fn () => $c->pendingStudentsGroupedData(req(['per_page' => 20, 'page' => 1])));
capture('pending.grouped.p2', fn () => $c->pendingStudentsGroupedData(req(['per_page' => 20, 'page' => 2])));
capture('pending.grouped.sorted', fn () => $c->pendingStudentsGroupedData(req(['sort_by' => 'feedback_not_given', 'sort_dir' => 'desc', 'per_page' => 20])));
foreach ($pendingCourseIds as $i => $cid) {
    capture("pending.grouped.course$i", fn () => $c->pendingStudentsGroupedData(req(['course_pk' => $cid, 'per_page' => 50])));
    capture("pending.grouped.course$i.archive", fn () => $c->pendingStudentsGroupedData(req(['course_pk' => $cid, 'course_type' => 'archive', 'per_page' => 50])));
}
capture('pending.grouped.archive', fn () => $c->pendingStudentsGroupedData(req(['course_type' => 'archive', 'per_page' => 50])));
capture('pending.grouped.search', fn () => $c->pendingStudentsGroupedData(req(['search' => 'a', 'per_page' => 20])));
capture('pending.grouped.archive.search', fn () => $c->pendingStudentsGroupedData(req(['course_type' => 'archive', 'search' => 'a', 'per_page' => 20])));
capture('pending.grouped.state.given', fn () => $c->pendingStudentsGroupedData(req(['filter_feedback_state' => 'given', 'per_page' => 20])));
capture('pending.grouped.state.notgiven', fn () => $c->pendingStudentsGroupedData(req(['filter_feedback_state' => 'not_given', 'per_page' => 20])));
capture('pending.grouped.sort.name.desc', fn () => $c->pendingStudentsGroupedData(req(['sort_by' => 'student_name', 'sort_dir' => 'desc', 'per_page' => 20])));
capture('pending.grouped.sort.given', fn () => $c->pendingStudentsGroupedData(req(['sort_by' => 'feedback_given', 'sort_dir' => 'desc', 'per_page' => 20])));
capture('pending.grouped.sort.summary', fn () => $c->pendingStudentsGroupedData(req(['sort_by' => 'course_summary', 'per_page' => 20])));

/*
 * Out-of-range pages. The endpoint clamps a too-high page to the last one rather than
 * returning an empty list — requesting page 999 must still come back with the final page's
 * students and the correct total. Anything that changes how the total is obtained has to
 * preserve this, so it is pinned here.
 */
foreach ($pendingCourseIds as $i => $cid) {
    capture("pending.grouped.course$i.page999", fn () => $c->pendingStudentsGroupedData(req(['course_pk' => $cid, 'per_page' => 20, 'page' => 999])));
}
capture('pending.grouped.page999', fn () => $c->pendingStudentsGroupedData(req(['per_page' => 20, 'page' => 999])));
capture('pending.grouped.page0', fn () => $c->pendingStudentsGroupedData(req(['per_page' => 20, 'page' => 0])));
capture('pending.grouped.pageneg', fn () => $c->pendingStudentsGroupedData(req(['per_page' => 20, 'page' => -5])));
capture('pending.grouped.search.nomatch.page999', fn () => $c->pendingStudentsGroupedData(req(['search' => 'zzzzzznomatch', 'per_page' => 20, 'page' => 999])));

/*
 * Walk every page of the pending list: each student must appear exactly once, and the pages
 * together must reproduce the reported total. Guards both the paging maths and the total,
 * which are about to start coming from the same query.
 */
capture('pending.PAGING_INTEGRITY', function () use ($c, $pendingCourseIds) {
    $params = ['course_pk' => $pendingCourseIds[0] ?? null, 'per_page' => 20];
    $first = json_decode($c->pendingStudentsGroupedData(req($params + ['page' => 1]))->getContent(), true);
    $total = (int) ($first['total'] ?? 0);
    $perPage = (int) ($first['per_page'] ?? 20);

    $seen = [];
    $dupes = 0;
    for ($p = 1; $p <= max(1, (int) ceil($total / max($perPage, 1))); $p++) {
        $body = json_decode($c->pendingStudentsGroupedData(req($params + ['page' => $p]))->getContent(), true);
        foreach ($body['students'] ?? [] as $student) {
            $key = $student['ot_code'] ?? ($student['email'] ?? json_encode($student));
            if (isset($seen[$key])) {
                $dupes++;
            }
            $seen[$key] = true;
        }
    }

    return [
        'total_reported' => $total,
        'distinct_reached' => count($seen),
        'duplicates_across_pages' => $dupes,
        'verdict' => ($dupes === 0 && count($seen) === $total) ? 'PASS' : 'FAIL',
    ];
});
capture('pending.stats.all', fn () => $c->getPendingStats(req()));
foreach ($pendingCourseIds as $i => $cid) {
    capture("pending.stats.course$i", fn () => $c->getPendingStats(req(['course_pk' => $cid])));
}
capture('pending.sessions', fn () => $c->getSessionsByCourse(req(['course_pk' => $pendingCourseIds[0] ?? null])));

/* ---------------- Faculty portal (service layer) ---------------- */
echo "[ Faculty Portal Service ]\n";
$svc = app(FacultyFeedbackReportService::class);
$svcRef = new ReflectionClass($svc);
foreach ($svcRef->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
    if ($m->getNumberOfRequiredParameters() === 0 && ! $m->isStatic() && $m->getName() !== '__construct') {
        capture('portal.' . $m->getName(), fn () => $svc->{$m->getName()}());
    }
}

/* ---------------- Export data builders (data only, not the binary) ---------------- */
echo "[ Export data builders ]\n";
capture('export.database.context', fn () => $priv('buildFeedbackDatabaseExportContext', req()));
foreach ($courseIds as $i => $cid) {
    capture("export.database.context.course$i", fn () => $priv('buildFeedbackDatabaseExportContext', req(['course_id' => $cid])));
}
capture('export.pending.grouped', fn () => $priv('buildGroupedExportData', req()));
capture('export.pending.query', fn () => $priv('buildExportQuery')->limit(500)->get());
capture('export.summary.query', fn () => $priv('buildSummaryExportQuery')->limit(500)->get());
capture('export.summary.detail', fn () => $priv('buildSummaryDetailExportQuery')->limit(500)->get());

ksort($manifest);
file_put_contents("$outDir/MANIFEST.json", json_encode($manifest, JSON_PRETTY_PRINT));

echo "\nCaptured " . count($manifest) . " cases -> $outDir/MANIFEST.json\n";
