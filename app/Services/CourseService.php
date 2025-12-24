<?php

namespace App\Services;

use App\Models\CourseMaster;
use App\Models\CourseCordinatorMaster;
use App\Models\FacultyMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseService
{
    protected $notificationService;
    protected $receiverService;

    public function __construct(
        NotificationService $notificationService,
        NotificationReceiverService $receiverService
    ) {
        $this->notificationService = $notificationService;
        $this->receiverService = $receiverService;
    }

    /**
     * Create or update a course with coordinators and send notifications
     * 
     * @param array $validatedData Validated form data
     * @param string|null $courseId Encrypted course ID (for update) or null (for create)
     * @return CourseMaster
     * @throws \Exception
     */
    public function createOrUpdateCourse(array $validatedData, ?string $courseId = null): CourseMaster
    {
        // Normalize dates
        // For courseyear, it's already a year value (validated as date_format:Y), so use it directly
        $validatedData['courseyear'] = (int) $validatedData['courseyear'];
        $validatedData['startdate'] = date('Y-m-d', strtotime($validatedData['startdate']));
        $validatedData['enddate'] = date('Y-m-d', strtotime($validatedData['enddate']));

        $isUpdate = !empty($courseId);
        $course = null;

        if ($isUpdate) {
            // Update existing course
            $course = CourseMaster::findOrFail(decrypt($courseId));
            $updateData = [
                'course_name' => $validatedData['coursename'],
                'couse_short_name' => $validatedData['courseshortname'],
                'course_year' => $validatedData['courseyear'],
                'start_year' => $validatedData['startdate'],
                'end_date' => $validatedData['enddate'],
                'Modified_date' => now(),
            ];
            
            // Add user_role_master_pk if provided
            if (isset($validatedData['supportingsection']) && !empty($validatedData['supportingsection'])) {
                $updateData['user_role_master_pk'] = $validatedData['supportingsection'];
            } else {
                $updateData['user_role_master_pk'] = null;
            }
            
            $course->update($updateData);
        } else {
            // Create new course
            $createData = [
                'course_name' => $validatedData['coursename'],
                'couse_short_name' => $validatedData['courseshortname'],
                'course_year' => $validatedData['courseyear'],
                'start_year' => $validatedData['startdate'],
                'end_date' => $validatedData['enddate'],
                'created_date' => now(),
                'Modified_date' => now(),
            ];
            
            // Add user_role_master_pk if provided
            if (isset($validatedData['supportingsection']) && !empty($validatedData['supportingsection'])) {
                $createData['user_role_master_pk'] = $validatedData['supportingsection'];
            }
            
            $course = CourseMaster::create($createData);
        }

        // Get old assistant coordinator IDs before deletion (only for updates)
        $oldAssistantCoordinatorIds = [];
        if ($isUpdate) {
            $oldAssistantCoordinatorIds = $this->getOldAssistantCoordinatorIds($course->pk);
        }

        // Update coordinators - delete existing and create new (batch operation)
        $this->updateCourseCoordinators($course, $validatedData);

        // Send optimized notifications
        $this->sendCourseNotifications($course, $validatedData, $isUpdate, $oldAssistantCoordinatorIds);

        return $course;
    }

    /**
     * Get old assistant coordinator user_ids before deletion (optimized with batch conversion)
     * 
     * @param int $coursePk
     * @return array Array of user_ids (employee_master_pk values)
     */
    protected function getOldAssistantCoordinatorIds(int $coursePk): array
    {
        // Get faculty PKs from coordinator records
        $facultyPks = CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->whereNotNull('Assistant_Coordinator_name')
            ->where('Assistant_Coordinator_name', '!=', '')
            ->pluck('Assistant_Coordinator_name')
            ->unique()
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->toArray();
        
        if (empty($facultyPks)) {
            return [];
        }
        
        // Batch convert faculty PKs to user_ids (employee_master_pk) - single query
        return FacultyMaster::whereIn('pk', $facultyPks)
            ->pluck('employee_master_pk')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Update course coordinators - batch delete and batch insert for better performance
     * 
     * @param CourseMaster $course
     * @param array $validatedData
     * @return void
     */
    protected function updateCourseCoordinators(CourseMaster $course, array $validatedData): void
    {
        // Delete existing coordinators
        $course->courseCordinatorMater()->delete();

        // Prepare coordinator records for batch insert
        $coordinatorRecords = [];
        $now = now();
        $coordinatorId = (int) $validatedData['coursecoordinator'];
        $assistantCoordinators = $validatedData['assistantcoursecoordinator'] ?? [];
        $assistantRoles = $validatedData['assistant_coordinator_role'] ?? [];

        foreach ($assistantCoordinators as $key => $assistantCoordinatorId) {
            if (!empty($assistantCoordinatorId)) {
                $coordinatorRecords[] = [
                    'courses_master_pk' => $course->pk,
                    'Coordinator_name' => $coordinatorId,
                    'Assistant_Coordinator_name' => $assistantCoordinatorId,
                    'assistant_coordinator_role' => $assistantRoles[$key] ?? '',
                    'created_date' => $now,
                    'Modified_date' => $now,
                ];
            }
        }

        // Batch insert for better performance
        if (!empty($coordinatorRecords)) {
            CourseCordinatorMaster::insert($coordinatorRecords);
        }
    }

    /**
     * Send optimized notifications for course create/update
     * Consolidates logic to reduce database calls and handle both scenarios efficiently
     * 
     * @param CourseMaster $course
     * @param array $validatedData
     * @param bool $isUpdate
     * @param array $oldAssistantCoordinatorIds
     * @return void
     */
    protected function sendCourseNotifications(
        CourseMaster $course,
        array $validatedData,
        bool $isUpdate,
        array $oldAssistantCoordinatorIds = []
    ): void {
        try {
            $courseName = $validatedData['coursename'];
            
            // Convert coordinator faculty PK to user_id
            $coordinatorFacultyPk = !empty($validatedData['coursecoordinator']) ? (int) $validatedData['coursecoordinator'] : null;
            $coordinatorUserId = $this->convertFacultyPkToUserId($coordinatorFacultyPk);
            
            // Extract and convert new assistant coordinator IDs from form data (batch query)
            $newAssistantCoordinatorIds = $this->extractAssistantCoordinatorIds($validatedData);

            // Get admin user ID (single query)
            $adminUserId = $this->receiverService->getAdminUserId();

            // Build base receiver list (admin + coordinator)
            $baseReceiverIds = array_filter([$adminUserId, $coordinatorUserId]);

            if ($isUpdate) {
                // UPDATE FLOW - optimized notification handling
                $this->handleUpdateNotifications(
                    $course,
                    $courseName,
                    $baseReceiverIds,
                    $newAssistantCoordinatorIds,
                    $oldAssistantCoordinatorIds
                );
            } else {
                // CREATE FLOW - simpler notification
                $this->handleCreateNotifications(
                    $course,
                    $courseName,
                    $baseReceiverIds,
                    $newAssistantCoordinatorIds
                );
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Failed to send course notifications: ' . $e->getMessage(), [
                'course_id' => $course->pk,
                'is_update' => $isUpdate,
                'exception' => $e
            ]);
        }
    }

    /**
     * Extract assistant coordinator IDs from validated data and convert to user_ids
     * 
     * @param array $validatedData
     * @return array Array of user_ids (employee_master_pk values)
     */
    protected function extractAssistantCoordinatorIds(array $validatedData): array
    {
        $assistantCoordinators = $validatedData['assistantcoursecoordinator'] ?? [];
        
        // Get unique faculty PKs
        $facultyPks = collect($assistantCoordinators)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
        
        if (empty($facultyPks)) {
            return [];
        }
        
        // Batch convert faculty PKs to user_ids (employee_master_pk) - single query
        return FacultyMaster::whereIn('pk', $facultyPks)
            ->pluck('employee_master_pk')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Convert faculty PK to user_id (employee_master_pk)
     * 
     * @param int|null $facultyPk
     * @return int|null
     */
    protected function convertFacultyPkToUserId(?int $facultyPk): ?int
    {
        if (empty($facultyPk)) {
            return null;
        }
        
        $employeeMasterPk = FacultyMaster::where('pk', $facultyPk)
            ->value('employee_master_pk');
        
        return $employeeMasterPk ? (int) $employeeMasterPk : null;
    }

    /**
     * Handle notifications for course update
     * 
     * @param CourseMaster $course
     * @param string $courseName
     * @param array $baseReceiverIds
     * @param array $newAssistantCoordinatorIds
     * @param array $oldAssistantCoordinatorIds
     * @return void
     */
    protected function handleUpdateNotifications(
        CourseMaster $course,
        string $courseName,
        array $baseReceiverIds,
        array $newAssistantCoordinatorIds,
        array $oldAssistantCoordinatorIds
    ): void {
        // Calculate differences (added/removed) - single operation
        $addedAssistantCoordinators = array_diff($newAssistantCoordinatorIds, $oldAssistantCoordinatorIds);
        $removedAssistantCoordinators = array_diff($oldAssistantCoordinatorIds, $newAssistantCoordinatorIds);

        // Send specific notifications to added assistant coordinators
        if (!empty($addedAssistantCoordinators)) {
            $this->notificationService->createMultiple(
                array_values($addedAssistantCoordinators),
                'course_assistant_coordinator_added',
                'course',
                $course->pk,
                'Added as Assistant Coordinator',
                "You have been added as an Assistant Coordinator for the course '{$courseName}'."
            );
        }

        // Send specific notifications to removed assistant coordinators
        if (!empty($removedAssistantCoordinators)) {
            $this->notificationService->createMultiple(
                array_values($removedAssistantCoordinators),
                'course_assistant_coordinator_removed',
                'course',
                $course->pk,
                'Removed as Assistant Coordinator',
                "You have been removed as an Assistant Coordinator from the course '{$courseName}'."
            );
        }

        // Send general update notification to all relevant parties
        // Include all new assistant coordinators in the general notification
        $allReceivers = array_merge($baseReceiverIds, $newAssistantCoordinatorIds);
        $allReceivers = array_values(array_unique(array_filter($allReceivers)));

        if (!empty($allReceivers)) {
            $this->notificationService->createMultiple(
                $allReceivers,
                'course_update',
                'course',
                $course->pk,
                'Course Updated',
                "The course '{$courseName}' has been updated."
            );
        }
    }

    /**
     * Handle notifications for course creation
     * 
     * @param CourseMaster $course
     * @param string $courseName
     * @param array $baseReceiverIds
     * @param array $assistantCoordinatorIds
     * @return void
     */
    protected function handleCreateNotifications(
        CourseMaster $course,
        string $courseName,
        array $baseReceiverIds,
        array $assistantCoordinatorIds
    ): void {
        // Combine all receivers
        $allReceivers = array_merge($baseReceiverIds, $assistantCoordinatorIds);
        $allReceivers = array_values(array_unique(array_filter($allReceivers)));

        if (!empty($allReceivers)) {
            $this->notificationService->createMultiple(
                $allReceivers,
                'course_create',
                'course',
                $course->pk,
                'New Course Added',
                "A new course '{$courseName}' has been added to the system."
            );
        }
    }
}

