<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Every Course Repository write — folder create / update / delete, document
 * upload / update / delete — carried only `auth`, so an officer trainee could
 * rename a document, replace its file or switch its video download off
 * (PR #334 F-041). They now need the repository admin menu permission
 * (course_repository), trainees refused first.
 *
 * Writes go through the HTTP kernel and roll back.
 */
class CourseRepositoryWriteAuthorisationTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const NAME = 'PR334 F-041 probe';

    /** A staff login whose ONLY role is $role. */
    private function staffWithOnlyRole(string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id');
        $pk = $roleId ? DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->where('m.role_id', $roleId))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->where('m.role_id', '!=', $roleId))
            ->orderBy('u.pk')
            ->value('u.pk') : null;

        if (! $pk) {
            $this->markTestSkipped("no staff login holding only the '{$role}' role");
        }

        return User::findOrFail($pk);
    }

    /** A staff login granted the repository menu through a role other than Super Admin. */
    private function repositoryAdmin(): User
    {
        $pk = DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')
                ->join('role_has_permissions as rp', 'rp.role_id', '=', 'm.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->whereColumn('m.model_id', 'u.pk')->where('m.model_type', User::class)
                ->where('p.name', 'course_repository'))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
                ->whereColumn('m.model_id', 'u.pk')->where('m.model_type', User::class)->where('r.name', 'Super Admin'))
            ->orderBy('u.pk')
            ->value('u.pk');

        if (! $pk) {
            $this->markTestSkipped('no non-Super-Admin staff login holds course_repository');
        }

        return User::findOrFail($pk);
    }

    private function asUser(User $user)
    {
        return $this->as($user, $user->user_category === 'S'
            ? ['Student-OT']
            : $user->roles()->pluck('name')->all());
    }

    /** @return array{folder: int, document: int} */
    private function fixtures(): array
    {
        $folder = (int) DB::table('course_repository_master')->insertGetId([
            'course_repository_name' => self::NAME,
            'status' => 1,
            'del_folder_status' => 1,
            'created_date' => now(),
        ]);

        $document = (int) DB::table('course_repository_documents')->insertGetId([
            'course_repository_master_pk' => $folder,
            'course_repository_type' => 1,
            'file_title' => self::NAME,
            'del_type' => 1,
        ]);

        return ['folder' => $folder, 'document' => $document];
    }

    /** @return array<string, int> each write, by name, with the status it returned */
    private function attemptEveryWrite(User $user, array $f): array
    {
        $as = fn () => $this->asUser($user);

        return [
            'folder create' => $as()->post(route('course-repository.store'), ['course_repository_name' => self::NAME.' new'])->getStatusCode(),
            'folder update' => $as()->put(route('course-repository.update', $f['folder']), ['course_repository_name' => self::NAME.' renamed'])->getStatusCode(),
            'document upload' => $as()->postJson(route('course-repository.upload-document', $f['folder']), ['category' => 'Other'])->getStatusCode(),
            'document update' => $as()->postJson(route('course-repository.document.update', $f['document']), ['file_title' => self::NAME.' renamed', 'video_download_enabled' => 0])->getStatusCode(),
            'document delete' => $as()->deleteJson(route('course-repository.document.delete', $f['document']))->getStatusCode(),
            'folder delete' => $as()->delete(route('course-repository.destroy', $f['folder']))->getStatusCode(),
        ];
    }

    private function assertNothingWritten(array $f): void
    {
        $this->assertSame(0, DB::table('course_repository_master')->where('course_repository_name', 'like', self::NAME.' %')->count(), 'no folder created or renamed');
        $this->assertSame(1, (int) DB::table('course_repository_master')->where('pk', $f['folder'])->value('del_folder_status'), 'folder not deleted');
        $document = DB::table('course_repository_documents')->where('pk', $f['document'])->first();
        $this->assertSame(self::NAME, $document->file_title, 'document not renamed');
        $this->assertSame(1, (int) $document->del_type, 'document not deleted');
    }

    public function test_an_officer_trainee_is_refused_every_repository_write(): void
    {
        $f = $this->fixtures();

        $statuses = $this->attemptEveryWrite($this->officerTrainee(), $f);

        $this->assertSame(array_fill_keys(array_keys($statuses), 403), $statuses);
        $this->assertNothingWritten($f);
    }

    public function test_an_unrelated_employee_is_refused_every_repository_write(): void
    {
        $f = $this->fixtures();
        $employee = $this->staffWithOnlyRole('Employee');

        $statuses = $this->attemptEveryWrite($employee, $f);

        $this->assertSame(array_fill_keys(array_keys($statuses), 403), $statuses);
        $this->assertNothingWritten($f);
    }

    /** Control: the repository admin still writes. */
    public function test_a_repository_admin_still_writes(): void
    {
        $f = $this->fixtures();
        $admin = $this->repositoryAdmin();

        $this->asUser($admin)->postJson(route('course-repository.document.update', $f['document']), ['file_title' => self::NAME.' renamed'])
            ->assertOk();
        $this->assertSame(self::NAME.' renamed', DB::table('course_repository_documents')->where('pk', $f['document'])->value('file_title'));

        $this->asUser($admin)->deleteJson(route('course-repository.document.delete', $f['document']))->assertOk();
        $this->assertSame(0, (int) DB::table('course_repository_documents')->where('pk', $f['document'])->value('del_type'));

        $this->asUser($admin)->post(route('course-repository.store'), ['course_repository_name' => self::NAME.' new'])
            ->assertRedirect();
        $this->assertSame(1, DB::table('course_repository_master')->where('course_repository_name', self::NAME.' new')->count());

        $this->asUser($admin)->put(route('course-repository.update', $f['folder']), ['course_repository_name' => self::NAME.' renamed'])
            ->assertRedirect();
        $this->assertSame(self::NAME.' renamed', DB::table('course_repository_master')->where('pk', $f['folder'])->value('course_repository_name'));

        $this->assertNotSame(403, $this->asUser($admin)->postJson(route('course-repository.upload-document', $f['folder']), ['category' => 'Other'])->getStatusCode());

        $this->asUser($admin)->delete(route('course-repository.destroy', $f['folder']))->assertRedirect();
        $this->assertSame(0, (int) DB::table('course_repository_master')->where('pk', $f['folder'])->value('del_folder_status'));
    }
}
