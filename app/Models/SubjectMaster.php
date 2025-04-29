<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectMaster extends Model
{
    use HasFactory;

    protected $table = 'subject_master'; // Table name
    protected $primaryKey = 'pk'; // Primary Key
    protected $fillable = [
        'subject_name',
        'sub_short_name',
        'Topic_name',
        'subject_module_master_pk',
        'active_inactive',
        'created_by',
        'created_date',
        'modified_date',
    ];

    // Timestamps handling (if you're using 'created_at' and 'updated_at')
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'modified_date';
    public function module()
{
    return $this->belongsTo(SubjectModuleMaster::class, 'subject_module_master_pk', 'pk');
}
}
