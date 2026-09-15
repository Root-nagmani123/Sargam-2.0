<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

/**
 * Holiday Master — the admin end for the holidays that the dashboard calendar
 * (and the Calendar Creation / OT calendars) read from the `holidays` table.
 */
class HolidayMasterController extends Controller
{
    public const TYPES = [
        'gazetted' => 'Gazetted Holiday',
        'restricted' => 'Restricted Holiday',
        'optional' => 'Optional Holiday',
    ];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('admin.holiday_master.index', [
            'types' => self::TYPES,
            'years' => $this->availableYears(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateHoliday($request);

        $holiday = new Holiday();
        $this->fillHoliday($holiday, $validated);
        $holiday->save();

        return $this->savedResponse($request, 'Holiday added successfully.');
    }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($this->resolveId($id));

        $validated = $this->validateHoliday($request, $holiday);
        $this->fillHoliday($holiday, $validated);
        $holiday->save();

        return $this->savedResponse($request, 'Holiday updated successfully.');
    }

    /**
     * Add / Edit are modals on the index page, so a save is an XHR: answer it with
     * JSON and let the grid redraw. A non-XHR post still redirects, so the endpoints
     * stay usable without JavaScript.
     */
    protected function savedResponse(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->route('admin.holiday-master.index')
            ->with('success', $message);
    }

    public function status(Request $request, $id)
    {
        $request->validate(['active_inactive' => 'required|in:0,1']);

        $holiday = Holiday::findOrFail($this->resolveId($id));
        $holiday->active_inactive = (int) $request->input('active_inactive');
        $holiday->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($this->resolveId($id));

        if ((int) $holiday->active_inactive === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only inactive holidays can be deleted. Please inactivate it first.',
            ], 422);
        }

        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->baseListQuery($request);

        if ($request->filled('search')) {
            $this->applySearch($query, (string) $request->input('search'));
        }

        $rows = $query->get();

        $columns = ['S. No.', 'Holiday Name', 'Date', 'Day', 'Holiday Type', 'Year', 'Description', 'Status'];
        $filename = 'Holiday_Master_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $serial = 1;
            foreach ($rows as $row) {
                fputcsv($out, [
                    $serial++,
                    $row->holiday_name,
                    $row->holiday_date ? $row->holiday_date->format('d-m-Y') : '',
                    $row->holiday_date ? $row->holiday_date->format('l') : '',
                    self::TYPES[$row->holiday_type] ?? $row->holiday_type,
                    $row->year,
                    $row->description,
                    (int) $row->active_inactive === 1 ? 'Active' : 'Inactive',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function datatable(Request $request)
    {
        $query = $this->baseListQuery($request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                if (! empty($request->search['value'])) {
                    $this->applySearch($query, $request->search['value']);
                }
            })
            ->addColumn('holiday_date_display', function ($row) {
                return $row->holiday_date ? $row->holiday_date->format('d-m-Y') : 'N/A';
            })
            ->addColumn('holiday_day', function ($row) {
                return $row->holiday_date ? $row->holiday_date->format('l') : 'N/A';
            })
            ->addColumn('holiday_type_display', function ($row) {
                $label = self::TYPES[$row->holiday_type] ?? ucfirst((string) $row->holiday_type);

                return '<span class="hm-type hm-type--' . e($row->holiday_type) . '">'
                    . e($label) . '</span>';
            })
            ->addColumn('description_display', function ($row) {
                // Not a rawColumn — Yajra escapes it for us, so don't e() it twice.
                return $row->description ?: '—';
            })
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                // Soft badge, display only; the control lives in the Action column.
                return '<span class="hm-status-pill badge rounded-1 '
                    . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($isActive ? 'Active' : 'Inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $id = (int) $row->id;
                $isActive = (int) $row->active_inactive === 1;

                // Edit opens the modal on this page — the row's values ride along on the
                // button so there is no second round trip. This is a rawColumn, so every
                // attribute value has to be escaped here.
                $edit = '<button type="button" class="hm-act hm-act--edit hm-edit-btn" title="Edit"'
                    . ' data-id="' . $id . '"'
                    . ' data-name="' . e($row->holiday_name) . '"'
                    . ' data-date="' . e($row->holiday_date ? $row->holiday_date->format('Y-m-d') : '') . '"'
                    . ' data-type="' . e($row->holiday_type) . '"'
                    . ' data-description="' . e((string) $row->description) . '"'
                    . ' data-status="' . ($isActive ? '1' : '0') . '">'
                    . '<span class="hm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="hm-act__label">Edit</span></button>';

                // No .form-check/.form-switch wrapper — custom.css:108 pulls the input
                // -2.375rem left inside one, collapsing this icon strip to 0px.
                // The caption names the ACTION; the badge one column over shows the state.
                $toggleWord = $isActive ? 'Deactivate' : 'Activate';
                $toggle = '<label class="hm-act hm-act--toggle">'
                    . '<span class="hm-act__icon">'
                    . '<input class="form-check-input plain-status-toggle hm-status-toggle" type="checkbox" role="switch" '
                    . 'data-id="' . $id . '"' . ($isActive ? ' checked' : '')
                    . ' aria-label="' . $toggleWord . ' this holiday"></span>'
                    . '<span class="hm-act__label">' . $toggleWord . '</span></label>';

                // destroy() refuses an active row, so mirror that rule here rather than
                // shipping a red icon that always fails.
                if ($isActive) {
                    $delete = '<span class="hm-act hm-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate this holiday before deleting it">'
                        . '<span class="hm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="hm-act__label">Delete</span></span>';
                } else {
                    $delete = '<button type="button" class="hm-act hm-act--del hm-delete-btn" data-id="' . $id . '" title="Delete">'
                        . '<span class="hm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="hm-act__label">Delete</span></button>';
                }

                return '<div class="hm-act-group" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete . '</div>';
            })
            ->rawColumns(['holiday_type_display', 'status', 'action'])
            ->make(true);
    }

    protected function baseListQuery(Request $request)
    {
        $query = Holiday::query()->orderBy('holiday_date', 'desc');

        $statusFilter = (string) $request->input('status_filter', 'all');
        if ($statusFilter === 'active') {
            $query->where('active_inactive', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('active_inactive', 0);
        }

        if ($request->filled('year_filter')) {
            $query->where('year', (int) $request->input('year_filter'));
        }

        if ($request->filled('type_filter')) {
            $query->where('holiday_type', $request->input('type_filter'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('holiday_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('holiday_date', '<=', $request->input('to_date'));
        }

        return $query;
    }

    protected function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('holiday_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('holiday_type', 'like', "%{$search}%");
        });
    }

    protected function validateHoliday(Request $request, ?Holiday $holiday = null): array
    {
        $uniqueName = Rule::unique('holidays', 'holiday_name')
            ->where(function ($q) use ($request) {
                return $q->where('holiday_date', $request->input('holiday_date'));
            });

        if ($holiday) {
            $uniqueName->ignore($holiday->id);
        }

        $validator = Validator::make($request->all(), [
            'holiday_name' => ['required', 'string', 'max:255', $uniqueName],
            'holiday_date' => ['required', 'date'],
            'holiday_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'description' => ['nullable', 'string', 'max:500'],
            'active_inactive' => ['nullable', 'in:0,1'],
        ], [
            'holiday_name.unique' => 'This holiday is already recorded for the selected date.',
            'holiday_type.in' => 'Please choose a valid holiday type.',
        ]);

        if ($validator->fails()) {
            // The Add/Edit modals render these messages inline, so an XHR must get
            // the error map back as JSON. $request->validate() would decide that via
            // expectsJson(), which is false unless the Accept header happens to be
            // */* — it then redirects instead, and the modal sees a 302 it cannot
            // read. Decide it here on ajax() so the behaviour can't hinge on a
            // header we don't control.
            if ($request->ajax() || $request->wantsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors(),
                ], 422));
            }

            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function fillHoliday(Holiday $holiday, array $validated): void
    {
        $date = \Carbon\Carbon::parse($validated['holiday_date']);

        $holiday->holiday_name = $validated['holiday_name'];
        $holiday->holiday_date = $date->toDateString();
        $holiday->holiday_type = $validated['holiday_type'];
        $holiday->description = $validated['description'] ?? null;
        // `year` is derived from the date so the calendar's year filters stay in sync.
        $holiday->year = $date->year;
        $holiday->active_inactive = (int) ($validated['active_inactive'] ?? 1);
    }

    /**
     * Ids travel plain in the URL path — an encrypt()ed id is base64 and its
     * "/" would have to be sent as %2F, which Apache rejects by default.
     */
    protected function resolveId($id): int
    {
        if (! is_numeric($id)) {
            abort(404);
        }

        return (int) $id;
    }

    protected function availableYears(): array
    {
        $years = Holiday::query()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(function ($y) {
                return (int) $y;
            })
            ->all();

        foreach ([now()->year, now()->year + 1] as $y) {
            if (! in_array($y, $years, true)) {
                $years[] = $y;
            }
        }

        rsort($years);

        return $years;
    }
}
