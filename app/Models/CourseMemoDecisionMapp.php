<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseMemoDecisionMapp extends Model
{
    protected $table = 'course_memo_decision_mapp';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [ 
        'course_master_pk',
        'memo_type_master_pk',
        'memo_conclusion_master_pk',
        'active_inactive',
        'created_date',
        'modified_date',
    ];
    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    // Define relationship with MemoTypeMaster
    public function memo()
    {
        return $this->belongsTo(MemoTypeMaster::class, 'memo_type_master_pk', 'pk');
    }
   public function memoConclusion()
{
    return $this->belongsTo(MemoConclusionMaster::class, 'memo_conclusion_master_pk', 'pk');
}
}
