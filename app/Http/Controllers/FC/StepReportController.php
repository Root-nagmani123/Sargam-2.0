<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcStepReportDataTable;
use App\Exports\FC\FcStepReportExport;
use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Services\FC\FcBankDetailsReport;
use App\Services\FC\FcPreMedicalHistoryReport;
use App\Services\FC\FcSpecialAssistantReport;
use App\Services\FC\FcStepReport;
use App\Services\FC\FcVisionStatementReport;
use App\Support\FC\FcUploadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Screen + exports for the step-shaped FC reports (Vision Statement, Special Assistant).
 *
 * One controller for all of them: they differ only in which {@see FcStepReport} they read, and
 * that arrives as a route parameter rather than as another near-identical controller class.
 */
class StepReportController extends Controller
{
    /**
     * Ceiling on the .xlsx export.
     *
     * Lower than the Descriptive Data report's 10,000 on purpose: these steps carry free text of
     * up to 1,500 characters, so a row here costs far more PhpSpreadsheet memory than a row of
     * short fields.
     */
    private const XLSX_MAX_ROWS = 5000;

    /** Hard ceiling on a single PDF. Past this the document is unusable anyway. */
    private const PDF_MAX_ROWS = 1000;

    /** Trainees read per batch when building the document archive. */
    private const ARCHIVE_CHUNK_ROWS = 500;

    /**
     * Ceiling on the document archive, in bytes of source files.
     *
     * A supporting document is typically well under 5 MB (the upload rule caps it there), so a
     * 600-trainee course lands in the low hundreds of MB. This is a backstop against an
     * unfiltered request across a course whose uploads are unusually large, not an expected
     * limit — it is checked as files are added, so the archive is closed and served rather than
     * failing outright.
     */
    private const ARCHIVE_MAX_BYTES = 2147483648;   // 2 GB

    /** Route key → the service that defines the report. Keep in step with the loop in routes. */
    private const REPORTS = [
        'vision-statement' => FcVisionStatementReport::class,
        'special-assistant' => FcSpecialAssistantReport::class,
        'bank-report' => FcBankDetailsReport::class,
        'pre-medical-history' => FcPreMedicalHistoryReport::class,
    ];

    private function report(string $key): FcStepReport
    {
        abort_unless(isset(self::REPORTS[$key]), 404);

        return app(self::REPORTS[$key]);
    }

    public function index(Request $request, string $report)
    {
        $service = $this->report($report);
        $form = $this->resolveForm($request);
        $dataTable = new FcStepReportDataTable($service, $form);

        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('fc.report.step-report-index', [
            'report' => $service,
            'reportKey' => $service->key(),
            'form' => $form,
            'forms' => $this->courseOptions(),
            'columnsJson' => $this->columnsForJson($service),
            'statusLabels' => $service->statusLabels(),
            'mapsStep' => $form ? $service->formMapsStep($form) : true,
        ]);
    }

    /** @return list<array<string,mixed>> */
    private function columnsForJson(FcStepReport $service): array
    {
        $out = [];
        foreach ($service->columns() as $key => $column) {
            $out[] = [
                'key' => $key,
                'label' => $column['label'],
                'orderable' => (bool) ($column['orderable'] ?? false),
                'long' => (bool) ($column['long'] ?? false),
            ];
        }

        return $out;
    }

    public function exportExcel(Request $request, string $report)
    {
        $service = $this->report($report);
        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        // Counted BEFORE anything is built: an OOM halfway through gives the admin a blank 500,
        // while this gives them a working alternative.
        $rowCount = $this->countRows($service, $form, $request);
        if ($rowCount > self::XLSX_MAX_ROWS) {
            return back()->with('error', sprintf(
                'This selection has %s records, over the %s-row Excel limit. Narrow the filters and try again.',
                number_format($rowCount),
                number_format(self::XLSX_MAX_ROWS)
            ));
        }

        $filename = $this->slug($service->title()).'_'.$this->slug($form->form_name).'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new FcStepReportExport($service, $form, $service->visibleColumns($request), $request),
            $filename
        );
    }

    public function exportPdf(Request $request, string $report)
    {
        @set_time_limit(0);

        $service = $this->report($report);
        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        $columns = $service->visibleColumns($request);
        $query = $service->build($form, $columns);
        $service->applyFilters($query, $request);

        // cursor(), not get() — rows stream in one at a time instead of materialising the whole
        // result set, which matters when each row carries long free text.
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
            return back()->with('error', 'No trainees match the current filters. Nothing to export.');
        }

        $html = view('fc.report.step-report-pdf', [
            'report' => $service,
            'form' => $form,
            'columns' => $columns,
            'rows' => $rows,
            'truncated' => $truncated,
            'maxRows' => self::PDF_MAX_ROWS,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Portrait, not landscape: these reports are a few narrow identity columns plus blocks
        // of prose, which read better in a column than stretched across a landscape page.
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);
        $mpdf->SetTitle($service->title().' - '.$form->form_name);
        $mpdf->WriteHTML($html);

        $filename = $this->slug($service->title()).'_'.$this->slug($form->form_name).'_'.now()->format('Ymd_His').'.pdf';

        return response((string) $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Endpoint that serves this family's uploads. Must match the route below. */
    public const FILE_PATH = '/admin/reports/step-file';

    /**
     * Serve one upload from an opaque token — the authenticated counterpart of the
     * descriptive-data file route.
     *
     * A separate endpoint on purpose. The older route is deliberately unauthenticated so an
     * emailed workbook keeps resolving for a recipient who is not a Sargam user, and that was
     * accepted for PHOTOGRAPHS AND SIGNATURES. These reports' uploads are Aadhaar cards, PAN
     * cards, cancelled cheques and medical documents; inheriting that exemption would hand a
     * stranger identity documents from any forwarded export. Same opaque token, same path
     * safety — but behind the gate.
     */
    public function file(Request $request)
    {
        $path = FcUploadUrl::decode($request->query(FcUploadUrl::TOKEN_PARAM));
        abort_if($path === null, 404);

        // Resolves across every directory an FC upload can live in, catches the traversal
        // exception Flysystem throws, and refuses anything outside an upload root.
        $full = FcUploadFile::resolve($path);
        abort_if($full === null, 404);

        $response = response()->file($full, [
            'Content-Type' => @mime_content_type($full) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($full).'"',
            // Never let a browser sniff a different type out of an uploaded file.
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // setPrivate() rather than a Cache-Control header: Symfony rebuilds that header when it
        // prepares a BinaryFileResponse and re-adds `public`, which is exactly wrong for an
        // identity document — it invites proxies to keep a copy.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('max-age', '0');

        return $response;
    }

    /**
     * Every uploaded document on the course, as one ZIP named after the course, with a folder
     * per trainee named <username>_<rank>_<exam year>.
     *
     * Optimised for the shape of the job rather than reusing the report query:
     *  - the SELECT is the folder-naming columns plus the upload paths, not the report's long
     *    free-text columns, which the archive never writes;
     *  - the roster is walked in chunks, so a 600-trainee course never holds every row;
     *  - ZipArchive::addFile() records a PATH — the files are read once, at close(), and are
     *    never held in PHP memory;
     *  - entries are STORED, not deflated. A PDF or JPEG is already compressed, so deflating it
     *    burns CPU across every file to save almost nothing.
     */
    public function exportDocuments(Request $request, string $report)
    {
        @set_time_limit(0);

        $service = $this->report($report);
        abort_unless($service->hasFileColumns(), 404);

        $form = $this->resolveForm($request);
        if (! $form) {
            return back()->with('error', 'Please select a course first.');
        }

        $fileColumns = $service->fileColumns();
        $query = $service->documentArchiveQuery($form);
        // The same filters the screen is showing, so the archive holds exactly the trainees the
        // admin is looking at — the same contract as the Excel and PDF exports.
        $service->applyFilters($query, $request);

        // At least one upload, or the trainee contributes nothing but an empty folder. Asked
        // through columnSql() rather than a hardcoded alias: which table the upload lives on is
        // the report's business, not this controller's.
        $query->where(function ($q) use ($fileColumns, $service, $form) {
            foreach (array_keys($fileColumns) as $key) {
                $sql = $service->columnSql($form, $key);
                if ($sql !== null) {
                    $q->orWhereRaw("TRIM(COALESCE({$sql}, '')) <> ''");
                }
            }
        });

        $tmpPath = tempnam(sys_get_temp_dir(), 'fc_docs_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpPath);

            return back()->with('error', 'Could not create the ZIP archive.');
        }

        $added = 0;
        $bytes = 0;
        $missing = 0;
        $truncated = false;
        // Two trainees can share a folder name; without this the second would silently
        // overwrite the first when the archive is unpacked.
        $usedFolders = [];

        $s1Col = fc_user_col('student_master_firsts');

        // chunkById on the UNIQUE s1 user key — NOT chunk() on a name. chunk() paginates with
        // LIMIT/OFFSET, so a non-unique sort key lets rows shift across a chunk boundary and be
        // skipped, silently dropping documents from any course past the first chunk.
        $query->chunkById(self::ARCHIVE_CHUNK_ROWS, function ($rows) use (
            $zip, $fileColumns, &$added, &$bytes, &$missing, &$truncated, &$usedFolders
        ) {
            foreach ($rows as $row) {
                if ($bytes >= self::ARCHIVE_MAX_BYTES) {
                    $truncated = true;

                    return false;   // stop chunking
                }

                $folder = $this->folderName($row, $usedFolders);

                foreach (array_keys($fileColumns) as $key) {
                    $full = FcUploadFile::resolve($row->{$key} ?? null);
                    if ($full === null) {
                        if (trim((string) ($row->{$key} ?? '')) !== '') {
                            $missing++;   // recorded in the database, absent on disk
                        }
                        continue;
                    }

                    $entry = $folder.'/'.FcUploadFile::safeName(pathinfo((string) $row->{$key}, PATHINFO_FILENAME))
                        .'.'.strtolower((string) pathinfo((string) $row->{$key}, PATHINFO_EXTENSION));

                    $zip->addFile($full, $entry);
                    if (method_exists($zip, 'setCompressionName')) {
                        $zip->setCompressionName($entry, \ZipArchive::CM_STORE);
                    }

                    $added++;
                    $bytes += (int) @filesize($full);
                }
            }

            return true;
        }, 's1.'.$s1Col, 'link_id');

        $zip->close();

        if ($added === 0) {
            @unlink($tmpPath);

            return back()->with(
                'error',
                $missing > 0
                    ? 'No document files could be found on disk for the selected trainees.'
                    : 'No trainees with an uploaded document match the current filters. Nothing to export.'
            );
        }

        $filename = $this->slug($form->form_name).'.zip';

        $response = response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);

        // Flashed as well as sent as headers: a browser download shows no headers at all, so an
        // admin would otherwise receive a short archive with nothing to indicate it.
        $notices = [];
        if ($truncated) {
            $notices[] = sprintf('the %s size limit was reached, so the archive is incomplete',
                round(self::ARCHIVE_MAX_BYTES / 1073741824, 1).' GB');
        }
        if ($missing > 0) {
            $notices[] = $missing.' document(s) recorded in the database could not be found on disk and were skipped';
        }
        if ($notices !== []) {
            session()->flash('error', sprintf('Document archive: %s file(s) included — %s.',
                number_format($added), implode('; ', $notices)));
        }

        $response->headers->set('X-Document-Count', (string) $added);
        if ($missing > 0) {
            $response->headers->set('X-Document-Missing', (string) $missing);
        }
        if ($truncated) {
            $response->headers->set('X-Document-Truncated', '1');
        }

        return $response;
    }

    /**
     * <username>_<rank>_<exam year>, with blank parts dropped rather than left as stray
     * underscores, and a numeric suffix when two trainees would collide.
     *
     * Falls back to the display name and then the trainee id when the username reduces to
     * nothing — a folder called "_154_2023" identifies nobody. Collisions are tracked
     * case-insensitively because Windows and macOS treat "Ravi_Kumar" and "ravi_kumar" as the
     * same directory when the archive is unpacked.
     *
     * @param  array<string,int>  $usedFolders  by reference — the collision ledger
     */
    private function folderName(object $row, array &$usedFolders): string
    {
        $name = FcUploadFile::safeName((string) ($row->login_username ?? ''));
        if ($name === '') {
            $name = FcUploadFile::safeName((string) ($row->display_name ?? ''));
        }
        if ($name === '') {
            $name = 'trainee_'.($row->link_id ?? count($usedFolders) + 1);
        }

        $stem = implode('_', array_filter([
            $name,
            FcUploadFile::safeName((string) ($row->reg_rank ?? '')),
            FcUploadFile::safeName((string) ($row->exam_year ?? '')),
        ], fn ($v) => $v !== ''));

        $key = strtolower($stem);
        if (isset($usedFolders[$key])) {
            $stem .= '_'.(++$usedFolders[$key]);
        } else {
            $usedFolders[$key] = 1;
        }

        return $stem;
    }

    /**
     * Row count for the current filters, off scopedBase() rather than the report query — the
     * report's own SELECT cannot change how many rows there are, so building it here is pure
     * cost. The extra join a report declares is a LEFT JOIN on a uniquely-indexed column, so it
     * cannot change the count either.
     */
    private function countRows(FcStepReport $service, FcForm $form, Request $request): int
    {
        $query = $service->countBase($form);
        $service->applyFilters($query, $request);

        return (int) $query->count();
    }

    private function resolveForm(Request $request): ?FcForm
    {
        $id = (int) $request->input('form_id', 0);

        // Named columns, not SELECT * (G1): id + form_name for headings and filenames,
        // course_master_pk for the Pre-Medical course scope, consolidation_table because
        // trackerStorageTable() reads it.
        return $id > 0
            ? FcForm::select(['id', 'form_name', 'course_master_pk', 'consolidation_table'])->find($id)
            : null;
    }

    /** Active courses for the picker. Explicit column list; a short dropdown needs no paging. */
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

        return trim((string) $slug, '_') ?: 'report';
    }
}
