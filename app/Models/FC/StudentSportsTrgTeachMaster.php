<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentSportsTrgTeachMaster extends Model {
    use FcUserAware;
    protected $table = 'student_sports_trg_teach_masters';
    protected $fillable = ['user_id', 'username','sport_id','training_institute','duration','year'];
    public function sport() { return $this->belongsTo(SportsMaster::class,'sport_id'); }
}
