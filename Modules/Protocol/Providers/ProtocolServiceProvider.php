<?php

namespace Modules\Protocol\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Protocol\Authorization\ProtocolGate;

class ProtocolServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Protocol';

    protected string $moduleNameLower = 'protocol';

    /**
     * Absolute path to this module's root folder, e.g.
     * /var/www/sargam-2.0/Modules/Protocol
     * No external package required — plain base_path() only.
     */
    protected function modulePath(string $relative = ''): string
    {
        return base_path('Modules/' . $this->moduleName . ($relative ? '/' . $relative : ''));
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
        $this->registerRoutes();
        $this->registerGates();
    }

    protected function registerGates(): void
    {
        Gate::define('protocol.view-requests', [ProtocolGate::class, 'viewRequests']);
    }

    /**
     * Register the service provider.
     * (Routes are handled in boot() -> registerRoutes(); no separate
     * RouteServiceProvider needed since this module doesn't depend on
     * nwidart/laravel-modules.)
     */
    public function register(): void
    {
        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('modules/protocol'),
        ], 'protocol-assets');
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            $this->modulePath('Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            $this->modulePath('Config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = $this->modulePath('Resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom($this->modulePath('Resources/lang'), $this->moduleNameLower);
        }
    }

    protected function registerRoutes(): void
    {
        Route::group([], function () {
            $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        });
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        return $paths;
    }
}
