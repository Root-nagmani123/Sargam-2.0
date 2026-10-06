<?php

// One side of a two-connection Leave-on-Behalf race (PR #334 F-022), started by
// Tests\Feature\LeaveOnBehalfConcurrentSubmitTest in its own PHP process, so it has
// its own MySQL connection.
//
//   php tests/Support/leave_on_behalf_race_worker.php <holder|second> <payload.json> <userPk>
//
// holder: runs store() inside an outer transaction and keeps it open 4 s before
//         committing, so the student_master FOR UPDATE lock is held across the
//         second call. second: waits 1 s, then runs the same store().
// Prints one JSON line: {"role", "started", "returned", "status", "errors"}.

use App\Http\Controllers\Admin\LeaveOnBehalfController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

[, $role, $payloadFile, $userPk] = $argv;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$t0 = microtime(true);
$payload = json_decode(file_get_contents($payloadFile), true);

$session = app('session.store');
$session->start();
$session->put('user_roles', ['Super Admin']);

$user = User::findOrFail((int) $userPk);
Auth::setUser($user);

$request = Request::create('/admin/leave-on-behalf/store', 'POST', $payload);
$request->setLaravelSession($session);
$request->setUserResolver(fn () => $user);
app()->instance('request', $request);

$controller = app(LeaveOnBehalfController::class);

if ($role === 'second') {
    usleep(1_000_000);
}
if ($role === 'holder') {
    DB::beginTransaction();
}

$started = microtime(true) - $t0;
$response = $controller->store($request);
$returned = microtime(true) - $t0;
$errors = $session->get('errors');

if ($role === 'holder') {
    sleep(4);
    DB::commit();
}

echo json_encode([
    'role' => $role,
    'started' => round($started, 2),
    'returned' => round($returned, 2),
    'status' => $response->getStatusCode(),
    'errors' => $errors ? $errors->getBag('default')->toArray() : [],
]), "\n";
