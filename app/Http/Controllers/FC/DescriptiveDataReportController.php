<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcDescriptiveDataReportDataTable;
use App\Exports\FC\FcDescriptiveDataExport;
use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Services\FC\FcDescriptiveDataChildLoader;
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

    /**
     * Rows held in memory at once on the CSV path.
     *
     * The repeating sections (Educational Details, Languages Known, ...) are loaded per batch
     * rather than per row, so the cursor is drained in chunks this size — one extra query per
     * child table per chunk, against one per row.
     */
    private const CSV_CHUNK_ROWS = 500;

    /** Trainees read per batch when building the photo archive. */
    private const PHOTO_CHUNK_ROWS = 500;

    /**
     * Ceiling on the photo archive, in bytes of source images.
     *
     * A passport photo is 100-500 KB, so a 600-trainee course lands around 150 MB. The cap is
     * a backstop against an unfiltered request across a course whose uploads are unusually
     * large, not an expected limit — it is checked as files are added, so the archive is
     * closed and served rather than failing outright.
     */
    private const PHOTO_MAX_BYTES = 2147483648;   // 2 GB

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
                // The section this column belongs to. The Columns menu groups by it — at ~100
                // columns a flat checkbox list is unusable.
                'group' => $field['group'] ?? 'Other',
                'type' => $field['type'],
                'filter' => $field['filter'] ?? null,
                // Expressions (concatenations, the assembled address, file paths) have no
                // single column to ORDER BY, so they are not sortable. Derived columns
                // (Service, Rank) declare their own sortability.
                'orderable' => $field['type'] === 'derived'
                    ? (bool) ($field['orderable'] ?? false)
                    : ! in_array($field['type'], ['concat', 'address', 'file', 'child'], true),
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
        // SELECT only the columns actually written, but FILTER on the full set: a column the
        // admin filtered on and then hid must still constrain the rows. Filter predicates name
        // s1/s2/s3 columns directly, which are joined by scopedBase() regardless of what is
        // selected, so narrowing the SELECT cannot break them — it just drops the lookup joins
        // for columns nobody is going to read.
        $query = $service->build($form, $fields);
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

            // The repeating sections need a batch of rows to load efficiently, so the cursor
            // is drained in chunks. The chunk is the only thing held in memory, so this stays
            // a streaming export with no row cap.
            $loader = app(FcDescriptiveDataChildLoader::class);
            $childrenNeeded = $loader->needed($fields);

            $flushChunk = function (array $chunk) use (&$serial, $out, $fields, $withUsername, $loader, $childrenNeeded) {
                if ($chunk === []) {
                    return;
                }
                if ($childrenNeeded) {
                    $loader->hydrate($chunk, $fields);
                }

                foreach ($chunk as $row) {
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
                }

                // Push the chunk out rather than letting PHP buffer the whole response.
                flush();
            };

            $chunk = [];
            foreach ($query->cursor() as $row) {
                $chunk[] = $row;
                if (count($chunk) >= self::CSV_CHUNK_ROWS) {
                    $flushChunk($chunk);
                    $chunk = [];
                }
            }
            $flushChunk($chunk);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Accel-Buffering' => 'no',   // stop nginx buffering the whole file first
        ]);
    }

    /**
     * Every trainee's photograph, as one ZIP named after the course.
     *
     * Each image is named <name>_<rank>_<exam year>, the same convention the joining-documents
     * archive uses for its folders. Signatures are deliberately not included — this is the
     * photo export.
     *
     * Optimised for the shape of the job rather than reusing the report query:
     *  - SELECT is five columns (name parts, photo, rank, exam year), not the report's ~97.
     *    Nothing else is written into the archive, so nothing else is read.
     *  - the roster is walked in chunks, so a 600-trainee course never holds every row.
     *  - ZipArchive::addFile() records a PATH; the images are read once, at close(), and are
     *    never held in PHP memory.
     *  - photos are STORED, not deflated. A JPEG/PNG is already compressed — deflating it
     *    burns CPU across every file to save almost nothing.
     */
    public function exportPhotos(Request $request)
    {
        @set_time_limit(0);

        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        $service = app(FcDescriptiveDataQuery::class);
        $all = app(FcDescriptiveDataFieldResolver::class)->forForm($form);

        // scopedBase() + the report's own filters, so the archive holds exactly the trainees
        // the admin is looking at — the same contract as the Excel/CSV/PDF exports.
        $query = $service->scopedBase($form);
        $service->applyFilters($query, $all, $request);

        $s1Col = fc_user_col('student_master_firsts');
        $select = [
            DB::raw("`s1`.`{$s1Col}` as `link_id`"),
            DB::raw("NULLIF(TRIM(`s1`.`photo_path`), '') as `photo_path`"),
            DB::raw($this->photoNameSql().' as `display_name`'),
        ];

        // rank / exam year live on the roster, which is only joined when the tracker keys on
        // user_id (a legacy username-keyed course never gets the alias).
        $hasFrm = fc_user_col($form->trackerStorageTable()) === 'user_id'
            && fc_schema_has_table('fc_registration_master');
        $select[] = ($hasFrm && fc_schema_has_column('fc_registration_master', 'rank'))
            ? DB::raw("NULLIF(TRIM(`frm`.`rank`), '') as `reg_rank`")
            : DB::raw('NULL as `reg_rank`');
        $select[] = ($hasFrm && fc_schema_has_column('fc_registration_master', 'exam_year'))
            ? DB::raw("NULLIF(TRIM(`frm`.`exam_year`), '') as `exam_year`")
            : DB::raw('NULL as `exam_year`');

        $query->select($select)->whereNotNull('s1.photo_path')->where('s1.photo_path', '!=', '');

        $tmpPath = tempnam(sys_get_temp_dir(), 'fc_photos_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpPath);

            return back()->with('error', 'Could not create the ZIP archive.');
        }

        $added = 0;
        $bytes = 0;
        $missing = 0;
        $truncated = false;
        // Two trainees can share a name; without this the second would silently replace the
        // first in the archive.
        $usedNames = [];

        // chunkById, ordered by the UNIQUE s1.user_id — NOT chunk() ordered by first_name.
        // chunk() paginates with LIMIT/OFFSET, so a non-unique sort key (first_name is neither
        // unique nor indexed here — four trainees share a blank one) lets rows shift across a
        // chunk boundary and be skipped or duplicated. That would silently drop photos from
        // any course past the first chunk. chunkById seeks on the unique key instead, so it
        // cannot skip, and it needs no filesort or growing OFFSET.
        $query->chunkById(self::PHOTO_CHUNK_ROWS, function ($rows) use (
            $zip, &$added, &$bytes, &$missing, &$truncated, &$usedNames
        ) {
            foreach ($rows as $row) {
                if ($bytes >= self::PHOTO_MAX_BYTES) {
                    $truncated = true;

                    return false;   // stop chunking
                }

                $full = $this->resolvePhotoPath((string) $row->photo_path);
                if ($full === null) {
                    $missing++;
                    continue;
                }

                $extension = strtolower((string) pathinfo((string) $row->photo_path, PATHINFO_EXTENSION));
                $name = $this->photoEntryName($row, $extension, $usedNames);

                $zip->addFile($full, $name);

                // Stored, not deflated — see the method docblock.
                if (method_exists($zip, 'setCompressionName')) {
                    $zip->setCompressionName($name, \ZipArchive::CM_STORE);
                }

                $added++;
                $bytes += (int) @filesize($full);
            }

            return true;
        }, 's1.'.$s1Col, 'link_id');

        $zip->close();

        if ($added === 0) {
            @unlink($tmpPath);

            return back()->with(
                'error',
                $missing > 0
                    ? 'No photo files could be found on disk for the selected trainees.'
                    : 'No trainees with a photo match the current filters. Nothing to export.'
            );
        }

        $filename = $this->slug($form->form_name).'_Photos_'.now()->format('Ymd_His').'.zip';

        $response = response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);

        // Flashed as well as sent as headers: a browser download shows no headers at all, so an
        // admin would otherwise receive a short archive with nothing to indicate it. The flash
        // surfaces on their next page view.
        $notices = [];
        if ($truncated) {
            $notices[] = sprintf(
                'the %s size limit was reached, so the archive is incomplete',
                round(self::PHOTO_MAX_BYTES / 1073741824, 1).' GB'
            );
        }
        if ($missing > 0) {
            $notices[] = $missing.' photo file(s) could not be found on disk and were skipped';
        }
        if ($notices !== []) {
            // 'error', not 'warning': the shared <x-session_message /> component renders only
            // error / errors / success, and it is used across the whole application — adding a
            // key there to serve one export is a shared-component change for a local need.
            // An incomplete archive is worth the error styling anyway.
            session()->flash('error', sprintf(
                'Photo archive: %s photo(s) included — %s.',
                number_format($added),
                implode('; ', $notices)
            ));
        }

        // Kept as headers too, for anything calling this programmatically.
        $response->headers->set('X-Photo-Count', (string) $added);
        if ($missing > 0) {
            $response->headers->set('X-Photo-Missing', (string) $missing);
        }
        if ($truncated) {
            $response->headers->set('X-Photo-Truncated', '1');
        }

        return $response;
    }

    /** full_name when the course stores one, else the three name parts. */
    private function photoNameSql(): string
    {
        $parts = [];
        if (fc_schema_has_column('student_master_firsts', 'full_name')) {
            $parts[] = "NULLIF(TRIM(`s1`.`full_name`), '')";
        }
        $parts[] = "NULLIF(CONCAT_WS(' ', "
            ."NULLIF(TRIM(`s1`.`first_name`), ''), "
            ."NULLIF(TRIM(`s1`.`middle_name`), ''), "
            ."NULLIF(TRIM(`s1`.`last_name`), '')), '')";

        return 'COALESCE('.implode(', ', $parts).')';
    }

    /**
     * <name>_<rank>_<exam year>.<ext>, with the blank parts dropped rather than left as stray
     * underscores, and a numeric suffix when two trainees would collide.
     *
     * The name segment falls back to the trainee id when it reduces to nothing — a name
     * written only in Devanagari strips away entirely, and "7_2023.jpg" identifies nobody.
     * Collisions are tracked case-insensitively because Windows and macOS treat
     * "Ravi_Kumar.jpg" and "ravi_kumar.jpg" as the same file when the archive is unpacked.
     *
     * @param  array<string,int>  $usedNames  by reference — the collision ledger
     */
    private function photoEntryName(object $row, string $extension, array &$usedNames): string
    {
        $name = $this->zipSafeName((string) ($row->display_name ?? ''));
        if ($name === '') {
            $name = 'trainee_'.($row->link_id ?? count($usedNames) + 1);
        }

        $stem = implode('_', array_filter(
            [$name, $this->zipSafeName((string) ($row->reg_rank ?? '')), $this->zipSafeName((string) ($row->exam_year ?? ''))],
            fn ($v) => $v !== ''
        ));

        $key = strtolower($stem);
        if (isset($usedNames[$key])) {
            $stem .= '_'.(++$usedNames[$key]);
        } else {
            $usedNames[$key] = 1;
        }

        return $stem.($extension !== '' ? '.'.$extension : '');
    }

    /**
     * Absolute path of a stored upload, or null.
     *
     * Goes through the codebase's own resolver, which knows every directory an upload can
     * live in, and is wrapped because it throws on a traversal-looking path rather than
     * returning null. A photo that cannot be resolved is counted and skipped, never fatal.
     */
    private function resolvePhotoPath(string $stored): ?string
    {
        if ($stored === '') {
            return null;
        }

        try {
            $full = fc_resolve_storage_file_path($stored);
        } catch (\Throwable $e) {
            return null;
        }

        if ($full === null || ! is_file($full) || ! $this->isUnderUploadRoot($full)) {
            return null;
        }

        return $full;
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
     * Counts off scopedBase(), not build(): the lookup joins are LEFT JOINs on uniquely-indexed
     * columns, so they cannot change the count — carrying all ~22 of them into a query whose
     * only output is a number is pure cost. Same reasoning, and the same verification, as
     * FcChildHydratingQueryDataTable::prepareCountQuery().
     *
     * @param  array<string,array<string,mixed>>  $fields  filtered on, not selected
     */
    private function countRows(FcForm $form, array $fields, Request $request): int
    {
        $service = app(FcDescriptiveDataQuery::class);
        $query = $service->scopedBase($form)->selectRaw('1');
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
        // Same as the CSV path: select the visible columns, filter on all of them.
        $query = $service->build($form, $fields);
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

        app(FcDescriptiveDataChildLoader::class)->hydrate($rows, $fields);

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

    /**
     * Filename-safe form of an archive entry name.
     *
     * Unlike slug(), this returns '' when nothing survives stripping — slug() substitutes the
     * literal "course", which is right for a download filename and very wrong for a trainee's
     * photo. The caller falls back to the trainee id instead.
     */
    private function zipSafeName(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $value), '_');
    }

    private function slug(?string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', (string) $value);

        return trim((string) $slug, '_') ?: 'course';
    }
}
