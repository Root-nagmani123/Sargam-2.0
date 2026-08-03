<?php

namespace App\Services\FC;

use App\Models\FoundationCourseStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FcRegistrationStatusService
{
    public const TAB_NOT_RESPONDED = 'not-responded';

    public const TAB_REGISTERED = 'registered';

    public const TAB_SERVICE = 'service';

    public const TAB_EXEMPTION = 'exemption';

    public const TAB_INCOMPLETE = 'incomplete';

    public const TABS = [
        self::TAB_NOT_RESPONDED,
        self::TAB_REGISTERED,
        self::TAB_SERVICE,
        self::TAB_EXEMPTION,
        self::TAB_INCOMPLETE,
    ];

    // courseMeta() removed: the status hero now carries the academy masthead instead of a
    // course title / date line, so nothing rendered its output. It read the whole
    // fc_front_pages row on every page load to build two strings no view asked for.

    public function counts(): array
    {
        return [
            self::TAB_NOT_RESPONDED => $this->baseQuery()->notResponded()->count(),
            self::TAB_REGISTERED => $this->baseQuery()->registered()->count(),
            self::TAB_EXEMPTION => $this->baseQuery()->exemption()->count(),
            self::TAB_INCOMPLETE => $this->baseQuery()->incomplete()->count(),
            self::TAB_SERVICE => $this->serviceWiseCounts()->count(),
        ];
    }

    /**
     * @return Collection<int, object{service_master_pk: int|null, count: int, service: ?object}>
     */
    public function serviceWiseCounts(): Collection
    {
        return $this->baseQuery()
            ->select('service_master_pk', DB::raw('count(*) as count'))
            ->groupBy('service_master_pk')
            ->with(['service' => fn ($q) => $q->select('pk', 'service_name', 'service_short_name')])
            ->orderByDesc('count')
            ->get();
    }

    public function participantsForTab(string $tab, int $perPage = 25, ?int $page = null): LengthAwarePaginator
    {
        $query = $this->baseQuery()
            ->with([
                'service' => fn ($q) => $q->select('pk', 'service_name', 'service_short_name'),
                'exemption' => fn ($q) => $q->select('Pk', 'Exemption_name'),
            ])
            ->select([
                'pk',
                'first_name',
                'middle_name',
                'last_name',
                'display_name',
                'service_master_pk',
                'rank',
                'fc_exemption_master_pk',
            ]);

        match ($tab) {
            self::TAB_REGISTERED => $query->registered(),
            self::TAB_EXEMPTION => $query->exemption(),
            self::TAB_INCOMPLETE => $query->incomplete(),
            default => $query->notResponded(),
        };

        $paginator = $query
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage, ['*'], 'page', $page ?? max(1, (int) request()->query('page', 1)));

        return $paginator->appends(['tab' => $tab]);
    }

    public function tabMeta(string $tab): array
    {
        return match ($tab) {
            self::TAB_REGISTERED => [
                'label' => 'CSE 2025 Registered',
                'list_title' => 'List of Participant who have Registered',
                'theme' => 'registered',
            ],
            self::TAB_SERVICE => [
                'label' => 'Service wise Registered',
                'list_title' => 'Service wise List Report',
                'theme' => 'service',
                // This badge counts SERVICES, not people — every other tab counts people.
                'count_unit' => 'services',
            ],
            self::TAB_EXEMPTION => [
                'label' => 'Applied for Exemption',
                'list_title' => 'List of Participant who have applied for Exemption',
                'theme' => 'exemption',
            ],
            self::TAB_INCOMPLETE => [
                'label' => 'Incomplete',
                'list_title' => 'List of Participant whose forms are still Incomplete',
                'theme' => 'incomplete',
            ],
            default => [
                'label' => 'Not Responded',
                'list_title' => 'List of Participant who have not Responded',
                'theme' => 'not-responded',
            ],
        };
    }

    public function resolveTab(?string $tab): string
    {
        $tab = strtolower(trim((string) $tab));

        return in_array($tab, self::TABS, true) ? $tab : self::TAB_NOT_RESPONDED;
    }

    private function baseQuery(): Builder
    {
        return FoundationCourseStatus::query();
    }
}
