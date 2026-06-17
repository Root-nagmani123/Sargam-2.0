<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Populate the (previously empty) `menus.icon` column with sensible Material
 * Symbols icons so the dynamic sidebar can render an icon per item.
 *
 * Only fills rows whose icon is currently NULL/empty, so any icon an admin sets
 * later via the Menu manager is preserved. Matching is keyword-based on the menu
 * name + route, first match wins; everything unmatched gets a neutral default.
 */
return new class extends Migration
{
    /**
     * Ordered keyword => icon map. First substring match (against the lowercased
     * "name route") wins, so more specific needles must come before generic ones.
     *
     * @var array<int, array{0:string,1:string}>
     */
    private array $map = [
        // --- Home / general ---
        ['edit profile', 'manage_accounts'],
        ['batch profile', 'groups'],
        ['statistic', 'query_stats'],
        ['dashboard', 'grid_view'],
        ['notice notification', 'notifications'],
        ['birthday', 'cake'],
        // --- Link groups ---
        ['quick link', 'link'],
        ['useful link', 'bookmark'],
        ['security request', 'shield'],
        ['directory', 'contacts'],
        // --- ID cards / security ---
        ['duplicate id', 'badge'],
        ['family id', 'family_restroom'],
        ['family-card', 'family_restroom'],
        ['family_card', 'family_restroom'],
        ['id card', 'badge'],
        ['id-card', 'badge'],
        ['idcard', 'badge'],
        ['i_card', 'badge'],
        ['vehicle', 'directions_car'],
        ['card type', 'style'],
        ['sub type', 'account_tree'],
        // --- Issues / centcom ---
        ['centcom', 'support_agent'],
        ['issue', 'report_problem'],
        ['complaint', 'support_agent'],
        ['priorit', 'flag'],
        ['escalation', 'trending_up'],
        // --- Estate ---
        ['estate', 'home_work'],
        ['house', 'house'],
        ['campus', 'apartment'],
        ['block', 'apartment'],
        ['building', 'apartment'],
        ['hostel', 'hotel'],
        ['meter', 'electric_meter'],
        ['electric slab', 'bolt'],
        ['bill', 'receipt_long'],
        ['hac', 'approval'],
        ['possession', 'vpn_key'],
        ['eligibilit', 'rule'],
        ['unit type', 'category'],
        // --- Mess / material ---
        ['mess', 'restaurant'],
        ['store', 'warehouse'],
        ['vendor', 'storefront'],
        ['item', 'inventory_2'],
        ['purchase', 'shopping_cart'],
        ['stock', 'inventory'],
        ['selling', 'sell'],
        ['voucher', 'receipt'],
        ['client', 'badge'],
        ['material management', 'inventory'],
        // --- Academics ---
        ['course', 'menu_book'],
        ['subject', 'subject'],
        ['module', 'view_module'],
        ['memo', 'gavel'],
        ['discipline', 'gavel'],
        ['feedback', 'rate_review'],
        ['exemption', 'medical_services'],
        ['medical', 'medical_services'],
        ['escort', 'supervisor_account'],
        ['moderator', 'supervisor_account'],
        ['mdo', 'supervisor_account'],
        ['attendance', 'fact_check'],
        ['time table', 'calendar_month'],
        ['timetable', 'calendar_month'],
        ['calendar', 'calendar_month'],
        ['peer', 'diversity_3'],
        ['enrollment', 'school'],
        ['class session', 'event_note'],
        ['session', 'event_note'],
        // --- People / roles ---
        ['role', 'admin_panel_settings'],
        ['permission', 'admin_panel_settings'],
        ['users', 'group'],
        ['employee', 'badge'],
        ['faculty', 'school'],
        ["who", 'contacts'],
        ['designation', 'work'],
        ['department', 'apartment'],
        ['caste', 'diversity_1'],
        ['appellation', 'label'],
        ['expertise', 'workspace_premium'],
        // --- Registration ---
        ['registration', 'how_to_reg'],
        ['landing page', 'web'],
        ['path page', 'web'],
        ['front', 'web'],
        ['form', 'description'],
        ['logo', 'image'],
        ['column', 'view_column'],
        ['migrat', 'sync_alt'],
        // --- Master data / location ---
        ['country', 'public'],
        ['state', 'map'],
        ['district', 'location_city'],
        ['city', 'location_city'],
        ['address', 'location_on'],
        ['venue', 'place'],
        ['stream', 'waterfall_chart'],
        ['general master', 'tune'],
        ['master', 'tune'],
        // --- Reports / db / docs ---
        ['report', 'assessment'],
        ['database', 'database'],
        ['document', 'description'],
        ['joining', 'folder_shared'],
        // --- Communications ---
        ['meeting', 'video_camera_front'],
        ['tweet', 'chat'],
        ['notice', 'campaign'],
        ['notification', 'notifications'],
        ['send', 'send'],
        // --- Sidebar admin ---
        ['sidebar', 'view_sidebar'],
        ['topbar', 'web_asset'],
        ['sidemenu', 'menu'],
        ['menu', 'menu'],
        ['categor', 'category'],
        // --- Generic ---
        ['link', 'link'],
        ['security', 'shield'],
        ['finance', 'payments'],
        ['others', 'more_horiz'],
        ['general', 'category'],
    ];

    private string $default = 'label';

    public function up(): void
    {
        if (! Schema::hasColumn('menus', 'icon')) {
            return;
        }

        $rows = DB::table('menus')
            ->where(function ($q) {
                $q->whereNull('icon')->orWhere('icon', '');
            })
            ->get(['id', 'name', 'route']);

        foreach ($rows as $row) {
            DB::table('menus')->where('id', $row->id)->update([
                'icon' => $this->iconFor($row->name, $row->route),
            ]);
        }
    }

    private function iconFor(?string $name, ?string $route): string
    {
        $haystack = strtolower(trim(($name ?? '') . ' ' . ($route ?? '')));

        foreach ($this->map as [$needle, $icon]) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return $icon;
            }
        }

        return $this->default;
    }

    public function down(): void
    {
        // Data backfill only; intentionally not reverted to avoid wiping icons an
        // admin may have edited after this migration ran.
    }
};
