<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\CourseMaster;
use App\Models\CourseCordinatorMaster;
use App\Models\StudentMaster;
use App\Models\UserCredential;

class FacultyNoticeMemoService
{
    /**
     * Get logged-in faculty from user_credentials
     * 
     * @param int $userPk
     * @return object|null
     */
    public function getLoggedInFaculty($userPk)
    {
        return UserCredential::where('pk', $userPk)
            ->first();
    }

    /**
     * Match faculty with course_coordinator_master
     * Match: course_coordinator_master.Assistant_Coordinator_name = user_id
     * AND course_coordinator_master.assistant_coordinator_role = 'discipline'
     * 
     * @param string $userId
     * @return \Illuminate\Support\Collection
     */
    public function getCoordinatorCourses($userId)
    {
        return CourseCordinatorMaster::where(function ($query) use ($userId) {
    
            // Case 1: Main Course Coordinator
            $query->where('Coordinator_name', $userId)
    
                  // Case 2: Assistant Course Coordinator with discipline role
                  ->orWhere(function ($q) use ($userId) {
                      $q->where('Assistant_Coordinator_name', $userId)
                        ->where('assistant_coordinator_role', 'discipline');
                  });
    
        })
        ->pluck('courses_master_pk')
        ->unique()
        ->filter();
    }
    

    /**
     * Validate courses (active = 1, end_date >= today)
     * 
     * @param \Illuminate\Support\Collection $courseIds
     * @param string $currentDate
     * @return \Illuminate\Support\Collection
     */
    public function getValidCourses($courseIds, $currentDate)
    {
        return CourseMaster::whereIn('pk', $courseIds)
            ->where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->get();
    }

    /**
     * Get notices for faculty
     * notice_memo = 1 (Notice)
     * Match: user_credential.user_id = student_notice_status.faculty_master_pk
     * 
     * @param string $userId
     * @param array $validCourseIds
     * @return \Illuminate\Support\Collection
     */
    public function getNotices($userId, $validCourseIds)
    {
        $notices = DB::table('student_notice_status')
            ->where('student_notice_status.faculty_master_pk', $userId)
            ->where('student_notice_status.notice_memo', 1)
            ->whereIn('student_notice_status.course_master_pk', $validCourseIds)
            ->leftJoin('course_master as cm', 'student_notice_status.course_master_pk', '=', 'cm.pk')
            ->leftJoin('student_master as sm', 'student_notice_status.student_pk', '=', 'sm.pk')
            ->leftJoin('timetable as t', 'student_notice_status.subject_topic', '=', 't.pk')
            ->leftJoin('faculty_master as fm', 'student_notice_status.faculty_master_pk', '=', 'fm.pk')
            ->select(
                'student_notice_status.pk as id',
                'student_notice_status.course_master_pk',
                'student_notice_status.date_ as session_date',
                'student_notice_status.subject_topic',
                'student_notice_status.status',
                'student_notice_status.faculty_master_pk',
                'cm.course_name',
                'sm.display_name as participant_name',
                'sm.pk as student_pk',
                't.subject_topic as topic',
                'fm.full_name as faculty_name',
                DB::raw("'Notice' as type")
            )
            ->get();

        // Fetch conversations for notices
        // For Notice → notice_message_student_discipline_incharge
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
     * Get memos for faculty
     * notice_memo = 0 (Memo)
     * Match: user_credential.user_id = student_notice_status.faculty_master_pk
     * Link via: student_memo_status.status_notice_status_pk = student_notice_status.pk
     * 
     * @param string $userId
     * @param array $validCourseIds
     * @return \Illuminate\Support\Collection
     */
    public function getMemos($userId, $validCourseIds)
    {
        // First get student_notice_status records where faculty_master_pk matches
        $noticeStatusIds = DB::table('student_notice_status')
            ->where('faculty_master_pk', $userId)
            ->where('notice_memo', 0)
            ->whereIn('course_master_pk', $validCourseIds)
            ->pluck('pk');

        if ($noticeStatusIds->isEmpty()) {
            return collect();
        }

        // Then get memos linked via student_notice_status_pk
        $memos = DB::table('student_memo_status')
            ->whereIn('student_memo_status.student_notice_status_pk', $noticeStatusIds)
            ->leftJoin('student_notice_status as sns', 'student_memo_status.student_notice_status_pk', '=', 'sns.pk')
            ->leftJoin('course_master as cm', 'student_memo_status.course_master_pk', '=', 'cm.pk')
            ->leftJoin('student_master as sm', 'student_memo_status.student_pk', '=', 'sm.pk')
            ->leftJoin('timetable as t', 'sns.subject_topic', '=', 't.pk')
            ->leftJoin('memo_conclusion_master as mcm', 'student_memo_status.memo_conclusion_master_pk', '=', 'mcm.pk')
            ->leftJoin('faculty_master as fm', 'sns.faculty_master_pk', '=', 'fm.pk')
            ->select(
                'student_memo_status.pk as id',
                'student_memo_status.course_master_pk',
                'student_memo_status.date as session_date',
                'student_memo_status.status',
                'student_memo_status.conclusion_remark',
                'student_memo_status.message as response',
                'student_memo_status.student_pk',
                'cm.course_name',
                'sm.display_name as participant_name',
                't.subject_topic as topic',
                'mcm.discussion_name as conclusion_type',
                'fm.full_name as faculty_name',
                DB::raw("'Memo' as type")
            )
            ->get();

        // Fetch conversations for memos
        // For Memo → memo_message_student_discipline_incharge
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
     * Get all notices and memos for a faculty
     * 
     * @param string $userId
     * @param array $validCourseIds
     * @return array
     */
    public function getAllRecords($userId, $validCourseIds)
    {
        $notices = $this->getNotices($userId, $validCourseIds);
        $memos = $this->getMemos($userId, $validCourseIds);

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
     * Prepare data for view
     * 
     * @param array $recordsData
     * @param \Illuminate\Support\Collection $validCourses
     * @return array
     */
    public function prepareViewData($recordsData, $validCourses)
    {
        // Group records by student
        $studentRecords = [];
        
        foreach ($recordsData['all_records'] as $record) {
            $studentPk = $record->student_pk;
            
            if (!isset($studentRecords[$studentPk])) {
                // Get student details
                $student = StudentMaster::where('pk', $studentPk)->first();
                
                if ($student) {
                    $studentName = $student->display_name ?? (trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) ?: 'N/A');
                } else {
                    $studentName = 'N/A';
                }
                
                $studentRecords[$studentPk] = [
                    'student_pk' => $studentPk,
                    'student_name' => $studentName,
                    'ot_code' => $student ? ($student->generated_OT_code ?? 'N/A') : 'N/A',
                    'email' => $student ? ($student->email ?? 'N/A') : 'N/A',
                    'records' => [],
                ];
            }
            
            $studentRecords[$studentPk]['records'][] = $record;
        }
        
        // Convert to array and calculate totals
        $studentData = [];
        $totalRecords = 0;
        
        foreach ($studentRecords as $student) {
            $recordCount = count($student['records']);
            $totalRecords += $recordCount;
            
            $student['total_record_count'] = $recordCount;
            $studentData[] = $student;
        }
        
        // Sort by student name
        usort($studentData, function($a, $b) {
            return strcmp($a['student_name'], $b['student_name']);
        });
        
        return [
            'studentData' => $studentData,
            'totalRecords' => $totalRecords,
            'hasData' => count($studentData) > 0,
        ];
    }
}

