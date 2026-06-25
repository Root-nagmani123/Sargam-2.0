<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $iconMap = [
        'bi-journal-text'       => 'menu_book',
        'bi-calendar-event'     => 'event',
        'bi-megaphone'          => 'campaign',
        'bi-person-vcard'       => 'contact_page',
        'bi-people-fill'        => 'group',
        'bi-clock-history'      => 'history',
        'bi-person-lines-fill'  => 'people',
        'bi-heart-pulse'        => 'monitor_heart',
        'bi-person-gear'        => 'manage_accounts',
        'bi-copy'               => 'content_copy',
        'bi-files'              => 'file_copy',
        'bi-people'             => 'group',
        'bi-car-front'          => 'directions_car',
        'bi-person-badge'       => 'badge',
        'bi-bell'               => 'notifications',
        'bi-twitter-x'          => 'share',
        'bi-twitter'            => 'share',
        'bi-cake2'              => 'cake',
        'bi-calendar3'          => 'calendar_month',
        'bi-grid'               => 'dashboard',
        'bi-align-middle'       => 'dashboard',
        'bi-bar-chart'          => 'bar_chart',
        'bi-pie-chart'          => 'pie_chart',
        'bi-person'             => 'person',
        'bi-envelope'           => 'mail',
        'bi-file-text'          => 'description',
        'bi-clock'              => 'schedule',
        'bi-star'               => 'star',
        'bi-house'              => 'home',
        'bi-building'           => 'business',
        'bi-gear'               => 'settings',
        'bi-shield'             => 'shield',
        'bi-check-circle'       => 'check_circle',
        'bi-x-circle'           => 'cancel',
        'bi-info-circle'        => 'info',
        'bi-exclamation-circle' => 'error',
    ];

    public function up(): void
    {
        $cards = DB::table('dashboard_cards')
            ->whereRaw("icon LIKE 'bi-%'")
            ->get(['id', 'icon']);

        foreach ($cards as $card) {
            $newIcon = $this->iconMap[$card->icon] ?? $this->fallback($card->icon);
            DB::table('dashboard_cards')->where('id', $card->id)->update(['icon' => $newIcon]);
        }

        DB::statement("ALTER TABLE `dashboard_cards` MODIFY COLUMN `icon` VARCHAR(100) NOT NULL DEFAULT 'dashboard'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `dashboard_cards` MODIFY COLUMN `icon` VARCHAR(100) NOT NULL DEFAULT 'bi-grid'");
    }

    private function fallback(string $icon): string
    {
        return str_replace('-', '_', substr($icon, 3));
    }
};
