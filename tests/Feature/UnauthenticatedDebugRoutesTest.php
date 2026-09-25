<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Pins the removal of two unauthenticated debug routes and the gate on the third
 * (PR #317 L-1, L-2).
 *
 * The removed routes are checked in the route table, not by requesting them:
 * test-menus ended in dd(), which would halt the PHPUnit process on the old code
 * instead of failing the test.
 *
 * Nothing here touches the database, so every case runs on a box without one.
 * The actors are unsaved users whose hasRole() is fixed, and Artisan is mocked
 * so a permitted request proves the caches would be cleared without clearing the
 * real ones (optimize:clear deletes tracked files under bootstrap/cache).
 */
class UnauthenticatedDebugRoutesTest extends TestCase
{
    public function test_the_debug_routes_are_gone(): void
    {
        $this->assertNull($this->route('GET', 'assign-role'), 'assign-role echoed a user\'s permissions to anyone');
        $this->assertNull($this->route('GET', 'test-menus'), 'test-menus dd()\'d the menu tree to anyone');
    }

    public function test_clearing_the_cache_is_a_post_inside_the_web_group(): void
    {
        $post = $this->route('POST', 'clear-cache');
        $this->assertNotNull($post, 'there is no POST clear-cache route');

        // The web group is what runs VerifyCsrfToken, so this is the CSRF check.
        $middleware = $post->gatherMiddleware();
        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);

        $this->assertContains('auth', $this->route('GET', 'clear-cache')->gatherMiddleware());
    }

    public function test_clear_cache_sends_a_guest_to_login(): void
    {
        Artisan::shouldReceive('call')->never();

        // A referer, because the old closure ended in redirect()->back(): without
        // one that falls back to a URL that happens to equal the login page, and
        // this assertion would pass against the unguarded route too.
        $this->from('/dashboard')->get('/clear-cache')->assertRedirect(route('login'));
        $this->from('/dashboard')->post('/clear-cache')->assertRedirect(route('login'));
    }

    public function test_clear_cache_refuses_a_user_who_is_not_super_admin(): void
    {
        Artisan::shouldReceive('call')->never();

        foreach (['get', 'post'] as $verb) {
            $this->assertForbiddenWithoutRendering(fn () => $this->actingAs($this->actor(false))
                ->withSession(['user_roles' => []])
                ->{$verb}('/clear-cache'));
        }
    }

    public function test_a_get_only_offers_the_form_and_clears_nothing(): void
    {
        Artisan::shouldReceive('call')->never();

        $this->actingAs($this->actor(true))
            ->withSession(['user_roles' => []])
            ->get('/clear-cache')
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_a_super_admin_can_clear_the_cache(): void
    {
        foreach (['cache:clear', 'config:clear', 'view:clear', 'route:clear', 'optimize:clear'] as $command) {
            Artisan::shouldReceive('call')->once()->with($command)->andReturn(0);
        }

        // user_roles is empty on purpose: this must pass on the Spatie role alone,
        // through hasRole()'s Super Admin alias branch, not only via the session.
        $this->actingAs($this->actor(true))
            ->withSession(['user_roles' => []])
            ->post('/clear-cache')
            ->assertRedirect(url('clear-cache'))
            ->assertSessionHas('success', 'Cache cleared successfully');
    }

    private function route(string $method, string $uri): ?\Illuminate\Routing\Route
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }

    /** An unsaved user whose Spatie role check answers $superAdmin without a query. */
    private function actor(bool $superAdmin): User
    {
        $user = new class extends User
        {
            public bool $superAdmin = false;

            public function hasRole($roles, ?string $guard = null): bool
            {
                return $this->superAdmin;
            }
        };
        $user->superAdmin = $superAdmin;
        $user->pk = 1;

        return $user;
    }

    /**
     * The 403 page renders the admin layout, which queries the sidebar tables, so
     * assert on the exception instead of the rendered response.
     */
    private function assertForbiddenWithoutRendering(callable $request): void
    {
        $this->withoutExceptionHandling();

        try {
            $request();
            $this->fail('the request was not refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        } finally {
            $this->withExceptionHandling();
        }
    }
}
