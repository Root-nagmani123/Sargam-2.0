<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class FcJoiningRelatedDocumentsMaster extends Model {
    protected $table = 'fc_joining_related_documents_masters';
    protected $fillable = ['document_name','document_code','is_mandatory','is_active','display_order'];
    protected $casts = ['is_mandatory'=>'boolean','is_active'=>'boolean'];
}
