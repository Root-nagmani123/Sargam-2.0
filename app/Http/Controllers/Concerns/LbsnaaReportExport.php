<?php

namespace App\Http\Controllers\Concerns;

trait LbsnaaReportExport
{
    protected function lbsnaaExportEmblemDataUri(): string
    {
        foreach ([public_path('images/ashoka.png'), public_path('images/lbsnaa_logo.png')] as $path) {
            if (is_file($path) && is_readable($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false) {
                    $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

                    return 'data:'.$mime.';base64,'.base64_encode($raw);
                }
            }
        }

        $url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->connectTimeout(6)->get($url);
            if ($response->successful() && strlen($response->body()) > 100) {
                return 'data:image/png;base64,'.base64_encode($response->body());
            }
        } catch (\Throwable $e) {
        }

        return $url;
    }

    protected function lbsnaaExportLogoDataUri(): string
    {
        foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $path) {
            if (is_file($path) && is_readable($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false) {
                    $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

                    return 'data:'.$mime.';base64,'.base64_encode($raw);
                }
            }
        }
        foreach ([public_path('admin_assets/images/logos/logo.png'), public_path('admin_assets/images/logos/logo.svg')] as $localPath) {
            if (is_file($localPath) && is_readable($localPath)) {
                $raw = @file_get_contents($localPath);
                if ($raw !== false) {
                    $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'svg' => 'image/svg+xml',
                        'png' => 'image/png',
                        default => 'image/jpeg',
                    };

                    return 'data:'.$mime.';base64,'.base64_encode($raw);
                }
            }
        }

        return '';
    }
}
