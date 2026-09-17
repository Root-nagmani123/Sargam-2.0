<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * POST /admin/toggle-status must only ever flip a status column on a known table.
 *
 * The endpoint used to read the table, the column, the id column and the value
 * from the request and pass all four to the query builder, behind `web,auth`
 * alone. That is an arbitrary-write primitive: any authenticated account could
 * UPDATE any column of any row of any table. It was never classic SQL injection
 * - the builder quotes identifiers - which is most of why it read as harmless.
 *
 * These tests assert the refusals AND that the write did not happen, because a
 * 422 on its own would not prove the row was untouched.
 */
class ToggleStatusAllowListTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to authenticate as');
        }

        return $user;
    }

    /** A table/column pair that is genuinely on the allow-list, with a real row. */
    private function toggleableRow(): array
    {
        foreach (['country_master' => 'active_inactive', 'state_master' => 'active_inactive'] as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $row = DB::table($table)->first();

            if ($row && isset($row->pk)) {
                return [$table, $column, $row->pk, (int) $row->{$column}];
            }
        }

        $this->markTestSkipped('no allow-listed master row available in this database');
    }

    public function test_an_allow_listed_toggle_still_works(): void
    {
        [$table, $column, $pk, $was] = $this->toggleableRow();
        $flipped = $was === 1 ? 0 : 1;

        $this->actingAs($this->actor())
            ->post(route('admin.toggleStatus'), [
                'table' => $table,
                'column' => $column,
                'id' => $pk,
                'status' => $flipped,
            ])
            ->assertOk();

        $this->assertSame(
            $flipped,
            (int) DB::table($table)->where('pk', $pk)->value($column),
            'the legitimate toggle must still write - an allow-list that breaks the feature is not a fix'
        );
    }

    /**
     * The escalation shape: a table nobody ever toggles, and a column that has
     * nothing to do with status.
     */
    public function test_a_table_outside_the_allow_list_is_refused(): void
    {
        $before = DB::table('user_credentials')->orderBy('pk')->first();

        if (! $before) {
            $this->markTestSkipped('no user_credentials row');
        }

        // A column worth protecting, chosen from the live schema rather than
        // guessed: this table is not toggleable at all, so no column of it is.
        $target = collect(Schema::getColumnListing('user_credentials'))
            ->first(fn ($c) => in_array($c, ['jbp_password', 'remember_token', 'email_id'], true));

        if (! $target) {
            $this->markTestSkipped('no recognisable sensitive column on user_credentials');
        }

        $this->actingAs($this->actor())
            ->post(route('admin.toggleStatus'), [
                'table' => 'user_credentials',
                'column' => $target,
                'id' => $before->pk,
                'status' => 1,
            ])
            ->assertStatus(422);

        $after = DB::table('user_credentials')->where('pk', $before->pk)->first();

        $this->assertEquals($before->{$target}, $after->{$target}, "{$target} must be untouched");
    }

    /** Right table, wrong column - the near-miss that would restore the primitive. */
    public function test_a_column_the_table_does_not_toggle_is_refused(): void
    {
        [$table, $column, $pk] = $this->toggleableRow();

        $other = collect(Schema::getColumnListing($table))
            ->first(fn ($c) => ! in_array($c, [$column, 'pk'], true));

        if (! $other) {
            $this->markTestSkipped("no second column on {$table}");
        }

        $was = DB::table($table)->where('pk', $pk)->value($other);

        $this->actingAs($this->actor())
            ->post(route('admin.toggleStatus'), [
                'table' => $table,
                'column' => $other,
                'id' => $pk,
                'status' => 1,
            ])
            ->assertStatus(422);

        $this->assertEquals($was, DB::table($table)->where('pk', $pk)->value($other));
    }

    /**
     * Right table, right column, but keyed on a column of the caller's choosing -
     * which would let one request update every row whose chosen column matches.
     */
    public function test_an_id_column_of_the_callers_choosing_is_refused(): void
    {
        [$table, $column, $pk, $was] = $this->toggleableRow();

        $this->actingAs($this->actor())
            ->post(route('admin.toggleStatus'), [
                'table' => $table,
                'column' => $column,
                'id_column' => $column,
                'id' => $was,
                'status' => $was === 1 ? 0 : 1,
            ])
            ->assertStatus(422);

        $this->assertSame($was, (int) DB::table($table)->where('pk', $pk)->value($column));
    }

    /** A status toggle writes 0 or 1; anything else is somebody else's payload. */
    public function test_a_status_outside_zero_or_one_is_refused(): void
    {
        [$table, $column, $pk, $was] = $this->toggleableRow();

        $this->actingAs($this->actor())
            ->post(route('admin.toggleStatus'), [
                'table' => $table,
                'column' => $column,
                'id' => $pk,
                'status' => 7,
            ])
            ->assertStatus(422);

        $this->assertSame($was, (int) DB::table($table)->where('pk', $pk)->value($column));
    }

    /**
     * Every table named in the cache-bump branches must be on the allow-list,
     * or its screen silently stops toggling.
     */
    public function test_every_cache_bumped_table_is_allow_listed(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/UserController.php'));
        $start = strpos($source, 'public function toggleStatus(');
        $end = strpos($source, 'public function assignRole(', $start);
        $body = substr($source, $start, $end - $start);

        preg_match_all("/\\\$table === '([a-z_]+)'/", $body, $matches);
        $this->assertNotEmpty($matches[1], 'expected the cache-bump branches to be found');

        $reflection = new \ReflectionClass(\App\Http\Controllers\Admin\UserController::class);
        $allowed = $reflection->getConstant('TOGGLEABLE');

        foreach (array_unique($matches[1]) as $table) {
            $this->assertArrayHasKey($table, $allowed,
                "{$table} has a cache-bump branch but is not toggleable, so its screen cannot work");
        }
    }
}
