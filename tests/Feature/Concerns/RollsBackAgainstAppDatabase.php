<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The suite convention (see DirectoryExportAccessTest): skip when the
 * application database is unreachable, otherwise wrap every test in a
 * transaction that is always rolled back — this suite runs against a real
 * schema with real reference rows.
 *
 * Used by the PR #334 review-fix tests, which need real users, roles, courses
 * and groups to exercise the authorisation and audience paths.
 */
trait RollsBackAgainstAppDatabase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('this test needs the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /** A login holding the named Spatie role, or the test is skipped. */
    protected function userWithRole(string $role): User
    {
        $pk = DB::table('model_has_roles as m')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', $role)
            ->where('m.model_type', User::class)
            ->orderBy('m.model_id')
            ->value('m.model_id');

        if (! $pk || ! ($user = User::find($pk))) {
            $this->markTestSkipped("no user holds the '{$role}' role");
        }

        return $user;
    }

    /** Act as $user with the session roles hasRole() reads first. */
    protected function as(User $user, array $sessionRoles)
    {
        return $this->actingAs($user)->withSession(['user_roles' => $sessionRoles]);
    }

    /** An Officer Trainee login (user_category 'S'), or the test is skipped. */
    protected function officerTrainee(): User
    {
        $pk = DB::table('user_credentials')
            ->where('user_category', 'S')
            ->whereNotNull('user_id')
            ->orderByDesc('pk')
            ->value('pk');

        if (! $pk) {
            $this->markTestSkipped('no officer trainee login');
        }

        return User::findOrFail($pk);
    }
}
