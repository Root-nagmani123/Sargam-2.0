<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueSubCategoryMaster extends Model
{
    use HasFactory;

    protected $table = 'issue_sub_category_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_category_master_pk',
        'issue_sub_category',
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
     * Get the category that owns the sub-category.
     */
    public function category()
    {
        return $this->belongsTo(IssueCategoryMaster::class, 'issue_category_master_pk', 'pk');
    }

    /**
     * Scope to get only active sub-categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('issue_category_master_pk', $categoryId);
    }
}
