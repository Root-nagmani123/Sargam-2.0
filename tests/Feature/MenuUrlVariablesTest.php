<?php

namespace Tests\Feature;

use App\Http\Requests\SidebarMenu\MenuRequest;
use App\Models\SidebarMenu\Menu;
use App\Models\User;
use App\Services\SidebarMenu\MenuRouteMatcher;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\MenuUrlVariables;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * {username} / {email} / {phone} in a menu Url.
 *
 * The menu stores the template; each signed-in user gets their own value put in
 * when the link is built. Static Urls must come out exactly as they did before.
 */
class MenuUrlVariablesTest extends TestCase
{
    private const TEMPLATE = 'https://sargam.lbsnaa.gov.in/LBSNA/gyanListAllTourReport?uname={username}';

    protected function setUp(): void
    {
        parent::setUp();

        config(['menu_url_variables.allowed_hosts' => ['sargam.lbsnaa.gov.in']]);
    }

    /** An unsaved user carrying only the columns the variables read. */
    private function user(array $attributes = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'pk' => 999001,
            'user_name' => 'b.rajender',
            'email_id' => 'b.rajender@lbsnaa.gov.in',
            'mobile_no' => '9876543210',
        ], $attributes));

        return $user;
    }

    private function hrefAs(?User $user, string $route, ?int $menuId = 204): string
    {
        if ($user) {
            $this->actingAs($user);
        } else {
            auth()->logout();
        }

        return app(MenuRouteMatcher::class)->resolveHref($route, $menuId);
    }

    private function errorHref(string $reason, int $menuId = 204): string
    {
        return route('admin.navigation.error', ['reason' => $reason, 'menu_id' => $menuId]);
    }

    // ---- resolution -------------------------------------------------------

    public function test_username_is_filled_in_for_the_signed_in_user(): void
    {
        $this->assertSame(
            'https://sargam.lbsnaa.gov.in/LBSNA/gyanListAllTourReport?uname=b.rajender',
            $this->hrefAs($this->user(), self::TEMPLATE)
        );
    }

    public function test_email_and_phone_are_filled_in(): void
    {
        $this->assertSame(
            'https://sargam.lbsnaa.gov.in/x?mail=b.rajender%40lbsnaa.gov.in',
            $this->hrefAs($this->user(), 'https://sargam.lbsnaa.gov.in/x?mail={email}')
        );
        $this->assertSame(
            'https://sargam.lbsnaa.gov.in/x?a=1&mob=9876543210',
            $this->hrefAs($this->user(), 'https://sargam.lbsnaa.gov.in/x?a=1&mob={phone}')
        );
    }

    public function test_values_are_url_encoded(): void
    {
        $user = $this->user(['user_name' => 'a b&c=d', 'email_id' => 'x+y@lbsnaa.gov.in']);

        $this->assertSame(
            'https://sargam.lbsnaa.gov.in/x?u=a%20b%26c%3Dd',
            $this->hrefAs($user, 'https://sargam.lbsnaa.gov.in/x?u={username}')
        );
        $this->assertSame(
            'https://sargam.lbsnaa.gov.in/x?e=x%2By%40lbsnaa.gov.in',
            $this->hrefAs($user, 'https://sargam.lbsnaa.gov.in/x?e={email}')
        );
    }

    public function test_a_url_with_more_than_one_variable_is_never_resolved(): void
    {
        // A row written outside the form is refused at click time too.
        foreach ([
            'https://sargam.lbsnaa.gov.in/x?u={username}&e={email}',
            'https://sargam.lbsnaa.gov.in/x?u={username}&v={username}',
        ] as $route) {
            $href = $this->hrefAs($this->user(), $route);

            $this->assertSame($this->errorHref('invalid_route'), $href, $route);
            $this->assertStringNotContainsString('b.rajender', $href, $route);
        }
    }

    public function test_each_user_gets_their_own_url_from_the_same_template(): void
    {
        $first = $this->hrefAs($this->user(['pk' => 1, 'user_name' => 'b.rajender']), self::TEMPLATE);
        $second = $this->hrefAs($this->user(['pk' => 2, 'user_name' => 'other.user']), self::TEMPLATE);

        $this->assertStringEndsWith('uname=b.rajender', $first);
        $this->assertStringEndsWith('uname=other.user', $second);
    }

    public function test_a_missing_value_goes_to_the_missing_profile_page(): void
    {
        foreach ([null, '', '   '] as $blank) {
            $this->assertSame(
                $this->errorHref('missing_profile'),
                $this->hrefAs($this->user(['mobile_no' => $blank]), 'https://sargam.lbsnaa.gov.in/x?mob={phone}'),
                'A blank phone number must never produce "?mob=".'
            );
        }
    }

    public function test_no_signed_in_user_goes_to_the_missing_profile_page(): void
    {
        $this->assertSame($this->errorHref('missing_profile'), $this->hrefAs(null, self::TEMPLATE));
    }

    public function test_a_host_outside_the_allowlist_never_receives_user_values(): void
    {
        // A row written outside the form (migration, seeder) is refused at click time too.
        foreach ([
            'https://evil.example.com/x?u={username}',
            'https://sargam.lbsnaa.gov.in.evil.example.com/x?u={username}',
            'http://sargam.lbsnaa.gov.in/x?u={username}',
            'https://user:pass@sargam.lbsnaa.gov.in/x?u={username}',
            'https://sargam.lbsnaa.gov.in/{username}/x',
            'admin/report?u={username}',
        ] as $route) {
            $href = $this->hrefAs($this->user(), $route);

            $this->assertSame($this->errorHref('invalid_route'), $href, $route);
            $this->assertStringNotContainsString('b.rajender', $href, $route);
        }
    }

    public function test_the_allowlist_is_read_from_config(): void
    {
        config(['menu_url_variables.allowed_hosts' => ['other.lbsnaa.gov.in']]);

        $this->assertSame($this->errorHref('invalid_route'), $this->hrefAs($this->user(), self::TEMPLATE));
        $this->assertSame(
            'https://other.lbsnaa.gov.in/x?u=b.rajender',
            $this->hrefAs($this->user(), 'https://other.lbsnaa.gov.in/x?u={username}')
        );
    }

    // ---- static Urls are untouched -----------------------------------------

    public function test_static_urls_resolve_exactly_as_before(): void
    {
        $user = $this->user();

        $this->assertSame('https://example.com/a?b=1', $this->hrefAs($user, 'https://example.com/a?b=1'));
        $this->assertSame(url('admin/course-master').'?menu=204', $this->hrefAs($user, 'admin/course-master'));
        $this->assertSame(url('admin/x').'?a=1&menu=204', $this->hrefAs($user, '/admin/x?a=1'));
        $this->assertSame(
            route('admin.navigation.error', ['reason' => 'missing_path', 'menu_id' => 204]),
            $this->hrefAs($user, '#')
        );
        // Braces that are not one of the three tokens are not treated as variables.
        $this->assertSame('https://example.com/a?b={other}', $this->hrefAs($user, 'https://example.com/a?b={other}'));
        $this->assertFalse(MenuUrlVariables::hasVariables('https://example.com/a?b={other}'));
    }

    public function test_breadcrumb_links_resolve_per_user_and_fall_back_to_plain_text(): void
    {
        $menuUrl = new \ReflectionMethod(SidebarNavResolver::class, 'menuUrl');
        $resolver = app(SidebarNavResolver::class);

        $menu = new Menu();
        $menu->forceFill(['id' => 204, 'route' => self::TEMPLATE]);

        $this->actingAs($this->user());
        $this->assertSame(
            'https://sargam.lbsnaa.gov.in/LBSNA/gyanListAllTourReport?uname=b.rajender',
            $menuUrl->invoke($resolver, $menu)
        );

        $this->actingAs($this->user(['user_name' => '']));
        $this->assertNull($menuUrl->invoke($resolver, $menu));
    }

    // ---- save-time validation (Add and Edit both go through MenuRequest) ---

    private function routeErrors(?string $route): array
    {
        $rules = (new MenuRequest())->rules();

        return Validator::make(['route' => $route], ['route' => $rules['route']])->errors()->get('route');
    }

    public function test_valid_templates_and_static_urls_are_accepted(): void
    {
        foreach ([
            null,
            '',
            'admin/course-master',
            'roles.index',
            'https://example.com/a?b=1',
            self::TEMPLATE,
            'https://sargam.lbsnaa.gov.in/x?a=1&e={email}&b=2',
            'https://sargam.lbsnaa.gov.in/x?p={phone}',
            'https://SARGAM.lbsnaa.gov.in/x?u={username}#top',
        ] as $route) {
            $this->assertSame([], $this->routeErrors($route), (string) $route);
        }
    }

    public function test_invalid_templates_are_refused(): void
    {
        foreach ([
            'https://sargam.lbsnaa.gov.in/x?u={password}' => 'Unknown variable',
            'https://sargam.lbsnaa.gov.in/x?u={Username}' => 'Unknown variable',
            'https://sargam.lbsnaa.gov.in/x?u={ username }' => 'Unknown variable',
            'https://sargam.lbsnaa.gov.in/x?u={username}&e={email}' => 'only one variable',
            'https://sargam.lbsnaa.gov.in/x?u={username}&e={email}&p={phone}' => 'only one variable',
            'https://sargam.lbsnaa.gov.in/x?u={username}&v={username}' => 'only one variable',
            'https://sargam.lbsnaa.gov.in/{username}/x' => 'query string',
            'https://sargam.lbsnaa.gov.in/x?a=1#{username}' => 'query string',
            'admin/report?u={username}' => 'approved host',
            'http://sargam.lbsnaa.gov.in/x?u={username}' => 'approved host',
            'https://evil.example.com/x?u={username}' => 'approved host',
        ] as $route => $expected) {
            $errors = $this->routeErrors($route);

            $this->assertNotEmpty($errors, $route);
            $this->assertStringContainsString($expected, $errors[0], $route);
        }
    }

    // ---- the form offers the variables -------------------------------------

    public function test_the_add_edit_form_offers_the_three_variables(): void
    {
        // One modal serves Add and Edit (openAddModal / openEditModal both reset
        // and reuse #menuForm), so one picker covers both.
        $html = file_get_contents(resource_path('views/SidebarMenu/menus/index.blade.php'));

        $this->assertStringContainsString('id="routeVariable"', $html);
        $this->assertStringContainsString('MenuUrlVariables::LABELS', $html);
        $this->assertSame(['username', 'email', 'phone'], array_keys(MenuUrlVariables::LABELS));

        // The picker's JS pattern must name exactly the tokens the server knows,
        // or Edit would fail to pre-select (or replace) a saved variable.
        $this->assertStringContainsString(
            'var SBM_URL_VARIABLE = /\{('.implode('|', array_keys(MenuUrlVariables::TOKENS)).')\}/g;',
            $html
        );
        // Both modals end in sbmSyncContainerFields(), which syncs the picker.
        $this->assertMatchesRegularExpression('/function sbmSyncContainerFields\(\) \{.*?sbmSyncRouteVariable\(\);\s*\}/s', $html);
    }

    // ---- database-backed: the error page and the cache ---------------------

    private function requireDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: '.$e->getMessage());
        }
    }

    public function test_the_error_page_explains_missing_profile_details(): void
    {
        $this->requireDatabase();

        $user = User::query()->first();
        if (! $user) {
            $this->markTestSkipped('no user_credentials row to sign in as');
        }

        $this->actingAs($user)
            ->get(route('admin.navigation.error', ['reason' => 'missing_profile', 'menu_id' => 204]))
            ->assertStatus(422)
            ->assertSee('Missing profile details.');
    }

    public function test_the_cached_sidebar_structure_holds_the_template_not_a_resolved_url(): void
    {
        $this->requireDatabase();

        $groupId = DB::table('menu_groups')->where('is_active', 1)->value('id');
        $categoryId = DB::table('sidebar_categories')->where('is_active', 1)->value('id');
        if (! $groupId || ! $categoryId) {
            $this->markTestSkipped('no menu_groups / sidebar_categories row to hang a menu off');
        }

        DB::beginTransaction();
        try {
            DB::table('menus')->insert([
                'category_id' => $categoryId,
                'group_id' => $groupId,
                'name' => 'Zz Variable Menu '.uniqid(),
                'route' => self::TEMPLATE,
                'is_active' => 1,
                'target' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            MenuService::clearStructureCache();
            config(['fc.menu_cache_ttl' => 600]);
            $this->actingAs($this->user());
            app(MenuService::class)->getMenus();

            $cached = (string) Cache::get(MenuService::STRUCTURE_CACHE_KEY);
            $this->assertNotSame('', $cached, 'getMenus() did not populate the structure cache.');
            $this->assertStringContainsString('uname={username}', $cached);
            $this->assertStringNotContainsString('uname=b.rajender', $cached);
        } finally {
            DB::rollBack();
            MenuService::clearStructureCache();
        }
    }
}
