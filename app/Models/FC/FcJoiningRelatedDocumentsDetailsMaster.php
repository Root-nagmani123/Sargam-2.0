<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class FcJoiningRelatedDocumentsDetailsMaster extends Model {
    protected $table = 'fc_joining_related_documents_details_masters';
    protected $fillable = ['username','document_master_id','document_name','file_path',
        'file_original_name','is_uploaded','is_verified','verified_by','verified_at','remarks'];
    protected $casts = ['is_uploaded'=>'boolean','is_verified'=>'boolean','verified_at'=>'datetime'];
    public function documentMaster() { return $this->belongsTo(FcJoiningRelatedDocumentsMaster::class,'document_master_id'); }
}

