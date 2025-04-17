<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeCourseMaster extends Model
{
    protected $table = 'programme_course_master';
    public $timestamps = false;
    protected $guarded = [];
    public $primaryKey = 'pk';
}
