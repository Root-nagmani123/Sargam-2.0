<?php

namespace App\Providers;

use App\Support\FeedbackReportRouteRegistry;
use App\Services\FC\FcPostArrivalAccessService;
use App\Services\NotificationService;
use App\Services\SidebarMenu\BreadcrumbResolver;
use App\Services\SidebarMenu\MenuService;
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
    public function boot(MenuService $menuService)
    {
        Paginator::useBootstrap();

        view()->composer('*', function ($view) use ($menuService) {
            if (! auth()->check()) {
                return;
            }

            if ($view->offsetExists('sidebarMenus')) {
                return;
            }

            $view->with('sidebarMenus', $menuService->getMenus());
        });

        // Keep the programme (?form=) token on the FC header login/logout links so it
        // is never dropped as the trainee moves through the public registration funnel.
        view()->composer('fc.layouts.header', function ($view) {
            if ($view->offsetExists('fcHeaderFormQuery')) {
                return;
            }

            $view->with(
                'fcHeaderFormQuery',
                app(\App\Services\FC\FcRegistrationIntentService::class)->formQueryForHeaderLinks(request())
            );
        });

        view()->composer(['admin.*', 'components.breadcrum'], function ($view) {
            if (! auth()->check()) {
                return;
            }

            if ($view->offsetExists('breadcrumbTrail')) {
                return;
            }

            try {
                $resolver = app(BreadcrumbResolver::class);
                $view->with('breadcrumbTrail', $resolver->resolve());
            } catch (\Throwable) {
                $view->with('breadcrumbTrail', null);
            }
        });

        View::composer([
            'admin.feedback.feedback_details',
            'admin.feedback.faculty_view',
            'admin.feedback.faculty_average',
            'admin.feedback.feedback_database',
            'admin.feedback.pending_students',
        ], function ($view) {
            if (View::shared('fr', null) !== null) {
                return;
            }

            $routes = FeedbackReportRouteRegistry::forRequest();
            $view->with('fr', $routes);

            if (View::shared('feedbackReportRoutes', null) === null) {
                $view->with('feedbackReportRoutes', $routes);
            }
        });

        View::composer('components.menu.fc-sidebar', function ($view) {
            if (! Auth::check()) {
                $view->with('fcActivityNavDepartments', collect());
                $view->with('fcActivityNavCanSetup', false);
                $view->with('fcSidebarShowMedical', false);

                return;
            }
            $svc = app(FcPostArrivalAccessService::class);
            $view->with('fcActivityNavDepartments', $svc->visibleDepartments());
            $view->with('fcActivityNavCanSetup', $svc->canManageActivitySetup());
            $view->with('fcSidebarShowMedical', $svc->canAccessMedicalModule());
        });
    }
}