<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pins the removal of two unauthenticated debug routes and the gate on the third
 * (PR #317 L-1, L-2).
 *
 * The removed routes are checked in the route table, not by requesting them:
 * test-menus ended in dd(), which would halt the PHPUnit process on the old code
 * instead of failing the test.
 */
class UnauthenticatedDebugRoutesTest extends TestCase
{
    use DatabaseTransactions;

    private function registered(string $uri): bool
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri) {
                return true;
            }
        }

        return false;
    }

    public function test_the_debug_routes_are_gone(): void
    {
        $this->assertFalse($this->registered('assign-role'), 'assign-role echoed a user\'s permissions to anyone');
        $this->assertFalse($this->registered('test-menus'), 'test-menus dd()\'d the menu tree to anyone');
    }

    public function test_clear_cache_sends_a_guest_to_login(): void
    {
        // A referer, because the old closure ended in redirect()->back(): without
        // one that falls back to a URL that happens to equal the login page, and
        // this assertion would pass against the unguarded route too.
        $this->from('/dashboard')->get('/clear-cache')->assertRedirect(route('login'));
    }

    public function test_clear_cache_refuses_a_user_who_is_not_super_admin(): void
    {
        $user = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'SuperAdmin']))
            ->first();
        if (!$user) {
            $this->markTestSkipped('every user here is a Super Admin');
        }

        $this->actingAs($user)
            ->withSession(['user_roles' => []])
            ->get('/clear-cache')
            ->assertForbidden();
    }
}
