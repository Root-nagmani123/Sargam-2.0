<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function index()
    {
        $date = request('date', now()->format('Y-m-d'));
        $path = storage_path("logs/laravel-$date.log");

        if (!File::exists($path)) {
            return "Log file not found for $date";
        }

        return response(
            "<pre style='background:#111;color:#0f0;padding:15px;'>"
            . e(File::get($path)) .
            "</pre>"
        );
    }
}
