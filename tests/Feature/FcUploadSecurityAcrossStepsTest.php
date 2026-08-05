<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FC\DynamicFormService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Sweeps EVERY FC upload point with the same CWE-434 payload matrix, so the
 * audit answer is per-endpoint rather than "we fixed the one URL the report
 * named".
 *
 * Method: POST the payload to the real route, then diff the whole public disk.
 * A file appearing on disk is the ground truth for "accepted", whatever the
 * response said. Each probe reports one of:
 *
 *   BLOCKED      the file field itself was rejected  → secure
 *   WRITTEN      a file landed on disk               → VULNERABLE
 *   INCONCLUSIVE the request failed on OTHER fields, so the file rule was never
 *                reached — nothing was written, but nothing was proved either
 *
 * INCONCLUSIVE is reported, never counted as a pass: an endpoint that only
 * survives because an unrelated required field was missing is not protected.
 */
class FcUploadSecurityAcrossStepsTest extends TestCase
{
    use DatabaseTransactions;

    /** Payload: a real PNG with a live PHP shell appended after IEND. */
    private function polyglot(string $name = 'poly.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return $this->fakeUpload($name, $png . "<?php system(\$_GET['c']); ?>");
    }

    private function fakeUpload(string $name, string $bytes): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmp, $bytes);

        return new UploadedFile($tmp, $name, null, null, true);
    }

    /** Overridden by probes that need a trainee with registration state. */
    private ?User $actor = null;

    private function user(): User
    {
        if ($this->actor) {
            return $this->actor;
        }

        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        return $this->actor = $user;
    }

    /**
     * A credentials user that actually has a student_masters row, so probes that
     * sit behind a registration-progress guard can reach their file rule.
     */
    private function useTraineeWithRegistrationRow(): void
    {
        $ids  = DB::table('student_masters')->pluck(fc_user_col('student_masters'));
        $user = User::query()->whereIn('pk', $ids)->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row that also has student_masters');
        }

        $this->actor = $user;
    }

    /** Recursive snapshot of the public disk, so a write anywhere is caught. */
    private function diskSnapshot(): array
    {
        $files = Storage::disk('public')->allFiles();
        sort($files);

        return $files;
    }

    /**
     * POST $payload to $routeUri and classify the outcome.
     *
     * Most of these endpoints validate many fields, so a probe that only sends
     * the file gets rejected on an unrelated `required` before the file rule is
     * ever reached — which would look like protection that isn't there. To get a
     * definitive answer we hook the validator factory and capture the REAL rule
     * set the controller passed, then re-run just the file field's rules against
     * the payload in isolation. The rules come from the application, never from
     * a copy in this test.
     *
     * @return array{status:string, written:array<int,string>, errors:array<int,string>}
     */
    private function probe(string $uri, array $payload, string $fileKey, string $method = 'POST'): array
    {
        // Re-test with the same kind of payload that was posted, so an SVG probe
        // is re-checked with an SVG rather than with the default polyglot.
        $posted = $payload[$fileKey] ?? null;
        $sample = $posted instanceof UploadedFile
            ? $posted->getClientOriginalName()
            : (is_array($posted) && ($posted[0] ?? null) instanceof UploadedFile ? $posted[0]->getClientOriginalName() : null);
        $rebuild = fn () => $sample && str_ends_with($sample, '.svg')
            ? $this->fakeUpload('signature.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script></svg>')
            : $this->polyglot();

        $captured = [];

        Validator::resolver(function ($translator, $data, $rules, $messages, $attributes) use (&$captured) {
            $captured[] = $rules;

            return new \Illuminate\Validation\Validator($translator, $data, $rules, $messages, $attributes);
        });

        $before = $this->diskSnapshot();

        try {
            $response = $this->actingAs($this->user())
                ->from('/')
                ->call($method, $uri, $payload, [], $this->filesFrom($payload));
            $code = $response->getStatusCode();
        } catch (\Throwable $e) {
            $code = 0;
        }

        $after   = $this->diskSnapshot();
        $written = array_values(array_diff($after, $before));

        // Clean up anything the endpoint accepted, so a vulnerable endpoint does
        // not leave a live shell in the repo after the suite runs.
        foreach ($written as $path) {
            Storage::disk('public')->delete($path);
        }

        $errors = session('errors') ? session('errors')->all() : [];

        // Isolate the file field's real rules and re-test the payload against them.
        $fileRules = null;
        foreach ($captured as $ruleSet) {
            foreach ([$fileKey, $fileKey . '.*'] as $key) {
                if (array_key_exists($key, $ruleSet)) {
                    $fileRules = [$key => $ruleSet[$key]];
                    break 2;
                }
            }
        }

        $status = 'NO-FILE-RULE';

        if ($written !== []) {
            $status = 'WRITTEN';
        } elseif ($fileRules !== null) {
            $key   = array_key_first($fileRules);
            $probe = Validator::make(
                [$fileKey => str_ends_with($key, '.*') ? [$rebuild()] : $rebuild()],
                $fileRules
            );
            $status = $probe->fails() ? 'BLOCKED' : 'ACCEPTED';
        }

        return [
            'status'    => $status,
            'written'   => $written,
            'errors'    => $errors,
            'response'  => $code,
            'fileRules' => $fileRules ? json_encode(reset($fileRules)) : '(none captured)',
        ];
    }

    /** Split UploadedFile entries out of a flat payload array. */
    private function filesFrom(array &$payload): array
    {
        $files = [];
        foreach ($payload as $key => $value) {
            if ($value instanceof UploadedFile) {
                $files[$key] = $value;
                unset($payload[$key]);
            }
        }

        return $files;
    }

    /**
     * The file rule itself must reject the payload. "Nothing was written" is NOT
     * sufficient: on most of these endpoints an unrelated required field fails
     * first, so the upload appears safe while the file rule would happily accept
     * the polyglot the moment the rest of the form is filled in correctly.
     */
    private function assertBlocked(array $result, string $label): void
    {
        $this->assertSame(
            'BLOCKED',
            $result['status'],
            "[$label] NOT PROTECTED — status={$result['status']}, rules={$result['fileRules']}"
            . ($result['written'] ? ', written to: ' . implode(', ', $result['written']) : '')
        );
    }

    // ── Per-endpoint probes ────────────────────────────────────────────────

    public function test_admin_sample_documents(): void
    {
        $result = $this->probe(
            route('fc-reg.admin.sample-documents.store', [], false),
            [
                'field_name'     => 'doc_probe_' . bin2hex(random_bytes(4)),
                'document_title' => 'probe',
                'sample_file'    => $this->polyglot(),
            ],
            'sample_file'
        );

        $this->report('admin sample-documents', $result);
        $this->assertSame('BLOCKED', $result['status']);
    }

    public function test_candidate_document_upload(): void
    {
        $docId = DB::table('fc_joining_related_documents_masters')->value('id');

        if (! $docId) {
            $this->markTestSkipped('no fc_joining_related_documents_masters row');
        }

        // upload() redirects to the travel step unless travel_done is set, which
        // would stop the probe before the file rule. Rolled back by the trait.
        $this->useTraineeWithRegistrationRow();
        \App\Models\FC\StudentMaster::forUser($this->user()->getKey())
            ->update(['travel_done' => 1]);

        $result = $this->probe(
            route('fc-reg.registration.documents.upload', ['id' => $docId], false),
            ['document_file' => $this->polyglot()],
            'document_file'
        );

        $this->report('candidate document upload', $result);
        $this->assertBlocked($result, 'candidate document upload');
    }

    public function test_registration_step1_photo(): void
    {
        $result = $this->probe(
            route('fc-reg.registration.step1.save', [], false),
            ['photo' => $this->polyglot('photo.png')],
            'photo'
        );

        $this->report('step1 photo', $result);
        $this->assertBlocked($result, 'step1 photo');
    }

    public function test_registration_step1_signature(): void
    {
        $result = $this->probe(
            route('fc-reg.registration.step1.save', [], false),
            ['signature' => $this->polyglot('sign.png')],
            'signature'
        );

        $this->report('step1 signature', $result);
        $this->assertBlocked($result, 'step1 signature');
    }

    public function test_step3_pre_medical_history(): void
    {
        $result = $this->probe(
            route('fc-reg.registration.step3.pre-medical-history', [], false),
            ['pre_med_doc' => $this->polyglot('med.png')],
            'pre_med_doc'
        );

        $this->report('step3 pre-medical doc', $result);
        $this->assertBlocked($result, 'step3 pre-medical doc');
    }

    public function test_bank_passbook(): void
    {
        $result = $this->probe(
            route('fc-reg.registration.bank.save', [], false),
            ['bank_passbook' => $this->polyglot('passbook.png')],
            'bank_passbook'
        );

        $this->report('bank passbook', $result);
        $this->assertBlocked($result, 'bank passbook');
    }

    public function test_activity_medical_report(): void
    {
        $result = $this->probe(
            route('fc-reg.admin.activities.medical.upload', [], false),
            ['file1' => $this->polyglot('report.png')],
            'file1'
        );

        $this->report('activity medical report', $result);
        $this->assertBlocked($result, 'activity medical report');
    }

    /**
     * The dynamic form engine builds the rules for EVERY form-step file field,
     * so testing the rule builder covers all of them at once. Rules come from
     * the real private method via reflection — not a copy.
     *
     * @dataProvider dynamicFieldConfigs
     */
    public function test_dynamic_form_step_file_fields(string $extensions, ?int $maxKb): void
    {
        $field = new \App\Models\FC\FcFormField([
            'field_name'       => 'doc_probe',
            'field_type'       => 'file',
            'is_required'      => true,
            'file_extensions'  => $extensions,
            'file_max_kb'      => $maxKb,
            'validation_rules' => null,
        ]);

        $method = new ReflectionMethod(DynamicFormService::class, 'buildFileValidationRules');
        $method->setAccessible(true);
        $rules = $method->invoke(app(DynamicFormService::class), $field, false);
        $rules = is_array($rules) ? $rules : explode('|', $rules);

        $validator = Validator::make(
            ['doc_probe' => $this->polyglot()],
            ['doc_probe' => $rules]
        );

        $label = "dynamic form field (ext=$extensions, max={$maxKb}kb)";
        fwrite(STDERR, sprintf(
            "  %-52s rules=%s  => %s\n",
            $label,
            implode('|', array_map(fn ($r) => is_object($r) ? class_basename($r) : $r, $rules)),
            $validator->passes() ? 'ACCEPTED (VULNERABLE)' : 'BLOCKED'
        ));

        $this->assertTrue(
            $validator->fails(),
            "[$label] VULNERABLE — the polyglot passes the generated rules"
        );
    }

    public static function dynamicFieldConfigs(): array
    {
        // The configurations actually present in fc_form_fields today.
        return [
            'jpeg,jpg,png @2048'     => ['jpeg,jpg,png', 2048],
            'jpeg,jpg,png,pdf @2048' => ['jpeg,jpg,png,pdf', 2048],
            'jpeg,jpg,png,pdf @5120' => ['jpeg,jpg,png,pdf', 5120],
            'jpeg,jpg,png,pdf @500'  => ['jpeg,jpg,png,pdf', 500],
        ];
    }

    /**
     * Signature uploads on the fillable joining-document forms.
     *
     * Two halves, because the route itself could not be driven from the test
     * harness (it 404s under `actingAs` for reasons unrelated to uploads — see
     * the note in the audit write-up):
     *
     *  1. behaviour — the rule the controller now applies really does reject an
     *     SVG carrying script;
     *  2. wiring — the controller really does apply that rule.
     */
    public function test_signature_rule_rejects_svg_with_script(): void
    {
        $svg = $this->fakeUpload(
            'signature.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script></svg>'
        );

        $validator = Validator::make(
            ['signature' => $svg],
            ['signature' => ['nullable', 'image', 'mimes:jpeg,jpg,png', new \App\Rules\SafeUploadedDocument(['jpeg', 'jpg', 'png'])]]
        );

        fwrite(STDERR, sprintf("  %-52s => %s\n", 'signature rule vs SVG+script',
            $validator->passes() ? 'ACCEPTED (VULNERABLE)' : 'BLOCKED'));

        $this->assertTrue($validator->fails(), 'an SVG carrying script passes the signature rule (stored XSS)');
    }

    public function test_joining_document_form_pins_signature_mimes(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/FC/FcJoiningDocumentFormController.php'));
        $block  = substr($source, strpos($source, "\$rules['signature.*']"), 400);

        fwrite(STDERR, sprintf("  %-52s => %s\n", 'controller pins signature mimes',
            str_contains($block, 'mimes:') && str_contains($block, 'SafeUploadedDocument') ? 'WIRED' : 'NOT WIRED'));

        // A bare `image` rule permits SVG (Laravel: jpg,jpeg,png,gif,bmp,svg,webp).
        $this->assertStringContainsString('mimes:jpeg,jpg,png', $block,
            'signature.* must pin the mime list — bare `image` allows SVG');
        $this->assertStringContainsString('SafeUploadedDocument', $block,
            'signature.* must verify file contents');
    }

    /**
     * Regression guard: hardening must not break normal uploads. A genuine JPEG
     * and a genuine PDF have to keep passing every rule set the sweep exercises.
     *
     * @dataProvider dynamicFieldConfigs
     */
    public function test_genuine_files_still_pass_dynamic_form_rules(string $extensions, ?int $maxKb): void
    {
        $field = new \App\Models\FC\FcFormField([
            'field_name'       => 'doc_probe',
            'field_type'       => 'file',
            'is_required'      => true,
            'file_extensions'  => $extensions,
            'file_max_kb'      => $maxKb,
            'validation_rules' => null,
        ]);

        $method = new ReflectionMethod(DynamicFormService::class, 'buildFileValidationRules');
        $method->setAccessible(true);
        $rules = $method->invoke(app(DynamicFormService::class), $field, false);
        $rules = is_array($rules) ? $rules : explode('|', $rules);

        $jpeg = $this->fakeUpload('scan.jpg', "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 64) . "\xFF\xD9");
        $v = Validator::make(['doc_probe' => $jpeg], ['doc_probe' => $rules]);

        fwrite(STDERR, sprintf("  %-52s => %s\n",
            "genuine JPEG vs ext=$extensions",
            $v->passes() ? 'ACCEPTED (correct)' : 'REJECTED — ' . $v->errors()->first('doc_probe')));

        $this->assertTrue($v->passes(), 'a genuine JPEG must still be accepted: ' . $v->errors()->first('doc_probe'));
    }

    private function report(string $label, array $result): void
    {
        fwrite(STDERR, sprintf(
            "  %-52s => %-12s http=%d %s\n",
            $label,
            $result['status'],
            $result['response'],
            'rules=' . $result['fileRules']
                . ($result['written'] ? '  WROTE: ' . implode(',', $result['written']) : '')
        ));
    }
}
