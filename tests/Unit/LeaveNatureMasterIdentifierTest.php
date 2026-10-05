<?php

namespace Tests\Unit;

use App\DataTables\LeaveNatureMasterDataTable;
use App\Http\Controllers\Admin\Master\LeaveNatureMasterController;
use Illuminate\Contracts\Encryption\DecryptException;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the identifier convention on the Leave Nature Master screen.
 *
 * The screen has three id-taking endpoints. edit() and destroy() route their id through
 * decryptId(); status() originally called findOrFail() on the raw path segment, so reaching a
 * specific row needed nothing but its integer pk. That asymmetry was a live hazard in two
 * directions: it made the row addressable by guessing a number, and it invited a later
 * "tidy-up" that wraps status() in decryptId() without touching the DataTable — which would
 * abort 404 on every toggle, because decryptId() rejects a plain integer.
 *
 * The URL id and the data-id attribute are deliberately different values and must stay that
 * way: the route carries the encrypted id, while custom.js and status-toggle-delete.js match
 * the table row on the raw pk in data-id. Locking both halves here is what keeps a future edit
 * from changing one without the other.
 *
 * Deliberately database-free, matching the other unit tests here: the application's legacy
 * tables have no factories, so the markup is asserted against a stub row rather than a seeded
 * record. See MemoNoticeAuthorisationTest for the same constraint.
 *
 * Run with:  php vendor/bin/phpunit --filter=LeaveNatureMasterIdentifier
 */
class LeaveNatureMasterIdentifierTest extends TestCase
{
    /** Read a method's source, so the assertions describe the code rather than a mock. */
    private function methodSource(string $class, string $method): string
    {
        $reflection = new ReflectionMethod($class, $method);
        $source = file($reflection->getDeclaringClass()->getFileName());

        return implode('', array_slice(
            $source,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }

    /**
     * The defect this test exists for: status() must not reach a row by raw pk.
     */
    public function test_status_resolves_its_id_through_decrypt_id(): void
    {
        $body = $this->methodSource(LeaveNatureMasterController::class, 'status');

        $this->assertStringContainsString(
            'decryptId($id)',
            $body,
            'status() must resolve its id through decryptId(), like edit() and destroy(). '
            . 'Taking the raw path segment makes any row reachable by guessing its integer pk.'
        );

        $this->assertStringNotContainsString(
            'findOrFail($id)',
            $body,
            'status() is calling findOrFail() on the raw path segment.'
        );
    }

    /** All three id-taking endpoints should answer the same way. */
    public function test_every_id_taking_endpoint_uses_the_same_convention(): void
    {
        foreach (['edit', 'status', 'destroy'] as $method) {
            $this->assertStringContainsString(
                'decryptId(',
                $this->methodSource(LeaveNatureMasterController::class, $method),
                "{$method}() does not use decryptId(); the three endpoints must agree."
            );
        }
    }

    /**
     * The other half of the fix. If the DataTable emits a raw pk in the status URL while the
     * controller decrypts it, every toggle 404s — so the two are asserted together.
     */
    public function test_the_datatable_emits_an_encrypted_id_in_the_status_url(): void
    {
        $body = $this->methodSource(LeaveNatureMasterDataTable::class, 'dataTable');

        $this->assertStringContainsString(
            "route('master.leave-nature.status', encrypt(\$row->pk))",
            $body,
            'The status URL must carry an encrypted id to match the controller.'
        );

        $this->assertStringNotContainsString(
            "route('master.leave-nature.status', \$row->pk)",
            $body,
            'The status URL is still emitting a raw pk.'
        );
    }

    /**
     * data-id stays the raw pk on purpose — the JS matches rows on it. Encrypting this too
     * would silently break the delete-icon refresh, which is the sort of over-correction this
     * test is here to catch.
     */
    public function test_the_row_attribute_keeps_the_raw_pk_for_client_side_matching(): void
    {
        $body = $this->methodSource(LeaveNatureMasterDataTable::class, 'dataTable');

        $this->assertStringContainsString(
            'data-id="\'.$row->pk.\'"',
            $body,
            'data-id must stay the raw pk: status-toggle-delete.js matches the table row on it.'
        );
    }

    /** decryptId() must reject a plain integer — the behaviour the DataTable half depends on. */
    public function test_decrypt_id_rejects_a_raw_integer(): void
    {
        $this->expectException(DecryptException::class);

        decrypt('5');
    }

    /** A round-trip through encrypt() resolves back to the original pk. */
    public function test_an_encrypted_pk_round_trips(): void
    {
        $this->assertSame(5, (int) decrypt(encrypt(5)));
    }
}
