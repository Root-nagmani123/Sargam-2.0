<?php

namespace Tests\Feature;

use App\Models\Stream;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The stream listing must not offer Delete on an ACTIVE stream.
 *
 * The guard read `$stream->status`, and stream_master has no `status` column —
 * its status lives in `active_inactive`, which is the column this PR's own
 * status switch and toggle allow-list already use. An undefined attribute is
 * null, `null == 1` is false, so the guard fell through to the else branch and
 * rendered the live Delete form for every row, including the active ones it was
 * written to protect.
 *
 * Asserted through the rendered page rather than by reading the template, so it
 * still holds if the markup is restyled.
 */
class StreamDeleteGuardTest extends TestCase
{
    /** Output-buffer nesting level on entry, so tearDown can unwind to it. */
    private int $obLevel = 0;

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

        $this->obLevel = ob_get_level();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: ' . $e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    /**
     * Rendering the admin layout leaves an output buffer open (a pre-existing
     * property of the layout, not of this PR), which PHPUnit reports as a risky
     * test. Unwind to the level we started at, the same way MasterGridExportTest
     * does around a streamed response.
     */
    protected function tearDown(): void
    {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }

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
        $stream = new Stream();
        $stream->stream_name     = 'Delete Guard Probe (active)';
        $stream->active_inactive = 1;
        $stream->save();

        $html = $this->actingAs($this->admin())->get('/stream')->assertOk()->getContent();

        $row = $this->rowFor($html, 'Delete Guard Probe (active)');

        $this->assertStringContainsString('Cannot delete active stream', $row);

        // Assert on the DELETE form, not on the row's URL: the Edit link is
        // /stream/{pk}/edit, so a bare "stream/{pk}" match is satisfied by a row
        // that offers no delete at all.
        $this->assertStringNotContainsString('value="DELETE"', $row);
    }

    public function test_an_inactive_stream_still_offers_delete(): void
    {
        $stream = new Stream();
        $stream->stream_name     = 'Delete Guard Probe (inactive)';
        $stream->active_inactive = 0;
        $stream->save();

        $html = $this->actingAs($this->admin())->get('/stream')->assertOk()->getContent();

        $row = $this->rowFor($html, 'Delete Guard Probe (inactive)');

        $this->assertStringContainsString('value="DELETE"', $row);
        $this->assertStringContainsString('stream/' . $stream->pk . '"', $row);
        $this->assertStringNotContainsString('Cannot delete active stream', $row);
    }

    /**
     * The single <tr> that carries $name.
     *
     * The listing paginates at 10, so a probe row can land on a later page;
     * walk the pages until the row is found rather than assuming page 1.
     */
    private function rowFor(string $html, string $name): string
    {
        $page = 1;

        while (true) {
            if (str_contains($html, $name)) {
                // Bound each chunk at the CLOSING tag: splitting on the opening
                // tag leaves the final row running to the end of the document,
                // which swallows the rest of the page into the "row".
                foreach (preg_split('#</tr>#i', $html) as $row) {
                    if (str_contains($row, $name)) {
                        return $row;
                    }
                }
            }

            $page++;

            if ($page > 40) {
                $this->fail('Probe stream row "' . $name . '" was not found in the listing.');
            }

            $next = $this->actingAs($this->admin())->get('/stream?page=' . $page);

            if ($next->getStatusCode() !== 200) {
                $this->fail('Probe stream row "' . $name . '" was not found in the listing.');
            }

            $html = $next->getContent();

            if (! str_contains($html, '<tbody')) {
                $this->fail('Probe stream row "' . $name . '" was not found in the listing.');
            }
        }
    }
}
