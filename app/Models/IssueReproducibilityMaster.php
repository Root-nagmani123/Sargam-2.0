<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueReproducibilityMaster extends Model
{
    use HasFactory;

    protected $table = 'issue_reproducibility_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'reproducibility_name',
        'reproducibility_description',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
        'status',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * Get the issue logs for this reproducibility level.
     */
    public function issueLogs()
    {
        return $this->hasMany(IssueLogManagement::class, 'issue_reproducibility_master_pk', 'pk');
    }

    /**
     * Scope to get only active reproducibility levels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
