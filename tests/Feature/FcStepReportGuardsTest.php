<?php

namespace Tests\Feature;

use App\Http\Controllers\FC\StepReportController;
use App\Models\FC\FcForm;
use App\Models\User;
use App\Services\FC\FcBankDetailsReport;
use App\Support\FC\FcUploadUrl;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pins the three protections the FC step reports depend on.
 *
 * Each test here fails against b21dc2ade, the commit before they existed. They are deliberately
 * defensive about fixtures — a missing course or a database with no matching user is a skip,
 * never a failure — because a suite that is permanently red teaches people to ignore it, and
 * this repository already carries two scratch tests doing exactly that.
 */
class FcStepReportGuardsTest extends TestCase
{
    private const REPORTS = [
        'vision-statement',
        'special-assistant',
        'pre-medical-history',
        'bank-report',
    ];

    // ── F-001 / N-02: a token is redeemable only at the endpoint that minted it ──────────

    public function test_a_step_report_token_is_refused_by_the_unauthenticated_route(): void
    {
        [$stored, $cleanup] = $this->temporaryUpload();

        try {
            $stepToken = FcUploadUrl::encode($stored, StepReportController::filePathFor('bank-report'));

            // The open descriptive-data route must not serve a token minted for the
            // authenticated step-report endpoint. Before the audience existed it did, which
            // handed out Aadhaar cards to anyone who edited the path segment in the URL.
            $this->get('/admin/reports/descriptive-data/file?t='.$stepToken)->assertNotFound();

            // ...and the converse, so neither endpoint is a way into the other.
            $descToken = FcUploadUrl::encode($stored, FcUploadUrl::DEFAULT_PATH);
            $this->actingAs($this->superAdmin())
                ->get('/admin/reports/bank-report/file?t='.$descToken)
                ->assertNotFound();
        } finally {
            $cleanup();
        }
    }

    public function test_the_open_route_still_serves_its_own_and_legacy_tokens(): void
    {
        [$stored, $cleanup] = $this->temporaryUpload();

        try {
            $own = FcUploadUrl::encode($stored, FcUploadUrl::DEFAULT_PATH);
            $this->get('/admin/reports/descriptive-data/file?t='.$own)->assertOk();

            // Pre-audience format: a bare encrypted path. Workbooks emailed under the PR #282
            // accepted-risk decision carry these and must keep resolving.
            $legacy = rtrim(strtr(base64_encode(Crypt::encryptString($stored)), '+/', '-_'), '=');
            $this->get('/admin/reports/descriptive-data/file?t='.$legacy)->assertOk();
        } finally {
            $cleanup();
        }
    }

    public function test_a_path_that_is_not_valid_utf8_survives_the_round_trip(): void
    {
        // json_encode() returns false here, which encrypted to an empty string and made the
        // file permanently unreachable. The payload is byte-transparent instead.
        $path = 'uploads/x/doc_'.chr(0xC3).chr(0x28).'.pdf';
        $token = FcUploadUrl::encode($path, StepReportController::filePathFor('bank-report'));

        $this->assertSame($path, FcUploadUrl::decode($token, StepReportController::filePathFor('bank-report')));
    }

    /**
     * F-016: the audience cannot be omitted when minting.
     *
     * decode() has required an explicit audience since the cross-endpoint bypass was closed,
     * but for() and encode() defaulted to DEFAULT_PATH — the UNAUTHENTICATED route. A caller
     * who forgot the argument published the document anonymously with no error, no log line
     * and no failing test. Asserted structurally because a missing required argument is an
     * ArgumentCountError, which is the point: it cannot reach production.
     */
    public function test_the_audience_cannot_be_omitted_when_minting(): void
    {
        foreach (['for' => 1, 'encode' => 1] as $method => $index) {
            $parameter = (new \ReflectionMethod(FcUploadUrl::class, $method))->getParameters()[$index];

            $this->assertFalse(
                $parameter->isOptional(),
                "FcUploadUrl::{$method}() must require its audience — an optional one defaults to "
                .'the unauthenticated route and fails open'
            );
        }
    }

    /** Every mint site in the application names its audience rather than relying on a default. */
    public function test_no_call_site_mints_without_naming_an_audience(): void
    {
        $roots = [app_path(), resource_path('views'), base_path('routes')];
        $offenders = [];

        foreach ($roots as $root) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (! $file->isFile() || ! preg_match('/\.php$/', $file->getFilename())) {
                    continue;
                }
                foreach (file($file->getPathname()) as $n => $line) {
                    if (! str_contains($line, 'FcUploadUrl::for(')) {
                        continue;
                    }
                    if (! str_contains($line, 'filePathFor') && ! str_contains($line, 'DEFAULT_PATH')) {
                        $offenders[] = $file->getPathname().':'.($n + 1);
                    }
                }
            }
        }

        $this->assertSame([], $offenders,
            'these call sites mint an upload URL without naming the endpoint that may serve it');
    }

    /**
     * F-020: the document endpoint enforces the report's own permission.
     *
     * It used to require only a login plus a capability token, so any staff account sent a
     * workbook could open the identity and medical documents it linked to. It now carries the
     * same `can:` as the report screens. The trainee guard behind it is redundant while that
     * gate stands and is asserted anyway — it is the rule that must never be relaxed, because
     * `auth` admits FC roster sessions.
     */
    public function test_the_document_route_requires_the_reports_own_permission(): void
    {
        [$stored, $cleanup] = $this->temporaryUpload();

        try {
            $url = '/admin/reports/bank-report/file?t='
                .FcUploadUrl::encode($stored, StepReportController::filePathFor('bank-report'));

            // A permission holder is served.
            $holder = $this->firstUserWith('bank_detail_report');
            if ($holder) {
                $this->actingAs($holder)->get($url)->assertOk();
            }

            // A staff account WITHOUT the permission is refused — the behaviour change.
            $plain = User::whereIn('pk', DB::table('model_has_roles as mr')
                ->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->where('r.name', 'Employee')->pluck('mr.model_id'))
                ->whereNotIn('pk', DB::table('model_has_roles as mr')
                    ->join('roles as r', 'r.id', '=', 'mr.role_id')
                    ->whereIn('r.name', ['Super Admin', 'FC Reports Viewer'])->pluck('mr.model_id'))
                ->first();

            if ($plain) {
                $this->assertTrue($plain->pk > 0, 'a real staff account must carry a positive id');
                $this->actingAs($plain)->get($url)->assertForbidden();
            }

            // A trainee is refused, and would be even if the gate above were removed.
            $roster = DB::table('fc_registration_master')
                ->whereNotNull('user_id')->where('user_id', '!=', '')
                ->whereNotNull('password')->where('password', '!=', '')->first();

            if ($roster) {
                $trainee = app(\App\Services\FC\FcRosterAuthService::class)->buildStagedUser($roster);
                $this->assertTrue($trainee->pk < 0, 'a staged trainee must carry a negative id');
                $this->actingAs($trainee)->get($url)->assertForbidden();
            }
        } finally {
            $cleanup();
        }
    }

    /** A token minted for one report cannot be redeemed at another report's file route. */
    public function test_a_token_for_one_report_is_refused_by_another(): void
    {
        [$stored, $cleanup] = $this->temporaryUpload();

        try {
            $bankToken = FcUploadUrl::encode($stored, StepReportController::filePathFor('bank-report'));

            $this->actingAs($this->superAdmin())
                ->get('/admin/reports/pre-medical-history/file?t='.$bankToken)
                ->assertNotFound();
        } finally {
            $cleanup();
        }
    }

    // ── F-003 / F-011: every report endpoint carries a permission gate that resolves ────

    public function test_every_step_report_endpoint_is_gated_on_a_permission(): void
    {
        $gated = 0;

        foreach (app('router')->getRoutes() as $route) {
            if (! preg_match('~^admin/reports/('.implode('|', self::REPORTS).')(/|$)~', $route->uri())) {
                continue;
            }

            $abilities = array_filter(
                $route->gatherMiddleware(),
                fn ($m) => is_string($m) && str_starts_with($m, 'can:')
            );

            $this->assertNotEmpty($abilities, $route->uri().' has no can: gate');
            $gated++;
        }

        $this->assertSame(20, $gated, 'expected 20 gated step-report endpoints (4 reports x screen + 3 exports + file)');
    }

    public function test_the_gated_permissions_exist_so_the_gate_can_ever_pass(): void
    {
        // Spatie denies an unknown permission outright and this application has no
        // Gate::before super-admin bypass, so a gate naming a permission with no row returns
        // 403 to every account including Super Admin. That is an outage, not a lockdown.
        $abilities = $this->gatedAbilities();

        // Asserted, not assumed: with no gates at all the loop below would pass vacuously and
        // report success for the exact state this test exists to catch.
        $this->assertCount(4, $abilities, 'expected one gated ability per report screen');

        foreach ($abilities as $uri => $ability) {
            $this->assertDatabaseHas('permissions', ['name' => $ability, 'guard_name' => 'web']);
        }
    }

    public function test_a_user_holding_no_roles_is_refused_every_report(): void
    {
        $nobody = User::whereNotIn('pk', DB::table('model_has_roles')->pluck('model_id'))->first();

        if (! $nobody) {
            $this->markTestSkipped('no role-less user in this database');
        }

        foreach (self::REPORTS as $report) {
            $this->actingAs($nobody)
                ->get('/admin/reports/'.$report)
                ->assertForbidden();
        }
    }

    public function test_a_permission_holder_is_admitted(): void
    {
        $admitted = 0;

        foreach ($this->gatedAbilities() as $uri => $ability) {
            $holder = $this->firstUserWith($ability);

            if (! $holder) {
                continue;
            }

            $this->actingAs($holder)->get('/'.$uri)->assertOk();
            $admitted++;
        }

        if ($admitted === 0) {
            $this->markTestSkipped('no user in this database holds any of the report permissions');
        }
    }

    // ── F-002: document columns resolve from the form, not a hardcoded spelling ─────────

    public function test_bank_details_reads_the_column_the_form_actually_maps(): void
    {
        $report = new FcBankDetailsReport;

        foreach (FcForm::where('is_active', 1)->get() as $form) {
            $sql = $report->columnSql($form, 'doc_aadhar_path');

            if ($sql === null || $sql === 'NULL') {
                continue;
            }

            $mapped = DB::table('fc_form_fields as f')
                ->join('fc_form_steps as s', 'f.step_id', '=', 's.id')
                ->where('s.form_id', $form->id)
                ->where('f.is_active', 1)->where('s.is_active', 1)
                ->where('f.target_table', 'new_registration_bank_details_masters')
                ->whereIn('f.target_column', ['doc_aadhar', 'doc_aadhar_path'])
                ->value('f.target_column');

            if ($mapped === null) {
                continue;
            }

            // The column this form maps must be the FIRST one the expression reads, so a
            // trainee who re-uploaded under the current mapping sees the new file and not the
            // superseded one still sitting in the other column.
            //
            // Compared as `backticked` identifiers: doc_aadhar is a prefix of doc_aadhar_path,
            // so a bare strpos() finds the same offset for both and proves nothing.
            $mine = '`'.$mapped.'`';
            $other = '`'.($mapped === 'doc_aadhar' ? 'doc_aadhar_path' : 'doc_aadhar').'`';

            $this->assertStringContainsString($mine, $sql, "form {$form->id} ignores its own mapping");

            $otherAt = strpos($sql, $other);
            if ($otherAt !== false) {
                $this->assertLessThan($otherAt, strpos($sql, $mine),
                    "form {$form->id} prefers the wrong spelling");
            }
        }
    }

    // ── F-004: chunked exports order on a unique key ────────────────────────────────────

    public function test_the_chunked_excel_export_orders_on_a_unique_key(): void
    {
        $form = FcForm::where('is_active', 1)->first();

        if (! $form) {
            $this->markTestSkipped('no active form');
        }

        $service = new FcBankDetailsReport;
        $export = new \App\Exports\FC\FcStepReportExport(
            $service, $form, $service->columns(), request()
        );

        // Laravel Excel re-runs query() per chunk with LIMIT/OFFSET. Ties in a non-unique sort
        // key let a row cross a chunk boundary twice, or not at all.
        $this->assertStringContainsString(
            '`s1`.`'.fc_user_col('student_master_firsts').'` asc',
            $export->query()->toSql(),
            'ORDER BY is not total, so chunk boundaries are not deterministic'
        );
    }

    // ── helpers ─────────────────────────────────────────────────────────────────────────

    /** @return array<string,string> route uri => the ability it gates on */
    private function gatedAbilities(): array
    {
        $out = [];

        foreach (app('router')->getRoutes() as $route) {
            if (! preg_match('~^admin/reports/('.implode('|', self::REPORTS).')$~', $route->uri())) {
                continue;
            }

            foreach ($route->gatherMiddleware() as $m) {
                if (is_string($m) && str_starts_with($m, 'can:')) {
                    $out[$route->uri()] = substr($m, 4);
                }
            }
        }

        return $out;
    }

    private function firstUserWith(string $ability): ?User
    {
        $permission = DB::table('permissions')->where('name', $ability)->where('guard_name', 'web')->first();

        if (! $permission) {
            return null;
        }

        $roleIds = DB::table('role_has_permissions')->where('permission_id', $permission->id)->pluck('role_id');
        $userId = DB::table('model_has_roles')->whereIn('role_id', $roleIds)->value('model_id');

        return $userId ? User::find($userId) : null;
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

    /**
     * A real file under an upload root, plus the callback that removes it.
     *
     * @return array{0: string, 1: callable}
     */
    private function temporaryUpload(): array
    {
        $dir = public_path('uploads/__phpunit');
        File::ensureDirectoryExists($dir);

        $name = 'guard_probe_'.getmypid().'.txt';
        File::put($dir.'/'.$name, 'fixture');

        return [
            'uploads/__phpunit/'.$name,
            function () use ($dir) {
                File::deleteDirectory($dir);
            },
        ];
    }
}
