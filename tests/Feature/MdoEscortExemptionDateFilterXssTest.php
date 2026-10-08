<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET /mdo-escrot-exemption — the Time Period picker defaults.
 *
 * from_date_filter / to_date_filter were echoed with e() into a single-quoted
 * JS string: e() does not escape a backslash, so from_date_filter=\ escaped the
 * closing quote and to_date_filter ran as code. The defaults are now emitted
 * JSON-encoded, and only when both values are real Y-m-d dates.
 *
 * Every assertion reads the whole block that sets fpDefaults (declaration and
 * any later assignment, up to the flatpickr call), not one line of it.
 */
class MdoEscortExemptionDateFilterXssTest extends TestCase
{
    private const URL = '/mdo-escrot-exemption';

    private const PAYLOAD = ['from_date_filter' => '\\', 'to_date_filter' => ']+(globalThis.PWNED=1)//'];

    private bool $inTransaction = false;

    private int $obLevel = 0;

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
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        if (! $id) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        return User::find($id);
    }

    /** Every statement from `var fpDefaults` up to the flatpickr() call. */
    private function fpDefaultsBlock(array $query): string
    {
        $html = $this->actingAs($this->admin())
            ->get(self::URL . '?' . http_build_query($query))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            preg_match('/var fpDefaults\b.*?(?=meeTimePeriodPicker\s*=\s*flatpickr)/s', $html, $m),
            'fpDefaults block not found'
        );

        return $m[0];
    }

    /**
     * Run the block in node and report what it left behind.
     *
     * @return array{defaults: mixed, pwned: string}
     */
    private function evaluate(string $block): array
    {
        $node = trim((string) @shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
        if ($node === '') {
            $this->markTestSkipped('node is not available to evaluate the rendered script');
        }

        $script = tempnam(sys_get_temp_dir(), 'fpd') . '.js';
        file_put_contents($script, $block
            . "\nprocess.stdout.write(JSON.stringify({defaults: fpDefaults, pwned: typeof globalThis.PWNED}));\n");

        try {
            $out = shell_exec('node ' . escapeshellarg($script) . ' 2>&1');
        } finally {
            @unlink($script);
        }

        $result = json_decode((string) $out, true);
        $this->assertIsArray($result, 'node did not run the block: ' . $out);

        return $result;
    }

    public function test_the_documented_payload_is_not_reflected_into_the_script(): void
    {
        $block = $this->fpDefaultsBlock(self::PAYLOAD);

        $this->assertStringNotContainsString('PWNED', $block);
        $this->assertStringContainsString('var fpDefaults = [];', $block);
    }

    public function test_the_documented_payload_leaves_pwned_unset_when_the_script_runs(): void
    {
        $result = $this->evaluate($this->fpDefaultsBlock(self::PAYLOAD));

        $this->assertSame('undefined', $result['pwned']);
        $this->assertSame([], $result['defaults']);
    }

    public function test_real_dates_still_preset_the_picker(): void
    {
        $result = $this->evaluate($this->fpDefaultsBlock(['from_date_filter' => '2026-01-05', 'to_date_filter' => '2026-01-10']));
        $this->assertSame(['2026-01-05', '2026-01-10'], $result['defaults']);

        // One side missing: no preset, as before.
        $this->assertSame([], $this->evaluate($this->fpDefaultsBlock(['from_date_filter' => '2026-01-05']))['defaults']);
    }
}
