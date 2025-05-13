<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormData extends Model
{
    protected $table = 'form_data';

    protected $fillable = [
        'form_id', 'section_id', 'format', 'field_type', 'field_title',
        'formlabel', 'formname', 'formtype', 'fieldoption', 'header',
        'row_index', 'col_index', 'required'
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function section()
    {
        return $this->belongsTo(FormSection::class);
    }
}
