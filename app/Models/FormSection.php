<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSection extends Model
{
    protected $table = 'form_sections';
    protected $fillable = [
        'formid',
        'section_title',
    ];
    public $timestamps = false;
}
