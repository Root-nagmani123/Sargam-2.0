<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The half of the status-toggle guarantee that needs a database: the allow-list
 * refuses what it should WITHOUT breaking the 46 screens that legitimately use
 * the endpoint.
 *
 * ToggleStatusAllowListTest proves the refusals and keeps the list in step with
 * the markup; this proves an accepted pair still reaches the row and writes it.
 *
 * Skips — never fails — when the application database is unreachable, per the
 * suite convention in phpunit.xml. The write happens inside a transaction that
 * is always rolled back.
 */
class ToggleStatusEndpointTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the toggle endpoint test needs the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    public function test_a_permitted_pair_still_writes_the_row(): void
    {
        $row = DB::table('department_master')->orderBy('pk')->first();

        if (! $row) {
            $this->markTestSkipped('no department_master row to toggle');
        }

        $target = (int) $row->active_inactive === 1 ? 0 : 1;

        $this->actingAs($this->toggleUser())
            ->post(route('admin.toggleStatus'), [
                'table' => 'department_master',
                'column' => 'active_inactive',
                'id' => $row->pk,
                'status' => $target,
            ])
            ->assertOk();

        $this->assertSame(
            $target,
            (int) DB::table('department_master')->where('pk', $row->pk)->value('active_inactive'),
            'a permitted toggle must still reach the row'
        );
    }

    /** The refusal must stop short of the database, not merely report an error. */
    public function test_a_refused_pair_leaves_the_row_untouched(): void
    {
        $row = DB::table('department_master')->orderBy('pk')->first();

        if (! $row) {
            $this->markTestSkipped('no department_master row to toggle');
        }

        $before = DB::table('department_master')->where('pk', $row->pk)->value('department_name');

        $this->actingAs($this->toggleUser())
            ->post(route('admin.toggleStatus'), [
                'table' => 'department_master',
                'column' => 'department_name',
                'id' => $row->pk,
                'status' => 1,
            ])
            ->assertForbidden();

        $this->assertSame(
            $before,
            DB::table('department_master')->where('pk', $row->pk)->value('department_name'),
            'a refused column must not be written'
        );
    }

    /**
     * The venue screen, keyed by venue_id — the exact payload its markup sends.
     *
     * venue_master has NO pk column: its primary key is venue_id, and the switch
     * posts data-id_column="venue_id" for that reason. While the endpoint forced
     * every lookup through `pk` it ran WHERE pk = <venue_id> here, which MySQL
     * answers with "Unknown column 'pk'" — the toggle was a dead button on a
     * screen this PR never touched.
     */
    public function test_the_venue_toggle_writes_the_row_it_names(): void
    {
        $row = DB::table('venue_master')->orderBy('venue_id')->first();

        if (! $row) {
            $this->markTestSkipped('no venue_master row to toggle');
        }

        $target = (int) $row->active_inactive === 1 ? 0 : 1;

        $this->actingAs($this->toggleUser())
            ->post(route('admin.toggleStatus'), [
                'table'     => 'venue_master',
                'column'    => 'active_inactive',
                'id_column' => 'venue_id',
                'id'        => $row->venue_id,
                'status'    => $target,
            ])
            ->assertOk();

        $this->assertSame(
            $target,
            (int) DB::table('venue_master')->where('venue_id', $row->venue_id)->value('active_inactive'),
            'the venue toggle must write the row keyed by venue_id'
        );
    }

    /**
     * A key column the allow-list does not name for that table is refused.
     *
     * Honouring the posted id_column must not mean trusting it: that was the
     * arbitrary-write half of the original defect.
     */
    public function test_a_key_column_the_table_does_not_use_is_refused(): void
    {
        $row = DB::table('department_master')->orderBy('pk')->first();

        if (! $row) {
            $this->markTestSkipped('no department_master row to toggle');
        }

        $before = (int) DB::table('department_master')->where('pk', $row->pk)->value('active_inactive');

        $this->actingAs($this->toggleUser())
            ->post(route('admin.toggleStatus'), [
                'table'     => 'department_master',
                'column'    => 'active_inactive',
                'id_column' => 'department_name',
                'id'        => $row->pk,
                'status'    => $before === 1 ? 0 : 1,
            ])
            ->assertForbidden();

        $this->assertSame(
            $before,
            (int) DB::table('department_master')->where('pk', $row->pk)->value('active_inactive'),
            'a refused key column must not be written'
        );
    }

    /**
     * A real user_credentials row.
     *
     * This endpoint is gated by auth only, but the sidebar view composer that
     * runs while any response is rendered reads the actor's permissions, so a
     * stub actor cannot get through the stack here the way it can in the
     * DB-free unit test.
     */
    private function toggleUser(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to act as');
        }

        return $user;
    }
}
