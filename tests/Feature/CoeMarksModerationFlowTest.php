<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\COE\ResultMarksService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * COE → Result Records and Marks Moderation: Super Admin always opens it, others
 * need coe_marks_moderation, and saving is refused until the moderation columns
 * exist. Development database, in a transaction.
 */
class CoeMarksModerationFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_opens_the_screen_and_others_need_the_permission(): void
    {
        $admin = User::role('Super Admin')->first();
        $other = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->whereDoesntHave('permissions', fn ($q) => $q->where('name', 'coe_marks_moderation'))
            ->whereDoesntHave('roles.permissions', fn ($q) => $q->where('name', 'coe_marks_moderation'))
            ->first();
        if (! $admin || ! $other) {
            $this->markTestSkipped('Needs a Super Admin and a user without coe_marks_moderation.');
        }

        $this->actingAs($admin)->get(route('admin.coe.marks-moderation.index'))
            ->assertOk()->assertSee('Result Records and Marks Moderation')->assertSee('Moderate Marks');
        $this->actingAs($admin)->get(route('admin.coe.marks-moderation.index', ['scope' => 'archived']))->assertOk();

        $this->actingAs($other)->get(route('admin.coe.marks-moderation.index'))->assertForbidden();

        Permission::firstOrCreate(['name' => 'coe_marks_moderation', 'guard_name' => 'web']);
        $other->givePermissionTo('coe_marks_moderation');
        $this->actingAs($other->fresh())->get(route('admin.coe.marks-moderation.index'))->assertOk();
    }

    public function test_save_is_validated_and_refused_without_the_columns(): void
    {
        $admin = User::role('Super Admin')->first() ?? $this->markTestSkipped('Needs a Super Admin.');

        $this->actingAs($admin)->postJson(route('admin.coe.marks-moderation.moderate'), ['keys' => ['nope']])
            ->assertUnprocessable()->assertJsonValidationErrors('keys.0');

        $this->actingAs($admin)->postJson(route('admin.coe.marks-moderation.moderate'), ['keys' => ['1:1:1|1']])
            ->assertUnprocessable()->assertJsonFragment(['message' => 'Some of these marks are no longer listed. Reload the page.']);
    }

    public function test_moderate_returns_the_sop_marks_and_saves_only_with_the_columns(): void
    {
        $admin = User::role('Super Admin')->first() ?? $this->markTestSkipped('Needs a Super Admin.');
        $row = collect(app(ResultMarksService::class)->moderationSheet('active'))
            ->first(fn ($r) => $r['status'] === 'not-moderated' && $r['preview'] !== null)
            ?? $this->markTestSkipped('Needs a fully approved component on an Active drive.');

        $res = $this->actingAs($admin)->postJson(route('admin.coe.marks-moderation.moderate'), ['keys' => [$row['key']]])
            ->assertOk()->assertJsonPath('saved', ResultMarksService::moderationEnabled());
        $this->assertEquals($row['preview'], $res->json('values')[$row['key']]);
    }
}
