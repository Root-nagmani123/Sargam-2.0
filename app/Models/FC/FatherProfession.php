<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class FatherProfession extends Model {
    protected $table = 'father_professions';
    protected $fillable = ['profession_name'];
}
