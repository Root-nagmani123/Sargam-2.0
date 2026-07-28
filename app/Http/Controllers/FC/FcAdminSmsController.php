<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Services\FC\FcAdminSmsBulkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * Admin page: choose B1/B2 template → bulk SMS+Email (chunked send, DataTables lists).
 */
class FcAdminSmsController extends Controller
{
    public function index(Request $request, FcAdminSmsBulkService $bulk): View
    {
        $forms = FcForm::query()
            ->where('is_active', true)
            ->orderByRaw('LOWER(form_name)')
            ->get(['id', 'form_name', 'form_slug']);

        $defaultForm = FcForm::activeRegistrationDynamicForm();
        $selectedFormId = (int) $request->query('form_id', $defaultForm?->id ?? 0);
        if ($selectedFormId <= 0 || ! $forms->pluck('id')->contains($selectedFormId)) {
            $selectedFormId = (int) ($defaultForm?->id ?? ($forms->first()->id ?? 0));
        }

        $selectedForm = $forms->firstWhere('id', $selectedFormId);
        $counts = $bulk->previewCounts($selectedFormId > 0 ? $selectedFormId : null);

        return view('admin.fc-sms.index', [
            'preview' => [
                'form_name' => $selectedForm?->form_name ?? $counts['programme'],
                'form_slug' => $selectedForm?->form_slug ?? '',
                'last_date' => $counts['last_date'],
            ],
            'forms' => $forms,
            'selectedFormId' => $selectedFormId,
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
            'form_id' => ['required', 'integer', Rule::exists('fc_forms', 'id')->where('is_active', true)],
        ]);

        $template = $validated['template'];
        $isB1 = $template === FcAdminSmsBulkService::TEMPLATE_B1;
        $rows = $bulk->allRecipientsForTemplate($template, (int) $validated['form_id']);
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
            'form_id' => ['required', 'integer', Rule::exists('fc_forms', 'id')->where('is_active', true)],
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

        $result = $bulk->send($validated['template'], $pks, (int) $validated['form_id']);

        $flash = $result['ok'] ? 'success' : 'error';
        $detail = $result['message'];
        if (($result['failed'] ?? 0) > 0) {
            $detail .= ' Failed: '.$result['failed'].'.';
        }

        return redirect()
            ->route('fc-reg.admin.sms.index', array_filter([
                'form_id' => (int) $validated['form_id'],
                'menu' => $request->input('menu') ?? $request->query('menu'),
            ]))
            ->with($flash, $detail);
    }
}
