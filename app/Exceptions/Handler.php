<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (PostTooLargeException $e, Request $request) {
            $maxMb = 5;
            $message = "The uploaded file is too large. Maximum allowed size is {$maxMb} MB. "
                .'Please choose a smaller file and try again.';

            if ($request->routeIs('fc.exemption.apply')) {
                $exemptionId = $request->route('id');

                return redirect()
                    ->route('fc.exemption_application', ['id' => $exemptionId])
                    ->withErrors(['medical_doc' => $message])
                    ->withInput($request->except('medical_doc', 'captcha'))
                    ->with('captcha_refresh', true);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return redirect()->back()
                ->withErrors(['upload' => $message])
                ->withInput($request->except('medical_doc'));
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof NotFoundHttpException && !$request->expectsJson()) {
            if (view()->exists('errors.404')) {
                return response()->view('errors.404', [], 404);
            }
        }

        return parent::render($request, $e);
    }
}
