<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRepositoryDetail extends Model
{
    protected $table = 'course_repository_details'; 
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'course_repository_master_pk',
        'course_repository_type',
        'course_master_pk',
        'subject_pk',
        'detail_document',
        'topic_pk',
        'session_date',
        'author_name',
        'sector_master_pk',
        'ministry_master_pk',
        'keyword',
        'created_date',
        'created_by',
        'modify_by',
        'modify_date',
        'status',
        'type',
        'videolink',
    ];

    protected $casts = [
        'session_date' => 'date',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Relationship with CourseRepositoryMaster
     */
    public function master()
    {
        return $this->belongsTo(CourseRepositoryMaster::class, 'course_repository_master_pk', 'pk');
    }

    /**
     * Relationship with CourseRepositoryDocument
     */
    public function documents()
    {
        return $this->hasMany(CourseRepositoryDocument::class, 'course_repository_details_pk', 'pk');
    }

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'pk');
    }

    /**
     * Relationship with User (modifier)
     */
    public function modifier()
    {
        return $this->belongsTo(User::class, 'modify_by', 'id');
    }

    /**
     * Relationship with CourseMaster (Program/Course)
     */
    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    /**
     * Relationship with SubjectMaster (Major Subject Name)
     */
    public function subject()
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_pk', 'pk');
    }

    /**
     * Relationship with Timetable (Topic Name)
     */
    public function topic()
    {
        return $this->belongsTo(Timetable::class, 'topic_pk', 'pk');
    }

    /**
     * Relationship with FacultyMaster (Author Name - when it's a PK)
     */
    public function author()
    {
        return $this->belongsTo(FacultyMaster::class, 'author_name', 'pk');
    }

    /**
     * Relationship with SectorMaster
     */
    public function sector()
    {
        return $this->belongsTo(SectorMaster::class, 'sector_master_pk', 'pk');
    }

    /**
     * Relationship with MinistryMaster
     */
    public function ministry()
    {
        return $this->belongsTo(MinistryMaster::class, 'ministry_master_pk', 'pk');
    }

    /**
     * Scope: Get only active details
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * iframe-friendly URL for stored video links (YouTube, Vimeo, or direct URL).
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        return self::toVideoEmbedUrl($this->videolink);
    }

    public static function toVideoEmbedUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('#(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/|youtube\.com/embed/)([a-zA-Z0-9_-]{11})#i', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#i', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return $url;
    }

    public function getMetadataAttribute(): array
    {
        $raw = trim((string) ($this->detail_document ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    public function getSubjectDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('subject') && $this->subject && !empty($this->subject->subject_name)) {
            return (string) $this->subject->subject_name;
        }

        $metaSubject = trim((string) ($this->metadata['other_subject'] ?? ''));
        if ($metaSubject !== '') {
            return $metaSubject;
        }

        $raw = trim((string) ($this->subject_pk ?? ''));
        return ($raw !== '' && !is_numeric($raw)) ? $raw : 'N/A';
    }

    public function getTopicDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('topic') && $this->topic && !empty($this->topic->subject_topic)) {
            return (string) $this->topic->subject_topic;
        }

        $metaTopic = trim((string) ($this->metadata['other_topic'] ?? ''));
        if ($metaTopic !== '') {
            return $metaTopic;
        }

        $raw = trim((string) ($this->topic_pk ?? ''));
        return ($raw !== '' && !is_numeric($raw)) ? $raw : 'N/A';
    }

    public function getAuthorDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('author') && $this->author && !empty($this->author->full_name)) {
            return (string) $this->author->full_name;
        }

        $metaAuthor = trim((string) ($this->metadata['other_author'] ?? ''));
        if ($metaAuthor !== '') {
            return $metaAuthor;
        }

        $raw = trim((string) ($this->author_name ?? ''));
        if ($raw === '') {
            return 'N/A';
        }

        if (is_numeric($raw)) {
            $faculty = FacultyMaster::select('full_name')->find((int) $raw);
            return $faculty && !empty($faculty->full_name) ? (string) $faculty->full_name : 'N/A';
        }

        return $raw;
    }
}
