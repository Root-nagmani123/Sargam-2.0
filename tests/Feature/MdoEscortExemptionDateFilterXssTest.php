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
 * Browser checks require the locked Playwright dependency and Chrome; external
 * requests are blocked while the actual rendered HTML is parsed.
 */
class MdoEscortExemptionDateFilterXssTest extends TestCase
{
    private const URL = '/mdo-escrot-exemption';

    private const PAYLOAD = ['from_date_filter' => '\\', 'to_date_filter' => ']+(globalThis.PWNED=1)//'];

    private bool $inTransaction = false;

    private int $obLevel = 0;

    private string $renderedHtml = '';

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
        $this->renderedHtml = $html;

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

        $script = tempnam(sys_get_temp_dir(), 'fpd');
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

    /** @dataProvider maliciousFilters */
    public function test_malicious_date_filters_never_execute_javascript(array $query): void
    {
        $block = $this->fpDefaultsBlock($query);
        $this->assertStringNotContainsString('PWNED', $block);
        $this->assertStringNotContainsString('</script', strtolower($block));
        $result = $this->evaluate($block);
        $this->assertSame('undefined', $result['pwned']);
        $this->assertSame([], $result['defaults']);
    }

    public static function maliciousFilters(): array
    {
        $payloads = [
            'backslash' => '\\',
            'single quote' => "';globalThis.PWNED=1;//",
            'double quote' => '";globalThis.PWNED=1;//',
            'HTML event handler' => '"><img src=x onerror="globalThis.PWNED=1">',
            'closing script tag' => '</script><script>globalThis.PWNED=1</script>',
            'JavaScript expression' => ']+(globalThis.PWNED=1)//',
        ];
        $cases = ['paired backslash exploit' => [self::PAYLOAD]];
        foreach ($payloads as $name => $payload) {
            $filters = array_fill_keys([
                'filter', 'course_filter', 'year_filter', 'duty_type_filter', 'time_from_filter', 'time_to_filter',
            ], $payload);
            $cases[$name.' in from date'] = [$filters + ['from_date_filter' => $payload, 'to_date_filter' => '2026-01-10']];
            $cases[$name.' in to date'] = [$filters + ['from_date_filter' => '2026-01-05', 'to_date_filter' => $payload]];
        }

        return $cases;
    }

    /** @dataProvider maliciousFilters */
    public function test_malicious_filters_do_not_execute_in_the_rendered_document(array $query): void
    {
        $block = $this->fpDefaultsBlock($query);
        $result = $this->evaluateInBrowser($block);
        $this->assertSame('undefined', $result['pwned']);
        $this->assertSame([], $result['defaults']);
        $this->assertSame($query['from_date_filter'], $result['from']);
        $this->assertSame($query['to_date_filter'], $result['to']);
    }

    public function test_real_dates_survive_browser_rendering(): void
    {
        $dates = ['from_date_filter' => '2026-01-05', 'to_date_filter' => '2026-01-10'];
        $result = $this->evaluateInBrowser($this->fpDefaultsBlock($dates));
        $this->assertSame('undefined', $result['pwned']);
        $this->assertSame(array_values($dates), $result['defaults']);
        $this->assertSame($dates['from_date_filter'], $result['from']);
        $this->assertSame($dates['to_date_filter'], $result['to']);
    }

    public function test_valid_date_filters_include_both_boundaries_and_exclude_outside_rows(): void
    {
        $course = DB::table('course_master')->insertGetId([
            'course_name' => 'F007 date range '.uniqid(), 'course_year' => 2026,
            'active_inactive' => 1, 'end_date' => now()->addDays(30),
        ]);
        $student = DB::table('student_master')->insertGetId([
            'service_master_pk' => 0, 'user_id' => 'f007.student.'.uniqid(),
            'display_name' => 'F007 date range student', 'generated_OT_code' => 'F007-OT', 'status' => 1,
        ]);
        $rows = [];
        foreach (['2026-01-04', '2026-01-05', '2026-01-10', '2026-01-11'] as $date) {
            $rows[$date] = DB::table('mdo_escot_duty_map')->insertGetId([
                'course_master_pk' => $course, 'selected_student_list' => $student, 'mdo_date' => $date,
                'mdo_duty_type_master_pk' => 2, 'Time_from' => '09:00', 'Time_to' => '10:00',
            ]);
        }

        $response = $this->actingAs($this->admin())->withSession(['user_roles' => ['Super Admin']])
            ->getJson(self::URL.'?'.http_build_query([
                'filter' => 'active', 'course_filter' => $course,
                'from_date_filter' => '2026-01-05', 'to_date_filter' => '2026-01-10',
            ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertEqualsCanonicalizing(
            [$rows['2026-01-05'], $rows['2026-01-10']],
            array_column($response->json('data'), 'pk')
        );
    }

    private function evaluateInBrowser(string $block): array
    {
        $script = <<<'JS'
const { chromium } = require(process.argv[1]);
let input = '';
process.stdin.setEncoding('utf8');
process.stdin.on('data', chunk => input += chunk);
process.stdin.on('end', async () => {
    let browser;
    try {
        const { html, block } = JSON.parse(input);
        browser = await chromium.launch({ channel: 'chrome' });
        const page = await browser.newPage();
        await page.route('**/*', route => route.abort());
        await page.setContent(html, { waitUntil: 'load' });
        await page.addScriptTag({ content: block });
        const result = await page.evaluate(() => ({
            defaults: fpDefaults,
            pwned: typeof globalThis.PWNED,
            from: document.getElementById('from_date_filter').value,
            to: document.getElementById('to_date_filter').value,
        }));
        process.stdout.write(JSON.stringify(result));
    } catch (error) {
        console.error(error);
        process.exitCode = 1;
    } finally {
        if (browser) await browser.close();
    }
});
JS;
        $process = proc_open(
            ['node', '-e', $script, base_path('node_modules/@playwright/test')],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes
        );
        $this->assertIsResource($process, 'could not start the rendered-document browser check');
        fwrite($pipes[0], json_encode(['html' => $this->renderedHtml, 'block' => $block], JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $this->assertSame(0, $exit, 'browser check failed: '.$errors);
        $result = json_decode($output, true);
        $this->assertIsArray($result, 'browser did not return a result: '.$output);

        return $result;
    }

    public function test_real_dates_still_preset_the_picker(): void
    {
        $result = $this->evaluate($this->fpDefaultsBlock(['from_date_filter' => '2026-01-05', 'to_date_filter' => '2026-01-10']));
        $this->assertSame(['2026-01-05', '2026-01-10'], $result['defaults']);

        // One side missing: no preset, as before.
        $this->assertSame([], $this->evaluate($this->fpDefaultsBlock(['from_date_filter' => '2026-01-05']))['defaults']);
    }
}
