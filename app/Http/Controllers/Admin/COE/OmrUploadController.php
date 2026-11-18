<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\COE\OmrResponse;
use App\Models\COE\OmrUpload;
use App\Services\COE\OmrCsvParser;
use App\Services\COE\OmrParticipantMatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * COE → Result Management → OMR Upload.
 *
 * An administrator uploads the CSV the OMR scanner exported; every answer sheet in
 * it becomes a coe_omr_responses row under one coe_omr_uploads row, stored as the
 * scanner read it (score included, positive or negative) and linked to the
 * participant whose Roll No. it carries. index() lists the uploads, show()
 * the sheets of one, destroy() removes an upload with its sheets. Reading the file
 * is OmrCsvParser's job; finding the participant is OmrParticipantMatcher's.
 */
class OmrUploadController extends Controller
{
    public const MAX_MB = 30;

    private const INSERT_CHUNK = 500;

    /** Reading and saving a 30 MB export takes longer than the default 30–120 s. */
    private const TIME_LIMIT_SECONDS = 600;

    /** Rows per page the sheets list offers; the first is the default. */
    private const PER_PAGE = [25, 50, 100, 200, 500];

    /** Sort choices of the sheets list: key => [label, column]. */
    private const SORTS = [
        'line' => ['Line', 'coe_omr_responses.row_no'],
        'barcode' => ['Barcode', 'coe_omr_responses.barcode'],
        'roll' => ['Roll No.', 'coe_omr_responses.roll_number'],
        'participant' => ['Participant', 'sm.display_name'],
        'ot_code' => ['OT Code', 'sm.generated_OT_code'],
        'booklet' => ['Booklet', 'coe_omr_responses.booklet_series'],
        'attempted' => ['Attempted', 'coe_omr_responses.attempted_count'],
        'blank' => ['Blank', 'coe_omr_responses.blank_count'],
        'multi' => ['Multi-marked', 'coe_omr_responses.multi_marked_count'],
        'score' => ['Score', 'coe_omr_responses.score'],
        'status' => ['Status', 'coe_omr_responses.has_error'],
    ];

    /**
     * "Problem" filter: key => [label, remark patterns]. The patterns are the
     * reasons OmrCsvParser, flagBarcodesUploadedBefore() and the matcher write.
     */
    private const PROBLEM_TYPES = [
        'duplicate_barcode' => ['Duplicate Barcode in file', ['Duplicate Barcode']],
        'barcode_uploaded' => ['Barcode already uploaded earlier', ['Barcode already uploaded']],
        'duplicate_roll' => ['Duplicate Roll No. in file', ['Duplicate Roll No.']],
        'duplicate_header' => ['Duplicate NCS Header', ['Duplicate NCS Header']],
        'barcode_unread' => ['Barcode blank / unread / not numeric', ['Barcode blank', 'Barcode not read clearly', 'Barcode is not numeric']],
        'roll_unread' => ['Roll No. blank / unread', ['Roll No. blank', 'Roll No. not read clearly']],
        'digits' => ['Barcode / Roll No. digit count differs', ['digits; most sheets have']],
        'booklet' => ['Booklet blank / multi-marked / invalid', ['Booklet series']],
        'no_answer' => ['No answer marked', ['No answer marked']],
        'score_missing' => ['Score missing', ['Score missing']],
        'header_blank' => ['NCS Header blank', ['NCS Header blank']],
    ];

    public function index()
    {
        $rows = OmrUpload::query()
            ->leftJoin('user_credentials as u', 'u.pk', '=', 'coe_omr_uploads.created_by')
            ->orderByDesc('coe_omr_uploads.pk')
            ->get(['coe_omr_uploads.*', DB::raw("TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as uploaded_by")])
            ->map(fn (OmrUpload $u) => [
                'id' => (int) $u->pk,
                'file' => $u->original_file_name,
                'remarks' => $u->remarks,
                'questions' => $u->question_count,
                'has_score' => $u->has_score,
                'total' => $u->total_rows,
                'errors' => $u->error_rows,
                'uploaded_by' => $u->uploaded_by ?: '—',
                'uploaded_at' => $u->created_date?->format('d-m-Y h:i A'),
                'show_url' => route('admin.coe.omr-upload.show', ['upload' => $u->pk]),
            ]);

        return view('admin.coe.omr_upload.index', ['rows' => $rows, 'maxMb' => self::MAX_MB]);
    }

    public function store(Request $request, OmrCsvParser $parser, OmrParticipantMatcher $matcher): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['bail', 'required', 'file',
                // Before mimes:, which would call an empty file "not a .csv".
                function (string $attribute, $value, \Closure $fail) {
                    if ($value->getSize() === 0) {
                        $fail('The file is empty.');
                    }
                },
                'mimes:csv,txt', 'max:' . (self::MAX_MB * 1024)],
            // Printable text only — no control characters, no leading formula character for Excel downloads.
            'remarks' => ['nullable', 'string', 'max:255', 'not_regex:/[\x00-\x1F\x7F]/', 'not_regex:/^\s*[=+\-@]/'],
        ], [
            'file.required' => 'Choose the CSV exported by the OMR software.',
            'file.mimes' => 'The file must be a .csv file.',
            'file.max' => 'The file must be ' . self::MAX_MB . ' MB or smaller.',
            'remarks.max' => 'Remarks may be at most 255 characters.',
            'remarks.not_regex' => 'Remarks may not start with = + - @ or contain control characters.',
        ]);

        $file = $request->file('file');
        // mimes: looks at the content (a CSV is guessed as text/plain), so the name is checked too.
        if (strtolower((string) $file->getClientOriginalExtension()) !== 'csv') {
            return response()->json(['message' => 'The file must be a .csv file.', 'errors' => ['file' => ['The file must be a .csv file.']]], 422);
        }
        $hash = hash_file('sha256', $file->getRealPath());

        if ($duplicate = $this->uploadWithHash($hash)) {
            return response()->json(['message' => $duplicate], 422);
        }

        // A 30 MB export is ~100k sheets: read three times and saved in chunks.
        @set_time_limit(self::TIME_LIMIT_SECONDS);
        $path = $file->getRealPath();

        try {
            $meta = $parser->inspect($path);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $now = now();
        $linked = 0;
        $errorRows = 0;

        try {
            $upload = DB::transaction(function () use ($parser, $matcher, $meta, $path, $validated, $file, $hash, $now, &$linked, &$errorRows) {
                $upload = OmrUpload::create([
                    'original_file_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'file_hash' => $hash,
                    'remarks' => trim((string) ($validated['remarks'] ?? '')) ?: null,
                    'question_count' => $meta['question_count'],
                    'has_score' => $meta['has_score'],
                    'total_rows' => $meta['total'],
                    'error_rows' => 0,
                    'created_by' => Auth::id(),
                    'created_date' => $now,
                ]);

                $chunk = [];
                $save = function () use (&$chunk, $matcher, $upload, $now, &$linked, &$errorRows) {
                    if ($chunk === []) {
                        return;
                    }
                    $this->flagBarcodesUploadedBefore($chunk, $upload->pk);
                    $matches = $matcher->match(array_column($chunk, 'roll_number'));

                    OmrResponse::insert(array_map(function (array $r) use ($matcher, $matches, $upload, $now, &$linked, &$errorRows) {
                        $link = $matcher->linkFor($r['roll_number'], $matches);
                        $linked += $link['student_master_pk'] !== null ? 1 : 0;
                        $errorRows += $r['issues'] === [] ? 0 : 1;

                        return [
                            'coe_omr_upload_pk' => $upload->pk,
                            'row_no' => $r['row_no'],
                            'ncs_header' => $r['ncs_header'],
                            'barcode' => $r['barcode'],
                            'roll_number' => $r['roll_number'],
                            'student_master_pk' => $link['student_master_pk'],
                            'match_remarks' => $link['match_remarks'],
                            'booklet_series' => $r['booklet_series'],
                            'answers' => json_encode($r['answers']),
                            'attempted_count' => $r['attempted_count'],
                            'blank_count' => $r['blank_count'],
                            'multi_marked_count' => $r['multi_marked_count'],
                            'score' => $r['score'],
                            'has_error' => $r['issues'] === [] ? 0 : 1,
                            'error_remarks' => $r['issues'] === [] ? null : mb_substr(implode('; ', $r['issues']), 0, 255),
                            'created_date' => $now,
                        ];
                    }, $chunk));
                    $chunk = [];
                };

                foreach ($parser->rows($path, $meta) as $row) {
                    $chunk[] = $row;
                    if (count($chunk) >= self::INSERT_CHUNK) {
                        $save();
                    }
                }
                $save();

                $upload->error_rows = $errorRows;
                $upload->save();

                return $upload;
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (QueryException $e) {
            // Two uploads of the same file at once: the unique hash lets one through.
            if (($e->errorInfo[0] ?? null) === '23000' && ($duplicate = $this->uploadWithHash($hash))) {
                return response()->json(['message' => $duplicate], 422);
            }
            throw $e;
        }

        $message = sprintf(
            '%d answer sheet%s imported (%d questions%s).',
            $upload->total_rows,
            $upload->total_rows === 1 ? '' : 's',
            $upload->question_count,
            $upload->has_score ? ', with score' : ''
        );
        $message .= ' ' . $linked . ' of ' . $upload->total_rows . ' linked to a participant by Roll No.';
        if ($errorRows > 0) {
            $message .= ' ' . $errorRows . ' sheet' . ($errorRows === 1 ? ' has' : 's have') . ' read problems — see the Remarks column.';
        }

        return response()->json([
            'message' => $message,
            'redirect' => route('admin.coe.omr-upload.show', ['upload' => $upload->pk]),
        ]);
    }

    /**
     * The upload's answer sheets: a filter form and one Laravel-paginated page.
     * Every filter, the sort and the page size travel in the query string, so a
     * page link or a Download keeps them.
     */
    public function show(Request $request, int $upload)
    {
        $record = OmrUpload::findOrFail($upload);
        $filters = $this->sheetFilters($request, $record);

        $query = $this->sheetQuery($record, $filters)
            ->orderBy(self::SORTS[$filters['sort']][1], $filters['dir'])
            ->orderBy('coe_omr_responses.row_no');

        if ($this->narrowed($filters)) {
            $sheets = $query->paginate($filters['per_page']);
        } else {
            // Nothing narrows the list: its size is the upload's total, so skip counting 100k joined rows.
            $page = max(1, (int) $request->input('page', 1));
            $sheets = new LengthAwarePaginator(
                $query->forPage($page, $filters['per_page'])->get(),
                $record->total_rows,
                $filters['per_page'],
                $page,
                ['path' => $request->url(), 'pageName' => 'page']
            );
        }
        // Page links carry the cleaned filters, not whatever was in the address bar.
        $sheets->appends(array_filter($filters, fn ($v, $k) => $v !== '' && $v !== null
            && ! ($k === 'sort' && $v === 'line') && ! ($k === 'dir' && $v === 'asc') && ! ($k === 'per_page' && $v === self::PER_PAGE[0]), ARRAY_FILTER_USE_BOTH));
        $sheets->setCollection($sheets->getCollection()->map(fn (OmrResponse $r) => $this->sheetRow($r)));

        return view('admin.coe.omr_upload.show', [
            'upload' => $record,
            'sheets' => $sheets,
            'filters' => $filters,
            'filtered' => $this->narrowed($filters),
            'linked' => $record->responses()->whereNotNull('student_master_pk')->count(),
            'booklets' => $record->responses()->whereNotNull('booklet_series')->distinct()->orderBy('booklet_series')->pluck('booklet_series'),
            'courses' => $record->responses()
                ->join('student_master as s', 's.pk', '=', 'coe_omr_responses.student_master_pk')
                ->join('course_master as c', 'c.pk', '=', 's.course_master_pk')
                ->distinct()->orderBy('c.course_name')->pluck('c.course_name', 'c.pk'),
            'problemTypes' => array_map(fn ($p) => $p[0], self::PROBLEM_TYPES),
            'sorts' => array_map(fn ($s) => $s[0], self::SORTS),
            'perPageOptions' => self::PER_PAGE,
        ]);
    }

    /** Download: every sheet the grid's search and filters match, as CSV, streamed. */
    public function export(Request $request, int $upload)
    {
        $record = OmrUpload::findOrFail($upload);
        $filters = $this->sheetFilters($request, $record);
        $query = $this->sheetQuery($record, $filters)
            ->orderBy(self::SORTS[$filters['sort']][1], $filters['dir'])
            ->orderBy('coe_omr_responses.row_no');
        $name = 'OMR_Answer_Sheets_' . $record->pk . '_' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($query, $record) {
            @set_time_limit(self::TIME_LIMIT_SECONDS);
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8

            $heading = ['Line', 'NCS Header', 'Barcode', 'Roll No.', 'Participant', 'OT Code', 'Course', 'Link', 'Booklet', 'Attempted', 'Blank', 'Multi-marked'];
            if ($record->has_score) {
                $heading[] = 'Score';
            }
            for ($q = 1; $q <= $record->question_count; $q++) {
                $heading[] = 'Q' . $q;
            }
            fputcsv($out, array_merge($heading, ['Status', 'Remarks']));

            $query->chunk(1000, function ($sheets) use ($out, $record) {
                foreach ($sheets as $r) {
                    $row = $this->sheetRow($r);
                    $line = [$row['line'], $row['header'], $row['barcode'], $row['roll'], $row['participant'], $row['ot_code'],
                        $row['course'], $row['student_pk'] ? 'Linked' : 'Not linked', $row['booklet'], $row['attempted'], $row['blank'], $row['multi']];
                    if ($record->has_score) {
                        $line[] = $row['score'];
                    }
                    $line = array_merge($line, array_pad($row['answers'], $record->question_count, ''),
                        [$row['has_error'] ? 'Problem' : 'OK', $row['remarks'] ?? $row['match_remarks']]);
                    fputcsv($out, array_map(fn ($v) => $this->csvCell($v), $line));
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Link the upload's sheets to participants again, e.g. after roll numbers were generated or corrected. */
    public function rematch(int $upload, OmrParticipantMatcher $matcher): JsonResponse
    {
        if (! OmrUpload::whereKey($upload)->exists()) {
            return response()->json(['message' => 'That upload no longer exists.'], 404);
        }

        $result = $matcher->rematchUpload($upload);

        return response()->json([
            'message' => $result['linked'] . ' of ' . $result['total'] . ' answer sheets are linked to a participant.',
        ]);
    }

    /** The upload and every sheet in it (FK cascade). */
    public function destroy(int $upload): JsonResponse
    {
        $record = OmrUpload::find($upload);
        if ($record === null) {
            return response()->json(['message' => 'That upload no longer exists.'], 404);
        }

        $record->delete();

        return response()->json(['message' => 'Upload and its ' . $record->total_rows . ' answer sheets deleted.']);
    }

    /**
     * A barcode identifies one physical answer sheet, so finding it in an earlier
     * upload means the sheet was scanned again into a different export.
     */
    private function flagBarcodesUploadedBefore(array &$rows, int $currentUploadPk): void
    {
        $barcodes = array_values(array_unique(array_filter(
            array_column($rows, 'barcode'),
            fn ($b) => $b !== null && ! str_contains($b, '*')
        )));

        $seen = [];
        foreach (array_chunk($barcodes, 1000) as $chunk) {
            OmrResponse::query()
                ->join('coe_omr_uploads as u', 'u.pk', '=', 'coe_omr_responses.coe_omr_upload_pk')
                // This upload's earlier chunks are already saved; repeats within the file are the parser's.
                ->where('u.pk', '<>', $currentUploadPk)
                ->whereIn('coe_omr_responses.barcode', $chunk)
                ->orderBy('u.pk')
                ->get(['coe_omr_responses.barcode', 'u.original_file_name'])
                ->each(function ($r) use (&$seen) {
                    $seen[$r->barcode] ??= $r->original_file_name;
                });
        }

        foreach ($rows as &$row) {
            if ($row['barcode'] !== null && isset($seen[$row['barcode']])) {
                $row['issues'][] = 'Barcode already uploaded (' . mb_substr($seen[$row['barcode']], 0, 60) . ')';
            }
        }
        unset($row);
    }

    /**
     * The sheets list's filters from the query string, cleaned: unknown choices are
     * dropped, numbers are whole numbers within range, a "from" above its "to" is
     * swapped. Nothing here can fail a request — a bad value just does not filter.
     *
     * @return array<string, mixed>
     */
    private function sheetFilters(Request $request, OmrUpload $upload): array
    {
        $text = fn (string $key, int $max = 100) => mb_substr(trim((string) $request->query($key, '')), 0, $max);
        $int = function (string $key, int $min, int $max) use ($request): ?int {
            $raw = trim((string) $request->query($key, ''));

            return preg_match('/^-?\d{1,6}$/', $raw) === 1 ? max($min, min($max, (int) $raw)) : null;
        };
        $pick = fn (string $key, array $allowed) => in_array($v = (string) $request->query($key, ''), $allowed, true) ? $v : '';

        $f = [
            'q' => $text('q'),
            'barcode' => $text('barcode', 30),
            'roll' => $text('roll', 50),
            'participant' => $text('participant'),
            'course' => preg_match('/^\d{1,18}$/', (string) $request->query('course', '')) === 1 ? (string) $request->query('course') : '',
            'booklet' => $text('booklet', 5),
            'status' => $pick('status', ['OK', 'Problem']),
            'link' => $pick('link', ['Linked', 'Not linked']),
            'problem' => $pick('problem', array_keys(self::PROBLEM_TYPES)),
            'multi' => $pick('multi', ['yes', 'no']),
            'score_min' => $upload->has_score ? $int('score_min', -32768, 32767) : null,
            'score_max' => $upload->has_score ? $int('score_max', -32768, 32767) : null,
            'attempted_min' => $int('attempted_min', 0, OmrCsvParser::MAX_QUESTIONS),
            'attempted_max' => $int('attempted_max', 0, OmrCsvParser::MAX_QUESTIONS),
            'line_from' => $int('line_from', 1, 9999999),
            'line_to' => $int('line_to', 1, 9999999),
            'sort' => $pick('sort', array_keys(self::SORTS)) ?: 'line',
            'dir' => $request->query('dir') === 'desc' ? 'desc' : 'asc',
            'per_page' => in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0],
        ];

        foreach ([['score_min', 'score_max'], ['attempted_min', 'attempted_max'], ['line_from', 'line_to']] as [$lo, $hi]) {
            if ($f[$lo] !== null && $f[$hi] !== null && $f[$lo] > $f[$hi]) {
                [$f[$lo], $f[$hi]] = [$f[$hi], $f[$lo]];
            }
        }

        return $f;
    }

    /** Whether any filter narrows the list (sort and page size do not). */
    private function narrowed(array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (! in_array($key, ['sort', 'dir', 'per_page'], true) && $value !== '' && $value !== null) {
                return true;
            }
        }

        return false;
    }

    /** The upload's sheets with their participant, narrowed by sheetFilters(). */
    private function sheetQuery(OmrUpload $upload, array $f)
    {
        $like = fn (string $v) => '%' . addcslashes($v, '%_\\') . '%';
        $col = fn (string $c) => 'coe_omr_responses.' . $c;

        $query = $upload->responses()
            ->leftJoin('student_master as sm', 'sm.pk', '=', $col('student_master_pk'))
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'sm.course_master_pk')
            ->select([$col('*'), 'sm.display_name', 'sm.generated_OT_code', 'cm.course_name']);

        if ($f['q'] !== '') {
            $term = $like($f['q']);
            $query->where(fn ($q) => $q->where($col('barcode'), 'like', $term)
                ->orWhere($col('roll_number'), 'like', $term)
                ->orWhere($col('ncs_header'), 'like', $term)
                ->orWhere($col('error_remarks'), 'like', $term)
                ->orWhere($col('match_remarks'), 'like', $term)
                ->orWhere('sm.display_name', 'like', $term)
                ->orWhere('sm.generated_OT_code', 'like', $term));
        }
        if ($f['barcode'] !== '') {
            $query->where($col('barcode'), 'like', $like($f['barcode']));
        }
        if ($f['roll'] !== '') {
            $query->where($col('roll_number'), 'like', $like($f['roll']));
        }
        if ($f['participant'] !== '') {
            $term = $like($f['participant']);
            $query->where(fn ($q) => $q->where('sm.display_name', 'like', $term)->orWhere('sm.generated_OT_code', 'like', $term));
        }
        if ($f['course'] !== '') {
            $query->where('sm.course_master_pk', $f['course']);
        }
        if ($f['booklet'] !== '') {
            $query->where($col('booklet_series'), $f['booklet']);
        }
        if ($f['status'] !== '') {
            $query->where($col('has_error'), $f['status'] === 'Problem' ? 1 : 0);
        }
        if ($f['link'] === 'Linked') {
            $query->whereNotNull($col('student_master_pk'));
        } elseif ($f['link'] === 'Not linked') {
            $query->whereNull($col('student_master_pk'));
        }
        if ($f['problem'] !== '') {
            $patterns = self::PROBLEM_TYPES[$f['problem']][1];
            $query->where(function ($q) use ($patterns, $like, $col) {
                foreach ($patterns as $p) {
                    $q->orWhere($col('error_remarks'), 'like', $like($p));
                }
            });
        }
        if ($f['multi'] === 'yes') {
            $query->where($col('multi_marked_count'), '>', 0);
        } elseif ($f['multi'] === 'no') {
            $query->where($col('multi_marked_count'), 0);
        }
        foreach (['score_min' => ['score', '>='], 'score_max' => ['score', '<='], 'attempted_min' => ['attempted_count', '>='],
            'attempted_max' => ['attempted_count', '<='], 'line_from' => ['row_no', '>='], 'line_to' => ['row_no', '<=']] as $key => [$c, $op]) {
            if ($f[$key] !== null) {
                $query->where($col($c), $op, $f[$key]);
            }
        }

        return $query;
    }

    /** One sheet as the grid, its Answers popup and the CSV show it. */
    private function sheetRow(OmrResponse $r): array
    {
        return [
            'id' => (int) $r->pk,
            'line' => $r->row_no,
            'header' => $r->ncs_header,
            'barcode' => $r->barcode,
            'roll' => $r->roll_number,
            'student_pk' => $r->student_master_pk,
            'participant' => $r->display_name,
            'ot_code' => $r->generated_OT_code,
            'course' => $r->course_name,
            'match_remarks' => $r->match_remarks,
            'booklet' => $r->booklet_series,
            'answers' => $r->answers ?? [],
            'attempted' => $r->attempted_count,
            'blank' => $r->blank_count,
            'multi' => $r->multi_marked_count,
            'score' => $r->score,
            'has_error' => (bool) $r->has_error,
            'remarks' => $r->error_remarks,
        ];
    }

    /**
     * Excel runs a cell that starts with = + - @ as a formula. Quote such text the
     * way CoeGrid's CSV does; numbers (a score of -2) stay numbers.
     */
    private function csvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    }

    private function uploadWithHash(string $hash): ?string
    {
        $existing = OmrUpload::where('file_hash', $hash)->first();

        return $existing
            ? 'This file was already uploaded on ' . $existing->created_date?->format('d-m-Y h:i A')
                . ' (' . $existing->original_file_name . '). Delete that upload first to upload it again.'
            : null;
    }
}
