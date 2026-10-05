<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every rendered page must leave the output-buffer stack exactly as it found it.
 *
 * It did not. Each admin page leaked one open buffer level holding stray
 * whitespace, and the cause was a single unbalanced directive in the shared
 * layout: a block `@section` was opened in admin/layouts/master.blade.php and
 * never closed.
 *
 * The mechanism is worth stating, because the symptom points away from the
 * cause. Blade renders an extends-child by evaluating the child inside one
 * ob_start(), then echoing the rendered parent at the end. The parent is
 * evaluated inside an ob_start() of its own, and its closing ob_get_clean()
 * closes whatever level is on top - which, with a section left open, is the
 * SECTION's buffer, not the parent's. The parent's own level survives, the
 * child's closing ob_get_clean() consumes that one instead, and the child's
 * level is the one stranded. So the leaked buffer belonged to the child and
 * held the child's stray whitespace, while the defect was in the parent.
 *
 * Consequences, in order of how much they matter:
 *
 *  - app/Http/Middleware/CompressResponse.php has to absorb the stray output
 *    before gzipping, or the response is a newline followed by a gzip stream and
 *    the page renders blank. That middleware became load-bearing for
 *    correctness rather than for performance, and it returns early when a
 *    response is too small or not compressible.
 *  - PHPUnit marks any test that renders an admin page RISKY ("did not (only)
 *    close its own output buffers"), so those tests never report a clean pass
 *    and would fail outright under --fail-on-risky.
 *
 * Two guards here: the executed one, which is the real check, and a static one
 * that catches a new offender in any blade rather than only in the handful of
 * pages this test dispatches.
 */
class ResponseOutputBufferTest extends TestCase
{
    use DatabaseTransactions;

    /** Routes that render a full page through the shared admin layout. */
    private const PAGES = [
        'admin.users.index',
        'admin.roles.index',
        'admin.dashboard',
    ];

    public function test_rendering_an_admin_page_leaves_no_output_buffer_open(): void
    {
        $user = $this->superAdmin();

        foreach (self::PAGES as $name) {
            if (! \Route::has($name)) {
                continue;
            }

            $before = ob_get_level();
            $response = $this->actingAs($user)->get(route($name));
            $after = ob_get_level();

            // Unwind before asserting, so one failure cannot cascade into the rest
            // of the suite by leaving the stack deeper than it found it.
            $leaked = '';
            while (ob_get_level() > $before) {
                $leaked .= (string) ob_get_contents();
                ob_end_clean();
            }

            $this->assertSame(200, $response->getStatusCode(), "{$name} should render");
            $this->assertSame(
                $before,
                $after,
                "{$name} left ".($after - $before).' output buffer level(s) open holding '
                .var_export($leaked, true).' - look for a section or push opened and never closed '
                .'in the layout this page extends.'
            );
        }
    }

    /**
     * The layout fix must not have cost the styles it was wrapping.
     *
     * Deleting the unclosed section was the right repair rather than closing it:
     * the css section is yielded from admin/layouts/pre_header, which is included
     * ABOVE the point where it was opened, so a section opened there could never
     * be yielded - closing it would have captured the style block and dropped it
     * from the page instead.
     */
    public function test_the_layout_still_emits_its_own_style_block(): void
    {
        $html = $this->actingAs($this->superAdmin())->get(route('admin.users.index'))->getContent();

        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        $this->assertStringStartsWith('<!DOCTYPE', ltrim((string) $html));
        $this->assertStringContainsString('#bbd9f7', (string) $html);
        $this->assertStringContainsString('.mini-nav', (string) $html);
    }

    /**
     * Repo-wide: no blade opens a buffering directive it does not close.
     *
     * Catches the next offender in a view no test dispatches. Blade comments,
     * raw-PHP blocks and verbatim blocks are stripped first - the shared layout
     * discusses a section name inside a // comment in a raw-PHP block, and Blade
     * does not compile that.
     */
    public function test_no_blade_leaves_a_buffering_directive_unclosed(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            $source = $this->stripNonDirectives((string) file_get_contents($path));

            $opens = $this->countBlockSections($source)
                + preg_match_all('/@(push|prepend|pushOnce|slot)\s*\(/', $source);

            $closes = preg_match_all(
                '/@(endsection|stop|show|append|overwrite|endpush|endprepend|endPushOnce|endslot)\b/i',
                $source
            );

            if ($opens !== $closes) {
                $offenders[] = str_replace('\\', '/', $path)." (opens {$opens}, closes {$closes})";
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These blades open a buffering directive they never close, which strands an output buffer:\n"
            .implode("\n", $offenders)
        );
    }

    /** Block-form section only - the two-argument form buffers nothing. */
    private function countBlockSections(string $source): int
    {
        $count = 0;

        foreach ($this->directiveArguments($source, 'section') as $args) {
            if (! $this->hasTopLevelComma($args)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * The raw argument text inside each occurrence of the named directive.
     *
     * @return list<string>
     */
    private function directiveArguments(string $source, string $name): array
    {
        $out = [];
        $offset = 0;
        $length = strlen($source);

        while (($at = strpos($source, '@'.$name, $offset)) !== false) {
            $after = $at + strlen($name) + 1;
            $offset = $after;

            $open = strpos($source, '(', $after);

            // sectionMissing( and the like must not be read as section(.
            if ($open === false || trim(substr($source, $after, $open - $after)) !== '') {
                continue;
            }

            $depth = 0;
            $quote = null;

            for ($i = $open; $i < $length; $i++) {
                $c = $source[$i];

                if ($quote !== null) {
                    if ($c === '\\') {
                        $i++;
                    } elseif ($c === $quote) {
                        $quote = null;
                    }

                    continue;
                }

                if ($c === "'" || $c === '"') {
                    $quote = $c;
                } elseif ($c === '(') {
                    $depth++;
                } elseif ($c === ')') {
                    $depth--;

                    if ($depth === 0) {
                        $out[] = substr($source, $open + 1, $i - $open - 1);
                        $offset = $i;
                        break;
                    }
                }
            }
        }

        return $out;
    }

    private function hasTopLevelComma(string $args): bool
    {
        $depth = 0;
        $quote = null;
        $length = strlen($args);

        for ($i = 0; $i < $length; $i++) {
            $c = $args[$i];

            if ($quote !== null) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($c === "'" || $c === '"') {
                $quote = $c;
            } elseif ($c === '(' || $c === '[') {
                $depth++;
            } elseif ($c === ')' || $c === ']') {
                $depth--;
            } elseif ($c === ',' && $depth === 0) {
                return true;
            }
        }

        return false;
    }

    private function stripNonDirectives(string $source): string
    {
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);
        $source = preg_replace('/@php\b.*?@endphp\b/s', '', (string) $source);
        $source = preg_replace('/@verbatim\b.*?@endverbatim\b/s', '', (string) $source);

        return (string) $source;
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];

        $walker = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($walker as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
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
