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
        $this->as($owner, [])->get($url)->assertNotFound();

        Storage::disk('private')->put($path, '%PDF-private-copy');
        $this->as($owner, [])->get($url)->assertOk();

        $other = User::findOrFail($otherPk);
        $this->as($other, [])->get($url)->assertNotFound();
    }
}
