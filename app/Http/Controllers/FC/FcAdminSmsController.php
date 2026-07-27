<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Services\FC\FcAdminSmsBulkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Admin page: choose B1/B2 template → bulk SMS+Email (chunked send, DataTables lists).
 */
class FcAdminSmsController extends Controller
{
    public function index(Request $request, FcAdminSmsBulkService $bulk): View
    {
        $counts = $bulk->previewCounts();

        return view('admin.fc-sms.index', [
            'preview' => [
                'programme' => $counts['programme'],
                'last_date' => $counts['last_date'],
            ],
            'templates' => [
                FcAdminSmsBulkService::TEMPLATE_B1 => [
                    'label' => 'Form step incomplete',
                    'code' => 'B1 / FC-IFM',
                    'help' => 'Started submitting the form (at least 1 step done) but still has pending steps — SMS uses their first pending step name.',
                    'count' => $counts['b1'],
                ],
                FcAdminSmsBulkService::TEMPLATE_B2 => [
                    'label' => 'Registration pending',
                    'code' => 'B2 / FC-R-P',
                    'help' => 'Registration not completed and form not started yet (or zero steps done) — overall registration deadline reminder.',
                    'count' => $counts['b2'],
                ],
            ],
            'openList' => in_array($request->query('open'), ['b1', 'b2'], true)
                ? $request->query('open')
                : null,
        ]);
    }

    public function recipients(Request $request, FcAdminSmsBulkService $bulk): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|in:b1,b2',
        ]);

        $template = $validated['template'];
        $isB1 = $template === FcAdminSmsBulkService::TEMPLATE_B1;
        $rows = $bulk->allRecipientsForTemplate($template);
        $total = count($rows);

        $dt = DataTables::of(collect($rows))
            ->addIndexColumn()
            ->addColumn('select', function (array $row) use ($template) {
                return '<input type="checkbox" class="form-check-input fc-sms-recipient-pick" '
                    .'value="'.(int) $row['pk'].'" data-template="'.e($template).'" '
                    .'aria-label="Select trainee">';
            })
            ->editColumn('name', fn (array $row) => e($row['name'] !== '' ? $row['name'] : '—'))
            ->editColumn('user_id', fn (array $row) => '<code class="small">'.e($row['user_id'] !== '' ? $row['user_id'] : '—').'</code>')
            ->editColumn('mobile', fn (array $row) => e($row['mobile']))
            ->filter(function ($query) use ($request) {
                $keyword = strtolower(trim((string) $request->input('search.value', '')));
                if ($keyword === '') {
                    return;
                }

                $query->collection = $query->collection->filter(function (array $row) use ($keyword) {
                    foreach (['name', 'user_id', 'mobile', 'step_name'] as $field) {
                        if (str_contains(strtolower((string) ($row[$field] ?? '')), $keyword)) {
                            return true;
                        }
                    }

                    return false;
                });
            })
            ->rawColumns(['select', 'user_id']);

        if ($isB1) {
            $dt->editColumn('step_name', function (array $row) {
                $step = trim((string) ($row['step_name'] ?? ''));

                return $step !== ''
                    ? '<span class="badge bg-warning text-dark">'.e($step).'</span>'
                    : '—';
            })->rawColumns(['select', 'user_id', 'step_name']);
        }

        return $dt
            ->setTotalRecords($total)
            ->setFilteredRecords($total)
            ->make(true);
    }

    public function send(Request $request, FcAdminSmsBulkService $bulk): RedirectResponse
    {
        $validated = $request->validate([
            'template' => 'required|in:b1,b2',
            'send_mode' => 'required|in:all,selected',
            'registration_pks' => 'required_if:send_mode,selected|array|min:1',
            'registration_pks.*' => 'integer|min:1',
        ], [
            'registration_pks.required_if' => 'Select at least one trainee from the list.',
            'registration_pks.min' => 'Select at least one trainee from the list.',
        ]);

        $pks = ($validated['send_mode'] ?? 'all') === 'selected'
            ? array_values(array_unique(array_map('intval', $validated['registration_pks'] ?? [])))
            : null;

        $result = $bulk->send($validated['template'], $pks);

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
