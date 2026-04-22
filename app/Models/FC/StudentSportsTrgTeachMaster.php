<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentSportsTrgTeachMaster extends Model {
    protected $table = 'student_sports_trg_teach_masters';
    protected $fillable = ['username','sport_id','training_institute','duration','year'];
    public function sport() { return $this->belongsTo(SportsMaster::class,'sport_id'); }
}
