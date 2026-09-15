<?php

namespace App\Support;

use App\Models\CourseRepositoryDocument;
use App\Models\CourseRepositoryMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Query layer for the user-facing "search everything" page of the Course Repository.
 *
 * Why this searches raw text columns rather than the model relations:
 * course_repository_details stores the batch, subject, topic and author as TEXT in
 * columns named *_pk (course_master_pk = "Phase-I 2024", subject_pk = "Law", ...).
 * Only a legacy minority hold numeric ids, and those ids come from the system the
 * data was imported from - they resolve to nothing in course_master / subject_master
 * / timetable / faculty_master. So belongsTo('course'), ('subject'), ('topic') and
 * ('author') are null for effectively every row, and anything built on them finds
 * nothing. The `keyword` column ("batch,subject,topic,dd-mm-yyyy,author") is the only
 * readable source for those legacy rows, and is therefore searched and parsed here.
 */
class CourseRepositorySearch
{
    public const TYPE_ALL = 'all';
    public const TYPE_DOCUMENTS = 'documents';
    public const TYPE_VIDEOS = 'videos';
    public const TYPE_CATEGORIES = 'categories';

    public const TYPES = [
        self::TYPE_ALL => 'Everything',
        self::TYPE_DOCUMENTS => 'Documents',
        self::TYPE_VIDEOS => 'Videos',
        self::TYPE_CATEGORIES => 'Categories',
    ];

    public const SORT_RELEVANCE = 'relevance';
    public const SORT_NEWEST = 'newest';
    public const SORT_OLDEST = 'oldest';
    public const SORT_TITLE = 'title';

    public const SORTS = [
        self::SORT_RELEVANCE => 'Best match',
        self::SORT_NEWEST => 'Newest first',
        self::SORT_OLDEST => 'Oldest first',
        self::SORT_TITLE => 'Title (A-Z)',
    ];

    /** Upper bound on search words, so the generated relevance SQL stays bounded. */
    public const MAX_TOKENS = 6;

    /** Categories shown alongside document hits when searching Everything. */
    public const CATEGORY_PREVIEW_LIMIT = 8;

    /** pk => ['name' => string, 'parent' => ?int]; loaded once per request. */
    private static ?array $folderTree = null;

    /** parent pk => int[] child pks; derived from the folder tree. */
    private static ?array $folderChildren = null;

    /**
     * Read and normalize every search input from the query string.
     */
    public static function criteria(Request $request): array
    {
        $q = DataTableSearchHelper::normalizeRaw(self::scalar($request, 'q'));
        $tokens = array_slice(DataTableSearchHelper::tokens($q), 0, self::MAX_TOKENS);

        $type = self::scalar($request, 'type', self::TYPE_ALL);
        if (! array_key_exists($type, self::TYPES)) {
            $type = self::TYPE_ALL;
        }

        $sort = self::scalar($request, 'sort', self::SORT_RELEVANCE);
        if (! array_key_exists($sort, self::SORTS)) {
            $sort = self::SORT_RELEVANCE;
        }

        // "Best match" is meaningless without words to match; fall back to newest so
        // a bare filter-only search still comes back in a sensible order.
        if ($sort === self::SORT_RELEVANCE && $tokens === []) {
            $sort = self::SORT_NEWEST;
        }

        $folder = $request->query('folder');
        $folder = is_numeric($folder) ? (int) $folder : null;

        $perPage = (int) self::scalar($request, 'per_page', '25');
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $year = self::scalar($request, 'year');

        return [
            'q' => $q,
            'tokens' => $tokens,
            'type' => $type,
            'sort' => $sort,
            'course' => trim(self::scalar($request, 'course')),
            'subject' => trim(self::scalar($request, 'subject')),
            'author' => DataTableSearchHelper::normalizeRaw(self::scalar($request, 'author')),
            'year' => preg_match('/^\d{4}$/', $year) ? $year : '',
            'from' => self::normalizeDate(self::scalar($request, 'from')),
            'to' => self::normalizeDate(self::scalar($request, 'to')),
            'folder' => $folder,
            'per_page' => $perPage,
        ];
    }

    /**
     * One query-string value as a plain string.
     *
     * A query string can carry arrays (?q[]=law), and casting one to string is a
     * PHP error — which turned a malformed or hand-edited URL into an error page
     * instead of an empty search. Nothing here ever wants a list, so the first
     * scalar found is used and anything else falls back to the default.
     */
    private static function scalar(Request $request, string $key, string $default = ''): string
    {
        $value = $request->query($key, $default);

        while (is_array($value)) {
            $value = reset($value);
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * True when the user gave the search something to work with. An empty page is
     * rendered as a prompt rather than as "no results", which would be a lie.
     *
     * Picking a tab counts: clicking "Videos" with an empty box is a request to see
     * the videos, so it lists them rather than falling back to the prompt.
     */
    public static function hasQuery(array $c): bool
    {
        return $c['q'] !== ''
            || $c['type'] !== self::TYPE_ALL
            || $c['course'] !== ''
            || $c['subject'] !== ''
            || $c['author'] !== ''
            || $c['year'] !== ''
            || $c['from'] !== ''
            || $c['to'] !== ''
            || $c['folder'] !== null;
    }

    /** Filters that describe a document/session and therefore cannot narrow folders. */
    public static function hasDocumentFilters(array $c): bool
    {
        return $c['course'] !== ''
            || $c['subject'] !== ''
            || $c['author'] !== ''
            || $c['year'] !== ''
            || $c['from'] !== ''
            || $c['to'] !== '';
    }

    /** The active refinements, as removable chips. */
    public static function activeChips(array $c): array
    {
        $chips = [];

        if ($c['course'] !== '') {
            $chips[] = ['key' => 'course', 'label' => 'Course', 'value' => $c['course']];
        }
        if ($c['subject'] !== '') {
            $chips[] = ['key' => 'subject', 'label' => 'Subject', 'value' => $c['subject']];
        }
        if ($c['author'] !== '') {
            $chips[] = ['key' => 'author', 'label' => 'Author', 'value' => $c['author']];
        }
        if ($c['year'] !== '') {
            $chips[] = ['key' => 'year', 'label' => 'Year', 'value' => $c['year']];
        }
        if ($c['from'] !== '') {
            $chips[] = ['key' => 'from', 'label' => 'From', 'value' => $c['from']];
        }
        if ($c['to'] !== '') {
            $chips[] = ['key' => 'to', 'label' => 'To', 'value' => $c['to']];
        }
        if ($c['folder'] !== null) {
            $trail = self::folderPath($c['folder']);
            $chips[] = [
                'key' => 'folder',
                'label' => 'In category',
                'value' => $trail === [] ? ('#' . $c['folder']) : end($trail),
            ];
        }

        return $chips;
    }

    // ---------------------------------------------------------------- documents

    /**
     * Every stored file, joined to the session record that describes it.
     *
     * Documents are the base table (not details): a detail row without a document has
     * neither a file nor a video link, so there would be nothing to open. The joins
     * are LEFT so the handful of documents whose detail or folder row is missing still
     * come back rather than silently disappearing from results.
     */
    public static function documentQuery(array $c): Builder
    {
        $query = CourseRepositoryDocument::query()
            ->from('course_repository_documents as doc')
            ->leftJoin('course_repository_details as dt', 'dt.pk', '=', 'doc.course_repository_details_pk')
            ->leftJoin('course_repository_master as m', 'm.pk', '=', 'dt.course_repository_master_pk')
            ->where('doc.del_type', 1)
            ->select([
                'doc.*',
                'dt.pk as detail_pk',
                'dt.course_repository_master_pk as detail_master_pk',
                'dt.course_master_pk as raw_course',
                'dt.subject_pk as raw_subject',
                'dt.topic_pk as raw_topic',
                'dt.author_name as raw_author',
                'dt.keyword as raw_keyword',
                'dt.session_date as raw_session_date',
                'dt.videolink as raw_videolink',
                'm.course_repository_name as folder_name',
            ]);

        foreach ($c['tokens'] as $token) {
            $like = DataTableSearchHelper::likePattern($token);
            $query->where(function ($q) use ($like) {
                $q->where('doc.file_title', 'like', $like)
                    ->orWhere('doc.upload_document', 'like', $like)
                    ->orWhere('dt.keyword', 'like', $like)
                    ->orWhere('dt.course_master_pk', 'like', $like)
                    ->orWhere('dt.subject_pk', 'like', $like)
                    ->orWhere('dt.topic_pk', 'like', $like)
                    ->orWhere('dt.author_name', 'like', $like)
                    ->orWhere('m.course_repository_name', 'like', $like)
                    // Dates are searchable the way they are displayed (18-03-2025),
                    // not the way they are stored (2025-03-18).
                    ->orWhereRaw("DATE_FORMAT(dt.session_date, '%d-%m-%Y') like ?", [$like]);
            });
        }

        if ($c['course'] !== '') {
            $query->where('dt.course_master_pk', $c['course']);
        }
        if ($c['subject'] !== '') {
            $query->where('dt.subject_pk', $c['subject']);
        }
        if ($c['author'] !== '') {
            $authorLike = DataTableSearchHelper::likePattern($c['author']);
            $query->where(function ($q) use ($authorLike) {
                $q->where('dt.author_name', 'like', $authorLike)
                    ->orWhere('dt.keyword', 'like', $authorLike);
            });
        }
        if ($c['year'] !== '') {
            $query->whereYear('dt.session_date', $c['year']);
        }
        if ($c['from'] !== '') {
            $query->whereDate('dt.session_date', '>=', $c['from']);
        }
        if ($c['to'] !== '') {
            $query->whereDate('dt.session_date', '<=', $c['to']);
        }

        if ($c['folder'] !== null) {
            $subtree = self::folderSubtreePks($c['folder']);
            $query->where(function ($q) use ($subtree) {
                $q->whereIn('dt.course_repository_master_pk', $subtree)
                    ->orWhereIn('doc.course_repository_master_pk', $subtree);
            });
        }

        if ($c['type'] === self::TYPE_VIDEOS) {
            $query->whereNotNull('dt.videolink')->where('dt.videolink', '!=', '');
        } elseif ($c['type'] === self::TYPE_DOCUMENTS) {
            $query->whereNotNull('doc.upload_document')->where('doc.upload_document', '!=', '');
        }

        self::applySort($query, $c);

        return $query;
    }

    /**
     * Order results. "Best match" scores each hit in SQL so the ranking survives
     * pagination - ordering a single page in PHP would rank 25 arbitrary rows.
     */
    private static function applySort(Builder $query, array $c): void
    {
        switch ($c['sort']) {
            case self::SORT_OLDEST:
                $query->orderByRaw('dt.session_date is null')
                    ->orderBy('dt.session_date')
                    ->orderBy('doc.pk');
                break;

            case self::SORT_TITLE:
                $query->orderByRaw("coalesce(nullif(doc.file_title, ''), doc.upload_document)")
                    ->orderBy('doc.pk');
                break;

            case self::SORT_RELEVANCE:
                [$sql, $bindings] = self::relevanceExpression($c['tokens'], $c['q']);
                $query->selectRaw($sql, $bindings)
                    ->orderByDesc('cru_relevance')
                    ->orderByRaw('dt.session_date is null')
                    ->orderByDesc('dt.session_date')
                    ->orderByDesc('doc.pk');
                break;

            case self::SORT_NEWEST:
            default:
                $query->orderByRaw('dt.session_date is null')
                    ->orderByDesc('dt.session_date')
                    ->orderByDesc('doc.pk');
                break;
        }
    }

    /**
     * Weighted score: a word in the file title or topic says much more about a hit
     * than the same word buried in the comma-joined keyword blob, and a hit on the
     * whole phrase says more than a hit on its separate words.
     *
     * @param  string[]  $tokens
     * @return array{0:string,1:array<int,string>}  [sql, bindings] for selectRaw()
     */
    private static function relevanceExpression(array $tokens, string $phrase): array
    {
        $weights = [
            'doc.file_title' => 5,
            'dt.topic_pk' => 4,
            'dt.subject_pk' => 3,
            'dt.course_master_pk' => 3,
            'dt.author_name' => 3,
            'doc.upload_document' => 2,
            'm.course_repository_name' => 1,
            'dt.keyword' => 1,
        ];

        $parts = [];
        $bindings = [];

        foreach ($tokens as $token) {
            $like = DataTableSearchHelper::likePattern($token);
            foreach ($weights as $column => $weight) {
                $parts[] = "(case when {$column} like ? then {$weight} else 0 end)";
                $bindings[] = $like;
            }
        }

        if (count($tokens) > 1 && $phrase !== '') {
            $phraseLike = DataTableSearchHelper::likePattern($phrase);
            foreach (['doc.file_title' => 20, 'dt.topic_pk' => 15] as $column => $weight) {
                $parts[] = "(case when {$column} like ? then {$weight} else 0 end)";
                $bindings[] = $phraseLike;
            }
        }

        if ($parts === []) {
            return ['0 as cru_relevance', []];
        }

        return [implode(' + ', $parts) . ' as cru_relevance', $bindings];
    }

    /**
     * Attach the display fields each result row needs. Kept out of the Blade so the
     * legacy-data rules below live in exactly one place.
     *
     * @param  iterable<CourseRepositoryDocument>  $documents
     */
    public static function decorate(iterable $documents): void
    {
        foreach ($documents as $doc) {
            $keyword = self::parseKeyword((string) ($doc->raw_keyword ?? ''));

            $doc->display_course = self::pickText($doc->raw_course, $keyword['course']);
            $doc->display_subject = self::pickText($doc->raw_subject, $keyword['subject']);
            $doc->display_topic = self::pickText($doc->raw_topic, $keyword['topic']);
            $doc->display_author = self::pickText($doc->raw_author, $keyword['author']);

            $title = trim((string) $doc->file_title);
            $doc->display_title = $title !== ''
                ? $title
                : (trim((string) $doc->upload_document) ?: 'Untitled document');

            $doc->display_date = self::formatDate($doc->raw_session_date);

            $folderPk = $doc->detail_master_pk ?? $doc->course_repository_master_pk;
            $doc->folder_pk = $folderPk !== null ? (int) $folderPk : null;
            $doc->folder_trail = $doc->folder_pk !== null ? self::folderPath($doc->folder_pk) : [];

            $video = trim((string) ($doc->raw_videolink ?? ''));
            $doc->display_video = $video !== '' ? $video : null;
        }
    }

    /**
     * Split the legacy "batch,subject,topic,dd-mm-yyyy,author" keyword blob.
     *
     * A plain explode() on the comma gets the topic and author wrong whenever the
     * topic itself contains a comma, which is common ("Balancing Development, Forest
     * Dweller Rights & Biodversity Conservation"). The date segment is an unambiguous
     * anchor, so it is located first and the topic taken as everything between the
     * subject and the date, the author as everything after it.
     *
     * @return array{course:?string,subject:?string,topic:?string,author:?string}
     */
    public static function parseKeyword(string $keyword): array
    {
        if (trim($keyword) === '') {
            return ['course' => null, 'subject' => null, 'topic' => null, 'author' => null];
        }

        $parts = array_map('trim', explode(',', $keyword));

        $dateIndex = null;
        foreach ($parts as $i => $part) {
            if ($i >= 2 && preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $part)) {
                $dateIndex = $i;
                break;
            }
        }

        if ($dateIndex === null) {
            // No date to anchor on: fall back to fixed positions.
            return [
                'course' => $parts[0] ?? null,
                'subject' => $parts[1] ?? null,
                'topic' => isset($parts[2]) ? implode(', ', array_slice($parts, 2)) : null,
                'author' => $parts[4] ?? null,
            ];
        }

        $topic = implode(', ', array_slice($parts, 2, $dateIndex - 2));
        $author = implode(', ', array_slice($parts, $dateIndex + 1));

        return [
            'course' => $parts[0] ?? null,
            'subject' => $parts[1] ?? null,
            'topic' => $topic !== '' ? $topic : null,
            'author' => $author !== '' ? $author : null,
        ];
    }

    /**
     * Prefer the stored column, but reject a bare number: those are ids from the
     * source system that resolve to nothing here, so showing one would put
     * "169438950" on screen where a subject name belongs.
     */
    private static function pickText($value, ?string $fallback): ?string
    {
        $value = trim((string) $value);

        if ($value !== '' && ! ctype_digit($value)) {
            return $value;
        }

        $fallback = trim((string) $fallback);

        return $fallback !== '' ? $fallback : null;
    }

    // --------------------------------------------------------------- categories

    /**
     * Folders whose full path matches every search word.
     *
     * Matched in PHP against the path ("Foundation Course > Week 04") rather than in
     * SQL against the name, so a multi-word search still finds a folder whose own
     * name is only one of those words. The whole tree is ~2k short rows.
     *
     * @return Collection<int,array{pk:int,name:string,trail:string[]}>
     */
    public static function categoryMatches(array $c): Collection
    {
        $tree = self::folderTree();

        $allowed = $c['folder'] !== null ? array_flip(self::folderSubtreePks($c['folder'])) : null;

        $matches = [];
        foreach ($tree as $pk => $node) {
            if ($allowed !== null && ! isset($allowed[$pk])) {
                continue;
            }

            $trail = self::folderPath($pk);

            if (! DataTableSearchHelper::haystackMatchesAllTokens(implode(' ', $trail), $c['tokens'])) {
                continue;
            }

            $matches[] = [
                'pk' => $pk,
                'name' => $node['name'],
                'trail' => $trail,
            ];
        }

        // Shallow folders first (a course sits above its weeks), then alphabetically.
        usort($matches, function ($a, $b) {
            return [count($a['trail']), mb_strtolower($a['name'])]
                <=> [count($b['trail']), mb_strtolower($b['name'])];
        });

        return collect($matches);
    }

    /**
     * Page a collection that was filtered in PHP, under its own page parameter so
     * category paging does not reset the document results.
     *
     * @param  Collection<int,mixed>  $items
     */
    public static function paginateCollection(Collection $items, int $perPage, string $pageName = 'cpage'): LengthAwarePaginator
    {
        $page = max(1, (int) Paginator::resolveCurrentPage($pageName));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
        );
    }

    /**
     * How many documents each of the given folders holds, counting its sub-folders.
     *
     * The subtree, not the folder's own files: the tree stores documents on the leaves
     * ("Week 04"), so an own-count reports 0 for exactly the folders a searcher is most
     * likely to have matched ("Phase-I 2024"), which reads as "this category is empty"
     * when it is anything but. The browse cards deliberately show own-counts instead,
     * because they sit next to a separate sub-category count.
     *
     * One grouped query for the whole corpus (~2k groups), summed per subtree in PHP —
     * cheaper and simpler than a query per folder or a huge IN list of descendant pks.
     *
     * @param  int[]  $pks
     * @return array<int,int>
     */
    public static function documentCounts(array $pks): array
    {
        $pks = array_values(array_unique(array_filter($pks)));
        if ($pks === []) {
            return [];
        }

        $folderExpression = 'coalesce(dt.course_repository_master_pk, doc.course_repository_master_pk)';

        $perFolder = CourseRepositoryDocument::query()
            ->from('course_repository_documents as doc')
            ->leftJoin('course_repository_details as dt', 'dt.pk', '=', 'doc.course_repository_details_pk')
            ->where('doc.del_type', 1)
            ->whereNotNull(DB::raw($folderExpression))
            ->selectRaw($folderExpression . ' as folder_pk, count(*) as cnt')
            ->groupBy('folder_pk')
            ->pluck('cnt', 'folder_pk');

        $out = [];
        foreach ($pks as $pk) {
            $total = 0;
            foreach (self::folderSubtreePks((int) $pk) as $descendant) {
                $total += (int) ($perFolder[$descendant] ?? 0);
            }
            $out[(int) $pk] = $total;
        }

        return $out;
    }

    /**
     * A folder path short enough to read at a glance.
     *
     * Trails run root-first ("Central Course Repository of LBSNAA / MCTP / Phase-V /
     * Phase-V 2016 / Class Materials / Week 01") and the useful end is the last one —
     * plain truncation cuts off exactly the part that says where the file actually is,
     * and leaves every row starting with the same shared prefix. The full path stays
     * available as the link's title.
     *
     * @param  string[]  $trail
     */
    public static function trailLabel(array $trail, int $keep = 3): string
    {
        $trail = array_values(array_filter($trail, fn ($segment) => trim((string) $segment) !== ''));

        if (count($trail) <= $keep) {
            return implode(' / ', $trail);
        }

        return '… / ' . implode(' / ', array_slice($trail, -$keep));
    }

    // ------------------------------------------------------------- folder tree

    /** @return array<int,array{name:string,parent:?int}> */
    public static function folderTree(): array
    {
        if (self::$folderTree !== null) {
            return self::$folderTree;
        }

        $tree = [];
        CourseRepositoryMaster::query()
            ->where('del_folder_status', 1)
            ->orderBy('pk')
            ->get(['pk', 'course_repository_name', 'parent_type'])
            ->each(function ($row) use (&$tree) {
                $tree[(int) $row->pk] = [
                    'name' => trim((string) $row->course_repository_name),
                    'parent' => $row->parent_type ? (int) $row->parent_type : null,
                ];
            });

        return self::$folderTree = $tree;
    }

    /**
     * Folder names from the root down to $pk.
     *
     * @return string[]
     */
    public static function folderPath(?int $pk): array
    {
        $tree = self::folderTree();

        $trail = [];
        $seen = [];
        $current = $pk;

        // parent_type is self-referencing and unconstrained, so a bad row could point
        // at itself or form a cycle. $seen makes that a truncated path, not a hang.
        while ($current !== null && isset($tree[$current]) && ! isset($seen[$current])) {
            $seen[$current] = true;
            array_unshift($trail, $tree[$current]['name']);
            $current = $tree[$current]['parent'];
        }

        return $trail;
    }

    /**
     * A folder and everything beneath it, so "search in this category" reaches the
     * documents filed in its sub-folders too.
     *
     * @return int[]
     */
    public static function folderSubtreePks(int $pk): array
    {
        if (self::$folderChildren === null) {
            $children = [];
            foreach (self::folderTree() as $childPk => $node) {
                if ($node['parent'] !== null) {
                    $children[$node['parent']][] = $childPk;
                }
            }
            self::$folderChildren = $children;
        }

        $out = [];
        $stack = [$pk];
        while ($stack !== []) {
            $current = array_pop($stack);
            if (isset($out[$current])) {
                continue;
            }
            $out[$current] = true;
            foreach (self::$folderChildren[$current] ?? [] as $child) {
                $stack[] = $child;
            }
        }

        return array_keys($out);
    }

    // ------------------------------------------------------------------ facets

    /**
     * Dropdown values for the refinement controls, read from the repository data
     * itself. course_master / subject_master are deliberately not used: their pks do
     * not match what is stored here, so those lists would filter nothing.
     *
     * @return array{courses:string[],subjects:string[],years:int[]}
     */
    public static function facets(): array
    {
        return [
            'courses' => self::distinctText('course_master_pk'),
            'subjects' => self::distinctText('subject_pk'),
            'years' => DB::table('course_repository_details')
                ->whereNotNull('session_date')
                ->selectRaw('distinct year(session_date) as y')
                ->orderByDesc('y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y)
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /** @return string[] */
    private static function distinctText(string $column): array
    {
        return DB::table('course_repository_details')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->whereRaw("{$column} not regexp '^[0-9]+$'")
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    // ---------------------------------------------------------------- rendering

    /**
     * Escape $text, then wrap each matched search word in <mark>.
     *
     * Splitting the raw text and escaping the pieces (rather than escaping first and
     * matching the result) keeps a search for "&" from matching the innards of an
     * HTML entity produced by escaping something else.
     *
     * @param  string[]  $tokens
     */
    public static function highlight($text, array $tokens, int $limit = 0): HtmlString
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new HtmlString('');
        }

        if ($limit > 0) {
            $text = Str::limit($text, $limit);
        }

        $tokens = array_values(array_filter(array_map('strval', $tokens), fn ($t) => trim($t) !== ''));

        if ($tokens === []) {
            return new HtmlString(e($text));
        }

        // Longest first, so "public administration" highlights as one run rather than
        // the shorter word carving it up.
        usort($tokens, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $pattern = '/(' . implode('|', array_map(fn ($t) => preg_quote($t, '/'), $tokens)) . ')/iu';

        $pieces = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($pieces === false) {
            return new HtmlString(e($text));
        }

        $html = '';
        foreach ($pieces as $i => $piece) {
            // preg_quote leaves no capture groups of its own, so the single group above
            // puts every match at an odd index.
            $html .= $i % 2 === 1
                ? '<mark class="cru-hl">' . e($piece) . '</mark>'
                : e($piece);
        }

        return new HtmlString($html);
    }

    // ----------------------------------------------------------------- helpers

    private static function normalizeDate($value): string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function formatDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000')) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
