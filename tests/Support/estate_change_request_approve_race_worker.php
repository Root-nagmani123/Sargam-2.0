<?php

// One side of a two-connection HAC approval race (PR #349 F-004), started by
// Tests\Feature\EstateChangeRequestConcurrentApprovalTest in its own PHP process, so
// it has its own MySQL connection.
//
//   php tests/Support/estate_change_request_approve_race_worker.php <holder|second> <changeRequestPk> <housePk> <userPk>
//
// holder: approves inside an outer transaction and keeps it open 3 s before
//         committing, so any lock the approval takes is held across the second call.
// second: waits 1 s, then approves the same change request with another house.
// Prints one JSON line: {"role", "started", "returned", "status", "body"}.

use App\Http\Controllers\Admin\EstateController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

[, $role, $changeRequestPk, $housePk, $userPk] = $argv;

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

$request = Request::create('/admin/estate/change-request/approve/'.$changeRequestPk, 'POST', ['estate_house_master_pk' => (int) $housePk]);
$request->headers->set('Accept', 'application/json');
$request->setLaravelSession($session);
$request->setUserResolver(fn () => $user);
app()->instance('request', $request);

if ($role === 'second') {
    usleep(1_000_000);
}
if ($role === 'holder') {
    DB::beginTransaction();
}

$started = microtime(true) - $t0;
try {
    $response = app(EstateController::class)->approveChangeRequest($request, (int) $changeRequestPk);
    $status = $response->getStatusCode();
    $body = json_decode($response->getContent(), true);
} catch (ValidationException $e) {
    [$status, $body] = [422, $e->errors()];
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
]), "\n";
