<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\RouteMaster;
use Spatie\Permission\Models\Permission;

class ExportRoutesToDB extends Command
{
    protected $signature = 'routes:export-to-db';
    protected $description = 'Export all Laravel routes to route_master table';

    public function handle()
    {
        RouteMaster::truncate();

        foreach (Route::getRoutes() as $route) {
            if (!$route->uri || !$route->getActionName()) continue;

            RouteMaster::create([
                'name'       => $route->getName(),
                'uri'        => $route->uri(),
                'method'     => implode('|', $route->methods),
                'action'     => $route->getActionName(),
                'middleware' => implode(',', $route->gatherMiddleware()),
            ]);
        }

        $routes = RouteMaster::whereNotNull('name')->pluck('name');

        Permission::truncate();
        foreach ($routes as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
