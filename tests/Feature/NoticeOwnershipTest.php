<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Holding the notice-authoring menu made a login the administrator of EVERY
 * author's notices: the list, the edit page (full description, saved recipients),
 * update and delete — Personal notices addressed to one named employee included,
 * which the dashboard feed would never show that author (PR #334 F-039).
 *
 * An author now manages the notices they created; Super Admin manages all.
 * Another author's notice is a 404. Every write rolls back.
 */
class NoticeOwnershipTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const TITLE = 'PR334 F-039 personal probe';

    private const BODY = 'PR334-F039-PRIVATE-BODY';

    /** @return array{0: User, 1: User} two staff logins holding admin_notice through a role, neither Super Admin */
    private function twoAuthors(): array
    {
        $pks = DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')
                ->join('role_has_permissions as rp', 'rp.role_id', '=', 'm.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->whereColumn('m.model_id', 'u.pk')->where('m.model_type', User::class)
                ->where('p.name', 'admin_notice'))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
                ->whereColumn('m.model_id', 'u.pk')->where('m.model_type', User::class)->where('r.name', 'Super Admin'))
            ->orderBy('u.pk')
            ->limit(2)
            ->pluck('u.pk');

        if ($pks->count() < 2) {
            $this->markTestSkipped('needs two non-Super-Admin staff logins holding admin_notice');
        }

        return [User::findOrFail($pks[0]), User::findOrFail($pks[1])];
    }

    private function asUser(User $user)
    {
        return $this->as($user, $user->roles()->pluck('name')->all());
    }

    /** An inactive (so deletable) Personal notice by $author, addressed to one employee. */
    private function personalNoticeBy(User $author): int
    {
        $employee = (int) DB::table('employee_master')->orderBy('pk')->value('pk');

        $pk = (int) DB::table('notices_notification')->insertGetId([
            'notice_title' => self::TITLE,
            'description' => '<p>'.self::BODY.'</p>',
            'notice_type' => 'Personal',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
            'active_inactive' => 0,
            'created_by' => $author->pk,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notice_audience_map')->insert([
            'notices_notification_pk' => $pk,
            'audience_type' => 'E',
            'reference_pk' => $employee,
        ]);

        return $pk;
    }

    private function update(User $user, int $pk)
    {
        return $this->asUser($user)->put(route('admin.notice.update', Crypt::encrypt($pk)), [
            'notice_title' => self::TITLE.' changed',
            'description' => 'changed',
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'All',
        ]);
    }

    public function test_a_second_author_does_not_see_anothers_personal_notice_in_the_list(): void
    {
        [$owner, $other] = $this->twoAuthors();
        $this->personalNoticeBy($owner);

        $this->asUser($other)->get(route('admin.notice.index'))->assertOk()->assertDontSee(self::TITLE);
    }

    public function test_a_second_author_cannot_open_edit_or_delete_anothers_personal_notice(): void
    {
        [$owner, $other] = $this->twoAuthors();
        $pk = $this->personalNoticeBy($owner);
        $enc = Crypt::encrypt($pk);

        $this->asUser($other)->get(route('admin.notice.edit', $enc))->assertNotFound()->assertDontSee(self::BODY, false);
        $this->update($other, $pk)->assertNotFound();
        $this->asUser($other)->delete(route('admin.notice.destroy', $enc))->assertNotFound();

        $row = DB::table('notices_notification')->where('pk', $pk)->first();
        $this->assertNotNull($row, 'not deleted');
        $this->assertSame(self::TITLE, $row->notice_title, 'not edited');
        $this->assertSame(1, DB::table('notice_audience_map')->where('notices_notification_pk', $pk)->count(), 'recipients untouched');
    }

    /** Control: the author still manages their own notice. */
    public function test_the_author_still_lists_reads_edits_and_deletes_their_own_notice(): void
    {
        [$owner] = $this->twoAuthors();
        $pk = $this->personalNoticeBy($owner);
        $enc = Crypt::encrypt($pk);

        $this->asUser($owner)->get(route('admin.notice.index'))->assertOk()->assertSee(self::TITLE);
        $this->asUser($owner)->get(route('admin.notice.edit', $enc))->assertOk()->assertSee(self::BODY, false);

        $this->update($owner, $pk)->assertRedirect(route('admin.notice.index'));
        $this->assertSame(self::TITLE.' changed', DB::table('notices_notification')->where('pk', $pk)->value('notice_title'));

        $this->asUser($owner)->delete(route('admin.notice.destroy', $enc))->assertRedirect();
        $this->assertNull(DB::table('notices_notification')->where('pk', $pk)->first());
    }

    /** Control: Super Admin administers every author's notices. */
    public function test_super_admin_still_reads_any_authors_notice(): void
    {
        [$owner] = $this->twoAuthors();
        $pk = $this->personalNoticeBy($owner);

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.edit', Crypt::encrypt($pk)))
            ->assertOk()
            ->assertSee(self::BODY, false);
    }
}
