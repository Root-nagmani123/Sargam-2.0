<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentIosrDetailsDocPathMaster extends Model {
    protected $table = 'student_iosr_details_doc_path_masters';
    protected $fillable = ['username','document_type','file_path'];
}
