<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcActivityMaster extends Model
{
    protected $table = 'fc_activity_master';

    protected $fillable = [
        'menuid',
        'menun',
        'department_id',
        'ccode',
        'sort_order',
        'status',
        'is_joined_marker',
        'entry_policy',
    ];

    protected $casts = [
        'is_joined_marker' => 'integer',
        'sort_order' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(FcActivityDepartment::class, 'department_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }

    public function scopeForCourse($q, ?string $ccode)
    {
        $c = trim((string) ($ccode ?? ''));
        if ($c === '') {
            return $q;
        }

        return $q->where(function ($q2) use ($c) {
            $q2->whereNull('ccode')
                ->orWhere('ccode', '')
                ->orWhereRaw('TRIM(ccode) = ?', [$c]);
        });
    }

    public function scopeForDepartmentIds($q, ?array $ids)
    {
        if ($ids === null) {
            return $q;
        }
        if ($ids === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q->whereIn('department_id', $ids);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('menun');
    }

    public static function joinedMarkerMenuid(): ?string
    {
        $m = static::active()->where('is_joined_marker', 1)->orderByDesc('sort_order')->value('menuid');

        return $m ?: (static::active()->where('menuid', 'joined')->value('menuid'));
    }
}
