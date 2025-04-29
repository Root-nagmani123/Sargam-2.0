<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectModuleMaster extends Model
{
    protected $table = 'subject_module_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'module_name',
        'active_inactive',
        'created_by',
        'created_date',
        'modified_date',
    ];
}
