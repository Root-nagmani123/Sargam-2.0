<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentMasterQualificationDetails extends Model {
    use FcUserAware;
    protected $table = 'student_master_qualification_details';
    protected $fillable = ['user_id', 'username','qualification_id','degree_name','board_id','institution_name',
        'year_of_passing','percentage_cgpa','stream_id','subject_details'];
    public function qualification() { return $this->belongsTo(QualificationMaster::class,'qualification_id','pk'); }
    public function board()         { return $this->belongsTo(BoardNameMaster::class,'board_id','pk'); }
    public function stream()        { return $this->belongsTo(HighestStreamMaster::class,'stream_id','pk'); }
}
