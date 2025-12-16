<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\UserCredential;
use App\Models\StudentCourseGroupMap;
use App\Models\GroupTypeMasterCourseMasterMap;
use App\Models\CourseCordinatorMaster;
use App\Models\UserRoleMaster;
use App\Models\EmployeeRoleMapping;

class NotificationReceiverService
{
    /**
     * Get receiver user_ids for medical exemption notifications
     * 
     * @param int $studentPk Student master primary key
     * @param int $coursePk Course master primary key
     * @return array Array of receiver user_ids
     */
    public function getMedicalExemptionReceivers(int $studentPk, int $coursePk): array
    {
        $receiverUserIds = [];

        // 1. Get student user_id
        $studentUserId = $this->getStudentUserId($studentPk);
        if ($studentUserId) {
            $receiverUserIds[] = $studentUserId;
        }

        // 2. Get faculty user_id from student_course_group_map
        $facultyUserId = $this->getFacultyUserIdFromStudentCourseGroup($studentPk);
        if ($facultyUserId) {
            $receiverUserIds[] = $facultyUserId;
        }

        // 3. Get coordinator and assistant coordinator user_ids
        $coordinatorUserIds = $this->getCoordinatorUserIds($coursePk);
        $receiverUserIds = array_merge($receiverUserIds, $coordinatorUserIds);

        // Remove duplicates and return
        return array_unique(array_filter($receiverUserIds));
    }

    /**
     * Get user_id for a student
     * 
     * @param int $studentPk Student master primary key
     * @return int|null User ID from user_credentials table
     */
    public function getStudentUserId(int $studentPk): ?int
    {
            $userCredential = UserCredential::where('user_id', $studentPk)
                ->where('user_category', 'S')
                ->first();
            return $userCredential ? $userCredential->user_id : null;
    }

    /**
     * Get user_ids for multiple students
     * 
     * @param array $studentPks Array of student master primary keys
     * @return array Array of user_ids from user_credentials table
     */
    public function getStudentUserIds(array $studentPks): array
    {
        if (empty($studentPks)) {
            return [];
        }

        $userCredentials = UserCredential::whereIn('user_id', $studentPks)
            ->where('user_category', 'S')
            ->pluck('user_id')
            ->toArray();

        return array_values(array_filter($userCredentials));
    }

    /**
     * Get receiver user_ids for memo/notice notifications
     * 
     * This method returns user_ids for students to whom the memo/notice is sent.
     * 
     * @param array $studentPks Array of student master primary keys (students to whom memo/notice is sent)
     * @return array Array of receiver user_ids
     */
    public function getMemoNoticeReceivers(array $studentPks): array
    {
        return $this->getStudentUserIds($studentPks);
    }

    /**
     * Get faculty user_id from student_course_group_map
     * 
     * Flow:
     * student_course_group_map.student_master_pk = studentPk
     * -> group_type_master_course_master_map_pk
     * -> group_type_master_course_master_map.pk = group_type_master_course_master_map_pk
     * -> facility_id (faculty_master.pk)
     * -> user_credentials.user_id = facility_id AND user_category = 'E'
     * 
     * @param int $studentPk Student master primary key
     * @return int|null Faculty user ID
     */
    public function getFacultyUserIdFromStudentCourseGroup(int $studentPk): ?int
    {
         // Step 1: student_course_group_map se group_type_master_course_master_map_pk nikaalo
     $groupMapPk = StudentCourseGroupMap::where('student_master_pk', $studentPk)
    ->select('group_type_master_course_master_map_pk')
    ->first();

            if (!$groupMapPk || !$groupMapPk->group_type_master_course_master_map_pk) {
        return null;
          }

        // Step 2: group_type_master_course_master_map se faculty_id nikaalo
     $facultyId = GroupTypeMasterCourseMasterMap::where('pk', $groupMapPk->group_type_master_course_master_map_pk)
    ->select('facility_id')
    ->first();

    return $facultyId ? $facultyId->facility_id : null;
    }


    public function getCoordinatorUserIds(int $coursePk): array
    {
        $records = CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->select('Coordinator_name', 'Assistant_Coordinator_name')
            ->get();
    
        $userIds = [];
    
        foreach ($records as $record) {
    
            // Coordinator
            if (!empty($record->Coordinator_name)) {
                $userIds[] = (int) $record->Coordinator_name;
            }
    
            // Assistant Coordinator (MULTIPLE ROWS handled)
            if (!empty($record->Assistant_Coordinator_name)) {
                $userIds[] = (int) $record->Assistant_Coordinator_name;
            }
        }
    
        return array_values(array_unique($userIds));
    }

    /**
     * Get admin user_id
     * 
     * Flow:
     * user_role_master.user_role_name = 'Admin'
     * -> employee_role_mapping.user_role_master_pk = user_role_master.pk
     * -> user_credentials.pk = employee_role_mapping.user_credentials_pk
     * -> user_credentials.user_id
     * 
     * @return int|null Admin user ID
     */
    public function getAdminUserId()
    {
        $adminRole = UserRoleMaster::where('user_role_name', 'Admin')->first();
        if (!$adminRole) {
            return null;
        }

        $adminMapping = EmployeeRoleMapping::where('user_role_master_pk', $adminRole->pk)->first();
        if (!$adminMapping) {
            return null;
        }

        $adminUserCredential = UserCredential::find($adminMapping->user_credentials_pk);
        if (!$adminUserCredential) {
            return null;
        }

        return $adminUserCredential->user_id;
    }

    /**
     * Get course coordinator user_id for a course
     * 
     * @param int $coursePk Course master primary key
     * @return int|null Coordinator user ID
     */
    public function getCourseCoordinatorUserId(int $coursePk): ?int
    {
        $record = CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->select('Coordinator_name')
            ->first();

        if (!$record || empty($record->Coordinator_name)) {
            return null;
        }

        return (int) $record->Coordinator_name;
    }

    /**
     * Get assistant coordinator user_ids for a course
     * 
     * @param int $coursePk Course master primary key
     * @return array Array of assistant coordinator user_ids
     */
    public function getAssistantCoordinatorUserIds(int $coursePk): array
    {
        $records = CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->select('Assistant_Coordinator_name')
            ->get();

        $userIds = [];

        foreach ($records as $record) {
            if (!empty($record->Assistant_Coordinator_name)) {
                $userIds[] = (int) $record->Assistant_Coordinator_name;
            }
        }

        return array_values(array_unique($userIds));
    }
    
    
}

