<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecVisitorCardGenerated extends Model
{
    protected $table = 'sec_visitor_card_generated';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'pass_number',
        'vehicle_number',
        'vehicle_pass_number',
        'company',
        'address',
        'employee_master_pk',
        'purpose',
        'in_time',
        'out_time',
        'upload_path',
        'mobile_number',
        'identity_card',
        'valid_for_days',
        'issued_date',
        'id_no',
        'created_date',
        'modified_date',
        'created_by',
    ];

    protected $casts = [
        'in_time' => 'datetime',
        'out_time' => 'datetime',
        'issued_date' => 'date',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }

    public function createdBy()
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by', 'pk');
    }

    public function visitorNames()
    {
        return $this->hasMany(SecVisitorName::class, 'sec_visitor_card_generated_pk', 'pk');
    }

    /**
     * Visitor names, or an empty collection where sec_visitor_names does not exist.
     *
     * The table is created by 2026_01_22_000006_create_sec_visitor_names_table, which
     * has not been run on every environment. Touching the relation there throws
     * "Base table or view not found" and takes the whole page down, so views and any
     * read path should go through this instead of the relation directly.
     */
    public function visitorNamesSafe(): \Illuminate\Support\Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sec_visitor_names')) {
            return collect();
        }

        return collect($this->visitorNames);
    }

    public function getValidToDateAttribute()
    {
        if ($this->issued_date && $this->valid_for_days) {
            return $this->issued_date->addDays($this->valid_for_days);
        }
        return null;
    }
}
