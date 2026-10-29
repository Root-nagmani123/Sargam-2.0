<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Repository-wide cover for the F-001 upload bug class (F-007, PR #311).
 *
 * The defect shape: the name a file is STORED under is taken from
 * getClientOriginalExtension(), which the uploader controls, while the type CHECK
 * reads content. Where those disagree, a genuine image or PDF can be persisted with an
 * active-content extension on the public disk.
 *
 * The guard test below is the durable part: it fails if anyone reintroduces the pattern
 * anywhere in app/, which a per-site test could never do.
 */
class UploadExtensionHardeningTest extends TestCase
{
    use DatabaseTransactions;

    /** Sites deliberately left reading the client extension, each with why. */
    private const ALLOWED_CLIENT_EXTENSION_READS = [
        // Friendlier early error only. The real gate is the
        // 'complaint_img_url.*' => file|image|mimes rule, and storage uses store(),
        // which names from content.
        'app/Http/Controllers/Admin/IssueManagement/IssueManagementController.php',
        // "No Excel" guard, now paired with a content check on the same line's branch;
        // the stored path is written by store(), which names from content.
        'app/Http/Controllers/Admin/Registration/FrontPageController.php',
    ];

    /**
     * No stored filename anywhere in app/ may be built from the client's extension.
     *
     * Scans source rather than behaviour on purpose: this is the only assertion that
     * covers the 40 sites at once and keeps covering new ones.
     */
    public function test_no_stored_filename_is_built_from_the_client_extension(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            $source = file_get_contents($file->getPathname());

            foreach (explode("\n", $source) as $i => $line) {
                if (! str_contains($line, 'getClientOriginalExtension')) {
                    continue;
                }
                // Comments describing the rule are not uses of it.
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '//')) {
                    continue;
                }
                if (in_array($relative, self::ALLOWED_CLIENT_EXTENSION_READS, true)) {
                    continue;
                }
                $offenders[] = $relative.':'.($i + 1).' — '.trim($line);
            }
        }

        $this->assertSame([], $offenders,
            "A stored filename must never be built from getClientOriginalExtension(); use "
            ."safe_upload_extension(\$file) instead. Offending sites:\n".implode("\n", $offenders));
    }

    /** The helper takes its answer from content, whatever the file is called. */
    public function test_helper_derives_the_extension_from_content(): void
    {
        $png = public_path('admin_assets/images/alert/alert.png');

        if (! is_file($png)) {
            $this->markTestSkipped('No source PNG available.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmp, file_get_contents($png));

        $this->assertSame('png', safe_upload_extension(new UploadedFile($tmp, 'payload.html', 'image/png', null, true)));
        $this->assertSame('png', safe_upload_extension(new UploadedFile($tmp, 'payload.svg', 'image/png', null, true)));
        $this->assertSame('dat', safe_upload_extension(null));
        $this->assertSame('', safe_upload_extension(null, ''));
    }

    /**
     * uploadPdf() gated on the client extension while store() named the file from
     * content, so an HTML document called notes.pdf was written as <hash>.html onto the
     * public disk. Content is now validated.
     */
    public function test_summernote_upload_rejects_html_disguised_as_pdf(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        $before = Storage::disk('public')->exists('summernote/pdf')
            ? Storage::disk('public')->files('summernote/pdf')
            : [];

        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmp, "<html><body><script>alert(document.cookie)</script></body></html>");

        $response = $this->actingAs($user)->post(route('admin.summernote.upload'), [
            'file' => new UploadedFile($tmp, 'notes.pdf', 'application/pdf', null, true),
        ]);

        $this->assertSame(422, $response->getStatusCode(),
            'An HTML document named notes.pdf must be rejected on content, not accepted on its name.');

        $after = Storage::disk('public')->exists('summernote/pdf')
            ? Storage::disk('public')->files('summernote/pdf')
            : [];

        $this->assertSame($before, $after, 'Nothing may be written to disk for a rejected upload.');
    }
}
