<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * An admin page must leave the output buffer stack exactly as it found it, and
 * must not emit anything before <!DOCTYPE html>.
 *
 * BladeSectionBalanceTest catches the cause by scanning source. This catches the
 * effect by rendering, because the two can come apart: a section opened inside
 * one branch of an @if and closed in the other balances on paper and leaks at
 * runtime.
 *
 * The stray bytes matter beyond tidiness. They are flushed in front of the
 * response, and in front of a gzip stream they make the page undecodable - the
 * blank-page incident CompressResponse still carries a workaround for.
 */
class AdminPageOutputBufferTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Probe before superAdmin() queries: with no connection that query throws,
        // and the skip below it would never be reached.
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the output-buffer test renders a real admin page and needs the application database');
        }
    }

    public function test_an_admin_page_leaves_no_output_buffer_open(): void
    {
        $superAdmin = $this->superAdmin();

        $before = ob_get_level();
        $response = $this->actingAs($superAdmin)->get('/admin/reports/bank-report');
        $after = ob_get_level();

        // Unwind before asserting, so a failure here does not also report every
        // later test in this file as risky.
        $leaked = $after - $before;
        while (ob_get_level() > $before) {
            ob_end_clean();
        }

        $response->assertOk();

        $this->assertSame(
            0,
            $leaked,
            'the page left '.$leaked.' output buffer(s) open. A Blade directive that calls '
            .'ob_start() - @section, @push, @component, @slot - was not closed on this render path.'
        );
    }

    public function test_an_admin_page_emits_nothing_before_the_doctype(): void
    {
        $superAdmin = $this->superAdmin();

        $before = ob_get_level();
        $response = $this->actingAs($superAdmin)->get('/admin/reports/bank-report');

        // What the browser actually receives is not just the response body. Anything
        // left in an unclosed buffer is flushed around it - which is exactly what
        // CompressResponse has to fold back in before it can gzip. Assert on the
        // assembled bytes, or this passes while the page still ships stray output.
        $stray = '';
        while (ob_get_level() > $before) {
            $stray = ob_get_clean().$stray;
        }

        $assembled = $stray.$response->getContent();

        $this->assertSame(
            '',
            $stray,
            'the page flushed '.strlen($stray).' byte(s) outside the response body: '
            .json_encode(substr($stray, 0, 40))
        );

        $this->assertStringStartsWith(
            '<!DOCTYPE html>',
            $assembled,
            'the page begins with '.json_encode(substr($assembled, 0, 20)).' rather than the doctype. '
            .'Bytes in front of the document are flushed ahead of the body, and ahead of a gzip stream '
            .'they make the response undecodable.'
        );
    }

    private function superAdmin(): User
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        if (! $id || ! ($user = User::find($id))) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        return $user;
    }
}
