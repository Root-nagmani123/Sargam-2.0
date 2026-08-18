<?php

namespace Tests\Feature;

use App\Models\FC\FcJoiningSampleDocument;
use App\Models\User;
use App\Rules\SafeUploadedDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end cover for the CWE-434 finding on /fc-reg/admin/sample-documents.
 *
 * These hit the real route through the real middleware stack, so they prove the
 * controller wiring rejects the payload — not just that the rule object would.
 * Every assertion also checks the DISK, because "validation failed" is only a
 * fix if nothing was written.
 */
class FcSampleDocumentUploadSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private const DIR = 'joining_sample_documents';

    private function admin(): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        return $user;
    }

    /** Files present in the upload dir before the request. */
    private function storedFiles(): array
    {
        return Storage::disk('public')->exists(self::DIR)
            ? Storage::disk('public')->files(self::DIR)
            : [];
    }

    private function submit(UploadedFile $file, string $fieldName): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())
            ->from(route('fc-reg.admin.sample-documents.index'))
            ->post(route('fc-reg.admin.sample-documents.store'), [
                'field_name'     => $fieldName,
                'document_title' => 'Security test',
                'sample_file'    => $file,
            ]);
    }

    private function fakeUpload(string $name, string $bytes): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmp, $bytes);

        // test mode = true: skip is_uploaded_file(), keep the real error code path.
        return new UploadedFile($tmp, $name, null, null, true);
    }

    private function pdfBytes(): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
    }

    /**
     * @dataProvider maliciousPayloads
     */
    public function test_malicious_upload_is_rejected_and_nothing_is_written(string $label, string $name, string $bytes): void
    {
        $before = $this->storedFiles();

        $response = $this->submit($this->fakeUpload($name, $bytes), 'doc_sec_' . bin2hex(random_bytes(4)));

        // NB: assertSessionHasErrors()'s 3rd arg is the error BAG, not a message.
        $response->assertSessionHasErrors('sample_file');

        $this->assertSame(
            $before,
            $this->storedFiles(),
            "[$label] a file was written to disk despite being rejected"
        );

        $this->assertDatabaseMissing('fc_joining_sample_documents', ['document_title' => 'Security test']);
    }

    public static function maliciousPayloads(): array
    {
        $shell = "<?php system(\$_GET['c']); ?>";
        $png   = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return [
            'web shell renamed .pdf'      => ['web shell renamed .pdf', 'shell.pdf', $shell],
            'double extension .php.pdf'   => ['double extension .php.pdf', 'shell.php.pdf', $shell],
            'double extension .phtml.jpg' => ['double extension .phtml.jpg', 'x.phtml.jpg', $shell],
            'raw .php'                    => ['raw .php', 'shell.php', $shell],
            'png/php polyglot'            => ['png/php polyglot', 'poly.png', $png . $shell],
            'jpeg header + php'           => ['jpeg header + php', 'poly.jpg', "\xFF\xD8\xFF" . $shell],
            'html xss vector'             => ['html xss vector', 'page.html', '<script>alert(1)</script>'],
            'svg with script'             => ['svg with script', 'logo.svg', '<svg onload=alert(1)>'],
            'htaccess override'           => ['htaccess override', '.htaccess', 'AddType application/x-httpd-php .pdf'],
            'magic bytes not at offset 0' => ['magic bytes not at offset 0', 'late.pdf', 'AAAA%PDF-1.4 rest'],
            'empty file'                  => ['empty file', 'empty.pdf', ''],
        ];
    }

    public function test_genuine_pdf_is_accepted_and_stored_under_a_server_generated_name(): void
    {
        $field = 'doc_sec_' . bin2hex(random_bytes(4));

        $response = $this->submit(
            // Deliberately hostile-looking but harmless original name.
            $this->fakeUpload('My Form <script>.pdf', $this->pdfBytes()),
            $field
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $row = FcJoiningSampleDocument::where('field_name', $field)->first();
        $this->assertNotNull($row, 'the sample document row was not created');

        // Stored name must be server-derived: 40 random chars + verified extension.
        $this->assertMatchesRegularExpression(
            '#^storage/' . self::DIR . '/[A-Za-z0-9]{40}\.pdf$#',
            $row->sample_file_path,
            'stored path is not a server-generated random name with the verified extension'
        );

        // The client filename must not survive into the stored path...
        $this->assertStringNotContainsString('My Form', $row->sample_file_path);
        // ...and the display label must be sanitised.
        $this->assertStringNotContainsString('<', $row->sample_original_name);
        $this->assertStringNotContainsString('>', $row->sample_original_name);

        $relative = substr($row->sample_file_path, strlen('storage/'));
        $this->assertTrue(Storage::disk('public')->exists($relative), 'file was not written');

        Storage::disk('public')->delete($relative);
    }

    public function test_size_limit_never_exceeds_what_php_will_accept(): void
    {
        $phpLimitKb = (int) floor(
            min(
                $this->iniBytes('upload_max_filesize'),
                $this->iniBytes('post_max_size')
            ) / 1024
        );

        $effective = SafeUploadedDocument::maxKilobytes(10240);

        $this->assertLessThanOrEqual(
            $phpLimitKb,
            $effective,
            'the max: rule promises more than php.ini accepts — oversized files would be silently dropped'
        );
        $this->assertGreaterThan(0, $effective);
    }

    public function test_oversized_file_fails_validation_with_a_message(): void
    {
        $overKb = SafeUploadedDocument::maxKilobytes(10240) + 64;

        // Valid PDF header padded past the limit, so only the size rule can fail.
        $bytes = $this->pdfBytes() . str_repeat('A', $overKb * 1024);

        $before = $this->storedFiles();

        $response = $this->submit(
            $this->fakeUpload('big.pdf', $bytes),
            'doc_sec_' . bin2hex(random_bytes(4))
        );

        $response->assertSessionHasErrors('sample_file');
        $this->assertSame($before, $this->storedFiles(), 'an oversized file was written to disk');
    }

    private function iniBytes(string $key): int
    {
        $raw   = trim((string) ini_get($key));
        $unit  = strtolower(substr($raw, -1));
        $value = (int) $raw;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
