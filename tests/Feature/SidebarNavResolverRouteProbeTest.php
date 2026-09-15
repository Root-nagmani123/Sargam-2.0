<?php

namespace Tests\Feature;

use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Cover for the route-rebinding finding in SidebarNavResolver (PR #311).
 *
 * The defect: routeExistsFor() probed a path with RouteCollection::match(), which does
 * not merely test — it calls $route->bind($request), overwriting the parameters on the
 * SHARED Route instance. Two menus rows store a literal parameterised path
 * (admin/fc/joining-documents/30), so probing one rebound the very route the user was
 * on and the live request's parameters silently became the menu row's.
 *
 * The first test would fail against the pre-fix code. The second pins the behaviour the
 * probe exists for, so a future "fix" cannot quietly break the breadcrumb it powers.
 */
class SidebarNavResolverRouteProbeTest extends TestCase
{
    private function probe(SidebarNavResolver $resolver, string $path): bool
    {
        $method = new \ReflectionMethod($resolver, 'routeExistsFor');
        $method->setAccessible(true);

        return $method->invoke($resolver, $path);
    }

    /** Probing a path must not disturb the route currently being served. */
    public function test_probing_a_parameterised_path_does_not_rebind_the_live_route(): void
    {
        $routes = app('router')->getRoutes();

        try {
            $live = $routes->match(Request::create('/admin/fc/joining-documents/47', 'GET'));
        } catch (\Throwable $e) {
            $this->markTestSkipped('Route admin/fc/joining-documents/{formId} is not registered.');
        }

        $this->assertSame('47', $live->parameter('formId'), 'Precondition: the live route holds formId=47.');

        // A menu row stores this literal path. Probing it must be inert.
        $this->probe(new SidebarNavResolver(), 'admin/fc/joining-documents/30');

        $this->assertSame('47', $live->parameter('formId'),
            'Probing a menu path rebound the live route: the request being served now reports the '
            .'menu row\'s formId instead of the user\'s.');
    }

    /**
     * The probe must still answer correctly — a container slug has no page behind it
     * and must render as plain text, while a real path must still link.
     */
    public function test_probe_still_distinguishes_real_paths_from_container_slugs(): void
    {
        $resolver = new SidebarNavResolver();

        $this->assertFalse($this->probe($resolver, 'role_permission'),
            'A container slug resolves to no GET route and must not be linked.');
        $this->assertFalse($this->probe($resolver, 'this-path-does-not-exist-'.__LINE__));

        $this->assertTrue($this->probe($resolver, 'admin/roles'),
            'A real registered path must still resolve so the breadcrumb links it.');
    }

    /** The memo must not leak across resolver instances (it was a function static). */
    public function test_memo_is_per_instance_not_shared_process_state(): void
    {
        $path = 'admin/roles';

        $this->assertTrue($this->probe(new SidebarNavResolver(), $path));
        $this->assertTrue($this->probe(new SidebarNavResolver(), $path));

        $reflection = new \ReflectionProperty(SidebarNavResolver::class, 'routeExistsMemo');
        $this->assertFalse($reflection->isStatic(),
            'A static memo survives into the next request on a persistent worker, outliving the '
            .'route table it was computed from.');
    }
}
