<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POST master.memo.type.master.store with pk[]=1 (PR #335 review F-015).
 *
 * A non-numeric pk is decrypted, and decrypt(array) throws a TypeError from
 * base64_decode(), which the DecryptException catch does not stop: a 500.
 * It must be refused as JSON 422, and a plain scalar pk still saves.
 */
class MemoTypeStoreArrayPkTest extends TestCase
{
    private const XHR = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: ' . $e->getMessage());
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

    private function admin(): User
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        if (! $id) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        return User::find($id);
    }

    public function test_an_array_pk_is_refused_with_422_json(): void
    {
        $pk = DB::table('memo_type_master')->insertGetId(['memo_type_name' => 'Fme Array Pk Probe', 'active_inactive' => 1]);

        $this->actingAs($this->admin())->withHeaders(self::XHR)
            ->post(route('master.memo.type.master.store'), [
                'pk' => [(string) $pk], 'memo_type_name' => 'Fme Array Pk Renamed', 'active_inactive' => 1,
            ])
            ->assertStatus(422)
            ->assertJson(['status' => false]);

        $this->assertSame('Fme Array Pk Probe', DB::table('memo_type_master')->where('pk', $pk)->value('memo_type_name'));
    }

    public function test_a_scalar_pk_still_updates_the_row(): void
    {
        $pk = DB::table('memo_type_master')->insertGetId(['memo_type_name' => 'Fme Scalar Pk Probe', 'active_inactive' => 1]);

        $this->actingAs($this->admin())->withHeaders(self::XHR)
            ->post(route('master.memo.type.master.store'), [
                'pk' => (string) $pk, 'memo_type_name' => 'Fme Scalar Pk Renamed', 'active_inactive' => 1,
            ])
            ->assertOk();

        $this->assertSame('Fme Scalar Pk Renamed', DB::table('memo_type_master')->where('pk', $pk)->value('memo_type_name'));
    }
}
