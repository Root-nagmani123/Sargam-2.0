<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseGroupTypeMaster extends Model
{
    protected $table = "course_group_type_master";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";
}
