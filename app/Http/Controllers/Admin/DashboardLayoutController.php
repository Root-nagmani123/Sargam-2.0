<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Customise on the dashboard: a login reorders and hides the cards and panels its roles have
 * on one module's dashboard. Only keys assigned to the login's roles on that module are kept,
 * so a layout can hide or reorder what Assign Dashboard gave, never add to it.
 */
class DashboardLayoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'integer'],
            'order' => ['present', 'array', 'max:200'],
            'order.*' => ['string', 'max:100', 'distinct'],
            'hidden' => ['present', 'array', 'max:200'],
            'hidden.*' => ['string', 'max:100', 'distinct'],
        ]);
        $module = $this->module($data['category']);
        $assigned = DashboardCard::assignedTo($this->roleIds(), $module->id)->pluck('key')->all();
        $keep = fn (array $keys) => array_values(array_intersect($keys, $assigned));

        $where = ['user_id' => (int) Auth::id(), 'sidebar_category_id' => $module->id];
        $values = [
            'card_order' => json_encode($keep($data['order'])),
            'hidden_cards' => json_encode($keep($data['hidden'])),
            'updated_at' => now(),
        ];
        DB::table('user_dashboard_layouts')->where($where)->exists()
            ? DB::table('user_dashboard_layouts')->where($where)->update($values)
            : DB::table('user_dashboard_layouts')->insert($where + $values + ['created_at' => now()]);

        return response()->json(['message' => 'Dashboard layout saved.']);
    }

    /** Reset to default: drop the login's layout for the module. */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['category' => ['required', 'integer']]);
        DB::table('user_dashboard_layouts')
            ->where('user_id', (int) Auth::id())
            ->where('sidebar_category_id', $this->module($data['category'])->id)
            ->delete();

        return response()->json(['message' => 'Dashboard layout reset.']);
    }

    private function module(int $id)
    {
        return DashboardCard::liveModule($id) ?? abort(422, 'Unknown dashboard.');
    }

    private function roleIds(): array
    {
        return (Auth::user()->roles ?? collect())->pluck('id')->all();
    }
}
