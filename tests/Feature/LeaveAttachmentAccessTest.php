<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class LeaveAttachmentAccessTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_owner_gets_private_attachment_and_public_disk_is_not_a_fallback(): void
    {
        $owner = $this->officerTrainee();
        $studentPk = (int) $owner->user_id;

        $otherPk = DB::table('user_credentials')
            ->where('user_category', 'S')
            ->where('user_id', '!=', $studentPk)
            ->orderBy('pk')
            ->value('pk');
        if (! $otherPk) {
            $this->markTestSkipped('needs a second officer trainee for the denied-access check');
        }

        $applicationPk = (int) DB::table('leave_application')->insertGetId([
            'course_master_pk' => 0,
            'student_master_pk' => $studentPk,
            'leave_type' => 'PT_EXEMPTION',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'total_days' => 1,
            'reason' => 'Private attachment access test',
            'status' => 2,
            'active_inactive' => 1,
            'created_date' => now(),
        ]);
        $path = 'uploads/leave-applications/'.bin2hex(random_bytes(12)).'.pdf';
        $attachmentPk = DB::table('leave_application_attachment')->insertGetId([
            'leave_application_pk' => $applicationPk,
            'attachment_title' => 'Access test',
            'file_path' => $path,
            'original_file_name' => 'private.pdf',
            'created_date' => now(),
        ]);
        Storage::fake('private');
        Storage::fake('public');
        Storage::disk('public')->put($path, '%PDF-public-copy');

        $url = route('leave.attachments.show', [$applicationPk, $attachmentPk]);
        $this->get($url)->assertRedirect();
        $this->as($owner, [])->get($url)->assertNotFound();

        Storage::disk('private')->put($path, '%PDF-private-copy');
        $this->as($owner, [])->get($url)->assertOk();

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->get($url)->assertOk();

        $other = User::findOrFail($otherPk);
        $this->as($other, [])->get($url)->assertNotFound();
    }

    public function test_course_authorized_faculty_can_download_a_pt_exemption_attachment(): void
    {
        $facultyPk = DB::table('faculty_master')->orderBy('pk')->value('pk');
        $studentPk = DB::table('student_master')->orderBy('pk')->value('pk');
        $coursePk = DB::table('course_master')->orderBy('pk')->value('pk');
        if (! $facultyPk || ! $studentPk || ! $coursePk) {
            $this->markTestSkipped('needs a faculty, trainee, and course reference row');
        }

        $groupTypePk = DB::table('course_group_type_master')->where('active_inactive', 1)->value('pk');
        if (! $groupTypePk) {
            $this->markTestSkipped('needs an active course group type');
        }

        DB::table('course_master')->where('pk', $coursePk)->update([
            'active_inactive' => 1,
            'end_date' => now()->addYear()->toDateString(),
        ]);
        $groupPk = (int) DB::table('group_type_master_course_master_map')->insertGetId([
            'type_name' => $groupTypePk,
            'course_name' => $coursePk,
            'group_name' => 'PT attachment access test',
            'facility_id' => $facultyPk,
            'active_inactive' => 1,
            'created_date' => now(),
            'modified_date' => now(),
        ]);
        DB::table('student_course_group_map')->insert([
            'student_master_pk' => $studentPk,
            'group_type_master_course_master_map_pk' => $groupPk,
            'active_inactive' => 1,
            'created_date' => now(),
            'modified_date' => now(),
        ]);

        $applicationPk = (int) DB::table('leave_application')->insertGetId([
            'course_master_pk' => $coursePk,
            'student_master_pk' => $studentPk,
            'leave_type' => 'PT_EXEMPTION',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'total_days' => 1,
            'reason' => 'Faculty PT attachment access test',
            'status' => 2,
            'active_inactive' => 1,
            'created_date' => now(),
        ]);
        $path = 'uploads/leave-applications/'.bin2hex(random_bytes(12)).'.pdf';
        $attachmentPk = DB::table('leave_application_attachment')->insertGetId([
            'leave_application_pk' => $applicationPk,
            'attachment_title' => 'Faculty access test',
            'file_path' => $path,
            'original_file_name' => 'private.pdf',
            'created_date' => now(),
        ]);
        Storage::fake('private');
        Storage::disk('private')->put($path, '%PDF-private-faculty');

        $faculty = new User;
        $faculty->forceFill([
            'pk' => random_int(900000000, 999999999),
            'user_id' => $facultyPk,
            'user_category' => 'F',
        ]);
        $this->as($faculty, ['Faculty'])
            ->get(route('leave.attachments.show', [$applicationPk, $attachmentPk]))
            ->assertOk();

        $detailUrl = route('admin.dashboard.students.detail', encrypt($studentPk));
        $this->as($faculty, ['Faculty'])
            ->get($detailUrl)
            ->assertOk()
            ->assertSee(route('leave.attachments.show', [$applicationPk, $attachmentPk]), false);
    }
}
