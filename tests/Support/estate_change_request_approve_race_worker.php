<?php

// One side of a two-connection Estate allotment race (PR #349 F-004), started by
// Tests\Feature\EstateChangeRequestConcurrentApprovalTest in its own PHP process, so
// it has its own MySQL connection.
//
//   php tests/Support/estate_change_request_approve_race_worker.php <holder|second> <approve|disapprove|allot> <id> <value> <userPk>
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
// Prints one JSON line: {"role", "started", "returned", "status", "body"}.

use App\Http\Controllers\Admin\EstateController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

[, $role, $action, $id, $value, $userPk] = $argv;

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

if ($role === 'second') {
    usleep(1_000_000);
}
if ($role === 'holder') {
    DB::beginTransaction();
}

$started = microtime(true) - $t0;
try {
    $controller = app(EstateController::class);
    $response = $withId ? $controller->{$method}($request, (int) $id) : $controller->{$method}($request);
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
    // The possession forms answer with a redirect and a flash message, not JSON.
    'flash' => ['error' => $session->get('error'), 'success' => $session->get('success')],
]), "\n";
