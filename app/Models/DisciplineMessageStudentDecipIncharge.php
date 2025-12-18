<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DisciplineMessageStudentDecipIncharge extends Model
{
    protected $table = 'discipline_message_student_decip_incharge';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'created_by', 'pk');
    }

    public function memo()
    {
        return $this->belongsTo(MemoDiscipline::class, 'discipline_memo_status_pk', 'pk');
    }
}
