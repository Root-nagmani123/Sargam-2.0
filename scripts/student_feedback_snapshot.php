<?php

/**
 * Student-facing feedback snapshot harness.
 *
 * Companion to scripts/feedback_report_snapshot.php, covering the three student-side entry
 * points in CalendarController — studentFeedback(), studentFeedback_url() and
 * studentFacultyFeedback() — which the admin-report harness does not reach because they run
 * as a student and build their own pending/submitted lists.
 *
 *   php scripts/student_feedback_snapshot.php <output-dir> [--timing] [--students=N]
 *
 * Run before and after a change and diff with scripts/feedback_snapshot_diff.py.
 *
 * These three pages decide which feedback a trainee still owes, so a false negative silently
 * hides a session from them and a false positive asks for feedback twice. Every student in
 * the sample is captured in full: the pending list, the submitted list, and the per-session
 * faculty resolution.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\CalendarController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$outDir = $argv[1] ?? null;
if (! $outDir) {
    fwrite(STDERR, "usage: php scripts/student_feedback_snapshot.php <output-dir> [--timing] [--students=N]\n");
    exit(1);
}
$withTiming = in_array('--timing', $argv, true);
$studentLimit = 12;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--students=')) {
        $studentLimit = max(1, (int) substr($arg, 11));
    }
}

@mkdir($outDir, 0777, true);
array_map('unlink', glob("$outDir/*.json") ?: []);
Cache::flush();

/** Volatile: per-call encryption and wall-clock stamps carry no report meaning. */
const VOLATILE_KEYS = ['otUrl', 'created_date', '_token'];

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
        if (array_is_list($out)) {
            usort($out, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
        } else {
            ksort($out);
        }

        return $out;
    }

    return is_float($value) ? round($value, 6) : $value;
}

function payloadOf($result)
{
    if ($result instanceof \Illuminate\View\View) {
        $data = $result->getData();
        unset($data['__env'], $data['app'], $data['errors']);

        return $data;
    }
    if ($result instanceof \Illuminate\Http\JsonResponse) {
        return json_decode($result->getContent(), true);
    }
    if ($result instanceof \Illuminate\Http\RedirectResponse) {
        return ['__redirect' => parse_url($result->getTargetUrl(), PHP_URL_PATH)];
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
        // Abort/validation outcomes are part of the contract — record them, don't hide them.
        $payload = ['__error' => get_class($e) . ': ' . $e->getMessage()];
    }
    $ms = (microtime(true) - $t0) * 1000;

    $json = json_encode(normalise($payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents("$outDir/" . preg_replace('/[^a-z0-9_.-]/i', '_', $case) . '.json', $json);

    $manifest[$case] = ['sha1' => sha1($json), 'bytes' => strlen($json)];
    if ($withTiming) {
        $manifest[$case]['ms'] = round($ms, 1);
    }
    printf("  %-52s %8.1fms  %s\n", $case, $ms, substr(sha1($json), 0, 12));
}

function asStudent(User $user): void
{
    Auth::logout();
    Auth::login($user);
}

function req(array $params = []): Request {
    $r = Request::create('/harness', 'GET', $params);
    $r->setLaravelSession(app('session.store'));
    app()->instance('request', $r);

    return $r;
}

/* --------------------------------------------------------------------------
 | Sample: students with the most attendance, so the pending lists are non-trivial.
 * ------------------------------------------------------------------------ */
$students = DB::table('user_credentials as uc')
    ->join('student_master as sm', 'sm.pk', '=', 'uc.user_id')
    ->join('course_student_attendance as csa', function ($j) {
        $j->on('csa.Student_master_pk', '=', 'sm.pk')->where('csa.status', '1');
    })
    ->where('uc.user_category', 'S')
    ->groupBy('uc.pk', 'uc.user_name')
    ->orderByRaw('COUNT(DISTINCT csa.timetable_pk) DESC')
    ->limit($studentLimit)
    ->pluck('uc.user_name', 'uc.pk');

if ($students->isEmpty()) {
    fwrite(STDERR, "No student users with attendance found.\n");
    exit(1);
}

echo "Snapshotting " . $students->count() . " students to $outDir\n\n";

$controller = new CalendarController();

/* ---------------- studentFeedback(): the trainee's own feedback page ---------------- */
echo "[ studentFeedback ]\n";
foreach ($students as $pk => $userName) {
    $user = User::where('pk', $pk)->first();
    if (! $user) {
        continue;
    }
    capture("studentFeedback.$userName", function () use ($controller, $user) {
        asStudent($user);
        req();

        return $controller->studentFeedback();
    });
}

/* ---------------- studentFeedback_url(): same page reached via ?username= SSO hop ---- */
echo "[ studentFeedback_url ]\n";
foreach ($students as $pk => $userName) {
    $user = User::where('pk', $pk)->first();
    if (! $user) {
        continue;
    }

    // Clean request: already authenticated, no query string.
    capture("studentFeedbackUrl.$userName", function () use ($controller, $user) {
        asStudent($user);

        return $controller->studentFeedback_url(req());
    });

    // SSO hop: ?username= should log the user in and redirect to the clean URL.
    capture("studentFeedbackUrl.ssohop.$userName", function () use ($controller, $userName) {
        Auth::logout();

        return $controller->studentFeedback_url(req(['username' => $userName]));
    });
}

/* ---------------- studentFacultyFeedback(): Moodle SSO entry, ?token= ---------------- */
echo "[ studentFacultyFeedback ]\n";
$key = config('services.moodle.key');
$iv = config('services.moodle.iv');

foreach ($students as $pk => $userName) {
    if (! $key || ! $iv) {
        break;
    }
    $token = base64_encode(openssl_encrypt($userName, 'AES-128-CBC', $key, 0, $iv));

    capture("studentFacultyFeedback.$userName", function () use ($controller, $token) {
        Auth::logout();

        return $controller->studentFacultyFeedback(req(['token' => $token]));
    });
}

// Contract cases: these must keep rejecting, not start returning data.
capture('studentFacultyFeedback.missing_token', function () use ($controller) {
    Auth::logout();

    return $controller->studentFacultyFeedback(req());
});
capture('studentFacultyFeedback.bad_token', function () use ($controller) {
    Auth::logout();

    return $controller->studentFacultyFeedback(req(['token' => base64_encode('not-a-real-token')]));
});

ksort($manifest);
file_put_contents("$outDir/MANIFEST.json", json_encode($manifest, JSON_PRETTY_PRINT));

echo "\nCaptured " . count($manifest) . " cases -> $outDir/MANIFEST.json\n";
