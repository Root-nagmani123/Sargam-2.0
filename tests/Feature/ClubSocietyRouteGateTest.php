<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMenuPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Cover for PR #340 F-001: every Club / Society route was gated by `auth` alone,
 * and OT trainees (user_category 'S') sign in through that same guard, so any OT
 * could publish results, delete drives and withdraw rivals' nominations.
 *
 * Each route group is now gated on the permission its own sidebar menu migration
 * creates (database/migrations/society/*_sidebar_menu.php).
 */
class ClubSocietyRouteGateTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** Route-name prefix => menus.permission_name of that screen. */
    private const GROUPS = [
        'master.club.society.role.programme.mapping.' => 'club_society_role_programme_mapping',
        'master.club.society.programme.mapping.'      => 'club_society_programme_mapping',
        'master.club.society.role.'                   => 'define_club_society_role',
        'master.club.society.'                        => 'define_club_society',
        'master.nomination.drive.'                    => 'create_nomination_drive',
        'master.election.drive.'                      => 'create_election_drive',
        'master.office.bearer.'                       => 'officer_bearers',
    ];

    /** @return array<string, array{0: \Illuminate\Routing\Route, 1: string}> */
    private function moduleRoutes(): array
    {
        $found = [];
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            // Longest prefix first, so a role route is not read as a society one.
            foreach (self::GROUPS as $prefix => $permission) {
                if (str_starts_with($name, $prefix)) {
                    $found[$name] = [$route, $permission];
                    break;
                }
            }
        }

        return $found;
    }

    public function test_every_club_society_route_is_gated_on_its_screen_permission(): void
    {
        $routes = $this->moduleRoutes();

        $this->assertCount(50, $routes, 'Route count changed: update this test and the gate together.');

        foreach ($routes as $name => [$route, $permission]) {
            $this->assertContains(
                EnsureMenuPermission::class.':'.$permission,
                $route->gatherMiddleware(),
                "{$name} is not gated on {$permission}"
            );
        }
    }

    public function test_an_ot_session_is_refused_on_every_club_society_route(): void
    {
        $pk = DB::table('user_credentials')->where('user_category', 'S')->orderBy('pk')->value('pk');
        if (! $pk || ! ($user = User::find($pk))) {
            $this->markTestSkipped('no OT (user_category S) login on this database');
        }
        if ($this->holdsAny($user, array_values(self::GROUPS))) {
            $this->markTestSkipped('the first OT login on this database holds a Club / Society permission');
        }

        $this->actingAs($user)->withSession(['user_roles' => ['Student-OT']]);

        foreach ($this->moduleRoutes() as $name => [$route]) {
            $uri    = '/'.preg_replace('/\{[^}]+\}/', encrypt(1), $route->uri());
            $method = strtolower(collect($route->methods())->reject(fn ($m) => $m === 'HEAD')->first());

            $response = $this->call(strtoupper($method), $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json']);

            $this->assertSame(403, $response->getStatusCode(), "{$method} {$uri} ({$name}) was not refused");
        }
    }

    /** True when $user holds any of the permissions, read straight from Spatie. */
    private function holdsAny(User $user, array $permissions): bool
    {
        return $user->getAllPermissions()->pluck('name')->intersect($permissions)->isNotEmpty();
    }
}
