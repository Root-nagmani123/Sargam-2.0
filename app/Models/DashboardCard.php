<?php

namespace App\Models;

use App\Models\SidebarMenu\SidebarCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class DashboardCard extends Model
{
    protected $fillable = ['key', 'label', 'icon', 'color_class', 'sort_order'];

    /** Every role that has this card on ANY module's dashboard. */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_dashboard_cards', 'dashboard_card_id', 'role_id');
    }

    /** Header modules a dashboard can be configured for: the live sidebar categories, in header order. */
    public static function modules(): Collection
    {
        return SidebarCategory::query()->where('is_active', '1')->orderBy('order')->get(['id', 'name', 'slug']);
    }

    /** The module asked for, or Home when the value is missing, not scalar, or not a live module. */
    public static function resolveModule(mixed $requested): ?SidebarCategory
    {
        $modules = self::modules();
        $id = is_scalar($requested) && ctype_digit((string) $requested) ? (int) $requested : 0;

        return $modules->firstWhere('id', $id)
            ?? $modules->firstWhere('slug', 'home')
            ?? $modules->first();
    }

    /** The live module with exactly this id, or null. For writes: falling back to Home would edit the wrong dashboard. */
    public static function liveModule(mixed $id): ?SidebarCategory
    {
        $id = is_scalar($id) && ctype_digit((string) $id) ? (int) $id : 0;

        return self::modules()->firstWhere('id', $id);
    }

    /** Cards any of $roleIds has on module $moduleId's dashboard. */
    public function scopeAssignedTo($query, array $roleIds, int $moduleId)
    {
        return $query->whereIn('id', fn ($q) => $q->select('dashboard_card_id')
            ->from('role_dashboard_cards')
            ->whereIn('role_id', $roleIds)
            ->where('sidebar_category_id', $moduleId));
    }
}
