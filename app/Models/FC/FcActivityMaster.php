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
        'entry_policy',
    ];

    protected $casts = [
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

    /**
     * Display label without trailing "(Department)" suffix stored in menun.
     */
    public static function shortLabel(?string $menun): string
    {
        $label = trim((string) $menun);
        if ($label === '') {
            return '';
        }
        $stripped = preg_replace('/\s*\([^)]*\)\s*$/', '', $label);

        return $stripped !== '' ? $stripped : $label;
    }

    /**
     * Activity menuid used for joined/arrival reporting (not-joined list, service-wise counts).
     * Uses the active master row whose menuid is the literal string `joined`.
     */
    public static function joinedMarkerMenuid(): ?string
    {
        return static::active()->where('menuid', 'joined')->value('menuid');
    }
}
