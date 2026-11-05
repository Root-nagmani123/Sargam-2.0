<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PR #349 F-013: a Return House save that deadlocks is retried by runAllotmentTransaction(),
 * and the retry must not store the NOC upload again; if no attempt commits, nothing is left
 * on the public disk and no meta entry is written.
 *
 * No outer test transaction here, because runAllotmentTransaction() does not retry inside
 * one. Every attempt is made to deadlock, so every attempt rolls back and the database is
 * left as it was (asserted).
 */
class EstateReturnHouseRetryTest extends TestCase
{
    public function test_a_save_that_deadlocks_on_every_attempt_leaves_no_upload_and_no_meta(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('this test needs the application database');
        }

        $epd = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')->whereNotNull('estate_home_request_details')
            ->where(fn ($q) => $q->whereNull('estate_change_id')->orWhere('estate_change_id', -1))
            ->where(fn ($q) => $q->whereNull('return_home_status')->orWhere('return_home_status', 0))
            ->orderByDesc('pk')->first();
        if (! $epd) {
            $this->markTestSkipped('no active LBSNAA possession');
        }
        $admin = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Estate Admin')->where('m.model_type', User::class)->orderBy('m.model_id')->value('m.model_id');
        if (! $admin) {
            $this->markTestSkipped("no login holds the 'Estate Admin' role");
        }

        Storage::fake('public');
        Storage::fake('local');
        $attempts = 0;
        DB::listen(function ($query) use (&$attempts) {
            if (str_starts_with(strtolower($query->sql), 'update `estate_possession_details`')) {
                $attempts++;
                $pdo = new \PDOException('Deadlock found when trying to get lock');
                $pdo->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock'];

                throw new QueryException($query->sql, $query->bindings, $pdo);
            }
        });

        $this->withoutExceptionHandling();
        try {
            $this->actingAs(User::findOrFail($admin))->withSession(['user_roles' => ['Estate Admin']])
                ->post(route('admin.estate.possession-view.store'), [
                    'redirect_to' => 'return-house',
                    'employee_type' => 'LBSNAA',
                    'employee_select_id' => $epd->estate_home_request_details,
                    'remarks' => 'F-013 probe',
                    'noc_document' => UploadedFile::fake()->create('noc.pdf', 10, 'application/pdf'),
                ]);
            $this->fail('the save should fail after its last deadlock');
        } catch (QueryException $e) {
            $this->assertSame(1213, (int) $e->errorInfo[1]);
        }

        $this->assertSame(3, $attempts, 'the transaction was retried');
        $this->assertSame([], Storage::disk('public')->allFiles(), 'no NOC upload is left behind');
        $this->assertFalse(Storage::disk('local')->exists('estate/return-house-meta.json'), 'no meta entry for a save that never committed');
        $this->assertEquals($epd, DB::table('estate_possession_details')->where('pk', $epd->pk)->first(), 'the possession row is unchanged');
    }
}
