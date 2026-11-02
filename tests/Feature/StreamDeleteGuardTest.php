<?php

namespace Tests\Feature;

use App\Models\Stream;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * An ACTIVE stream must not be deletable — not from the listing, and not by
 * posting DELETE by hand.
 *
 * The listing is the StreamMasterDataTable server-side grid: GET /stream
 * renders an empty table and the rows arrive from the same URL as an XHR JSON
 * feed. So the listing guard is asserted on the feed's `actions` cell — the
 * HTML the browser injects into each row — not on the page.
 *
 * stream_master has no `status` column; its status lives in `active_inactive`.
 * An earlier guard read `$stream->status` (always null) and offered Delete on
 * every row, which is what the schema test below pins down.
 */
class StreamDeleteGuardTest extends TestCase
{
    private bool $inTransaction = false;

    /**
     * The transaction is opened by hand rather than by DatabaseTransactions.
     *
     * That trait is booted from parent::setUp(), so it opens the connection —
     * and throws — BEFORE any guard placed after that call can run. With the
     * trait in place the markTestSkipped() below was unreachable and this file
     * reported errors, not skips, on a host with no database. Same shape as
     * ToggleStatusEndpointTest, which is why that file skips cleanly.
     */
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
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('No user rows in this database to act as.');
        }

        return $user;
    }

    private function probe(string $name, int $active): Stream
    {
        $stream = new Stream();
        $stream->stream_name     = $name;
        $stream->active_inactive = $active;
        $stream->save();

        return $stream;
    }

    public function test_stream_master_has_no_status_column_so_the_guard_must_not_read_one(): void
    {
        $this->assertTrue(
            Schema::hasColumn('stream_master', 'active_inactive'),
            'stream_master stores its status in active_inactive.'
        );
        $this->assertFalse(
            Schema::hasColumn('stream_master', 'status'),
            'There is no stream_master.status — a guard reading it is always null.'
        );
    }

    public function test_an_active_stream_cannot_be_deleted_from_the_listing(): void
    {
        $stream = $this->probe('Delete Guard Probe (active)', 1);

        $actions = $this->actionsCellFor($stream);

        $this->assertStringContainsString('Cannot delete an active stream', $actions);

        // Assert on the DELETE form, not on the row's URL: the Edit link is
        // /stream/{pk}/edit, so a bare "stream/{pk}" match is satisfied by a row
        // that offers no delete at all.
        $this->assertStringNotContainsString('value="DELETE"', $actions);
        $this->assertStringNotContainsString('action="' . route('stream.destroy', $stream->pk) . '"', $actions);
    }

    public function test_an_inactive_stream_still_offers_delete(): void
    {
        $stream = $this->probe('Delete Guard Probe (inactive)', 0);

        $actions = $this->actionsCellFor($stream);

        $this->assertStringContainsString('value="DELETE"', $actions);
        $this->assertStringContainsString('action="' . route('stream.destroy', $stream->pk) . '"', $actions);
        $this->assertStringNotContainsString('Cannot delete an active stream', $actions);
    }

    public function test_destroy_refuses_an_active_stream_and_deletes_an_inactive_one(): void
    {
        $active   = $this->probe('Delete Guard Probe (active, direct)', 1);
        $inactive = $this->probe('Delete Guard Probe (inactive, direct)', 0);

        $this->actingAs($this->admin())->delete(route('stream.destroy', $active->pk))
            ->assertRedirect(route('stream.index'))
            ->assertSessionHas('error');
        $this->assertTrue(Stream::where('pk', $active->pk)->exists(), 'An active stream must survive a direct DELETE.');

        $this->actingAs($this->admin())->delete(route('stream.destroy', $inactive->pk))
            ->assertRedirect(route('stream.index'))
            ->assertSessionHas('success');
        $this->assertFalse(Stream::where('pk', $inactive->pk)->exists(), 'An inactive stream is still deletable.');
    }

    /**
     * The `actions` HTML of $stream's row in the grid's XHR feed, found with the
     * grid's own search box so the probe row is on the first page.
     */
    private function actionsCellFor(Stream $stream): string
    {
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 100,
            'search'  => ['value' => $stream->stream_name, 'regex' => 'false'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
                ['data' => 'stream_name', 'name' => 'stream_master.stream_name', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'status', 'name' => 'status', 'searchable' => 'false', 'orderable' => 'true'],
                ['data' => 'actions', 'name' => 'actions', 'searchable' => 'false', 'orderable' => 'false'],
            ],
        ]);

        $json = $this->actingAs($this->admin())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->get('/stream?' . $query)
            ->assertOk()
            ->json();

        $this->assertIsArray($json['data'] ?? null, 'GET /stream as XHR must return the DataTables feed.');

        foreach ($json['data'] as $row) {
            if (($row['stream_name'] ?? null) === $stream->stream_name) {
                return (string) $row['actions'];
            }
        }

        $this->fail('Probe stream "' . $stream->stream_name . '" was not found in the grid feed.');
    }
}
