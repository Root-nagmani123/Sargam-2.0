<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\UserCredential;
use App\Models\User;
use App\Models\StudentCourseGroupMap;
use App\Models\GroupTypeMasterCourseMasterMap;
use App\Models\CourseCordinatorMaster;
use App\Models\UserRoleMaster;
use App\Models\EmployeeRoleMapping;
use App\Models\FacultyMaster;

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
        // 1. Get coordinator mapping for the course
        $record = CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->value('Coordinator_name'); // directly get value
    
        if (!$record) {
            return null;
        }
    
        // 2. Get faculty record using coordinator PK
        $coordinatorUser = FacultyMaster::where('pk', $record)
            ->value('employee_master_pk'); // directly get value
    
        // 3. Return user id or null
        return $coordinatorUser ? (int) $coordinatorUser : null;
    }
    

    /**
     * Get assistant coordinator user_ids for a course
     * 
     * @param int $coursePk Course master primary key
     * @return array Array of assistant coordinator user_ids
     */
    public function getAssistantCoordinatorUserIds(int $coursePk): array
    {
        // 1. Get all assistant coordinator faculty PKs
        $facultyPks = CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->whereNotNull('Assistant_Coordinator_name')
            ->pluck('Assistant_Coordinator_name')
            ->unique()
            ->toArray();
    
        if (empty($facultyPks)) {
            return [];
        }
    
        // 2. Get corresponding employee_master_pk
        $userIds = FacultyMaster::whereIn('pk', $facultyPks)
            ->pluck('employee_master_pk')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->toArray();
    
        return $userIds;
    }

    /**
     * Get receiver user_ids for memo/notice notifications
     * 
     * @param array $studentPks Array of student master primary keys
     * @return array Array of receiver user_ids
     */
    public function getMemoNoticeReceivers(array $studentPks): array
    {
        $receiverUserIds = [];

        foreach ($studentPks as $studentPk) {
            $studentUserId = $this->getStudentUserId((int) $studentPk);
            if ($studentUserId) {
                $receiverUserIds[] = $studentUserId;
            }
        }

        // Remove duplicates and return
        return array_unique(array_filter($receiverUserIds));
    }

    /**
     * Admin/faculty user_ids who should be notified when an OT replies on a discipline memo.
     * Prefers admins already on the thread, then Super Admin / Training Induction roles,
     * then course coordinator (if configured).
     *
     * @return int[]
     */
    public function getDisciplineMemoAdminReceivers(int $memoPk, ?int $coursePk = null): array
    {
        $userIds = [];

        // 1. Admins/faculty who already messaged on this memo
        $threadAdminIds = DB::table('discipline_message_student_decip_incharge')
            ->where('discipline_memo_status_pk', $memoPk)
            ->where('role_type', 'f')
            ->whereNotNull('created_by')
            ->pluck('created_by')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();
        $userIds = array_merge($userIds, $threadAdminIds);

        // 2. Roles that manage Send Discipline Memo (same set as destroy authorization)
        $roleNames = [
            'Super Admin',
            'Training Induction Admin',
            'Training-Induction',
        ];

        if (\Illuminate\Support\Facades\Schema::hasTable('model_has_roles')) {
            $spatieIds = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->join('user_credentials as uc', 'uc.pk', '=', 'mhr.model_id')
                ->where('mhr.model_type', User::class)
                ->whereIn('r.name', $roleNames)
                ->pluck('uc.user_id')
                ->all();
            $userIds = array_merge($userIds, $spatieIds);
        }

        $rolePks = UserRoleMaster::whereIn('user_role_name', $roleNames)->pluck('pk');
        if ($rolePks->isNotEmpty()) {
            $legacyIds = EmployeeRoleMapping::query()
                ->whereIn('user_role_master_pk', $rolePks)
                ->join('user_credentials as uc', 'uc.pk', '=', 'employee_role_mapping.user_credentials_pk')
                ->pluck('uc.user_id')
                ->all();
            $userIds = array_merge($userIds, $legacyIds);
        }

        // 3. Course coordinator(s) for the memo's programme
        if ($coursePk) {
            $coordinatorId = $this->getCourseCoordinatorUserId($coursePk);
            if ($coordinatorId) {
                $userIds[] = $coordinatorId;
            }
            $userIds = array_merge($userIds, $this->getAssistantCoordinatorUserIds($coursePk));
        }

        return array_values(array_unique(array_filter(array_map('intval', $userIds))));
    }

    /**
     * Resolve admin/faculty user_ids who should receive OT chat replies on a notice/memo.
     * Prefers incharge faculty on the notice, admins already on the thread, then management roles.
     *
     * @return int[]
     */
    public function getMemoNoticeAdminReceivers(int $memoNoticeId, string $type): array
    {
        $type = $type === 'memo' ? 'memo' : 'notice';
        $userIds = [];

        $userIds = array_merge($userIds, $this->resolveMemoNoticeFacultyUserIds($memoNoticeId, $type));

        $messageTable = $type === 'memo'
            ? 'memo_message_student_decip_incharge'
            : 'notice_message_student_decip_incharge';
        $foreignKey = $type === 'memo' ? 'student_memo_status_pk' : 'student_notice_status_pk';

        $threadAdminIds = DB::table($messageTable)
            ->where($foreignKey, $memoNoticeId)
            ->where('role_type', 'f')
            ->whereNotNull('created_by')
            ->pluck('created_by')
            ->map(fn ($id) => $this->normalizeStaffReceiverUserId((int) $id))
            ->filter()
            ->all();
        $userIds = array_merge($userIds, $threadAdminIds);

        $userIds = array_merge($userIds, $this->getMemoNoticeManagementRoleUserIds());

        $coursePk = $this->getMemoNoticeCoursePk($memoNoticeId, $type);
        if ($coursePk) {
            $coordinatorId = $this->getCourseCoordinatorUserId($coursePk);
            if ($coordinatorId) {
                $userIds[] = $coordinatorId;
            }
            $userIds = array_merge($userIds, $this->getAssistantCoordinatorUserIds($coursePk));
        }

        return array_values(array_unique(array_filter(array_map('intval', $userIds))));
    }

    /**
     * Map faculty_master_pk on a notice/memo to bell receiver user_ids.
     *
     * @return int[]
     */
    private function resolveMemoNoticeFacultyUserIds(int $memoNoticeId, string $type): array
    {
        if ($type === 'memo') {
            $facultyRaw = DB::table('student_memo_status as sms')
                ->leftJoin('student_notice_status as sns', 'sms.student_notice_status_pk', '=', 'sns.pk')
                ->where('sms.pk', $memoNoticeId)
                ->value('sns.faculty_master_pk');
        } else {
            $facultyRaw = DB::table('student_notice_status')
                ->where('pk', $memoNoticeId)
                ->value('faculty_master_pk');
        }

        if ($facultyRaw === null || $facultyRaw === '') {
            return [];
        }

        $trimmed = trim((string) $facultyRaw);
        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            $facultyIds = is_array($decoded) ? $decoded : [];
        } else {
            $facultyIds = [$facultyRaw];
        }

        $facultyIds = array_values(array_filter(array_map('intval', $facultyIds)));
        if ($facultyIds === []) {
            return [];
        }

        return FacultyMaster::query()
            ->whereIn('pk', $facultyIds)
            ->whereNotNull('employee_master_pk')
            ->pluck('employee_master_pk')
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->all();
    }

    private function getMemoNoticeCoursePk(int $memoNoticeId, string $type): ?int
    {
        if ($type === 'memo') {
            $coursePk = DB::table('student_memo_status')->where('pk', $memoNoticeId)->value('course_master_pk');
        } else {
            $coursePk = DB::table('student_notice_status')->where('pk', $memoNoticeId)->value('course_master_pk');
        }

        return $coursePk ? (int) $coursePk : null;
    }

    /**
     * Normalize chat created_by to user_credentials.user_id for bell notifications.
     */
    private function normalizeStaffReceiverUserId(int $createdBy): ?int
    {
        $credential = UserCredential::query()
            ->where('user_id', $createdBy)
            ->where('user_category', '!=', 'S')
            ->first();

        if (!$credential) {
            $credential = UserCredential::query()
                ->where('pk', $createdBy)
                ->where('user_category', '!=', 'S')
                ->first();
        }

        return $credential ? (int) $credential->user_id : null;
    }

    /**
     * Roles that manage Send Memo / Notice.
     *
     * @return int[]
     */
    private function getMemoNoticeManagementRoleUserIds(): array
    {
        $roleNames = [
            'Super Admin',
            'Training Induction Admin',
            'Training-Induction',
        ];
        $userIds = [];

        if (\Illuminate\Support\Facades\Schema::hasTable('model_has_roles')) {
            $spatieIds = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->join('user_credentials as uc', 'uc.pk', '=', 'mhr.model_id')
                ->where('mhr.model_type', User::class)
                ->whereIn('r.name', $roleNames)
                ->pluck('uc.user_id')
                ->all();
            $userIds = array_merge($userIds, $spatieIds);
        }

        $rolePks = UserRoleMaster::whereIn('user_role_name', $roleNames)->pluck('pk');
        if ($rolePks->isNotEmpty()) {
            $legacyIds = EmployeeRoleMapping::query()
                ->whereIn('user_role_master_pk', $rolePks)
                ->join('user_credentials as uc', 'uc.pk', '=', 'employee_role_mapping.user_credentials_pk')
                ->pluck('uc.user_id')
                ->all();
            $userIds = array_merge($userIds, $legacyIds);
        }

        return array_values(array_unique(array_filter(array_map('intval', $userIds))));
    }

    /**
     * User IDs for estate workflow alerts (new request): Super Admin and Estate Admin only.
     *
     * @return int[]
     */
    public function getEstateRequestApproverUserIds(): array
    {
        $roleNames = ['Super Admin', 'Estate Admin'];
        $userIds = [];

        if (\Illuminate\Support\Facades\Schema::hasTable('model_has_roles')) {
            $spatieIds = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->join('user_credentials as uc', 'uc.pk', '=', 'mhr.model_id')
                ->where('mhr.model_type', User::class)
                ->whereIn('r.name', $roleNames)
                ->pluck('uc.user_id')
                ->all();
            $userIds = array_merge($userIds, $spatieIds);
        }

        $rolePks = UserRoleMaster::whereIn('user_role_name', $roleNames)->pluck('pk');
        if ($rolePks->isNotEmpty()) {
            $legacyIds = EmployeeRoleMapping::query()
                ->whereIn('user_role_master_pk', $rolePks)
                ->join('user_credentials as uc', 'uc.pk', '=', 'employee_role_mapping.user_credentials_pk')
                ->pluck('uc.user_id')
                ->all();
            $userIds = array_merge($userIds, $legacyIds);
        }

        return array_values(array_unique(array_filter(array_map('intval', $userIds))));
    }

    /**
     * User IDs for HAC workflow (Put in HAC / HAC approved).
     *
     * @return int[]
     */
    public function getEstateHacPersonUserIds(): array
    {
        $rolePk = UserRoleMaster::where('user_role_name', 'HAC Person')->value('pk');
        if (! $rolePk) {
            return [];
        }

        return EmployeeRoleMapping::query()
            ->where('user_role_master_pk', $rolePk)
            ->join('user_credentials as uc', 'uc.pk', '=', 'employee_role_mapping.user_credentials_pk')
            ->pluck('uc.user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}

