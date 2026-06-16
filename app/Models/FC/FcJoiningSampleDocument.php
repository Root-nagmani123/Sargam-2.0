<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

/**
 * Sample / blank downloadable form for a joining document, keyed by the
 * document field_name (e.g. doc_family_details). One row applies to every
 * form replica that uses the same field_name.
 */
class FcJoiningSampleDocument extends Model
{
    protected $table = 'fc_joining_sample_documents';

    protected $fillable = [
        'field_name',
        'document_title',
        'section',
        'sample_file_path',
        'sample_original_name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];
}
