<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLogSubCategoryMap extends Model
{
    use HasFactory;

    protected $table = 'issue_log_sub_category_map';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'issue_log_management_pk',
        'issue_category_master_pk',
        'issue_sub_category_master_pk',
        'sub_category_name',
    ];

    /**
     * Get the issue log.
     */
    public function issueLog()
    {
        return $this->belongsTo(IssueLogManagement::class, 'issue_log_management_pk', 'pk');
    }

    /**
     * Get the category.
     */
    public function category()
    {
        return $this->belongsTo(IssueCategoryMaster::class, 'issue_category_master_pk', 'pk');
    }

    /**
     * Get the sub-category.
     */
    public function subCategory()
    {
        return $this->belongsTo(IssueSubCategoryMaster::class, 'issue_sub_category_master_pk', 'pk');
    }
}
