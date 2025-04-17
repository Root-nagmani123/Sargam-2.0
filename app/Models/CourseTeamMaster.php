<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTeamMaster extends Model
{
    protected $table = 'course_team_master';
    public $timestamps = false;
    protected $guarded = [];
    public $primaryKey = 'pk';
}
