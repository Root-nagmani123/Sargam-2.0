<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use Illuminate\View\View;

class FcActivityStatusController extends Controller
{
    private function singleActivityStatus(string $activityCode): array
    {
        $ots = FcOtDetail::active()->orderBy('otcode')->get();
        $done = FcOtActivity::where('activity', $activityCode)->pluck('activityval', 'username')->toArray();

        $rows = $ots->map(fn ($ot) => [
            'otname' => $ot->otname,
            'otcode' => $ot->otcode,
            'done' => isset($done[$ot->username]),
        ]);

        return [
            'rows' => $rows,
            'count' => count($done),
            'total' => $ots->count(),
        ];
    }

    public function admin(): View
    {
        return view('admin.fc-activities.status.admin', $this->singleActivityStatus('joined'));
    }

    public function security(): View
    {
        return view('admin.fc-activities.status.security', $this->singleActivityStatus('idcard'));
    }

    public function it(): View
    {
        return view('admin.fc-activities.status.it', $this->singleActivityStatus('biometric'));
    }

    public function training(): View
    {
        return view('admin.fc-activities.status.training', $this->singleActivityStatus('trgind'));
    }

    public function shop(): View
    {
        return view('admin.fc-activities.status.shop', $this->singleActivityStatus('souvenir'));
    }

    public function medical(): View
    {
        $codes = ['height', 'weight', 'pulse', 'bp', 'vialtube', 'bloodsample'];
        $ots = FcOtDetail::active()->orderBy('otcode')->get();
        $actMap = FcOtActivity::whereIn('activity', $codes)->get()->groupBy('username')->map(
            fn ($acts) => $acts->pluck('activityval', 'activity')
        );

        $rows = $ots->map(function ($ot) use ($actMap, $codes) {
            $vals = $actMap[$ot->username] ?? collect();
            return [
                'otname' => $ot->otname,
                'otcode' => $ot->otcode,
                'activities' => array_combine($codes, array_map(fn ($c) => $vals[$c] ?? null, $codes)),
            ];
        });

        return view('admin.fc-activities.status.medical', compact('rows'));
    }

    public function all(): View
    {
        $codes = ['joined', 'idcard', 'biometric', 'trgind', 'souvenir', 'height'];
        $ots = FcOtDetail::active()->orderBy('otcode')->get();
        $actMap = FcOtActivity::whereIn('activity', $codes)->get()->groupBy('username')->map(
            fn ($acts) => $acts->pluck('activityval', 'activity')
        );

        $rows = $ots->map(function ($ot) use ($actMap, $codes) {
            $vals = $actMap[$ot->username] ?? collect();
            return [
                'otname' => $ot->otname,
                'otcode' => $ot->otcode,
                'mobileno' => $ot->mobileno,
                'service' => $ot->service,
                'activities' => array_combine($codes, array_map(fn ($c) => $vals[$c] ?? null, $codes)),
            ];
        });

        return view('admin.fc-activities.status.all', compact('rows'));
    }
}
