<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\Feature\Concerns\SeedsModuleDashboardCards;
use Tests\TestCase;

/**
 * PR #356 F-008: the migration that ships the module dashboard cards works from a database that
 * never had them, covers every card the code defines, is safe to run twice, and changes no existing row.
 */
class ModuleDashboardCardsMigrationTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }
    use SeedsModuleDashboardCards;

    /** Defined at base 147b5812 already, with no row on testsargam8 or testsargam9; outside PR #356. */
    private const ROWLESS_BEFORE_PR_356 = [
        'student_details', 'my_course_participant', 'protocol_guest_house_requests', 'protocol_vehicle_pass_requests',
        'protocol_ticket_requests', 'protocol_pending_my_action', 'protocol_recommend_action', 'my_protocol_request',
    ];

    private object $migration;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        $this->migration = self::moduleDashboardCardsMigration();
        // Start clean on any dev database: drop the cards (assignments cascade), inside the rollback.
        DB::table('dashboard_cards')->whereIn('key', array_keys($this->migration::CARDS))->delete();
    }

    public function test_it_creates_every_card_and_exactly_the_expected_assignments(): void
    {
        $this->migration->up();

        $keys = array_keys($this->migration::CARDS);
        $this->assertCount(23, $keys);
        $this->assertSame(23, DB::table('dashboard_cards')->whereIn('key', $keys)->count());
        foreach ($this->migration::CARDS as $key => [$label, $icon, $colour]) {
            $row = DB::table('dashboard_cards')->where('key', $key)->first();
            $this->assertSame([$label, $icon, $colour], [$row->label, $row->icon, $row->color_class], $key);
        }

        $this->assertEqualsCanonicalizing($this->expectedAssignments(), $this->actualAssignments());
    }

    public function test_every_card_the_dashboard_defines_has_a_row(): void
    {
        $this->migration->up();
        $this->actingAs($this->staffWithRole('Super Admin'));

        $definitions = (fn () => $this->dashboardCardDefinitions())->call(app(UserController::class));
        $missing = array_diff(array_keys($definitions), DB::table('dashboard_cards')->pluck('key')->all(), self::ROWLESS_BEFORE_PR_356);

        $this->assertSame([], array_values($missing), 'card keys defined in code with no dashboard_cards row');
    }

    public function test_a_second_run_changes_nothing_and_existing_rows_are_kept(): void
    {
        $this->migration->up();
        DB::table('dashboard_cards')->where('key', 'mess_unpaid_bills')->update(['label' => 'Relabelled by an admin', 'sort_order' => 1]);
        $before = [$this->cardRows(), $this->allAssignments()];

        $this->migration->up();

        $this->assertEquals($before, [$this->cardRows(), $this->allAssignments()]);
        $this->assertSame('Relabelled by an admin', DB::table('dashboard_cards')->where('key', 'mess_unpaid_bills')->value('label'));
    }

    /** @return list<string> "role|key|tab" for every assignment whose role and tab exist here */
    private function expectedAssignments(): array
    {
        $roles = DB::table('roles')->pluck('name')->flip();
        $tabs = DB::table('sidebar_categories')->whereNull('deleted_at')->pluck('slug')->flip();
        $pairs = [];
        foreach ($this->migration::CARDS as $key => [, , , $assignments]) {
            foreach ($assignments as [$role, $tab]) {
                $pairs[] = [$role, $key, $tab];
            }
        }
        foreach (DB::table('dashboard_cards')->whereIn('key', $this->migration::SECURITY_APPROVER_CARDS)->pluck('key') as $key) {
            $pairs[] = ['Security Approver II', $key, 'home'];
            $pairs[] = ['Security Approver III', $key, 'home'];
        }

        return collect($pairs)->filter(fn ($p) => isset($roles[$p[0]], $tabs[$p[2]]))->map(fn ($p) => implode('|', $p))->unique()->values()->all();
    }

    /** @return list<string> */
    private function actualAssignments(): array
    {
        $keys = array_merge(array_keys($this->migration::CARDS), $this->migration::SECURITY_APPROVER_CARDS);

        return DB::table('role_dashboard_cards as rdc')
            ->join('roles as r', 'r.id', '=', 'rdc.role_id')
            ->join('dashboard_cards as c', 'c.id', '=', 'rdc.dashboard_card_id')
            ->join('sidebar_categories as s', 's.id', '=', 'rdc.sidebar_category_id')
            ->whereIn('c.key', $keys)
            ->whereIn('r.name', collect($this->migration::CARDS)->flatMap(fn ($c) => array_column($c[3], 0))->push('Security Approver II', 'Security Approver III')->unique()->all())
            ->get(['r.name', 'c.key', 's.slug'])
            // A security card keeps older assignments to other roles and tabs; compare only the ones up() makes.
            ->filter(fn ($r) => ! in_array($r->key, $this->migration::SECURITY_APPROVER_CARDS, true)
                || (str_starts_with($r->name, 'Security Approver') && $r->slug === 'home'))
            ->map(fn ($r) => "{$r->name}|{$r->key}|{$r->slug}")->values()->all();
    }

    private function cardRows(): array
    {
        return DB::table('dashboard_cards')->orderBy('id')->get(['id', 'key', 'label', 'icon', 'color_class', 'sort_order'])->map(fn ($r) => (array) $r)->all();
    }

    private function allAssignments(): array
    {
        return DB::table('role_dashboard_cards')->orderBy('role_id')->orderBy('dashboard_card_id')->orderBy('sidebar_category_id')->get()->map(fn ($r) => (array) $r)->all();
    }
}
