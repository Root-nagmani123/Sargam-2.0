<?php

namespace Tests\Unit;

use App\Support\SecurityFiles;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Security-module files live on a private disk in the legacy E:\security layout
 * (contractual/<photo_path>/, DuplicateCardRequest/<id_card_no>/, familyCard/<emp_id_apply>/, ...).
 * Legacy rows store a bare file name; SecurityFiles derives the folder from the row.
 *
 * What is asserted: refs cannot escape the disks (they are served by a route), legacy names
 * resolve through the row's key column, a missing file keeps its stored value so views can
 * say "No file available", and names Flysystem rejects (U+202F from scanner apps) still work.
 *
 * DB-free: both disks are pointed at a temp directory.
 */
class SecurityFilesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'secfiles-' . uniqid();
        config([
            'filesystems.disks.security.root' => $this->root . DIRECTORY_SEPARATOR . 'security',
            'filesystems.disks.public.root' => $this->root . DIRECTORY_SEPARATOR . 'public',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function plant(string $disk, string $path): void
    {
        $abs = $this->root . DIRECTORY_SEPARATOR . $disk . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        File::ensureDirectoryExists(dirname($abs));
        file_put_contents($abs, 'x');
    }

    public function test_refs_cannot_leave_the_allowed_disks_or_prefixes(): void
    {
        foreach ([
            'security:../.env',
            'security:contractual/../../.env',
            'public:../.env',
            'public:avatars/someone.jpg',   // public disk, outside the security prefixes
            'local:contractual/1/a.jpg',    // disk not allowed at all
            'not a ref',
        ] as $ref) {
            $this->assertNull(SecurityFiles::refToAbsolute($ref), $ref);
        }
        $this->assertNotNull(SecurityFiles::refToAbsolute('public:idcard/photos/a.jpg'));
        // An absolute-looking path is taken relative to the disk root, never the filesystem root.
        $this->assertStringStartsWith(
            $this->root . DIRECTORY_SEPARATOR . 'security' . DIRECTORY_SEPARATOR,
            (string) SecurityFiles::refToAbsolute('security:/etc/passwd')
        );
    }

    public function test_bare_legacy_names_resolve_through_the_row_key_column(): void
    {
        $this->plant('security', 'contractual/1000290590/vikash kumar0001.jpg');
        $this->plant('security', 'DuplicateCardRequest/CCL00003/FIR Copy.jpg');
        $this->plant('security', 'familyCard/NNP00060/nisha2.jpg');

        $con = (object) ['photo_path' => 1000290590, 'id_card_no' => 'CCL00003', 'id_photo_path' => 'vikash kumar0001.jpg', 'doc_path' => 'FIR Copy.jpg'];
        $this->assertSame('security:contractual/1000290590/vikash kumar0001.jpg', SecurityFiles::forRow('security_con_oth_id_apply', $con, 'id_photo_path'));
        // A contractual card's documents may sit in its DuplicateCardRequest/<card no> folder.
        $this->assertSame('security:DuplicateCardRequest/CCL00003/FIR Copy.jpg', SecurityFiles::forRow('security_con_oth_id_apply', $con, 'doc_path'));

        $dup = (object) ['id_card_no' => 'CCL00003    ', 'fir_doc' => 'FIR Copy.jpg'];
        $this->assertSame('security:DuplicateCardRequest/CCL00003/FIR Copy.jpg', SecurityFiles::forRow('security_dup_other_id_apply', $dup, 'fir_doc'));

        $fam = (object) ['emp_id_apply' => 'NNP00060', 'family_photo' => 'nisha2.jpg'];
        $this->assertSame('security:familyCard/NNP00060/nisha2.jpg', SecurityFiles::forRow('security_family_id_apply', $fam, 'family_photo'));
    }

    public function test_a_missing_file_keeps_its_stored_value_and_does_not_exist(): void
    {
        $row = (object) ['id_card_no' => 'CCL00003', 'fir_doc' => 'not-there.pdf'];
        $value = SecurityFiles::forRow('security_dup_other_id_apply', $row, 'fir_doc');

        $this->assertSame('not-there.pdf', $value);
        $this->assertFalse(SecurityFiles::exists($value));
        $this->assertNull(SecurityFiles::url($value));
        $this->assertNull(SecurityFiles::forRow('security_dup_other_id_apply', (object) ['fir_doc' => ''], 'fir_doc'));
    }

    public function test_pre_layout_public_uploads_still_resolve(): void
    {
        $this->plant('public', 'idcard/photos/abc.jpg');
        $this->plant('public', 'idcard/dup_docs/DUO00001_PAY_1.pdf');

        $this->assertSame('public:idcard/photos/abc.jpg', SecurityFiles::locate('idcard/photos/abc.jpg'));
        $row = (object) ['id_card_no' => 'X1', 'payment_receipt' => 'DUO00001_PAY_1.pdf'];
        $this->assertSame('public:idcard/dup_docs/DUO00001_PAY_1.pdf', SecurityFiles::forRow('security_dup_other_id_apply', $row, 'payment_receipt'));
    }

    public function test_names_flysystem_rejects_are_still_found(): void
    {
        $name = "DocScanner 1 Sept 2025 10-33\u{202F}am.pdf";
        $this->plant('security', 'DuplicateCardRequest/SVC00016/' . $name);

        $row = (object) ['id_card_no' => 'SVC00016', 'service_ext' => $name];
        $this->assertSame('security:DuplicateCardRequest/SVC00016/' . $name, SecurityFiles::forRow('security_dup_other_id_apply', $row, 'service_ext'));
    }

    public function test_uploads_store_only_the_file_name_like_the_legacy_table(): void
    {
        $upload = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.4\n");

        // Duplicate: DuplicateCardRequest/<id_card_no>/, column gets the bare name (fits varchar(50)).
        $name = SecurityFiles::storeForRow($upload(), 'security_dup_perm_id_apply', ['id_card_no' => 'NNP00122']);
        $this->assertStringNotContainsString('/', $name);
        $this->assertLessThanOrEqual(50, strlen($name));
        $row = (object) ['id_card_no' => 'NNP00122', 'emp_id_apply' => 'DUP00291', 'service_ext' => $name];
        $this->assertSame("security:DuplicateCardRequest/NNP00122/{$name}", SecurityFiles::forRow('security_dup_perm_id_apply', $row, 'service_ext'));

        // No card number: the DUP/DUO number names the folder, and forRow() looks there too.
        $name = SecurityFiles::storeForRow($upload(), 'security_dup_other_id_apply', ['id_card_no' => null, 'emp_id_apply' => 'DUO05500']);
        $row = (object) ['id_card_no' => '', 'emp_id_apply' => 'DUO05500', 'fir_doc' => $name];
        $this->assertSame("security:DuplicateCardRequest/DUO05500/{$name}", SecurityFiles::forRow('security_dup_other_id_apply', $row, 'fir_doc'));

        // Contractual: contractual/<photo_path>/.
        $name = SecurityFiles::storeForRow($upload(), 'security_con_oth_id_apply', ['photo_path' => '1000290590']);
        $this->assertSame("security:contractual/1000290590/{$name}", SecurityFiles::forRow('security_con_oth_id_apply', (object) ['photo_path' => 1000290590, 'doc_path' => $name], 'doc_path'));
    }

    public function test_delete_for_row_never_removes_archive_files(): void
    {
        $this->plant('security', 'DuplicateCardRequest/CCL00003/FIR Copy.jpg');
        $generated = SecurityFiles::storeForRow(
            \Illuminate\Http\UploadedFile::fake()->createWithContent('x.pdf', "%PDF-1.4\n"),
            'security_dup_other_id_apply',
            ['id_card_no' => 'CCL00003']
        );
        $row = (object) ['id_card_no' => 'CCL00003', 'fir_doc' => 'FIR Copy.jpg', 'service_ext' => $generated];

        SecurityFiles::deleteForRow('security_dup_other_id_apply', $row, 'fir_doc');
        SecurityFiles::deleteForRow('security_dup_other_id_apply', $row, 'service_ext');

        $this->assertNotNull(SecurityFiles::locate(SecurityFiles::forRow('security_dup_other_id_apply', $row, 'fir_doc')));
        $this->assertNull(SecurityFiles::locate(SecurityFiles::forRow('security_dup_other_id_apply', $row, 'service_ext')));
    }

    public function test_folders_follow_the_legacy_layout_and_are_reused_on_edit(): void
    {
        $this->assertSame('DuplicateCardRequest/CCL00003', SecurityFiles::folder('duplicate', ' CCL00003 '));
        $this->assertMatchesRegularExpression('#^permanent/\d{10}$#', SecurityFiles::newFolder('permanent'));
        // A key that is not a single safe segment never becomes a path.
        $this->assertMatchesRegularExpression('#^DuplicateCardRequest/\d{10}$#', SecurityFiles::folder('duplicate', '../x'));

        $this->assertSame(
            'VechicalDoc/8514',
            SecurityFiles::folderOf([null, 'legacy.pdf', 'VechicalDoc/8514/1776539572.pdf'], 'vehicle')
        );
        $this->assertSame('familyCard/NNP00060', SecurityFiles::folderOf(['idcard/photos/a.jpg'], 'family', 'NNP00060'));
    }
}
