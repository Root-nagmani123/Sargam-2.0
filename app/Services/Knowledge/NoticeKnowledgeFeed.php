<?php

namespace App\Services\Knowledge;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Which notices ज्ञानकोश may pull from Sargam, and how each one is described.
 *
 * A notice is "published" while it is active, its display date has come, it
 * has not expired, it carries a document file, and it is not held back by
 * config/knowledge.php (personal or course-scoped notices). The same rule
 * decides the dashboard feed (notice_feed_base_query()), so the knowledge base
 * never quotes a notice the portal itself no longer shows.
 *
 * external_version is a hash of exactly what is served (metadata + file), so it
 * changes when the notice is edited or its file replaced, and only then.
 */
class NoticeKnowledgeFeed
{
    public const TABLE = 'notices_notification';

    public function externalId(int $noticePk): string
    {
        return config('knowledge.notice_id_prefix', 'SARGAM-NOTICE-') . $noticePk;
    }

    /**
     * Every notice currently published.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function publishedNotices()
    {
        $today = Carbon::today()->toDateString();

        // Coarse SQL pre-filter; holdBackReason() is the authority.
        return $this->noticeQuery()
            ->where('n.active_inactive', 1)
            ->where('n.display_date', '<=', $today)
            ->where('n.expiry_date', '>=', $today)
            ->whereNotNull('n.document')
            ->where('n.document', '<>', '')
            ->orderBy('n.pk')
            ->get()
            ->filter(fn ($notice) => $this->holdBackReason($notice) === null)
            ->values();
    }

    /** One notice with the columns holdBackReason()/describe() need, or null. */
    public function findNotice(int $noticePk): ?object
    {
        return $this->noticeQuery()->where('n.pk', $noticePk)->first();
    }

    /** Null when the notice belongs in ज्ञानकोश, else why it does not. */
    public function holdBackReason(object $notice): ?string
    {
        $today = Carbon::today()->toDateString();

        if ((int) $notice->active_inactive !== 1) {
            return 'deactivated on Sargam';
        }
        if ($notice->display_date !== null && Carbon::parse($notice->display_date)->toDateString() > $today) {
            return 'not yet displayed on Sargam';
        }
        if ($notice->expiry_date !== null && Carbon::parse($notice->expiry_date)->toDateString() < $today) {
            return 'expired on Sargam';
        }
        if (in_array((string) $notice->notice_type, config('knowledge.notices.exclude_types', []), true)) {
            return 'notice type "' . $notice->notice_type . '" is not shared Academy-wide';
        }
        if (! empty($notice->course_master_pk) && ! config('knowledge.notices.include_course_notices')) {
            return 'course-specific notice is not shared Academy-wide';
        }
        if ($this->documentPath($notice) === null) {
            return 'no document file';
        }

        return null;
    }

    /**
     * The edition of a published notice: its metadata (external_version
     * included), the absolute file path and the file name to serve it under.
     * Call only when holdBackReason() is null.
     *
     * @return array{fields: array<string, string>, path: string, filename: string}
     */
    public function describe(object $notice): array
    {
        $path = $this->documentPath($notice);
        $fields = $this->fields($notice);
        $fields['external_version'] = $this->version($fields, $path);

        return [
            'fields' => $fields,
            'path' => $path,
            'filename' => $this->filename($notice, $path),
        ];
    }

    /** @return array<string, string> */
    private function fields(object $notice): array
    {
        $department = trim((string) $notice->author_department);

        $fields = [
            'external_id' => $this->externalId((int) $notice->pk),
            'title' => trim((string) $notice->notice_title),
            'kind' => config('knowledge.notices.kind_map.' . $notice->notice_type)
                ?? config('knowledge.notices.default_kind', 'other'),
            'issued_on' => $notice->display_date ? Carbon::parse($notice->display_date)->toDateString() : null,
            'issued_by' => $department,
            'section' => $department,
        ];

        return array_filter($fields, fn ($v) => $v !== null && $v !== '');
    }

    private function version(array $fields, string $path): string
    {
        ksort($fields);

        return substr(hash('sha256', json_encode($fields, JSON_UNESCAPED_UNICODE) . '|' . hash_file('sha256', $path)), 0, 16);
    }

    private function documentPath(object $notice): ?string
    {
        $document = trim((string) ($notice->document ?? ''));
        if ($document === '') {
            return null;
        }

        // Older rows may carry the "public/" prefix of the store('public/...') form.
        $relative = preg_replace('#^/?(storage/|public/)#', '', $document);
        $disk = Storage::disk('public');

        return $disk->exists($relative) ? $disk->path($relative) : null;
    }

    private function filename(object $notice, string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'pdf';
        $slug = Str::limit(Str::slug((string) $notice->notice_title), 80, '') ?: 'notice';

        return $slug . '-' . $notice->pk . '.' . $extension;
    }

    private function noticeQuery()
    {
        return DB::table(self::TABLE . ' as n')
            ->leftJoin('user_credentials as notice_author', 'notice_author.pk', '=', 'n.created_by')
            ->leftJoin('employee_master as notice_author_emp', 'notice_author_emp.pk', '=', 'notice_author.user_id')
            ->leftJoin('department_master as notice_author_dept', 'notice_author_dept.pk', '=', 'notice_author_emp.department_master_pk')
            ->select(
                'n.pk', 'n.notice_title', 'n.notice_type', 'n.document', 'n.display_date', 'n.expiry_date',
                'n.course_master_pk', 'n.active_inactive',
                'notice_author_dept.department_name as author_department'
            );
    }
}
