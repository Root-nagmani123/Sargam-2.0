<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Protocol\Database\Seeders\ProtocolPermissionsSeeder;
use Tests\TestCase;

/**
 * Spatie denies everyone when a permission row is missing, and the module ships no
 * migrations, so the deploy seeder must create every name a route gates on (PR #348 F-020).
 */
class ProtocolPermissionsSeederTest extends TestCase
{
    public function test_the_seeder_creates_every_permission_a_protocol_route_gates_on(): void
    {
        $gated = collect(Route::getRoutes()->getRoutes())
            ->flatMap(fn ($route) => $route->gatherMiddleware())
            ->filter(fn ($middleware) => is_string($middleware) && Str::startsWith($middleware, 'can:protocol.'))
            ->map(fn ($middleware) => Str::after($middleware, 'can:'))
            ->unique()
            ->values();

        $this->assertNotEmpty($gated);
        $this->assertSame([], $gated->diff(ProtocolPermissionsSeeder::PERMISSIONS)->values()->all());
    }
}
