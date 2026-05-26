<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentKnowledgeHindiMaster extends Model {
    use FcUserAware;
    protected $table = 'student_knowledge_hindi_masters';
    protected $fillable = ['user_id', 'username','medium_of_study','hindi_medium_school','hindi_subject_studied','highest_hindi_exam'];
    protected $casts = ['hindi_medium_school'=>'boolean','hindi_subject_studied'=>'boolean'];
}
