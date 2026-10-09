<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Knowledge\NoticeKnowledgeFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Pull feed for ज्ञानकोश: their server polls Sargam for published notices.
 *
 * GET /api/knowledge/notices is a full snapshot, not a change log. The caller
 * compares it with what it holds:
 *   - external_id it does not hold, or a different external_version → fetch
 *     file_url and store it;
 *   - external_id it holds that is missing here → withdraw it.
 * Publishing, editing, deactivating, expiring and deleting all show up without
 * Sargam having to call anyone.
 *
 * Eligibility is NoticeKnowledgeFeed::holdBackReason(), so personal /
 * course-scoped notices never appear and the file endpoint answers 404 for
 * anything not currently published.
 */
class KnowledgeFeedController extends Controller
{
    public function __construct(private NoticeKnowledgeFeed $notices)
    {
    }

    public function index(Request $request)
    {
        $documents = $this->notices->publishedNotices()->map(function ($notice) {
            ['fields' => $fields, 'path' => $path, 'filename' => $filename] = $this->notices->describe($notice);

            return $fields + [
                'file_name' => $filename,
                'file_size' => filesize($path),
                'file_sha256' => hash_file('sha256', $path),
                'file_url' => route('api.knowledge.notices.file', ['notice' => $notice->pk]),
            ];
        })->values();

        Log::info('Knowledge feed: snapshot served', ['ip' => $request->ip(), 'count' => $documents->count()]);

        return response()->json([
            'external_source' => config('knowledge.external_source'),
            'generated_at' => now()->toIso8601String(),
            'count' => $documents->count(),
            'documents' => $documents,
        ]);
    }

    public function file(Request $request, int $notice)
    {
        $row = $this->notices->findNotice($notice);

        if ($row === null || $this->notices->holdBackReason($row) !== null) {
            return response()->json(['error' => 'not_published'], 404);
        }

        ['fields' => $fields, 'path' => $path, 'filename' => $filename] = $this->notices->describe($row);

        Log::info('Knowledge feed: file served', [
            'ip' => $request->ip(),
            'external_id' => $fields['external_id'],
            'external_version' => $fields['external_version'],
        ]);

        return response()->download($path, $filename, [
            'X-External-Id' => $fields['external_id'],
            'X-External-Version' => $fields['external_version'],
            'Cache-Control' => 'no-store',
        ]);
    }
}
