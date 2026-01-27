<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRepositorySubtopic extends Model
{
    protected $table = 'course_repository_subtopic';
    protected $primaryKey = 'pk';
    public $timestamps = false; 

    protected $fillable = [
        'course_repo_topic',
        'course_repo_sub_topic',
        'create_date',
    ];
}
