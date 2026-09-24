<?php

namespace App\Providers;

use App\Models\CalendarEvent;
use App\Models\FacultyMaster;
use App\Models\Timetable;
use App\Support\FeedbackReportCache;
use App\Support\FeedbackReportRouteRegistry;
use App\Services\FC\FcPostArrivalAccessService;
use App\Services\NotificationService;
use App\Services\SidebarMenu\BreadcrumbResolver;
use App\Services\SidebarMenu\MenuService;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
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

        /*
         * The session-feedback report lookups (topic list, faculty dropdown, typeahead) are
         * cached under a generation counter that only submitFeedback() used to bump. Those
         * lists derive from timetable and faculty_master, not from topic_feedback, so adding
         * a session or renaming a faculty left the dropdowns stale until the TTL expired —
         * before the caching went in they were always fresh.
         *
         * Hooked on the models rather than the write call sites: the timetable is written
         * from more than twenty places and faculty_master from several, so a per-call-site
         * bust would be one missed edit away from silently going stale again. Both are
         * Eloquent models with no raw query-builder writes, so saved/deleted covers them.
         *
         * CalendarEvent is listed because Eloquent events are per model CLASS, not per table,
         * and TWO classes map to `timetable`: Timetable (app/Models/Timetable.php) and
         * CalendarEvent (app/Models/CalendarEvent.php). Every session write goes through
         * CalendarEvent — create/update/delete in CalendarController — while Timetable is
         * only ever read from. Hooking Timetable alone therefore registered on a class
         * nothing writes, and session changes left the topic dropdown stale for the full
         * TTL. Timetable stays in the list so the coverage survives if a write path is ever
         * added through it.
         */
        foreach ([CalendarEvent::class, Timetable::class, FacultyMaster::class] as $model) {
            $model::saved(static fn () => FeedbackReportCache::bust());
            $model::deleted(static fn () => FeedbackReportCache::bust());
        }

        // Schema introspection (Schema::hasTable/hasColumn) is cached across requests
        // by fc_schema_columns() because information_schema reads contend badly under
        // load. Migrations are the only thing that can invalidate it, so flush there.
        Event::listen(MigrationsEnded::class, static function () {
            if (function_exists('fc_schema_cache_forget')) {
                fc_schema_cache_forget();
            }
        });

        // Reject HTML / angle brackets in free-text inputs to block stored XSS at the
        // source (CWE-20 / CWE-79). Non-string values pass through untouched; only
        // fields that explicitly opt in via the `no_html` rule are affected.
        Validator::extend('no_html', function ($attribute, $value) {
            return ! is_string($value) || preg_match('/[<>]/', $value) === 0;
        }, 'The :attribute field must not contain HTML or the characters < and >.');

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