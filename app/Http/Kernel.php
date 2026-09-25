<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BindSessionToUserAgent;
use App\Http\Middleware\BlockFcFormBuilderAction;
use App\Http\Middleware\BlockFcFormBuilderDelete;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureFcActivityCoordinator;
use App\Http\Middleware\EnsureFcActivityMatrixAccess;
use App\Http\Middleware\EnsureFcRegAdmin;
use App\Http\Middleware\EnsureIssueReportsAdmin;
use App\Http\Middleware\EnsureMemberPiiAccess;
use App\Http\Middleware\EnsureMemberRecordAccess;
use App\Http\Middleware\EnsureMemoNoticeManager;
use App\Http\Middleware\EnsureRoleAssigned;
use App\Http\Middleware\FcAuth;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use Fruitcake\Cors\HandleCors;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // Outermost: gzips the finished response (no-op when the web server
            // already compressed it). FC form pages compress ~13x.
            CompressResponse::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            // Reject a session cookie replayed from a different browser (copied-cookie
            // hijacking). Runs right after the session is available.
            BindSessionToUserAgent::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            SecurityHeaders::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'ensure.role' => EnsureRoleAssigned::class,
        'fc.auth' => FcAuth::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'fc.activity.coordinator' => EnsureFcActivityCoordinator::class,
        'fc.activity.matrix' => EnsureFcActivityMatrixAccess::class,
        'fc.reg.admin' => EnsureFcRegAdmin::class,
        'fc.builder.delete' => BlockFcFormBuilderDelete::class,
        'issue.reports.admin' => EnsureIssueReportsAdmin::class,
        'member.pii' => EnsureMemberPiiAccess::class,
        'member.record' => EnsureMemberRecordAccess::class,
        'memo.notice.manager' => EnsureMemoNoticeManager::class,
        'fc.builder.action' => BlockFcFormBuilderAction::class,
    ];
}
