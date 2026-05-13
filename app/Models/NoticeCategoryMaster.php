<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoticeCategoryMaster extends Model
{
    protected $table = 'notice_category_master';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    protected $fillable = [
        'name',
        'sort_order',
        'active_inactive',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'active_inactive' => 'integer',
    ];

    public function subCategories(): HasMany
    {
        return $this->hasMany(NoticeSubcategoryMaster::class, 'notice_category_master_pk', 'pk')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(NoticeNotification::class, 'notice_category_master_pk', 'pk');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeActive($query)
    {
        return $query->where('active_inactive', '=', 1);
    }
}
