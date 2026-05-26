<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeSubcategoryMaster extends Model
{
    protected $table = 'notice_subcategory_master';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    protected $fillable = [
        'notice_category_master_pk',
        'name',
        'sort_order',
        'active_inactive',
    ];

    protected $casts = [
        'notice_category_master_pk' => 'integer',
        'sort_order' => 'integer',
        'active_inactive' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NoticeCategoryMaster::class, 'notice_category_master_pk', 'pk');
    }
}
