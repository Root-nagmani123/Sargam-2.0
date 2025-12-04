<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\StudentMaster;

class OTNoticeMemoService
{
    /**
     * Get logged-in student from user_credentials
     * 
     * @param int $userPk
     * @return object|null
     */
    public function getLoggedInStudent($userPk)
    {
        return DB::table('user_credentials')
            ->where('pk', $userPk)
            ->where('user_category', 'S')
            ->first();
    }

    /**
     * Get student master record
     * 
     * @param int $studentMasterPk
     * @return StudentMaster|null
     */
    public function getStudentMaster($studentMasterPk)
    {
        return StudentMaster::where('pk', $studentMasterPk)->first();
    }

    /**
     * Get notices (where notice_memo = 1)
     * Matches: user_credential.user_id = student_notice_status.student_pk
     * 
     * @param int $studentMasterPk
     * @return \Illuminate\Support\Collection
     */
    public function getNotices($studentMasterPk)
    {
        $notices = DB::table('student_notice_status')
            ->where('student_notice_status.student_pk', $studentMasterPk)
            ->where('student_notice_status.notice_memo', 1)
            ->leftJoin('course_master as cm', 'student_notice_status.course_master_pk', '=', 'cm.pk')
            ->leftJoin('student_master as sm', 'student_notice_status.student_pk', '=', 'sm.pk')
            ->leftJoin('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')
            ->select(
                'student_notice_status.pk as id',
                'student_notice_status.course_master_pk',
                'student_notice_status.date_ as session_date',
                'student_notice_status.subject_topic',
                'student_notice_status.status',
                'cm.course_name',
                'sm.display_name as participant_name',
                't.subject_topic as topic',
                DB::raw("'Notice' as type")
            )
            ->get();

        // Fetch conversations for notices
        // For Notice → notice_message_student_discipline_incharge (using notice_message_student_decip_incharge)
        foreach ($notices as $notice) {
            $conversations = DB::table('notice_message_student_decip_incharge')
                ->where('student_notice_status_pk', $notice->id)
                ->orderBy('created_date', 'asc')
                ->get();
            $notice->conversations = $conversations;
        }

        return $notices;
    }

    /**
     * Get memos (where notice_memo = 0, use student_memo_status table)
     * Matches: user_credential.user_id = student_memo_status.student_pk
     * 
     * @param int $studentMasterPk
     * @return \Illuminate\Support\Collection
     */
    public function getMemos($studentMasterPk)
    {
        $memos = DB::table('student_memo_status')
            ->where('student_memo_status.student_pk', $studentMasterPk)
            ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
            ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
            ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
            ->select(
                'student_memo_status.pk as id',
                'student_memo_status.course_master_pk',
                'student_memo_status.date as session_date',
                'student_memo_status.status',
                'student_memo_status.conclusion_remark',
                'student_memo_status.message as response',
                'cm.course_name',
                'sm.display_name as participant_name',
                't.subject_topic as topic',
                'mcm.discussion_name as conclusion_type',
                DB::raw("'Memo' as type")
            )
            ->get();

        // Fetch conversations for memos
        // For Memo → memo_message_student_discipline_incharge (using memo_message_student_decip_incharge)
        foreach ($memos as $memo) {
            $conversations = DB::table('memo_message_student_decip_incharge')
                ->where('student_memo_status_pk', $memo->id)
                ->orderBy('created_date', 'asc')
                ->get();
            $memo->conversations = $conversations;
        }

        return $memos;
    }

    /**
     * Get all notices and memos for a student
     * 
     * @param int $studentMasterPk
     * @return array
     */
    public function getAllRecords($studentMasterPk)
    {
        $notices = $this->getNotices($studentMasterPk);
        $memos = $this->getMemos($studentMasterPk);

        // Combine notices and memos
        $allRecords = $notices->merge($memos);

        return [
            'notices' => $notices,
            'memos' => $memos,
            'all_records' => $allRecords,
            'total_count' => $allRecords->count(),
        ];
    }

    /**
     * Prepare student data for view
     * 
     * @param StudentMaster $student
     * @param object $userCredential
     * @param array $recordsData
     * @return array
     */
    public function prepareStudentData($student, $userCredential, $recordsData)
    {
        return [
            'student_name' => $student->display_name ?? ($student->first_name . ' ' . $student->last_name),
            'ot_code' => $student->generated_OT_code,
            'email' => $student->email ?? $userCredential->email_id ?? 'N/A',
            'total_count' => $recordsData['total_count'],
            'records' => $recordsData['all_records'],
            'has_records' => $recordsData['total_count'] > 0,
        ];
    }
}

