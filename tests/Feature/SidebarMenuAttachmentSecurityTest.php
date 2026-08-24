<?php

namespace Tests\Feature;

use App\Models\SidebarMenu\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cover for the stored-XSS finding on the sidebar menu attachment upload (PR #311).
 *
 * The defect: storeAttachment() named the stored file from getClientOriginalExtension(),
 * while the `mimes:` rule validates guessExtension() (derived from CONTENT). The two
 * never met, so a genuine PNG uploaded as "payload.html" passed validation and landed
 * as .html on the public disk, where the browser reads it back as markup.
 *
 * These assert on the EXTENSION THAT REACHES DISK, not on the validation outcome —
 * the payload is a valid image and is supposed to pass validation. The bug was never
 * that the file was accepted; it was what it was called afterwards. A test asserting
 * "upload rejected" would pass against the vulnerable code and prove nothing.
 */
class SidebarMenuAttachmentSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private const DIR = 'menu-attachments';

    /** Files this test wrote, removed in tearDown — disk writes do not roll back. */
    private array $written = [];

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        return $user;
    }

    /**
     * A real PNG — byte-for-byte a valid image, so guessExtension() says "png" and
     * the mimes: rule accepts it — carrying a script tag after the image data, and
     * offered under an attacker-chosen filename.
     */
    private function polyglot(string $clientName): UploadedFile
    {
        $source = public_path('admin_assets/images/alert/alert.png');

        if (! is_file($source)) {
            $this->markTestSkipped('No source PNG available to build the payload.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'atc');
        file_put_contents($tmp, file_get_contents($source).'<script>alert(document.cookie)</script>');

        return new UploadedFile($tmp, $clientName, 'image/png', null, true);
    }

    private function storeAttachment(UploadedFile $file): string
    {
        $method = new \ReflectionMethod(
            \App\Http\Controllers\SidebarMenu\MenuController::class,
            'storeAttachment'
        );
        $method->setAccessible(true);

        $path = $method->invoke(app(\App\Http\Controllers\SidebarMenu\MenuController::class), $file);
        $this->written[] = $path;

        return $path;
    }

    /** The finding itself: the client's extension must never become the stored one. */
    public function test_client_supplied_extension_does_not_reach_disk(): void
    {
        $path = $this->storeAttachment($this->polyglot('payload.html'));

        $this->assertStringEndsNotWith('.html', $path,
            'A valid PNG uploaded as payload.html was stored as .html — it is served from the '
            .'public disk as text/html, which is the stored-XSS finding.');
        $this->assertStringEndsWith('.png', $path,
            'The stored extension must be derived from the file content (guessExtension).');
    }

    /**
     * The same defect with the extensions that actually matter alongside .html.
     * Laravel's shouldBlockPhpUpload() covers php/phtml/phar and nothing else, so
     * these are the ones that were live.
     */
    public function test_active_content_extensions_do_not_reach_disk(): void
    {
        foreach (['payload.html', 'payload.htm', 'payload.svg', 'payload.xhtml'] as $name) {
            $path = $this->storeAttachment($this->polyglot($name));

            $this->assertStringEndsWith('.png', $path,
                "Upload named {$name} should be stored under its content type, not its client name.");
        }
    }

    /** A legitimate upload must still keep its real, readable name and type. */
    public function test_legitimate_upload_keeps_its_content_derived_extension(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'atc');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");

        $path = $this->storeAttachment(new UploadedFile($tmp, 'Annual Report 2026.pdf', 'application/pdf', null, true));

        $this->assertStringEndsWith('.pdf', $path, 'A genuine PDF must still be stored as .pdf.');
        $this->assertStringContainsString('annual-report-2026', $path,
            'The readable slugged filename is the reason storeAs() is used at all — keep it.');
    }

    /** End-to-end: the same payload through the real route and middleware stack. */
    public function test_upload_through_the_menu_store_route_is_not_written_as_html(): void
    {
        $group = \App\Models\SidebarMenu\MenuGroup::query()->first();

        if (! $group) {
            $this->markTestSkipped('No menu_groups row to attach a menu to.');
        }

        $name = 'Attachment Security '.Str::random(6);

        $response = $this->actingAs($this->admin())->post(route('sidebar.menus.store'), [
            'category_id' => $group->category_id,
            'group_id'    => $group->id,
            'name'        => $name,
            'is_active'   => 1,
            'attachment'  => $this->polyglot('payload.html'),
        ]);

        $response->assertSessionHasNoErrors();

        $menu = Menu::query()->where('name', $name)->first();
        $this->assertNotNull($menu, 'The menu row should have been created.');

        if (filled($menu->attachment)) {
            $this->written[] = $menu->attachment;
        }

        $this->assertStringEndsNotWith('.html', (string) $menu->attachment,
            'The stored attachment path must not carry the client-supplied .html extension.');

        $this->assertEmpty(
            array_filter(
                Storage::disk('public')->files(self::DIR),
                fn ($f) => str_ends_with(strtolower($f), '.html')
            ),
            'No .html file may exist in the public menu-attachments directory.'
        );
    }
}
