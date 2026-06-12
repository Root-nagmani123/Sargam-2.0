<?php

namespace App\Providers;

use App\Support\FeedbackReportRouteRegistry;
use App\Support\HtmlSanitizer;
use App\Services\NotificationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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

        // @clean($html) — echoes sanitised rich text. Use instead of {!! $html !!}
        // for any user/DB-authored HTML (notice descriptions, chat templates).
        Blade::directive('clean', function ($expression) {
            return "<?php echo \App\Support\HtmlSanitizer::clean($expression); ?>";
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
    }
}
