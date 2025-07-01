<?php
function view_file_link($path) {
    return $path ? asset('storage/' . $path) : null;
}

function format_date($date, $format = 'd-m-Y') {
    return \Carbon\Carbon::parse($date)->format($format);
}

function createDirectory($path)
{
    $directory = public_path('storage/'. $path);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

function safeDecrypt($value, $default = null)
{
    try {
        return decrypt($value);
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        return $default;
    }
}