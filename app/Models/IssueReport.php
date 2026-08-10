<?php

namespace App\Models;

use App\Models\SidebarMenu\MenuGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueReport extends Model
{
    use HasFactory;

    protected $table = 'issue_reports';

    protected $fillable = [
        'reported_by',
        'menu_group_id',
        'module_name',
        'sub_module',
        'description',
        'attachment',
        'page_url',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public const STATUS_OPEN = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_RESOLVED = 2;
    public const STATUS_CLOSED = 3;

    public function menuGroup()
    {
        return $this->belongsTo(MenuGroup::class, 'menu_group_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
