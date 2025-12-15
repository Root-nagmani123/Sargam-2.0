<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplineMaster extends Model
{
    protected $table = 'discipline_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'discipline_name',
        'mark_diduction',
        'course_master_pk',
        'active_inactive',
        'created_date',
        'modified_date'
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
}
