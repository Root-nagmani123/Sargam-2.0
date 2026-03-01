<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    /**
     * Display application log viewer (requires auth).
     */
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $content = 'Log file not found or empty.';
        if (File::exists($logPath)) {
            $content = File::get($logPath);
            if (strlen($content) > 1024 * 512) {
                $content = '[truncated...]' . substr($content, -1024 * 512);
            }
        }

        return response('<pre style="white-space:pre-wrap;font-size:12px;">' . e($content) . '</pre>')
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
