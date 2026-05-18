<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Services\NotificationService;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Support\Facades\View;


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
            if (!auth()->check()) {
                return;
            }

            $view->with('sidebarMenus', $menuService->getMenus());

            static $navShared = false;
            if ($navShared) {
                return;
            }
            $navShared = true;

            $resolver = app(SidebarNavResolver::class);
            $navContext = $resolver->resolve();
            $activeNavTab = $navContext['nav_tab'] ?? SidebarNavResolver::HOME_TAB;
            $sidebarContentSection = $resolver->contentSectionForSlug($navContext['category_slug'] ?? 'home');

            View::share([
                'navContext' => $navContext,
                'activeNavTab' => $activeNavTab,
                'activeCategoryId' => $navContext['category_id'],
                'activeGroupId' => $navContext['group_id'],
                'sidebarContentSection' => $sidebarContentSection,
            ]);
        });

    }
}
