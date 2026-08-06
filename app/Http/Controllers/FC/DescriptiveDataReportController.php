<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcDescriptiveDataReportDataTable;
use App\Exports\FC\FcDescriptiveDataExport;
use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Services\FC\FcDescriptiveDataFieldResolver;
use App\Services\FC\FcDescriptiveDataQuery;
use App\Support\FC\FcUploadUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Descriptive Data report — the Descriptive Roll fields as a filterable table, with Excel
 * and PDF export.
 *
 * Its own controller rather than more methods on FC\ReportController, which is already ~2,800
 * lines; nothing here is shared with the other reports except the course-options helper,
 * which is re-declared below as a 6-line query rather than making the two controllers
 * inherit from each other.
 */
class DescriptiveDataReportController extends Controller
{
    /** Hard ceiling on a single PDF export. Beyond this the file is unusable anyway. */
    private const PDF_MAX_ROWS = 2000;

    /**
     * Ceiling on the .xlsx export.
     *
     * Not arbitrary: a 28-column sheet costs ~25 MB of PHP memory per 1,000 rows, because
     * PhpSpreadsheet holds every cell as an object until the file is written (measured
     * 507 MB at 20,000 rows). At 10,000 rows that is ~250 MB, which fits a typical 512 MB
     * limit with room for the rest of the request. Past this the CSV export is offered
     * instead — it streams and has no row limit at all.
     */
    private const XLSX_MAX_ROWS = 10000;

    public function index(Request $request)
    {
        $form = $this->resolveForm($request);
        $dataTable = new FcDescriptiveDataReportDataTable($form);

        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('fc.report.descriptive-data-index', [
            'form' => $form,
            'forms' => $this->courseOptions(),
            'fields' => $dataTable->fields(),
            'fieldsJson' => $this->fieldsForJson($dataTable->fields()),
            'filterOptions' => $form ? $this->filterOptions($form, $dataTable->fields()) : [],
        ]);
    }

    /**
     * Column + filter metadata for one course, as JSON.
     *
     * Exists so changing the course can rebuild the table in place. The column SET differs
     * per course (26 columns on one form, 11 on another), so the browser cannot just refetch
     * rows — it has to know the new header before it asks for data.
     */
    public function columns(Request $request)
    {
        $form = $this->resolveForm($request);
        if (! $form) {
            return response()->json(['fields' => [], 'filterOptions' => (object) []]);
        }

        $fields = app(FcDescriptiveDataFieldResolver::class)->forForm($form);

        return response()->json([
            'fields' => $this->fieldsForJson($fields),
            'filterOptions' => (object) $this->filterOptions($form, $fields),
        ]);
    }

    /**
     * @param  array<string,array<string,mixed>>  $fields
     * @return list<array<string,mixed>>
     */
    private function fieldsForJson(array $fields): array
    {
        $out = [];
        foreach ($fields as $key => $field) {
            $out[] = [
                'key' => $key,
                'label' => $field['label'],
                'type' => $field['type'],
                'filter' => $field['filter'] ?? null,
                // Expressions (concatenations, the assembled address, file paths) have no
                // single column to ORDER BY, so they are not sortable. Derived columns
                // (Service, Rank) declare their own sortability.
                'orderable' => $field['type'] === 'derived'
                    ? (bool) ($field['orderable'] ?? false)
                    : ! in_array($field['type'], ['concat', 'address', 'file'], true),
            ];
        }

        return $out;
    }

    /**
     * Serve one upload (photo / signature) from an opaque token.
     *
     * The token is the encrypted stored path — see FcUploadUrl. Nothing about the file is in
     * the URL, so there is no user id to read and no directory to walk, and a tampered token
     * fails to decrypt rather than resolving to a different file.
     *
     * Access is deliberately the same as before this route existed: the uploads were public
     * files under public/storage, readable by anyone with the URL. Making the link opaque was
     * the ask; requiring a login is a separate decision (add 'auth' to this route to do it).
     */
    public function file(Request $request)
    {
        $path = FcUploadUrl::decode($request->query(FcUploadUrl::TOKEN_PARAM));
        abort_if($path === null, 404);

        // Resolve through the codebase's own resolver, which knows every place an upload can
        // actually live (public disk, storage/app/public, a real public/storage directory,
        // public/ itself, storage/app).
        //
        // Wrapped: the resolver goes through Flysystem, which THROWS PathTraversalDetected on
        // a path like ../../../.env rather than returning null. Uncaught that is a 500 with a
        // stack trace; a bad token deserves the same flat 404 as a missing file.
        try {
            $full = fc_resolve_storage_file_path($path);
        } catch (\Throwable $e) {
            abort(404);
        }

        abort_if($full === null || ! is_file($full), 404);

        // Defence in depth: even though the token is encrypted and unforgeable without
        // APP_KEY, never serve anything that resolves outside the upload roots.
        abort_unless($this->isUnderUploadRoot($full), 404);

        $mime = @mime_content_type($full) ?: 'application/octet-stream';

        $response = response()->file($full, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($full).'"',
            // Never let a browser sniff a different type out of an uploaded file.
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // setPrivate() rather than a Cache-Control header: Symfony rebuilds that header when
        // it prepares a BinaryFileResponse and had been re-adding `public`, which is exactly
        // wrong for a passport photograph — it invites proxies to keep a copy.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('max-age', '0');

        return $response;
    }

    /** Is this resolved absolute path inside one of the directories uploads may live in? */
    private function isUnderUploadRoot(string $full): bool
    {
        $real = realpath($full);
        if ($real === false) {
            return false;
        }

        foreach ([
            storage_path('app/public'),
            public_path('storage'),
            public_path(),
            storage_path('app'),
        ] as $root) {
            $realRoot = realpath($root);
            if ($realRoot !== false && str_starts_with($real, $realRoot.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    public function exportExcel(Request $request)
    {
        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        $all = app(FcDescriptiveDataFieldResolver::class)->forForm($form);
        $fields = $this->visibleFields($all, $request);

        // Checked BEFORE building anything: an OOM halfway through gives the admin a blank
        // 500 page, while this gives them a working alternative.
        $rowCount = $this->countRows($form, $all, $request);
        if ($rowCount > self::XLSX_MAX_ROWS) {
            return back()->with('error', sprintf(
                'This selection has %s records, over the %s-row Excel limit. Use the CSV export, which has no limit, or narrow the filters.',
                number_format($rowCount),
                number_format(self::XLSX_MAX_ROWS)
            ));
        }

        $filename = 'Descriptive_Data_'.$this->slug($form->form_name).'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new FcDescriptiveDataExport($form, $fields, $request, $this->showsUsername($request, $all)),
            $filename
        );
    }

    /**
     * CSV export — the path for large result sets.
     *
     * Streams straight to the client from a DB cursor, so memory is flat regardless of row
     * count: no PhpSpreadsheet, no in-memory cell objects, no row cap. The trade-off is no
     * banner, styling or clickable links — a CSV cannot carry them. Opens in Excel.
     */
    public function exportCsv(Request $request)
    {
        @set_time_limit(0);

        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        $all = app(FcDescriptiveDataFieldResolver::class)->forForm($form);
        $selection = $this->selection($all, $request);
        $fields = $selection['fields'];
        $withUsername = $selection['username'];

        $service = app(FcDescriptiveDataQuery::class);
        $query = $service->build($form, $all);
        $service->applyFilters($query, $all, $request);
        $query->orderBy('s1.first_name');

        $filename = 'Descriptive_Data_'.$this->slug($form->form_name).'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query, $fields, $withUsername) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM: without it Excel reads the file as ANSI and the Devanagari values
            // (and any accented name) arrive as mojibake.
            fwrite($out, "\xEF\xBB\xBF");

            $headings = ['S.No.'];
            if ($withUsername) {
                $headings[] = 'Username';
            }
            foreach ($fields as $field) {
                $headings[] = $field['label'];
            }
            fputcsv($out, $headings);

            $serial = 0;
            foreach ($query->cursor() as $row) {
                $serial++;
                $line = [$serial];
                if ($withUsername) {
                    $line[] = (string) ($row->login_username ?? '');
                }

                foreach ($fields as $key => $field) {
                    $value = $row->{$key} ?? null;

                    if ($field['type'] === 'file') {
                        $line[] = FcUploadUrl::for($value);
                    } elseif ($field['type'] === 'date') {
                        $line[] = $this->formatDateValue($value);
                    } else {
                        $line[] = trim((string) $value);
                    }
                }

                fputcsv($out, $line);

                // Push each row out rather than letting PHP buffer the whole response.
                if ($serial % 500 === 0) {
                    flush();
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Accel-Buffering' => 'no',   // stop nginx buffering the whole file first
        ]);
    }

    private function formatDateValue($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000')) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Row count for the current filters. One COUNT, and only on the .xlsx path where the
     * answer decides whether the export can run at all.
     *
     * @param  array<string,array<string,mixed>>  $fields
     */
    private function countRows(FcForm $form, array $fields, Request $request): int
    {
        $service = app(FcDescriptiveDataQuery::class);
        $query = $service->build($form, $fields);
        $service->applyFilters($query, $fields, $request);

        return (int) $query->count();
    }

    /**
     * PDF export, rendered by the same mPDF path as the Descriptive Roll so a server without
     * headless Chrome produces the identical document.
     */
    public function exportPdf(Request $request)
    {
        @set_time_limit(0);

        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        $allFields = app(FcDescriptiveDataFieldResolver::class)->forForm($form);
        $fields = $this->visibleFields($allFields, $request);
        $service = app(FcDescriptiveDataQuery::class);
        $query = $service->build($form, $allFields);
        $service->applyFilters($query, $allFields, $request);

        // G3: cursor(), not get() — the rows are streamed into the row array one at a time
        // instead of materialising the whole result set first.
        $rows = [];
        $truncated = false;
        foreach ($query->orderBy('s1.first_name')->cursor() as $row) {
            if (count($rows) >= self::PDF_MAX_ROWS) {
                $truncated = true;
                break;
            }
            $rows[] = $row;
        }

        if ($rows === []) {
            return back()->with('error', 'No students match the current filters. Nothing to export.');
        }

        $html = view('fc.report.descriptive-data-pdf', [
            'form' => $form,
            'fields' => $fields,
            'rows' => $rows,
            'showUsername' => $this->showsUsername($request, $allFields),
            'truncated' => $truncated,
            'maxRows' => self::PDF_MAX_ROWS,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Landscape: ~25 columns will not fit portrait at a legible size.
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 8,
            'margin_right' => 8,
        ]);
        $mpdf->SetTitle('Descriptive Data - '.$form->form_name);
        $mpdf->WriteHTML($html);

        $filename = 'Descriptive_Data_'.$this->slug($form->form_name).'_'.now()->format('Ymd_His').'.pdf';

        return response((string) $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Narrow the resolved columns to the ones still ticked in the report's Columns menu.
     *
     * The screen sends `cols` as a comma-separated list of column keys. Anything unknown is
     * dropped rather than trusted, and an absent/empty list means "everything" — so an export
     * link built before this existed, or hit directly, still returns the full report.
     *
     * @param  array<string,array<string,mixed>>  $fields
     * @return array<string,array<string,mixed>>
     */
    private function visibleFields(array $fields, Request $request): array
    {
        return $this->selection($fields, $request)['fields'];
    }

    private function showsUsername(Request $request, array $fields = []): bool
    {
        return $this->selection($fields, $request)['username'];
    }

    /**
     * Resolve `cols` into the field subset AND whether Username survives, together — the two
     * answers have to agree. Deciding them separately meant an unrecognised `cols` value fell
     * back to every field but still dropped Username, which is a shape the screen can never
     * produce.
     *
     * @param  array<string,array<string,mixed>>  $fields
     * @return array{fields: array<string,array<string,mixed>>, username: bool}
     */
    private function selection(array $fields, Request $request): array
    {
        $requested = $this->requestedColumns($request);
        if ($requested === null) {
            return ['fields' => $fields, 'username' => true];
        }

        // array_intersect_key keeps the DEFINITION order, not the order the browser happened
        // to send, so the export columns always read in the report's own sequence.
        $visible = array_intersect_key($fields, array_flip($requested));
        $wantsUsername = in_array('login_username', $requested, true);

        // Nothing recognised at all (a hand-edited or stale link): fall back to the whole
        // report rather than emit a sheet of nothing but S.No.
        if ($visible === [] && ! $wantsUsername) {
            return ['fields' => $fields, 'username' => true];
        }

        return ['fields' => $visible, 'username' => $wantsUsername];
    }

    /**
     * @return list<string>|null  null when the caller did not specify any columns
     */
    private function requestedColumns(Request $request): ?array
    {
        $raw = trim((string) $request->input('cols', ''));
        if ($raw === '') {
            return null;
        }

        $keys = array_filter(array_map('trim', explode(',', $raw)), fn ($k) => $k !== '');

        return $keys === [] ? null : array_values($keys);
    }

    private function resolveForm(Request $request): ?FcForm
    {
        $id = (int) $request->input('form_id', 0);

        return $id > 0 ? FcForm::find($id) : null;
    }

    /**
     * Distinct values for the select filters, read in ONE grouped query per column rather
     * than a query per row (G5). Only the columns that actually carry a select filter are
     * touched, and lookup filters read their id/name pairs from the lookup table itself.
     *
     * @param  array<string,array<string,mixed>>  $fields
     * @return array<string,array<int|string,string>>
     */
    private function filterOptions(FcForm $form, array $fields): array
    {
        // Cached: these are ~8 scoped DISTINCT reads that would otherwise repeat on every
        // full page load while returning the same handful of values. The key runs through
        // FcDescriptiveDataFieldResolver::cacheKey(), so the form builder's invalidation
        // drops this alongside the column resolution instead of leaving it stale for the TTL.
        $key = FcDescriptiveDataFieldResolver::cacheKey(
            'filters',
            (int) $form->id,
            substr(md5(implode(',', array_keys($fields))), 0, 8)
        );

        try {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Cache store unavailable — build inline.
        }

        $options = $this->buildFilterOptions($form, $fields);

        try {
            Cache::put($key, $options, now()->addMinutes(30));
        } catch (\Throwable $e) {
            // Not worth failing the page over.
        }

        return $options;
    }

    /**
     * Dropdown values for the filterable columns, scoped to the selected course.
     *
     * One query per filterable column (~6-9 for a full form), each a DISTINCT over the
     * course's own trainees. That is a loop issuing queries (G5), but bounded by the number of
     * COLUMNS, not rows — it does not grow with the roster — and the whole result is cached.
     * The joins are built once and cloned, so the per-column cost is the DISTINCT alone.
     *
     * @param  array<string,array<string,mixed>>  $fields
     * @return array<string,list<array{value: string, label: string}>>
     */
    private function buildFilterOptions(FcForm $form, array $fields): array
    {
        $service = app(FcDescriptiveDataQuery::class);
        $base = $service->scopedBase($form);

        $options = [];

        foreach ($fields as $key => $field) {
            $filter = $field['filter'] ?? null;

            // Every filter type needs options EXCEPT date_range, which renders two date
            // inputs. Listing the types that do need them meant a newly added type (service)
            // was silently skipped and its dropdown rendered empty.
            if ($filter === null || $filter === 'date_range') {
                continue;
            }

            $options[$key] = $service->distinctFilterValues($base, $field);
        }

        return $options;
    }

    /**
     * Active courses for the picker. Explicit column list (G1); no pagination needed because
     * this is a short dropdown, not a listing screen.
     */
    private function courseOptions()
    {
        return DB::table('fc_forms')
            ->leftJoin('course_master as cm', 'fc_forms.course_master_pk', '=', 'cm.pk')
            ->where('fc_forms.is_active', 1)
            ->orderBy('fc_forms.form_name')
            ->get(['fc_forms.id', 'fc_forms.form_name', 'cm.course_name']);
    }

    private function slug(?string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', (string) $value);

        return trim((string) $slug, '_') ?: 'course';
    }
}
