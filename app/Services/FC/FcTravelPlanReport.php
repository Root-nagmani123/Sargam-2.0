<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * Travel Plan report — every trainee registered on a course, with their travel plan if they
 * have one, so an admin can see who has filled it and who has not.
 *
 * Separate from the admin Travel Plans screen (/admin/travel) on purpose: that screen is driven
 * by student_travel_plan_masters, so a trainee with no plan is simply absent from it. Here the
 * driving rows are the course's registration rows (scopedBase()), and the plan is LEFT JOINed —
 * a trainee without one still appears, with empty travel columns and status "Not filled".
 *
 * The join cannot multiply rows: student_travel_plan_masters.user_id carries a UNIQUE index.
 */
class FcTravelPlanReport extends FcStepReport
{
    private const TABLE = 'student_travel_plan_masters';

    /** Whether the query being filtered carries the `tp` join — set in applyFilters(). */
    private bool $travelJoined = false;

    public function key(): string
    {
        return 'travel-plan';
    }

    public function title(): string
    {
        return 'Travel Plan';
    }

    public function subtitle(): string
    {
        return 'Who has filled the Travel Plan and who has not — course wise, with Excel and PDF export.';
    }

    /** Short fields only, so the PDF is a grid like the on-screen table. */
    public function pdfLayout(): string
    {
        return 'table';
    }

    public function statusLabels(): array
    {
        return [
            'filled' => 'Filled',
            'pending' => 'Not filled',
        ];
    }

    public function reportColumns(): array
    {
        return [
            'travel_status' => ['label' => 'Travel Plan Status', 'orderable' => true],
            'arrival_date' => ['label' => 'Arrival Date', 'orderable' => true],
            'activity_slot' => ['label' => 'Activity Slot', 'orderable' => false],
            'mode_of_journey' => ['label' => 'Mode of Journey', 'orderable' => true],
            'journey_vehicle_no' => ['label' => 'Flight / Train / Vehicle No', 'orderable' => false],
            'arrival_time' => ['label' => 'Arrival Time at Airport/Railway Station', 'orderable' => true],
            'require_academy_vehicle' => ['label' => 'Require Academy Vehicle', 'orderable' => true],
            'special_requirements' => ['label' => 'Remarks', 'orderable' => false],
            'last_updated' => ['label' => 'Last Updated', 'orderable' => true],
        ];
    }

    protected function reportExpressions(FcForm $form): array
    {
        if (! $this->joinable($form)) {
            return array_map(fn () => 'NULL', $this->reportColumns());
        }

        return [
            'travel_status' => $this->statusSql(),
            'arrival_date' => "DATE_FORMAT(`tp`.`joining_date`, '%d-%m-%Y')",
            'activity_slot' => $this->slotSql(),
            'mode_of_journey' => "NULLIF(TRIM(`tp`.`mode_of_journey`), '')",
            'journey_vehicle_no' => "NULLIF(TRIM(`tp`.`journey_vehicle_no`), '')",
            'arrival_time' => "NULLIF(TRIM(`tp`.`arrival_time_dehradun`), '')",
            // Only meaningful when a plan exists; NULL (shown as "—") for a trainee without one.
            'require_academy_vehicle' => "CASE WHEN `tp`.`id` IS NULL THEN NULL "
                ."WHEN `tp`.`require_academy_vehicle` = 1 THEN 'Yes' ELSE 'No' END",
            'special_requirements' => "NULLIF(TRIM(`tp`.`special_requirements`), '')",
            'last_updated' => "DATE_FORMAT(`tp`.`updated_at`, '%d-%m-%Y %H:%i')",
        ];
    }

    protected function applyJoins(Builder $query, FcForm $form): void
    {
        if (! $this->joinable($form)) {
            return;
        }

        // Joined on the TRACKER's user_id, not through s1: the travel plan is written under
        // Auth::id() (TravelPlanController), which is exactly the id the tracker row is keyed
        // on. Going through s1 would lose the plan of a trainee whose s1 row is missing.
        $tracker = $form->trackerStorageTable();
        $query->leftJoin(self::TABLE.' as tp', 'tp.user_id', '=', "{$tracker}.user_id");

        if (fc_schema_has_table('fc_travel_arrival_slots')) {
            $query->leftJoin('fc_travel_arrival_slots as tps', 'tps.id', '=', 'tp.fc_travel_arrival_slot_id');
        }
    }

    /**
     * Filled / Not filled, replacing the base filter.
     *
     * "Filled" means a plan row exists. Unlike the text-field steps, a travel plan always has
     * its required fields once saved, so the row's presence — not the content of a column — is
     * the honest test.
     */
    public function applyFilters(Builder $query, Request $request): void
    {
        $status = (string) $request->input('f_status', '');
        // Read by extraSearchColumns(), which has no query of its own to inspect.
        $this->travelJoined = $this->hasTravelJoin($query);

        if ($status !== '' && $this->travelJoined) {
            match ($status) {
                'filled' => $query->whereNotNull('tp.id'),
                'pending' => $query->whereNull('tp.id'),
                default => null,
            };
        }

        $this->applySearch($query, $request);
    }

    /** Not used: applyFilters() is overridden, because "not filled" is a missing row, not an empty column. */
    public function statusColumns(): array
    {
        return [];
    }

    protected function reportOrderSql(string $key, FcForm $form): ?string
    {
        if (! $this->joinable($form)) {
            return null;
        }

        return match ($key) {
            'travel_status' => $this->statusSql(),
            'arrival_date' => 'tp.joining_date',
            'mode_of_journey' => 'tp.mode_of_journey',
            'arrival_time' => 'tp.arrival_time_dehradun',
            'require_academy_vehicle' => 'tp.require_academy_vehicle',
            'last_updated' => 'tp.updated_at',
            default => null,
        };
    }

    protected function extraSearchColumns(): array
    {
        return $this->travelJoined
            ? ['tp.mode_of_journey', 'tp.journey_vehicle_no']
            : [];
    }

    /**
     * The Travel Plan is not an admin-configurable form step — it is appended after the last
     * step by the registration flow — so there is no fc_form_fields row to probe. It applies to
     * every course whose tracker the plan can be joined to.
     */
    public function formMapsStep(FcForm $form): bool
    {
        return $this->joinable($form);
    }

    /** Required by the base class; unused because formMapsStep() is overridden. */
    protected function probeField(): array
    {
        return ['table' => self::TABLE, 'column' => 'user_id'];
    }

    private function statusSql(): string
    {
        return "CASE WHEN `tp`.`id` IS NULL THEN 'Not filled' "
            ."WHEN `tp`.`is_submitted` = 1 THEN 'Submitted' ELSE 'Draft' END";
    }

    private function slotSql(): string
    {
        if (! fc_schema_has_table('fc_travel_arrival_slots')) {
            return 'NULL';
        }

        return "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(TRIM(`tps`.`slot_label`), ''), "
            ."CASE WHEN `tps`.`time_start` IS NOT NULL AND `tps`.`time_end` IS NOT NULL "
            ."THEN CONCAT('(', LEFT(`tps`.`time_start`, 5), '–', LEFT(`tps`.`time_end`, 5), ')') END)), '')";
    }

    /** The plan table exists and the course's tracker is keyed on user_id, as the plan is. */
    private function joinable(FcForm $form): bool
    {
        return fc_schema_has_table(self::TABLE)
            && fc_user_col($form->trackerStorageTable()) === 'user_id';
    }

    /** The status filter names `tp`, so it only applies when applyJoins() added it. */
    private function hasTravelJoin(Builder $query): bool
    {
        foreach ((array) $query->joins as $join) {
            if (is_string($join->table) && str_ends_with($join->table, ' as tp')) {
                return true;
            }
        }

        return false;
    }
}
