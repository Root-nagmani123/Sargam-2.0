<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** activity_master — maps activity code (menuid) to display name (menun) per course */
class ActivityMaster extends Model
{
    protected $table   = 'activity_master';
    protected $fillable = ['menuid','menun','ccode','status'];
    public function scopeActive($q)              { return $q->where('status',1); }
    public function scopeForCourse($q, $ccode)  { return $q->where('ccode',$ccode); }
}
