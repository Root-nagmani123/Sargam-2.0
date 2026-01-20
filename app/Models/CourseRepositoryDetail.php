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
        'program_structure_pk',
        'subject_pk',
        'detail_document',
        'topic_pk',
        'session_date',
        'author_name',
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
        return $this->belongsTo(User::class, 'created_by', 'id');
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
        return $this->belongsTo(CourseMaster::class, 'program_structure_pk', 'pk');
    }

    /**
     * Relationship with SubjectMaster (Major Subject Name)
     */
    public function subject()
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_pk', 'pk');
    }

    /**
     * Relationship with CourseRepositorySubtopic (Topic Name)
     */
    public function topic()
    {
        return $this->belongsTo(CourseRepositorySubtopic::class, 'topic_pk', 'pk');
    }

    /**
     * Scope: Get only active details
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
