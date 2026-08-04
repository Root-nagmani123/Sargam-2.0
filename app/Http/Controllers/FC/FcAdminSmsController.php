<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Services\FC\FcAdminSmsBulkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
                FcAdminSmsBulkService::TEMPLATE_B3 => [
                    'label' => 'Travel pending',
                    'code' => 'B3 / Email only',
                    'help' => 'All registration form steps are complete but the travel plan has not been submitted yet — email reminder only (no SMS template approved yet).',
                    'count' => $counts['b3'],
                ],
            ],
            'openList' => in_array($request->query('open'), ['b1', 'b2', 'b3'], true)
                ? $request->query('open')
                : null,
        ]);
    }

    public function recipients(Request $request, FcAdminSmsBulkService $bulk): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|in:b1,b2,b3',
            'form_id' => ['required', 'integer', Rule::exists('fc_forms', 'id')->where('is_active', true)],
        ]);

        $template = $validated['template'];
        $isB1 = $template === FcAdminSmsBulkService::TEMPLATE_B1;
        $formId = (int) $validated['form_id'];
        $keyword = trim((string) $request->input('search.value', ''));

        // Fast path: no search box text typed — page the underlying scan directly
        // instead of collecting every matching row just to slice out 25 of them.
        // Search still needs the full (classified) list to filter across all fields,
        // so it keeps using the original full-scan + in-memory filter below.
        if ($keyword === '') {
            $start = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 25);
            $length = $length > 0 ? $length : 25;

            $total = $bulk->countRecipientsForTemplate($template, $formId);
            $rows = $bulk->recipientsPage($template, $start, $length, $formId);

            $data = [];
            foreach ($rows as $i => $row) {
                $entry = [
                    'select' => '<input type="checkbox" class="form-check-input fc-sms-recipient-pick" '
                        .'value="'.(int) $row['pk'].'" data-template="'.e($template).'" '
                        .'aria-label="Select trainee">',
                    'DT_RowIndex' => $start + $i + 1,
                    'name' => e($row['name'] !== '' ? $row['name'] : '—'),
                    'user_id' => '<code class="small">'.e($row['user_id'] !== '' ? $row['user_id'] : '—').'</code>',
                    'mobile' => e($row['mobile']),
                ];
                if ($isB1) {
                    $step = trim((string) ($row['step_name'] ?? ''));
                    $entry['step_name'] = $step !== ''
                        ? '<span class="badge bg-warning text-dark">'.e($step).'</span>'
                        : '—';
                }
                $data[] = $entry;
            }

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data,
            ]);
        }

        $rows = $bulk->allRecipientsForTemplate($template, $formId);
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

    /**
     * How long a "send in progress" lock is held for, as a hard ceiling — the
     * background command itself releases the lock as soon as it finishes (success
     * or failure), so this only matters if the process is killed/crashes in a way
     * that skips its own cleanup. Generous enough to cover a large "send to all"
     * cohort without expiring mid-send.
     */
    private const SEND_LOCK_TTL_SECONDS = 3600;

    public function send(Request $request, FcAdminSmsBulkService $bulk): RedirectResponse
    {
        $validated = $request->validate([
            'template' => 'required|in:b1,b2,b3',
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

        $formId = (int) $validated['form_id'];
        $label = match ($validated['template']) {
            FcAdminSmsBulkService::TEMPLATE_B1 => 'Form step incomplete',
            FcAdminSmsBulkService::TEMPLATE_B3 => 'Travel pending',
            default => 'Registration pending',
        };

        // Guards against double-sends from a double-click, a resubmitted/refreshed
        // form, or two tabs — without this, each request spawns its own background
        // process with no idea another one is already working through the same
        // template+form cohort, so the same recipients could get SMS+email twice.
        $lockKey = $this->sendLockKey($validated['template'], $formId);
        $lock = Cache::lock($lockKey, self::SEND_LOCK_TTL_SECONDS);

        if (! $lock->get()) {
            return redirect()
                ->route('fc-reg.admin.sms.index', array_filter([
                    'form_id' => $formId,
                    'menu' => $request->input('menu') ?? $request->query('menu'),
                ]))
                ->with('error', "{$label} is already being sent for this template — please wait for it to finish before sending again.");
        }

        // Each recipient is a live SMS-gateway call plus a live SMTP send, so doing this
        // inline blocked the page for as long as the whole cohort took to work through.
        // Spawned as a detached one-shot background process instead — no queue worker
        // to keep running, the browser just doesn't wait for it. The background command
        // releases $lockKey itself when it finishes (see FcAdminSmsSendCommand).
        $started = $this->spawnBackgroundSend($validated['template'], $formId, $pks, $lockKey);

        if (! $started) {
            $lock->release();
        }

        $detail = $started
            ? ($pks !== null
                ? "{$label} sending to ".count($pks)." selected trainee(s) in the background. Check back shortly."
                : "{$label} sending to all matching trainees in the background. Check back shortly.")
            : 'Could not start the background send. Check the server logs.';

        return redirect()
            ->route('fc-reg.admin.sms.index', array_filter([
                'form_id' => $formId,
                'menu' => $request->input('menu') ?? $request->query('menu'),
            ]))
            ->with($started ? 'success' : 'error', $detail);
    }

    private function sendLockKey(string $template, int $formId): string
    {
        return "fc-admin-sms-send-lock:{$formId}:{$template}";
    }

    /**
     * Launch `php artisan fc:admin-sms-send ...` as a detached process and return
     * immediately without waiting for it — no persistent queue worker required.
     * $lockKey is passed through so the background command can release it itself
     * once the send finishes (success or failure) — see FcAdminSmsSendCommand.
     *
     * @param  list<int>|null  $pks
     */
    private function spawnBackgroundSend(string $template, int $formId, ?array $pks, string $lockKey): bool
    {
        $php = $this->resolvePhpCliBinary();
        if ($php === null) {
            Log::error('FC admin bulk send: could not locate a PHP CLI binary to spawn the background send.');

            return false;
        }

        $artisan = base_path('artisan');

        $args = [
            $php,
            $artisan,
            'fc:admin-sms-send',
            '--template='.$template,
            '--form-id='.$formId,
            '--lock-key='.$lockKey,
        ];
        if ($pks !== null && $pks !== []) {
            $args[] = '--pks='.implode(',', $pks);
        }

        $cmd = implode(' ', array_map(
            fn ($a) => '"'.str_replace('"', '\\"', $a).'"',
            $args
        ));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', storage_path('logs/fc-admin-sms-bg.log'), 'a'],
            2 => ['file', storage_path('logs/fc-admin-sms-bg.log'), 'a'],
        ];

        // Windows: "start /B" detaches the child from this request's process tree
        // so it keeps running after we close the pipes and return the redirect.
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $fullCmd = $isWindows ? 'start "" /B '.$cmd : $cmd.' > /dev/null 2>&1 &';

        $process = @proc_open($fullCmd, $descriptors, $pipes, base_path(), null, ['bypass_shell' => false]);

        if (! is_resource($process)) {
            Log::error('FC admin bulk send: failed to spawn background process.', [
                'template' => $template,
                'form_id' => $formId,
            ]);

            return false;
        }

        fclose($pipes[0]);
        proc_close($process);

        return true;
    }

    /**
     * PHP_BINARY is only a real CLI executable path when the request itself runs
     * under the CLI/php-server SAPI. Under Apache's mod_php (apache2handler), it
     * resolves to httpd.exe instead — using that to spawn `php artisan ...` would
     * silently fail. Prefer explicit config, then check next to the running SAPI's
     * own module for a sibling php.exe, then fall back to PATH.
     */
    private function resolvePhpCliBinary(): ?string
    {
        $configured = config('services.php_cli_binary') ?: env('PHP_CLI_BINARY');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
            if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_file(PHP_BINARY)) {
                return PHP_BINARY;
            }
        }

        // Apache (mod_php) etc.: PHP_BINARY points at httpd.exe, not php.exe — instead
        // look for a php.exe/php-cgi.exe next to the loaded php ini, which sits in the
        // same PHP install directory as the CLI binary on a standard XAMPP layout.
        $candidateDirs = array_unique(array_filter([
            dirname(php_ini_loaded_file() ?: ''),
            PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\php' : null,
            PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp1\\php' : null,
        ]));

        $binaryName = PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php';
        foreach ($candidateDirs as $dir) {
            $candidate = rtrim($dir, '\\/').DIRECTORY_SEPARATOR.$binaryName;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Last resort: rely on PATH.
        return $binaryName === 'php.exe' ? 'php.exe' : 'php';
    }
}
