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

    /**
     * The gated cards and whether the logged-in user may see each: the OT and
     * faculty-portal cards, whose role mapping alone would show them to a login
     * the page behind them refuses. Any card not listed shows to whoever's role
     * has it. dashboard() and the header's module landing both read this, so a
     * tab never lands on a dashboard whose every card is filtered out.
     */
    public static function gatedCardVisibility(): array
    {
        $notSecurity = ! (hasRole('Security Card') || hasRole('Admin Security'));
        $isStudentOT = hasRole('Student-OT');
        // Student-OT is a session pseudo-role; an OT holding only the Spatie
        // "Officer Trainee" role does not have it.
        $isOtUser = $isStudentOT || isOfficerTraineeUser();
        $faculty = is_faculty_portal_user();

        return [
            'my_groups' => $notSecurity && $isStudentOT,
            'discipline_marks_deducted' => $notSecurity && $isOtUser,
            'pending_feedback' => $notSecurity && $isOtUser,
            'total_sessions' => $notSecurity && $faculty,
            'total_feedback' => $notSecurity && $faculty,
            'my_counsellees' => $notSecurity && $faculty,
            'house_wise_details' => $notSecurity && $faculty,
            'whos_who' => $notSecurity && $faculty,
            // The academy scope refuses trainees (PR #334 F-047).
            'academic_timetable' => $notSecurity && $faculty && ! $isOtUser,
            'my_timetable' => $notSecurity && ($faculty || $isOtUser),
        ];
    }

    /** Ids of the modules where $roleIds have at least one card the logged-in user can see. */
    public static function moduleIdsVisibleTo(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }
        $hidden = array_keys(array_filter(self::gatedCardVisibility(), fn ($visible) => ! $visible));

        return self::query()->whereNotIn('dashboard_cards.key', $hidden)
            ->join('role_dashboard_cards', 'role_dashboard_cards.dashboard_card_id', '=', 'dashboard_cards.id')
            ->whereIn('role_dashboard_cards.role_id', $roleIds)
            ->distinct()->pluck('role_dashboard_cards.sidebar_category_id')->map(fn ($id) => (int) $id)->all();
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
