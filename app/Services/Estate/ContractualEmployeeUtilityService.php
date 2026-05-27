<?php

namespace App\Services\Estate;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contractual (Other) utility list from estate_month_reading_details_other only.
 */
class ContractualEmployeeUtilityService
{
    public function hasMonthReadingTable(): bool
    {
        return Schema::hasTable('estate_month_reading_details_other')
            && Schema::hasTable('estate_possession_other')
            && Schema::hasTable('estate_other_req');
    }

    public function baseQuery(): Builder
    {
        return DB::table('estate_month_reading_details_other as emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk');
    }

    public function listQuery(): Builder
    {
        if (! $this->hasMonthReadingTable()) {
            return DB::table('estate_month_reading_details_other')->whereRaw('1 = 0');
        }

        $employeeNameSql = $this->employeeNameSql();

        return $this->baseQuery()->select([
            'emro.pk',
            'eor.pk as other_req_pk',
            DB::raw("({$employeeNameSql}) as employee_name"),
            DB::raw("NULLIF(TRIM(eor.section), '') as department"),
            DB::raw('emro.house_no as house_code'),
            'emro.bill_month',
            'emro.bill_year',
            DB::raw('COALESCE(emro.electricty_charges, 0) as electricity_charges'),
            DB::raw('COALESCE(emro.water_charges, 0) as water_charges'),
            DB::raw('COALESCE(emro.licence_fees, 0) as licence_fee'),
            DB::raw(
                '(COALESCE(emro.electricty_charges, 0) + COALESCE(emro.water_charges, 0) + COALESCE(emro.licence_fees, 0)) as total_amount'
            ),
            'emro.bill_no',
            DB::raw(
                "CASE WHEN emro.bill_no IS NOT NULL AND TRIM(CAST(emro.bill_no AS CHAR)) NOT IN ('', '0')"
                . ' THEN TRIM(CAST(emro.bill_no AS CHAR)) ELSE CAST(emro.pk AS CHAR) END as bill_number'
            ),
        ]);
    }

    /**
     * Legacy rows often have bill_no 0/NULL; use reading pk for display (same as generate bill).
     */
    public function resolveBillNumber(mixed $billNo, mixed $pk): string
    {
        $billNoStr = trim((string) ($billNo ?? ''));
        if ($billNoStr !== '' && $billNoStr !== '0') {
            return $billNoStr;
        }

        $pkStr = trim((string) ($pk ?? ''));

        return $pkStr !== '' ? $pkStr : '';
    }

    /**
     * @param  Request|Arrayable<string, mixed>|array<string, mixed>  $request
     */
    public function applyFilters(Builder $q, Request|Arrayable|array $request): void
    {
        $employeeNameSql = $this->employeeNameSql();

        $filterEmployee = $this->requestInput($request, 'filter_employee_name');
        if ($filterEmployee !== null && $filterEmployee !== '') {
            $q->whereRaw("({$employeeNameSql}) = ?", [$filterEmployee]);
        }

        $filterDepartment = $this->requestInput($request, 'filter_department');
        if ($filterDepartment !== null && $filterDepartment !== '') {
            $q->where('eor.section', $filterDepartment);
        }

        $filterMonth = $this->requestInput($request, 'filter_bill_month');
        if ($filterMonth !== null && $filterMonth !== '') {
            $q->where('emro.bill_month', $filterMonth);
        }

        $filterYear = $this->requestInput($request, 'filter_bill_year');
        if ($filterYear !== null && $filterYear !== '') {
            $q->where('emro.bill_year', $filterYear);
        }
    }

    public function applySearch(Builder $q, string $search): void
    {
        $search = strtolower(trim($search));
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $employeeNameSql = $this->employeeNameSql();

        $q->where(function ($sub) use ($like, $employeeNameSql) {
            $sub->whereRaw("LOWER({$employeeNameSql}) LIKE ?", [$like])
                ->orWhereRaw('LOWER(eor.section) LIKE ?', [$like])
                ->orWhereRaw('LOWER(emro.house_no) LIKE ?', [$like])
                ->orWhereRaw('LOWER(emro.bill_month) LIKE ?', [$like])
                ->orWhereRaw('LOWER(emro.bill_year) LIKE ?', [$like])
                ->orWhereRaw('CAST(emro.bill_no AS CHAR) LIKE ?', [$like])
                ->orWhereRaw('CAST(emro.pk AS CHAR) LIKE ?', [$like])
                ->orWhereRaw(
                    "(CASE WHEN emro.bill_no IS NOT NULL AND TRIM(CAST(emro.bill_no AS CHAR)) NOT IN ('', '0')"
                    . " THEN TRIM(CAST(emro.bill_no AS CHAR)) ELSE CAST(emro.pk AS CHAR) END) LIKE ?",
                    [$like]
                );
        });
    }

    /**
     * @return array{employeeNames: \Illuminate\Support\Collection, departments: \Illuminate\Support\Collection, billMonths: array, billYears: array}
     */
    public function filterOptions(): array
    {
        if (! $this->hasMonthReadingTable()) {
            return [
                'employeeNames' => collect(),
                'departments' => collect(),
                'billMonths' => $this->defaultBillMonths(),
                'billYears' => $this->defaultBillYears(),
            ];
        }

        $employeeNameSql = $this->employeeNameSql();
        $base = $this->baseQuery();

        $employeeNames = (clone $base)
            ->selectRaw("DISTINCT ({$employeeNameSql}) as employee_name")
            ->whereRaw("TRIM({$employeeNameSql}) <> ''")
            ->orderBy('employee_name')
            ->pluck('employee_name');

        $departments = (clone $base)
            ->whereRaw("TRIM(COALESCE(eor.section, '')) <> ''")
            ->selectRaw('DISTINCT NULLIF(TRIM(eor.section), "") as department')
            ->orderBy('department')
            ->pluck('department')
            ->filter();

        $billMonths = (clone $base)
            ->whereNotNull('emro.bill_month')
            ->distinct()
            ->orderByRaw("FIELD(emro.bill_month, 'January','February','March','April','May','June','July','August','September','October','November','December')")
            ->pluck('emro.bill_month')
            ->filter()
            ->values()
            ->all();

        $billYears = (clone $base)
            ->whereNotNull('emro.bill_year')
            ->distinct()
            ->orderByDesc('emro.bill_year')
            ->pluck('emro.bill_year')
            ->filter()
            ->values()
            ->all();

        return [
            'employeeNames' => $employeeNames,
            'departments' => $departments,
            'billMonths' => $billMonths !== [] ? $billMonths : $this->defaultBillMonths(),
            'billYears' => $billYears !== [] ? $billYears : $this->defaultBillYears(),
        ];
    }

    public function findReadingRow(int $emroPk): ?object
    {
        return $this->listQuery()->where('emro.pk', $emroPk)->first();
    }

    private function employeeNameSql(): string
    {
        return "TRIM(COALESCE(NULLIF(TRIM(eor.emp_name), ''), ''))";
    }

    /**
     * @param  Request|Arrayable<string, mixed>|array<string, mixed>  $request
     */
    private function requestInput(Request|Arrayable|array $request, string $key): mixed
    {
        if ($request instanceof Request) {
            return $request->input($key);
        }
        if (is_array($request)) {
            return $request[$key] ?? null;
        }

        return $request->toArray()[$key] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function defaultBillMonths(): array
    {
        return [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function defaultBillYears(): array
    {
        $current = (int) date('Y');
        $years = [];
        for ($y = $current; $y >= $current - 5; $y--) {
            $years[] = (string) $y;
        }

        return $years;
    }
}
