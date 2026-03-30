<?php

namespace App\Console\Commands;

use App\Http\Controllers\Mess\ReportController;
use App\Models\DepartmentMaster;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendStockAlertCommand extends Command
{
    protected $signature = 'send:stock_alert {--till_date=} {--store_id=}';

    protected $description = 'Send low stock summary notifications to mess users';

    public function handle(): int
    {
        $tillDate = $this->option('till_date') ?: now()->format('Y-m-d');
        $storeId = $this->option('store_id') ?: null;

        $lowStockItems = ReportController::getLowStockAlertItems($tillDate, $storeId);
        if (count($lowStockItems) === 0) {
            $this->info('No low stock items found. Notification skipped.');
            return self::SUCCESS;
        }

        $receiverUserIds = $this->resolveMessReceiverUserIds();
        if (count($receiverUserIds) === 0) {
            $this->warn('No mess recipients found. Notification skipped.');
            return self::SUCCESS;
        }

        // Avoid double-send within the same scheduled window (12:00 / 17:00), but allow the evening run after noon.
        $now = now();
        $hour = (int) $now->format('G');
        $slotHours = null;
        if ($hour >= 11 && $hour <= 13) {
            $slotHours = [11, 13];
        } elseif ($hour >= 16 && $hour <= 18) {
            $slotHours = [16, 18];
        }
        if ($slotHours !== null) {
            $alreadySentThisWindow = Notification::query()
                ->whereIn('receiver_user_id', $receiverUserIds)
                ->where('type', 'mess_stock')
                ->where('module_name', 'LowStock')
                ->whereDate('created_at', $now->toDateString())
                ->whereRaw('HOUR(created_at) BETWEEN ? AND ?', $slotHours)
                ->exists();

            if ($alreadySentThisWindow) {
                $this->info('Low stock notification already sent for this time window today. Skipped.');
                return self::SUCCESS;
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

        $message = 'The following mess items are at or below their minimum stock level: ' . $itemsSummary;

        $created = notification()->createMultiple(
            $receiverUserIds,
            'mess_stock',
            'LowStock',
            0,
            'Low stock alert',
            $message
        );

        $this->info("Low stock notification sent to {$created} user(s).");
        return self::SUCCESS;
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
