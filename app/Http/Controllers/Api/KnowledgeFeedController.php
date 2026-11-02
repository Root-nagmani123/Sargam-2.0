<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Knowledge\NoticeKnowledgeFeed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Pull feed for ज्ञानकोश: their server calls one endpoint and gets the day's
 * published notices as a single ZIP, which it unpacks on its side.
 *
 * GET /api/gyankosh/published-notices[?date=YYYY-MM-DD]   (date defaults to today)
 *
 * The archive holds every notice created (created_at) on that day that has a
 * file, plus manifest.json describing each file (external_id,
 * external_version, title, kind, ...). A day with nothing created still
 * returns a ZIP, holding only a manifest with count 0.
 *
 * Eligibility is NoticeKnowledgeFeed::createdOn(), so personal /
 * course-scoped and deactivated notices never appear.
 */
class KnowledgeFeedController extends Controller
{
    public function __construct(private NoticeKnowledgeFeed $notices)
    {
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'invalid_date', 'messages' => $validator->errors()], 422);
        }
        $date = $validator->validated()['date'] ?? Carbon::today()->toDateString();

        $tmpPath = tempnam(sys_get_temp_dir(), 'gyankosh_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpPath);

            return response()->json(['error' => 'archive_failed'], 500);
        }

        $documents = [];
        foreach ($this->notices->createdOn($date) as $notice) {
            ['fields' => $fields, 'path' => $path, 'filename' => $filename] = $this->notices->describe($notice);

            // PDFs are already compressed; store rather than deflate.
            $zip->addFile($path, $filename);
            if (method_exists($zip, 'setCompressionName')) {
                $zip->setCompressionName($filename, \ZipArchive::CM_STORE);
            }

            $documents[] = $fields + [
                'file_name' => $filename,
                'file_size' => filesize($path),
                'file_sha256' => hash_file('sha256', $path),
            ];
        }

        $zip->addFromString('manifest.json', json_encode([
            'external_source' => config('knowledge.external_source'),
            'date' => $date,
            'generated_at' => now()->toIso8601String(),
            'count' => count($documents),
            'documents' => $documents,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $zip->close();

        Log::info('Knowledge feed: archive served', ['ip' => $request->ip(), 'date' => $date, 'count' => count($documents)]);

        return response()->download($tmpPath, 'sargam-notices-' . $date . '.zip', [
            'Content-Type' => 'application/zip',
            'X-Notice-Count' => (string) count($documents),
            'Cache-Control' => 'no-store',
        ])->deleteFileAfterSend(true);
    }
}
