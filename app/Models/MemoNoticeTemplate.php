<?php
// app/Models/MemoNoticeTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemoNoticeTemplate extends Model
{
    use HasFactory;

    protected $table = 'memo_notice_templates';
    protected $primaryKey = 'pk';   

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';

    protected $dates = [
        'created_date',
        'updated_date',
        'deleted_date',
    ];

    protected $guarded = []; 

    // Relationships
    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeForCourse($query, $courseId)
    {
        if ($courseId) {
            return $query->where('course_master_pk', $courseId);
        }
        return $query;
    }
}
