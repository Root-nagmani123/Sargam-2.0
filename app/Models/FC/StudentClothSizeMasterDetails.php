<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentClothSizeMasterDetails extends Model {
    protected $table = 'student_cloth_size_master_details';
    protected $fillable = ['username','shirt_size','trouser_size','shoe_size','blazer_size'];
}

