<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRepositoryMaster extends Model
{
    protected $table = 'course_repository_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'course_repository_name',
        'folder_type',
        'parent_type',
        'file_type',
        'full_path',
        'course_repository_details',
        'created_date',
        'modify_date',
        'created_by',
        'modify_by',
        'status',
        'del_folder_status',
        'del_folder_date',
        'delete_by',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
        'del_folder_date' => 'datetime',
    ];

    /**
     * Relationship with CourseRepositoryDetails
     */
    public function details()
    {
        return $this->hasMany(CourseRepositoryDetail::class, 'course_repository_master_pk', 'pk');
    }

    /**
     * Relationship with CourseRepositoryDocument
     */
    public function documents()
    {
        return $this->hasMany(CourseRepositoryDocument::class, 'course_repository_master_pk', 'pk');
    }

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(FacultyMaster::class, 'author_name', 'pk');
    }

    /**
     * Relationship with User (modifier)
     */
    public function modifier()
    {
        return $this->belongsTo(User::class, 'modify_by', 'pk');
    }

    /**
     * Relationship with Parent Repository (self-referencing)
     */
    public function parent()
    {
        return $this->belongsTo(CourseRepositoryMaster::class, 'parent_type', 'pk');
    }

    /**
     * Relationship with Child Repositories (self-referencing)
     */
    public function children()
    {
        return $this->hasMany(CourseRepositoryMaster::class, 'parent_type', 'pk')
            ->where('del_folder_status', 1);
    }

    /**
     * Get all descendants recursively
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Get total document count for this repository
     * Documents can be linked directly via course_repository_master_pk
     * OR through CourseRepositoryDetail via course_repository_details_pk
     */
    public function getDocumentCount()
    {
        // Count documents linked directly to this repository
        $directCount = $this->documents()->where('del_type', 1)->count();
        
        // Count documents linked through details
        $detailsCount = CourseRepositoryDocument::where('del_type', 1)
            ->whereIn('course_repository_details_pk', function($query) {
                $query->select('pk')
                    ->from('course_repository_details')
                    ->where('course_repository_master_pk', $this->pk);
            })
            ->count();
        
        return $directCount + $detailsCount;
    }

    /**
     * Get total document count including all child repositories
     */
    public function getTotalDocumentCount()
    {
        // Count documents for this repository
        $count = $this->getDocumentCount();
        
        // Add documents from all child repositories recursively
        foreach ($this->children as $child) {
            $count += $child->getTotalDocumentCount();
        }
        
        return $count;
    }
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
        return $this->belongsTo(Timetable::class, 'topic_pk', 'pk');
    }

    /**
     * Scope: Get only active details
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
