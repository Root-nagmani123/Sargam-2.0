<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRepositoryDocument extends Model
{
    protected $table = 'course_repository_documents';
    protected $primaryKey = 'pk';
    public $timestamps = false; 

    protected $fillable = [
        'upload_document',
        'course_repository_details_pk',
        'course_repository_master_pk',
        'course_repository_type',
        'file_title',
        'del_type',
        'deleted_date',
        'deleted_by',
        'full_path',
    ];

    protected $casts = [
        'deleted_date' => 'datetime',
    ];

    /**
     * Relationship with CourseRepositoryMaster
     */
    public function master()
    {
        return $this->belongsTo(CourseRepositoryMaster::class, 'course_repository_master_pk', 'pk');
    }

    /**
     * Relationship with CourseRepositoryDetail
     */
    public function detail()
    {
        return $this->belongsTo(CourseRepositoryDetail::class, 'course_repository_details_pk', 'pk');
    }

    /**
     * Relationship with User (deleter)
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    /**
     * Scope: Get only active documents
     */
    public function scopeActive($query)
    {
        return $query->where('del_type', 1);
    }

    /**
     * Scope: Get deleted documents
     */
    public function scopeDeleted($query)
    {
        return $query->where('del_type', 0);
    }

    /**
     * Normalize stored path so both old/new records work.
     */
    public function getNormalizedFullPathAttribute()
    {
        $path = trim((string) $this->full_path);

        if ($path === '') {
            return null;
        }

        // Handle full URL values like "https://domain/storage/..."
        $path = preg_replace('#^https?://[^/]+/#i', '', $path);

        // Handle old data saved as "storage/app/public/..."
        $path = preg_replace('#^/?storage/app/public/#', '', $path);

        // Handle old data saved as "/storage/..."
        $path = preg_replace('#^/?storage/#', '', $path);

        // Handle old data saved as "app/public/..."
        $path = preg_replace('#^/?app/public/#', '', $path);

        // Windows-style paths
        $path = str_replace('\\', '/', $path);

        return ltrim($path, '/');
    }

    /**
     * URL for browser download/view links.
     *
     * Deliberately NOT asset('storage/…') any more. These documents are
     * access-controlled, and anything under public/storage is served by the web
     * server before Laravel loads — so that URL was readable without a session.
     * Points at the authenticated stream route instead; the name is kept because
     * several Blades and JSON payloads already read it.
     */
    public function getPublicFileUrlAttribute()
    {
        if (!$this->normalized_full_path && !$this->full_path) {
            return null;
        }

        return route('course-repository.document.stream', ['pk' => $this->pk]);
    }
}
