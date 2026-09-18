<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The exports converted off dompdf's in-view PHP must still produce a PDF.
 *
 * Nineteen call sites had `isPhpEnabled => true` flipped off and their in-view
 * `<script type="text/php">` page-number block replaced by a canvas stamp after
 * render. ExportPdfPhpDisabledTest guards the source - that the flag stays off
 * and that no blade carries the block again - but source assertions cannot show
 * that the documents still render. This dispatches three of the converted
 * endpoints through the real router and checks the bytes that come back.
 *
 * Skips rather than fails when a route or a Super Admin account is absent: a
 * permanently red test teaches people to ignore the suite.
 */
class ConvertedPdfExportsRenderTest extends TestCase
{
    use DatabaseTransactions;

    /** Converted endpoints, one per distinct export blade shape. */
    private const EXPORTS = [
        'admin.issue-categories.export' => ['format' => 'pdf'],
        'admin.issue-priorities.export' => ['format' => 'pdf'],
        'admin.issue-management.export.pdf' => [],
    ];

    public function test_each_converted_export_still_returns_a_pdf(): void
    {
        $user = $this->superAdmin();
        $checked = 0;

        foreach (self::EXPORTS as $name => $params) {
            if (! \Route::has($name)) {
                continue;
            }

            $response = $this->actingAs($user)->get(route($name, $params));
            $body = (string) $response->getContent();

            // The layout leak is fixed, but a render that dies mid-buffer would
            // still leave the stack deeper than it found it.
            while (ob_get_level() > 1) {
                ob_end_clean();
            }

            $this->assertSame(200, $response->getStatusCode(), "{$name} should render");
            $this->assertSame('%PDF', substr($body, 0, 4),
                "{$name} must return a PDF - the in-view page-number script was removed from its blade, "
                .'so a failure here means the canvas stamp did not replace it correctly');
            $this->assertGreaterThan(1024, strlen($body), "{$name} returned a suspiciously small PDF");

            $checked++;
        }

        if ($checked === 0) {
            $this->markTestSkipped('none of the converted export routes are registered');
        }
    }

    private function superAdmin(): User
    {
        $roleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $pk = $roleId ? DB::table('model_has_roles')->where('role_id', $roleId)->value('model_id') : null;
        $user = $pk ? User::where('pk', $pk)->first() : null;

        if (! $user) {
            $this->markTestSkipped('no Super Admin account in this database');
        }

        return $user;
    }
}
