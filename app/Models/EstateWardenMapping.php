<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateWardenMapping extends Model
{
    use HasFactory;

    protected $table = 'estate_warden_mapping';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'estate_campus_master_pk',
        'employee_master_pk',
        'department_master_pk',
        'designation_master_pk',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function campus()
    {
        return $this->belongsTo(EstateCampusMaster::class, 'estate_campus_master_pk', 'pk');
    }
}
