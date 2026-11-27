<?php

namespace Modules\GuestTaClaim\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\GuestTaClaim\Contracts\TimetableProvider;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Http\Middleware\EnsureGtcPermission;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Services\PermissionService;
use Modules\GuestTaClaim\Support\DatabaseTimetableProvider;
use Modules\GuestTaClaim\Support\DemoTimetableProvider;
use Modules\GuestTaClaim\Support\SargamTimetableProvider;
use Modules\GuestTaClaim\View\Composers\SidebarComposer;

class GuestTaClaimServiceProvider extends ServiceProvider
{
    public const VIEW_NAMESPACE = 'guesttaclaim';

    public function register(): void
    {
        $this->mergeConfigFrom(self::path('Config/config.php'), 'guest-ta-claim');

        $this->app->scoped(PermissionService::class);
        $this->app->scoped(SargamTimetableProvider::class);

        $this->app->bind(TimetableProvider::class, function ($app) {
            return match (config('guest-ta-claim.timetable_provider')) {
                'demo' => $app->make(DemoTimetableProvider::class),
                'database' => $app->make(DatabaseTimetableProvider::class),
                default => $app->make(SargamTimetableProvider::class),
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(self::path('Database/Migrations'));
        $this->loadViewsFrom(self::path('Resources/views'), self::VIEW_NAMESPACE);
        $this->loadRoutesFrom(self::path('Routes/web.php'));
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'GuestTaClaim'
        );

        $this->publishes([
            self::path('Config/config.php') => config_path('guest-ta-claim.php'),
        ], 'guest-ta-claim-config');
        $this->publishes([
            self::path('Resources/views') => resource_path('views/vendor/'.self::VIEW_NAMESPACE),
        ], 'guest-ta-claim-views');

        Route::aliasMiddleware('gtc.can', EnsureGtcPermission::class);
        Route::model('claim', Claim::class);

        foreach (Permission::cases() as $permission) {
            Gate::define('gtc.'.$permission->value, fn ($user) => app(PermissionService::class)->has($user, $permission));
        }

        View::composer(self::VIEW_NAMESPACE.'::partials.sidebar', SidebarComposer::class);
    }

    public static function path(string $relative): string
    {
        return dirname(__DIR__).'/'.$relative;
    }
}