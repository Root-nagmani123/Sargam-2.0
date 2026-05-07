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

        // Handle old data saved as "storage/app/public/..."
        $path = preg_replace('#^/?storage/app/public/#', '', $path);

        // Handle old data saved as "/storage/..."
        $path = preg_replace('#^/?storage/#', '', $path);

        return ltrim($path, '/');
    }

    /**
     * Public URL for browser download/view links.
     */
    public function getPublicFileUrlAttribute()
    {
        if (!$this->normalized_full_path) {
            return null;
        }

        return asset('storage/' . $this->normalized_full_path);
    }
}
