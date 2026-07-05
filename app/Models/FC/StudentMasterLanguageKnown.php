<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentMasterLanguageKnown extends Model {
    use FcUserAware;
    protected $table = 'student_master_language_knowns';
    protected $fillable = ['user_id', 'username','language_id','can_read','can_write','can_speak','proficiency'];
    protected $casts = ['can_read'=>'boolean','can_write'=>'boolean','can_speak'=>'boolean'];
    public function language() { return $this->belongsTo(LanguageMaster::class,'language_id'); }
}
