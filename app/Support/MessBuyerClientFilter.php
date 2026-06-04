<?php

namespace App\Support;

use App\Models\EmployeeMaster;
use App\Models\FacultyMaster;
use App\Models\KitchenIssueMaster;
use App\Models\Mess\ClientType;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\DepartmentMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Match mess voucher rows by client_id when available; fall back to client_name patterns.
 */
class MessBuyerClientFilter
{
    /**
     * @param  Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<int, string>  $buyerValues
     * @param  array<int, string>  $clientTypeSlugs  employee/ot/course slugs; empty = any
     */
    public static function apply($query, array $buyerValues, array $clientTypeSlugs = [], int $clientTypePk = 0): void
    {
        $buyerValues = collect($buyerValues)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        if ($buyerValues === []) {
            return;
        }

        $clientTypeSlugs = collect($clientTypeSlugs)
            ->map(fn ($slug) => strtolower(trim((string) $slug)))
            ->filter()
            ->values()
            ->all();

        $isOtOrCourseOnly = $clientTypeSlugs !== []
            && collect($clientTypeSlugs)->every(fn ($slug) => in_array($slug, [ClientType::TYPE_OT, ClientType::TYPE_COURSE], true));

        $query->where(function ($outer) use ($buyerValues, $clientTypeSlugs, $clientTypePk, $isOtOrCourseOnly) {
            foreach ($buyerValues as $buyerValue) {
                $outer->orWhere(function ($single) use ($buyerValue, $clientTypeSlugs, $clientTypePk, $isOtOrCourseOnly) {
                    if ($isOtOrCourseOnly) {
                        if (ctype_digit($buyerValue)) {
                            $single->where('client_id', (int) $buyerValue);
                        } else {
                            self::applyNamePattern($single, $buyerValue);
                        }

                        return;
                    }

                    $clientId = self::resolveClientId($buyerValue, $clientTypeSlugs);
                    if ($clientId !== null && $clientId > 0) {
                        $nameVariants = self::nameVariants($buyerValue, $clientId, $clientTypePk);
                        $single->where(function ($match) use ($clientId, $nameVariants) {
                            $match->where('client_id', $clientId);
                            if ($nameVariants !== []) {
                                $match->orWhere(function ($fallback) use ($nameVariants) {
                                    $fallback->where(function ($nullId) {
                                        $nullId->whereNull('client_id')->orWhere('client_id', '<=', 0);
                                    });
                                    $fallback->where(function ($nameQ) use ($nameVariants) {
                                        foreach ($nameVariants as $variant) {
                                            $nameQ->orWhere(function ($nq) use ($variant) {
                                                self::applyNamePattern($nq, $variant);
                                            });
                                        }
                                    });
                                });
                            }
                        });

                        return;
                    }

                    self::applyNamePattern($single, $buyerValue);
                });
            }
        });
    }

    /**
     * @param  array<int, string>  $clientTypeSlugs
     */
    public static function resolveClientId(string $buyerValue, array $clientTypeSlugs = []): ?int
    {
        $buyerValue = trim($buyerValue);
        if ($buyerValue === '') {
            return null;
        }

        if (ctype_digit($buyerValue)) {
            return (int) $buyerValue;
        }

        $baseName = trim((string) preg_replace('/\s*\([^)]+\)\s*$/', '', $buyerValue));

        $fromSv = self::resolveClientIdFromQuery(
            SellingVoucherDateRangeReport::query(),
            $buyerValue,
            $baseName,
            $clientTypeSlugs,
            'client_type_slug'
        );
        if ($fromSv !== null) {
            return $fromSv;
        }

        $fromKi = self::resolveClientIdFromQuery(
            KitchenIssueMaster::query()
                ->whereIn('kitchen_issue_type', [
                    KitchenIssueMaster::TYPE_SELLING_VOUCHER,
                    KitchenIssueMaster::TYPE_SELLING_VOUCHER_DATE_RANGE,
                ]),
            $buyerValue,
            $baseName,
            $clientTypeSlugs,
            'client_type',
            true
        );
        if ($fromKi !== null) {
            return $fromKi;
        }

        if ($clientTypeSlugs !== [] && ! in_array(ClientType::TYPE_EMPLOYEE, $clientTypeSlugs, true)) {
            return null;
        }

        $employeePk = self::findEmployeePkByDisplayName($buyerValue, $baseName);
        if ($employeePk !== null && $employeePk > 0) {
            return $employeePk;
        }

        $facultyPk = FacultyMaster::query()
            ->where(function ($q) use ($buyerValue, $baseName) {
                $q->where('full_name', $buyerValue);
                if ($baseName !== '' && $baseName !== $buyerValue) {
                    $q->orWhere('full_name', $baseName);
                }
            })
            ->value('pk');

        return ($facultyPk !== null && (int) $facultyPk > 0) ? (int) $facultyPk : null;
    }

    /**
     * Current display name for a buyer (e.g. employee name with latest department).
     */
    public static function resolveDisplayNameForClient(int $clientId, int $clientTypePk = 0): string
    {
        return self::resolveEmployeeDisplayName($clientId, $clientTypePk);
    }

    /** @var list<string> */
    private const BUYER_GROUP_CLIENT_SLUGS = [
        ClientType::TYPE_EMPLOYEE,
        ClientType::TYPE_OT,
        ClientType::TYPE_COURSE,
        ClientType::TYPE_OTHER,
    ];

    public static function voucherClientTypeSlug(object $voucher): string
    {
        if (isset($voucher->client_type_slug) && trim((string) $voucher->client_type_slug) !== '') {
            return (string) $voucher->client_type_slug;
        }

        return self::kitchenClientTypeIdToSlug((int) ($voucher->client_type ?? 0));
    }

    public static function voucherClientId(object $voucher): int
    {
        if (isset($voucher->client_id) && (int) $voucher->client_id > 0) {
            return (int) $voucher->client_id;
        }

        $name = trim((string) ($voucher->client_name ?? ''));
        if ($name === '') {
            return 0;
        }

        $resolved = self::resolveClientId($name, [self::voucherClientTypeSlug($voucher)]);

        return ($resolved !== null && $resolved > 0) ? $resolved : 0;
    }

    /**
     * Stable group key: one report section per client_id (not per historical client_name).
     */
    public static function buyerGroupKey(object $voucher): string
    {
        $slug = self::voucherClientTypeSlug($voucher);
        $clientId = self::voucherClientId($voucher);
        if ($clientId > 0 && in_array($slug, self::BUYER_GROUP_CLIENT_SLUGS, true)) {
            return 'cid:' . $clientId . '|' . $slug;
        }

        $name = trim((string) ($voucher->client_name ?? ''));
        $pk = (string) ($voucher->client_type_pk ?? '');

        return 'name:' . $name . '|' . $slug . '|' . $pk;
    }

    /**
     * @param  Collection<int, object>|null  $group
     */
    public static function resolveBuyerDisplayName(object $voucher, $group = null): string
    {
        $clientTypePk = (int) ($voucher->client_type_pk ?? 0);
        $clientId = 0;
        if ($group instanceof Collection) {
            foreach ($group as $row) {
                $clientId = max($clientId, self::voucherClientId($row));
            }
        } else {
            $clientId = self::voucherClientId($voucher);
        }

        if ($clientId > 0) {
            $display = self::resolveDisplayNameForClient($clientId, $clientTypePk);
            if ($display !== '') {
                return $display;
            }
        }

        return trim((string) ($voucher->client_name ?? ''));
    }

    /**
     * @param  Collection<int, object>  $vouchers
     * @return Collection<int, object>
     */
    public static function normalizeVoucherGroupDisplayNames(Collection $vouchers): Collection
    {
        if ($vouchers->isEmpty()) {
            return $vouchers;
        }

        $displayName = self::resolveBuyerDisplayName($vouchers->first(), $vouchers);
        if ($displayName === '' || $displayName === '—') {
            return $vouchers;
        }

        return $vouchers->map(function ($voucher) use ($displayName) {
            $voucher->client_name = $displayName;

            return $voucher;
        });
    }

    /**
     * @return list<string>
     */
    public static function nameVariants(string $buyerValue, int $clientId, int $clientTypePk = 0): array
    {
        $variants = array_values(array_unique(array_filter([
            trim($buyerValue),
            trim((string) preg_replace('/\s*\([^)]+\)\s*$/', '', $buyerValue)),
            trim(self::resolveEmployeeDisplayName($clientId, $clientTypePk)),
        ], fn ($name) => $name !== '')));

        $svNames = SellingVoucherDateRangeReport::query()
            ->where('client_id', $clientId)
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        $kiNames = KitchenIssueMaster::query()
            ->where('client_id', $clientId)
            ->whereIn('kitchen_issue_type', [
                KitchenIssueMaster::TYPE_SELLING_VOUCHER,
                KitchenIssueMaster::TYPE_SELLING_VOUCHER_DATE_RANGE,
            ])
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($variants, $svNames, $kiNames)));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $employees
     * @return list<array{value: string, text: string}>
     */
    public static function employeeBuyerOptions($employees): array
    {
        return $employees->map(function ($employee) {
            $value = (string) ($employee->pk ?? '');
            $text = (string) ($employee->full_name_with_department ?? $employee->full_name ?? '');
            if ($value === '' || $text === '') {
                return null;
            }

            return ['value' => $value, 'text' => $text];
        })->filter()->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $faculties
     * @return list<array{value: string, text: string}>
     */
    public static function facultyBuyerOptions($faculties): array
    {
        return $faculties->map(function ($faculty) {
            $value = (string) ($faculty->pk ?? '');
            $text = (string) ($faculty->full_name ?? '');
            if ($value === '' || $text === '') {
                return null;
            }

            return ['value' => $value, 'text' => $text];
        })->filter()->values()->all();
    }

    /**
     * @param  Builder  $query
     * @param  array<int, string>  $clientTypeSlugs
     */
    private static function resolveClientIdFromQuery(
        $query,
        string $buyerValue,
        string $baseName,
        array $clientTypeSlugs,
        string $typeColumn,
        bool $kitchenClientType = false
    ): ?int {
        $existingClientIdQuery = (clone $query)
            ->whereNotNull('client_id')
            ->where('client_id', '>', 0)
            ->where(function ($nameQ) use ($buyerValue, $baseName) {
                $nameQ->where('client_name', $buyerValue)
                    ->orWhere('client_name', 'LIKE', $buyerValue.' (%');
                if ($baseName !== '' && $baseName !== $buyerValue) {
                    $nameQ->orWhere('client_name', $baseName)
                        ->orWhere('client_name', 'LIKE', $baseName.' (%');
                }
            });

        if ($clientTypeSlugs !== []) {
            if ($kitchenClientType) {
                $typeIds = collect($clientTypeSlugs)
                    ->map(fn ($slug) => self::kitchenClientTypeId($slug))
                    ->filter()
                    ->values()
                    ->all();
                if ($typeIds !== []) {
                    $existingClientIdQuery->whereIn($typeColumn, $typeIds);
                }
            } else {
                $existingClientIdQuery->whereIn($typeColumn, $clientTypeSlugs);
            }
        }

        $existingClientId = $existingClientIdQuery->value('client_id');

        return ($existingClientId !== null && (int) $existingClientId > 0) ? (int) $existingClientId : null;
    }

    private static function kitchenClientTypeId(string $slug): ?int
    {
        return match ($slug) {
            ClientType::TYPE_EMPLOYEE => KitchenIssueMaster::CLIENT_EMPLOYEE,
            ClientType::TYPE_OT => KitchenIssueMaster::CLIENT_OT,
            ClientType::TYPE_COURSE => KitchenIssueMaster::CLIENT_COURSE,
            ClientType::TYPE_OTHER => KitchenIssueMaster::CLIENT_OTHER,
            ClientType::TYPE_SECTION, 'section' => KitchenIssueMaster::CLIENT_SECTION,
            default => null,
        };
    }

    private static function kitchenClientTypeIdToSlug(int $clientType): string
    {
        return match ($clientType) {
            KitchenIssueMaster::CLIENT_EMPLOYEE => ClientType::TYPE_EMPLOYEE,
            KitchenIssueMaster::CLIENT_OT => ClientType::TYPE_OT,
            KitchenIssueMaster::CLIENT_COURSE => ClientType::TYPE_COURSE,
            KitchenIssueMaster::CLIENT_OTHER => ClientType::TYPE_OTHER,
            KitchenIssueMaster::CLIENT_SECTION => 'section',
            default => ClientType::TYPE_OTHER,
        };
    }

    /**
     * @param  Builder|\Illuminate\Database\Query\Builder  $query
     */
    private static function applyNamePattern($query, string $buyerName): void
    {
        $query->where(function ($q) use ($buyerName) {
            $q->where('client_name', $buyerName)
                ->orWhere('client_name', 'LIKE', $buyerName.' (%');
        });
    }

    private static function findEmployeePkByDisplayName(string $buyerName, string $baseName): ?int
    {
        $nameForMatch = trim($baseName !== '' ? $baseName : $buyerName);
        if ($nameForMatch === '') {
            return null;
        }

        $pk = EmployeeMaster::query()
            ->whereRaw(
                "TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(middle_name,''), ' ', COALESCE(last_name,''))) = ?",
                [$nameForMatch]
            )
            ->value('pk');

        return ($pk !== null && (int) $pk > 0) ? (int) $pk : null;
    }

    private static function resolveEmployeeDisplayName(int $clientId, int $clientTypePk): string
    {
        if ($clientId <= 0) {
            return '';
        }

        $categoryName = '';
        if ($clientTypePk > 0) {
            $categoryName = strtolower(trim((string) ClientType::query()
                ->where('id', $clientTypePk)
                ->where('client_type', ClientType::TYPE_EMPLOYEE)
                ->value('client_name')));
        }

        if ($categoryName === 'faculty') {
            return trim((string) FacultyMaster::query()
                ->where('pk', $clientId)
                ->value('full_name'));
        }

        $employee = EmployeeMaster::query()
            ->select('first_name', 'middle_name', 'last_name', 'department_master_pk')
            ->where('pk', $clientId)
            ->first();

        if (! $employee) {
            return '';
        }

        $fullName = trim(($employee->first_name ?? '').' '.($employee->middle_name ?? '').' '.($employee->last_name ?? ''));
        if ($fullName === '') {
            return '';
        }

        if (in_array($categoryName, ['academy staff', 'mess staff'], true)) {
            $departmentName = trim((string) DepartmentMaster::query()
                ->where('pk', $employee->department_master_pk)
                ->value('department_name'));
            if ($departmentName !== '') {
                return $fullName.' ('.$departmentName.')';
            }
        }

        return $fullName;
    }
}
