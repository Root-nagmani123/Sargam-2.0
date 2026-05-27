<?php

namespace App\Services\Estate;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EstateVacantHouseService
{
    public function syncActiveVacantRecords(?int $actorPk = null): void
    {
        if (! Schema::hasTable('estate_vacant_house_monitoring')) {
            return;
        }

        $vacantHousePks = $this->currentlyVacantHousePks();
        $now = now();

        DB::table('estate_vacant_house_monitoring')
            ->where('is_active', 1)
            ->when($vacantHousePks === [], fn ($q) => $q, fn ($q) => $q->whereNotIn('estate_house_master_pk', $vacantHousePks))
            ->update([
                'is_active' => 0,
                'modify_date' => $now,
                'modify_by' => $actorPk,
            ]);

        if ($vacantHousePks === []) {
            return;
        }

        $houses = $this->houseRowsByPk($vacantHousePks);
        $lastPossessionByHouse = $this->lastReturnedPossessionByHouse($vacantHousePks);

        foreach ($vacantHousePks as $housePk) {
            $existing = DB::table('estate_vacant_house_monitoring')
                ->where('estate_house_master_pk', $housePk)
                ->where('is_active', 1)
                ->first();

            if ($existing) {
                continue;
            }

            $house = $houses->get($housePk);
            if (! $house) {
                continue;
            }

            $last = $lastPossessionByHouse->get($housePk);
            $meterOne = (int) ($house->meter_one ?? 0);
            $meterTwo = (int) ($house->meter_two ?? 0);
            $readingOne = $meterOne;
            $readingTwo = $meterTwo;

            if ($last) {
                $readingOne = (int) ($last->meter_reading_oth ?? $meterOne);
                $readingTwo = (int) ($last->meter_reading_oth1 ?? $meterTwo);
            }

            DB::table('estate_vacant_house_monitoring')->insert([
                'estate_house_master_pk' => $housePk,
                'house_code' => (string) ($house->house_no ?? ''),
                'house_name' => $this->buildHouseName($house),
                'meter_number' => $meterOne,
                'meter_number_two' => $meterTwo,
                'last_meter_reading_before_vacancy' => $readingOne,
                'last_meter_reading_two_before_vacancy' => $readingTwo,
                'last_allottee_employee_name' => $last->allottee_name ?? null,
                'last_allottee_employee_master_pk' => $last->employee_master_pk ?? null,
                'last_allottee_other_req_pk' => $last->other_req_pk ?? null,
                'estate_possession_details_pk' => $last && $last->scope === 'L' ? $last->possession_pk : null,
                'estate_possession_other_pk' => $last && $last->scope === 'O' ? $last->possession_pk : null,
                'possession_type' => $last->scope ?? null,
                'vacancy_date' => $last->vacancy_date ?? $now->toDateString(),
                'is_active' => 1,
                'created_date' => $now,
                'created_by' => $actorPk,
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    public function currentlyVacantHousePks(): array
    {
        $houses = DB::table('estate_house_master as h')
            ->select('h.pk', 'h.used_home_status', 'h.vacant_renovation_status')
            ->get();

        if ($houses->isEmpty()) {
            return [];
        }

        $housePks = $houses->pluck('pk')->map(fn ($pk) => (int) $pk)->all();

        $lbsnaaActive = DB::table('estate_possession_details as epd')
            ->whereIn('epd.estate_house_master_pk', $housePks)
            ->whereNotNull('epd.estate_house_master_pk')
            ->when(Schema::hasColumn('estate_possession_details', 'return_home_status'), fn ($q) => $q->where('epd.return_home_status', 0))
            ->pluck('epd.estate_house_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->flip();

        $otherActive = DB::table('estate_possession_other as epo')
            ->whereIn('epo.estate_house_master_pk', $housePks)
            ->whereNotNull('epo.estate_house_master_pk')
            ->where('epo.return_home_status', 0)
            ->pluck('epo.estate_house_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->flip();

        $vacant = [];
        foreach ($houses as $h) {
            $pk = (int) $h->pk;
            $vr = (int) ($h->vacant_renovation_status ?? 1);
            $used = (int) ($h->used_home_status ?? 0);

            if ($vr !== 1) {
                continue;
            }
            if (isset($lbsnaaActive[$pk]) || isset($otherActive[$pk]) || $used === 1) {
                continue;
            }
            $vacant[] = $pk;
        }

        return $vacant;
    }

    /**
     * @param  array<int, int>  $housePks
     */
    private function houseRowsByPk(array $housePks): Collection
    {
        return DB::table('estate_house_master as h')
            ->leftJoin('estate_campus_master as ec', 'h.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'h.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->whereIn('h.pk', $housePks)
            ->select(
                'h.pk',
                'h.house_no',
                'h.meter_one',
                'h.meter_two',
                'ec.campus_name',
                'eb.block_name',
                'eust.unit_sub_type'
            )
            ->get()
            ->keyBy('pk');
    }

    /**
     * @param  array<int, int>  $housePks
     */
    private function lastReturnedPossessionByHouse(array $housePks): Collection
    {
        $rows = collect();

        $lbsnaa = DB::table('estate_possession_details as epd')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->whereIn('epd.estate_house_master_pk', $housePks)
            ->when(Schema::hasColumn('estate_possession_details', 'return_home_status'), fn ($q) => $q->where('epd.return_home_status', 1))
            ->select(
                'epd.pk as possession_pk',
                'epd.estate_house_master_pk as house_pk',
                'epd.electric_meter_reading as meter_reading_oth',
                'epd.electric_meter_reading_2 as meter_reading_oth1',
                'epd.current_meter_reading_date as vacancy_date',
                'ehrd.emp_name as allottee_name',
                'epd.emploee_master_pk as employee_master_pk',
                DB::raw("'L' as scope")
            )
            ->orderByDesc('epd.pk')
            ->get();

        foreach ($lbsnaa as $row) {
            $hpk = (int) $row->house_pk;
            if (! $rows->has($hpk)) {
                $rows->put($hpk, $row);
            }
        }

        $other = DB::table('estate_possession_other as epo')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->whereIn('epo.estate_house_master_pk', $housePks)
            ->where('epo.return_home_status', 1)
            ->select(
                'epo.pk as possession_pk',
                'epo.estate_house_master_pk as house_pk',
                'epo.meter_reading_oth',
                'epo.meter_reading_oth1',
                'epo.current_meter_reading_date as vacancy_date',
                'eor.emp_name as allottee_name',
                DB::raw('NULL as employee_master_pk'),
                'epo.estate_other_req_pk as other_req_pk',
                DB::raw("'O' as scope")
            )
            ->orderByDesc('epo.pk')
            ->get();

        foreach ($other as $row) {
            $hpk = (int) $row->house_pk;
            $existing = $rows->get($hpk);
            if (! $existing || (int) $row->possession_pk > (int) $existing->possession_pk) {
                $rows->put($hpk, $row);
            }
        }

        return $rows;
    }

    private function buildHouseName(object $house): string
    {
        $parts = array_filter([
            trim((string) ($house->campus_name ?? '')),
            trim((string) ($house->block_name ?? '')),
            trim((string) ($house->unit_sub_type ?? '')),
        ]);

        return $parts !== [] ? implode(' / ', $parts) : (string) ($house->house_no ?? '');
    }

    public static function billPeriodDates(string $billMonth, string $billYear): array
    {
        try {
            $start = Carbon::parse('1 ' . trim($billMonth) . ' ' . trim($billYear))->startOfMonth();
        } catch (\Throwable $e) {
            return [null, null];
        }

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }
}
