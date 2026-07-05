<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CourseMaster extends Model
{
    protected $table = 'course_master';
    protected $fillable = ['c_code','c_name','status'];
    public function scopeActive($q) { return $q->where('status',1); }
}
