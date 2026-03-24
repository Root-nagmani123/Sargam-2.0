<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Services\NotificationService;
use App\Services\SidebarMenu\MenuService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(MenuService $menuService)
    {
        Paginator::useBootstrap();

        view()->composer('*', function ($view) use ($menuService) {
            if (auth()->check()) {
                $view->with('sidebarMenus', $menuService->getMenus());
            }
        });

    }
}
