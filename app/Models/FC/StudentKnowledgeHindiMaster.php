<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentKnowledgeHindiMaster extends Model {
    protected $table = 'student_knowledge_hindi_masters';
    protected $fillable = ['username','medium_of_study','hindi_medium_school','hindi_subject_studied','highest_hindi_exam'];
    protected $casts = ['hindi_medium_school'=>'boolean','hindi_subject_studied'=>'boolean'];
}
