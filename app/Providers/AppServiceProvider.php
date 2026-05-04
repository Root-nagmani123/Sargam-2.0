<?php

namespace App\Providers;

use App\Services\FC\FcPostArrivalAccessService;
use App\Services\NotificationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;


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
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer('components.menu.fc-sidebar', function ($view) {
            if (! Auth::check()) {
                $view->with('fcActivityNavDepartments', collect());
                $view->with('fcActivityNavCoordinator', false);
                $view->with('fcActivityNavCanSetup', false);
                $view->with('fcSidebarShowMedical', false);

                return;
            }
            $svc = app(FcPostArrivalAccessService::class);
            $view->with('fcActivityNavDepartments', $svc->visibleDepartments());
            $view->with('fcActivityNavCoordinator', $svc->isCoordinator());
            $view->with('fcActivityNavCanSetup', $svc->canManageActivitySetup());
            $view->with('fcSidebarShowMedical', $svc->canAccessMedicalModule());
        });
    }
}
