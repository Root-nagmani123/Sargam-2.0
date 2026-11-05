<?php

// One side of a two-connection Estate allotment race (PR #349 F-004), started by
// Tests\Feature\EstateChangeRequestConcurrentApprovalTest in its own PHP process, so
// it has its own MySQL connection.
//
//   php tests/Support/estate_change_request_approve_race_worker.php <holder|second> <approve|disapprove|allot> <id> <value> <userPk> [binlogFormat]
//
// approve:    approveChangeRequest(<change request pk>), value = house pk
// disapprove: disapproveChangeRequest(<change request pk>), value = reason
// allot:      allotNewRequest(<home request pk>), value = house pk
// store-other:   storePossession() (Possession for Others), value = JSON form payload, id unused
// store-details: storePossessionDetails() (Add Possession), value = JSON form payload, id unused
// delete-house:  destroyDefineHouse(<house pk>), value unused
//
// holder: runs the action inside an outer transaction and keeps it open 3 s before
//         committing, so any lock the action takes is held across the second call.
// second: waits 1 s, then runs its action.
// together: starts at once, and pauses 3 s right after its occupancy / duplicate check (the
//         first "select exists" on a possession table), so two of them both hold whatever that
//         check locked before either writes: the F-007 deadlock shape. "hook" is that pause as
//         absolute [from, to] times, so the caller can prove the two pauses overlapped.
// binlogFormat: optional session binlog_format (STATEMENT|ROW|MIXED) set before the action, to run it
//         as on a server whose binary log is in that format (PR #349 F-008).
// Prints one JSON line: {"role", "started", "returned", "status", "body"}.

use App\Http\Controllers\Admin\EstateController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

[, $role, $action, $id, $value, $userPk] = $argv;
$binlogFormat = $argv[6] ?? '';

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$t0 = microtime(true);

$session = app('session.store');
$session->start();
$session->put('user_roles', ['Estate Admin']);

$user = User::findOrFail((int) $userPk);
Auth::setUser($user);

[$method, $payload] = match ($action) {
    'approve' => ['approveChangeRequest', ['estate_house_master_pk' => (int) $value]],
    'disapprove' => ['disapproveChangeRequest', ['disapprove_reason' => $value]],
    'allot' => ['allotNewRequest', ['estate_house_master_pk' => (int) $value]],
    'store-other' => ['storePossession', json_decode($value, true)],
    'store-details' => ['storePossessionDetails', json_decode($value, true)],
    'delete-house' => ['destroyDefineHouse', []],
};
$withId = ! in_array($action, ['store-other', 'store-details'], true);

$request = Request::create('/admin/estate/race/'.$id, 'POST', $payload);
$request->headers->set('Accept', 'application/json');
$request->setLaravelSession($session);
$request->setUserResolver(fn () => $user);
app()->instance('request', $request);

if (in_array($binlogFormat, ['STATEMENT', 'ROW', 'MIXED'], true)) {
    DB::unprepared("SET SESSION binlog_format = '{$binlogFormat}'");
}

if ($role === 'second') {
    usleep(1_000_000);
}
if ($role === 'holder') {
    DB::beginTransaction();
}
$hook = null;
if ($role === 'together') {
    DB::listen(function ($query) use (&$hook) {
        if ($hook === null && str_starts_with($query->sql, 'select exists') && str_contains($query->sql, '`estate_possession_')) {
            $hook = [microtime(true)];
            sleep(3);
            $hook[] = microtime(true);
        }
    });
}

$started = microtime(true) - $t0;
try {
    $controller = app(EstateController::class);
    $response = $withId ? $controller->{$method}($request, (int) $id) : $controller->{$method}($request);
    $status = $response->getStatusCode();
    $body = json_decode($response->getContent(), true);
} catch (ValidationException $e) {
    [$status, $body] = [422, $e->errors()];
} catch (QueryException $e) {
    // What the browser would get as an HTTP 500 (a deadlock is SQLSTATE 40001 / 1213).
    [$status, $body] = [500, ['message' => $e->getMessage()]];
}
$returned = microtime(true) - $t0;

if ($role === 'holder') {
    sleep(3);
    DB::commit();
}

echo json_encode([
    'role' => $role,
    'started' => round($started, 2),
    'returned' => round($returned, 2),
    'status' => $status,
    'body' => $body,
    'hook' => $hook,
    // The possession forms answer with a redirect and a flash message, not JSON.
    'flash' => ['error' => $session->get('error'), 'success' => $session->get('success')],
]), "\n";
