<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcActivityDepartment extends Model
{
    protected $table = 'fc_activity_department';

    protected $fillable = ['code', 'name', 'sort_order', 'status'];

    public function masters(): HasMany
    {
        return $this->hasMany(FcActivityMaster::class, 'department_id');
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(FcActivityDepartmentUser::class, 'fc_activity_department_id');
    }

    /**
     * @param  array<int|string>  $userPks  user_credentials.pk values (from form multi-select)
     */
    public function syncAssignedStaffPks(array $userPks): void
    {
        $clean = array_values(array_unique(array_filter(array_map(
            fn ($v) => (int) $v,
            $userPks
        ), fn (int $pk) => $pk > 0)));
        $this->staffAssignments()->delete();
        if ($clean === []) {
            return;
        }
        $now = now();
        $rows = array_map(fn (int $pk) => [
            'fc_activity_department_id' => $this->id,
            'user_credentials_pk' => $pk,
            'created_at' => $now,
            'updated_at' => $now,
        ], $clean);
        FcActivityDepartmentUser::query()->insert($rows);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
