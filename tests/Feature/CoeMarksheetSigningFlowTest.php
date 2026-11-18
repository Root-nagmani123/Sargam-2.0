<?php

namespace Tests\Feature;

use App\Models\COE\Marksheet;
use App\Models\User;
use App\Services\COE\MarksheetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * COE marksheets are signed one by one with Verify, in order: the UDC, then the
 * AAO, then the COE, which publishes the marksheet. Each puts only their own
 * signature. Development database, in a transaction.
 */
class CoeMarksheetSigningFlowTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * The $nth user outside Super Admin and the signatory roles, given the role that
     * signs as $designation (UDC / AAO / COE → COE-Incharge) and the screen's permission.
     */
    private function signer(?string $designation, int $nth = 0): User
    {
        $role = $designation ? MarksheetService::SIGNER_ROLES[$designation] : null;
        foreach (MarksheetService::SIGNER_ROLES as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $user = User::whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'COE', ...array_values(MarksheetService::SIGNER_ROLES)]))
            ->orderBy('pk')->skip($nth)->first()
            ?? $this->markTestSkipped('Needs users outside Super Admin, UDC, AAO, COE-Incharge and COE.');

        // Through the role: the dev database keys model_has_permissions on permission_id
        // alone, so a permission can be given directly to one user only.
        Permission::firstOrCreate(['name' => 'coe_marksheet_generation', 'guard_name' => 'web']);
        if ($role) {
            $roleModel = Role::findByName($role, 'web');
            if (! $roleModel->hasPermissionTo('coe_marksheet_generation')) {
                $roleModel->givePermissionTo('coe_marksheet_generation');
            }
            $user->assignRole($role);
        } elseif (! $user->permissions()->where('name', 'coe_marksheet_generation')->exists()) {
            $user->givePermissionTo('coe_marksheet_generation');
        }

        return $user->fresh();
    }

    /** A Generated marksheet nobody has signed yet. */
    private function unsigned(): array
    {
        if (! MarksheetService::signingEnabled()) {
            $this->markTestSkipped('Needs the coe_marksheets table with its signed column.');
        }
        if (app(MarksheetService::class)->signatureProblem() !== null) {
            $this->markTestSkipped('Needs one Active UDC, AAO and COE signature with its image.');
        }
        foreach (['active', 'archived'] as $scope) {
            foreach (app(MarksheetService::class)->rows($scope) as $row) {
                if (! $row['published'] && empty($row['signatures'])) {
                    return $row;
                }
            }
        }
        $this->markTestSkipped('Needs a Generated marksheet nobody has signed.');
    }

    private function verify(User $user, array $row)
    {
        return $this->actingAs($user)->postJson(route('admin.coe.marksheet-generation.publish'), ['course' => $row['course_key'], 'ots' => [$row['code']]]);
    }

    private function record(array $row): Marksheet
    {
        return Marksheet::where('course_master_pk', (int) $row['course_key'])->where('student_master_pk', (int) $row['code'])->first();
    }

    /** The marksheet's Status column as Marksheet Generation lists it now. */
    private function status(array $row): string
    {
        return app(MarksheetService::class)->find($row['course_key'], $row['code'])['status'];
    }

    public function test_udc_then_aao_then_coe_sign_and_the_coe_publishes(): void
    {
        $row = $this->unsigned();
        $this->assertSame('Generated', $this->status($row));

        $udc = $this->signer('UDC', 0);
        $this->verify($udc, $row)->assertOk()->assertJsonPath('signed', 1)->assertJsonPath('published', 0);
        $this->assertSame(['UDC'], array_keys($this->record($row)->signed));
        $this->assertFalse($this->record($row)->isPublished());
        $this->assertSame('Verified by UDC', $this->status($row));

        // Signing twice changes nothing, and the UDC no longer gets Verify on it.
        $this->verify($udc, $row)->assertOk()->assertJsonPath('signed', 0);
        $listed = collect([[], ['scope' => 'archived']])
            ->flatMap(fn ($q) => $this->actingAs($udc)->get(route('admin.coe.marksheet-generation.index', $q))->viewData('rows'))
            ->firstWhere('code', $row['code']);
        if ($listed) {
            $this->assertFalse($listed['can_verify']);
        }

        // The UDC took one of the free users, so the next one is now the first.
        $this->verify($this->signer('AAO', 0), $row)->assertOk()->assertJsonPath('signed', 1)->assertJsonPath('published', 0);
        $this->assertEqualsCanonicalizing(['UDC', 'AAO'], array_keys($this->record($row)->signed));
        $this->assertSame('Verified by AAO', $this->status($row));

        $this->verify($this->signer('COE', 0), $row)->assertOk()->assertJsonPath('published', 1);
        $record = $this->record($row);
        $this->assertTrue($record->isPublished());
        $this->assertSame('Published', $this->status($row));
        $this->assertEqualsCanonicalizing(['UDC', 'AAO', 'COE'], array_keys($record->snapshot['signatures']));
    }

    public function test_aao_cannot_verify_before_the_udc(): void
    {
        $row = $this->unsigned();

        $this->verify($this->signer('AAO'), $row)->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'not verified by the UDC'));
        $this->assertNull($this->record($row)->signed);
    }

    public function test_coe_cannot_verify_before_the_aao(): void
    {
        $row = $this->unsigned();

        $coe = $this->signer('COE');
        $this->verify($coe, $row)->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'not verified by the AAO'));

        // Only the UDC has signed: still not the COE's turn.
        $this->verify($this->signer('UDC'), $row)->assertOk()->assertJsonPath('signed', 1);
        $this->verify($coe, $row)->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'not verified by the AAO'));
        $this->assertSame(['UDC'], array_keys($this->record($row)->signed));
        $this->assertFalse($this->record($row)->isPublished());
    }

    public function test_only_coe_incharge_signs_as_the_coe_not_the_coe_section(): void
    {
        Role::firstOrCreate(['name' => 'COE', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'COE-Incharge', 'guard_name' => 'web']);
        $user = User::whereDoesntHave('roles')->orderBy('pk')->first()
            ?? $this->markTestSkipped('Needs a user with no role.');

        $user->assignRole('COE');
        $this->assertNull(MarksheetService::signerRole($user->fresh()));

        $user->assignRole('COE-Incharge');
        $this->assertSame('COE', MarksheetService::signerRole($user->fresh()));
    }

    public function test_a_signer_needs_their_signature_in_signature_master(): void
    {
        $row = $this->unsigned();
        \App\Models\COE\SignatureMaster::where('designation_name', 'UDC')->update(['active_inactive' => 0]);

        $this->verify($this->signer('UDC'), $row)->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'UDC'));
        $this->assertNull($this->record($row)->signed);
    }

    public function test_someone_outside_the_three_roles_cannot_verify(): void
    {
        $this->verify($this->signer(null), ['course_key' => '1', 'code' => '1'])
            ->assertForbidden()->assertJsonPath('message', 'Only the UDC, AAO or COE can verify marksheets.');
    }
}
