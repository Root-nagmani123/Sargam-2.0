<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

/**
 * Structured data entered through a fillable joining-document form
 * (e.g. Details of Family — Form No. 3), one row per candidate per document.
 */
class FcJoiningDocumentForm extends Model
{
    protected $table = 'fc_joining_document_forms';

    protected $fillable = [
        'user_id', 'form_id', 'step_id', 'field_name',
        'template_key', 'form_data', 'pdf_path',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];
}
