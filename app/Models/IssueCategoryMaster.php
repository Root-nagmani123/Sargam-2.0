<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueCategoryMaster extends Model
{
    use HasFactory;

    protected $table = 'issue_category_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_category',
        'description',
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
     * Get the sub-categories for this issue category.
     */
    public function subCategories()
    {
        return $this->hasMany(IssueSubCategoryMaster::class, 'issue_category_master_pk', 'pk')
                    ->where('status', 1);
    }

    /**
     * Get the employee mappings for this category.
     */
    public function employeeMappings()
    {
        return $this->hasMany(IssueCategoryEmployeeMap::class, 'issue_category_master_pk', 'pk');
    }

    /**
     * Get the issue logs for this category.
     */
    public function issueLogs()
    {
        return $this->hasMany(IssueLogManagement::class, 'issue_category_master_pk', 'pk');
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
