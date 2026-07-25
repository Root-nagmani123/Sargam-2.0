<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Services\FC\FcAdminSmsBulkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin page: choose B1/B2 SMS template → bulk send to incomplete trainees (no user picker).
 */
class FcAdminSmsController extends Controller
{
    public function index(FcAdminSmsBulkService $bulk): View
    {
        $payload = $bulk->previewPayload();

        return view('admin.fc-sms.index', [
            'preview' => [
                'programme' => $payload['programme'],
                'last_date' => $payload['last_date'],
            ],
            'lists' => $payload['lists'],
            'templates' => [
                FcAdminSmsBulkService::TEMPLATE_B1 => [
                    'label' => 'Form step incomplete',
                    'code' => 'B1 / FC-IFM',
                    'help' => 'Started the form (at least 1 step done) but still has pending steps — SMS uses their first pending step name.',
                    'count' => $payload['b1'],
                ],
                FcAdminSmsBulkService::TEMPLATE_B2 => [
                    'label' => 'Registration pending',
                    'code' => 'B2 / FC-R-P',
                    'help' => 'Has login but has not completed any form step yet — overall registration deadline reminder.',
                    'count' => $payload['b2'],
                ],
            ],
        ]);
    }

    public function send(Request $request, FcAdminSmsBulkService $bulk): RedirectResponse
    {
        $validated = $request->validate([
            'template' => 'required|in:b1,b2',
        ]);

        $result = $bulk->send($validated['template']);

        $flash = $result['ok'] ? 'success' : 'error';
        $detail = $result['message'];
        if (($result['failed'] ?? 0) > 0) {
            $detail .= ' Failed: '.$result['failed'].'.';
        }

        return redirect()
            ->route('fc-reg.admin.sms.index')
            ->with($flash, $detail);
    }
}
