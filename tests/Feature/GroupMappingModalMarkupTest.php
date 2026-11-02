<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Group Mapping page's modals keep their footers inside .modal-content.
 *
 * Bootstrap gives .modal-dialog `pointer-events: none` and re-enables them only
 * on .modal-content. A stray closing tag that ends .modal-content early leaves
 * the footer under .modal-dialog, where its buttons cannot be clicked - while
 * the page still answers 200 and every lint and route check stays green. A
 * merge once left exactly that in #addStudentModal.
 *
 * The page is rendered for real and parsed; the static source is also checked
 * for an equal count of opening and closing <div> tags. Read-only, wrapped in
 * a rolled-back transaction, and skips without a database.
 */
class GroupMappingModalMarkupTest extends TestCase
{
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the Group Mapping render test needs the application database');
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

    public function test_the_view_source_has_balanced_div_tags(): void
    {
        $source = file_get_contents(resource_path('views/admin/group_mapping/index.blade.php'));

        $this->assertSame(
            substr_count($source, '<div'),
            substr_count($source, '</div>'),
            'group_mapping/index.blade.php must open and close the same number of <div> tags'
        );
    }

    /**
     * @dataProvider modals
     */
    public function test_each_modal_footer_is_inside_its_modal_content(string $modalId): void
    {
        $html = $this->actingAs($this->administrator())
            ->withSession(['user_roles' => ['Super Admin']])
            ->get('/group-mapping')
            ->assertOk()
            ->getContent();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $has = fn (string $class) => "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";

        $this->assertSame(1, $xpath->query("//div[@id='{$modalId}']")->length, "#{$modalId} must be on the page");

        $footers = $xpath->query("//div[@id='{$modalId}']//div[{$has('prog-modal-footer')} or {$has('modal-footer')}]");
        $this->assertGreaterThan(0, $footers->length, "#{$modalId} must render a footer");

        $inside = $xpath->query(
            "//div[@id='{$modalId}']//div[{$has('modal-content')}]//div[{$has('prog-modal-footer')} or {$has('modal-footer')}]"
        );

        $this->assertSame(
            $footers->length,
            $inside->length,
            "every footer of #{$modalId} must sit inside .modal-content, or its buttons cannot be clicked"
        );
    }

    public static function modals(): array
    {
        return ['add student' => ['addStudentModal']];
    }

    private function administrator(): User
    {
        $id = DB::table('model_has_roles as m')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', User::class)
            ->where('r.name', 'Super Admin')
            ->orderBy('m.model_id')
            ->value('m.model_id');

        $user = $id ? User::find($id) : null;

        if (! $user) {
            $this->markTestSkipped('no Super Admin account to render the page as');
        }

        return $user;
    }
}
