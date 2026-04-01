<?php

namespace App\Console\Commands;

use App\Http\Controllers\Mess\ReportController;
use App\Models\DepartmentMaster;
use App\Models\Mess\Store;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendStockAlertCommand extends Command
{
    protected $signature = 'send:stock_alert {--till_date=} {--store_id=}';

    protected $description = 'Send low stock summary notifications to mess users (per active store when store_id omitted)';

    public function handle(): int
    {
        $tillDate = $this->option('till_date') ?: now()->format('Y-m-d');
        $singleStoreId = $this->option('store_id');

        $receiverUserIds = $this->resolveMessReceiverUserIds();
        if (count($receiverUserIds) === 0) {
            $this->warn('No mess recipients found. Notification skipped.');
            return self::SUCCESS;
        }

        $now = now();
        $hour = (int) $now->format('G');
        $slotHours = null;
        if ($hour >= 11 && $hour <= 13) {
            $slotHours = [11, 13];
        } elseif ($hour >= 16 && $hour <= 18) {
            $slotHours = [16, 18];
        }

        if ($singleStoreId !== null && $singleStoreId !== '') {
            $created = $this->sendLowStockForStore((int) $singleStoreId, $tillDate, $receiverUserIds, $slotHours, $now);
            $this->info($created > 0
                ? "Low stock notification sent ({$created} user row(s)) for store id {$singleStoreId}."
                : 'No low stock items or skipped for this store.');

            return self::SUCCESS;
        }

        $stores = Store::query()->active()->orderBy('store_name')->get();
        if ($stores->isEmpty()) {
            $this->warn('No active stores found. Notification skipped.');
            return self::SUCCESS;
        }

        $totalCreated = 0;
        foreach ($stores as $store) {
            $totalCreated += $this->sendLowStockForStore(
                (int) $store->id,
                $tillDate,
                $receiverUserIds,
                $slotHours,
                $now,
                (string) ($store->store_name ?? '')
            );
        }

        $this->info("Per-store low stock run complete. Total notification row(s) created: {$totalCreated}.");
        return self::SUCCESS;
    }

    /**
     * @param  array<int, int>  $receiverUserIds
     * @param  array{0: int, 1: int}|null  $slotHours
     * @return int Number of notification rows inserted
     */
    private function sendLowStockForStore(
        int $storeId,
        string $tillDate,
        array $receiverUserIds,
        ?array $slotHours,
        \Illuminate\Support\Carbon $now,
        ?string $storeName = null
    ): int {
        $lowStockItems = ReportController::getLowStockAlertItems($tillDate, $storeId);
        if (count($lowStockItems) === 0) {
            return 0;
        }

        $storeLabel = $storeName !== null && $storeName !== ''
            ? $storeName
            : (Store::find($storeId)?->store_name ?? "Store #{$storeId}");

        if ($slotHours !== null) {
            $alreadySentThisWindow = Notification::query()
                ->whereIn('receiver_user_id', $receiverUserIds)
                ->where('type', 'mess_stock')
                ->where('module_name', 'LowStock')
                ->where('reference_pk', $storeId)
                ->whereDate('created_at', $now->toDateString())
                ->whereRaw('HOUR(created_at) BETWEEN ? AND ?', $slotHours)
                ->exists();

            if ($alreadySentThisWindow) {
                $this->info("Low stock for \"{$storeLabel}\" (id {$storeId}) already sent this time window today. Skipped.");

                return 0;
            }
        }

        $itemsSummary = collect($lowStockItems)
            ->take(5)
            ->map(function (array $row): string {
                $name = $row['item_name'] ?? '-';
                $remaining = isset($row['remaining_quantity']) ? number_format((float) $row['remaining_quantity'], 2) : '0';
                $unit = $row['unit'] ?? 'Unit';
                $alert = isset($row['alert_quantity']) ? number_format((float) $row['alert_quantity'], 2) : '0';

                return "{$name} ({$remaining} {$unit} / Min {$alert} {$unit})";
            })
            ->implode('; ');

        if (count($lowStockItems) > 5) {
            $itemsSummary .= '; and more items are below minimum stock.';
        }

        $message = "[{$storeLabel}] The following items are at or below minimum stock for this store: {$itemsSummary}";
        $title = "Low stock: {$storeLabel}";

        return notification()->createMultiple(
            $receiverUserIds,
            'mess_stock',
            'LowStock',
            $storeId,
            $title,
            $message
        );
    }

    /**
     * @return array<int, int>
     */
    private function resolveMessReceiverUserIds(): array
    {
        $roleBased = User::query()
            ->where('user_category', '!=', 'S')
            ->whereHas('roles', function ($q) {
                $q->whereRaw('LOWER(user_role_name) = ?', ['mess staff']);
            })
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $officersMessDepartmentId = DepartmentMaster::query()
            ->where('department_name', 'Officers Mess')
            ->value('pk');

        $departmentBased = [];
        if ($officersMessDepartmentId) {
            $departmentBased = DB::table('user_credentials as uc')
                ->join('employee_master as em', 'uc.user_id', '=', 'em.pk')
                ->where('uc.user_category', 'E')
                ->where('em.department_master_pk', $officersMessDepartmentId)
                ->pluck('uc.user_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return array_values(array_unique(array_merge($roleBased, $departmentBased)));
    }
}
