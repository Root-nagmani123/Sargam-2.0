<?php

namespace App\Http\Controllers\Admin\Registration;

use App\DataTables\FC\FcMigratedRosterDataTable;
use App\DataTables\FC\FcMigrateStudentsDataTable;
use App\Exports\FcRosterListExport;
use App\Http\Controllers\Concerns\LbsnaaReportExport;
use App\Http\Controllers\Controller;
use App\Services\FC\FcMigrateStudentsExportService;
use App\Services\OfficerTraineeRoleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportController extends Controller
{
    use LbsnaaReportExport;

    /** @var array<int, string>|null Cached list of re-key target tables (resolved once per request). */
    private ?array $rekeyTablesResolved = null;

    /** @var array{enabled: bool, pre_history: bool, ot_details: bool}|null */
    private ?array $courseBackfillFlagsResolved = null;

    public function __construct(
        private FcMigrateStudentsExportService $exports,
        private OfficerTraineeRoleService $officerTraineeRole,
    ) {
    }

    public function index(
        FcMigrateStudentsDataTable $migrateDataTable,
        FcMigratedRosterDataTable $migratedDataTable
    ) {
        $courses = DB::table('course_master')
            ->where('active_inactive', 1)
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);

        $services = DB::table('service_master')
            ->orderBy('service_name')
            ->get(['pk', 'service_name', 'service_short_name']);

        return $migrateDataTable->render('admin.registration.import_students', [
            'courses' => $courses,
            'services' => $services,
            // Yajra views call table()/scripts() on Html\Builder, not the service class.
            'migratedDataTable' => $migratedDataTable->html(),
        ]);
    }

    public function migratedIndex(FcMigratedRosterDataTable $dataTable): JsonResponse
    {
        return $dataTable->ajax();
    }

    public function tabCounts(Request $request): JsonResponse
    {
        return response()->json($this->exports->tabCounts($request));
    }

    public function exportPrint(Request $request, string $list): View
    {
        $list = $this->exports->validateList($list);
        $payload = $this->exports->buildExportPayload($request, $list);

        return view('admin.registration.export-report', array_merge($payload, [
            'emblemSrc' => $this->lbsnaaExportEmblemDataUri(),
            'logoSrc' => $this->lbsnaaExportLogoDataUri(),
            'autoprint' => $request->boolean('autoprint'),
            'forPdf' => false,
        ]));
    }

    public function exportPdf(Request $request, string $list)
    {
        $list = $this->exports->validateList($list);

        @ini_set('memory_limit', '256M');
        @set_time_limit(120);

        $payload = $this->exports->buildExportPayload($request, $list);

        $pdf = Pdf::loadView('admin.registration.export-report', array_merge($payload, [
            'emblemSrc' => $this->lbsnaaExportEmblemDataUri(),
            'logoSrc' => $this->lbsnaaExportLogoDataUri(),
            'autoprint' => false,
            'forPdf' => true,
        ]))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]);

        $slug = $list === FcMigrateStudentsExportService::LIST_ELIGIBLE ? 'ready-to-migrate' : 'migrated-roster';

        return $pdf->download('fc-migrate-'.$slug.'-'.now()->format('Y-m-d_His').'.pdf');
    }

    public function exportExcel(Request $request, string $list)
    {
        $list = $this->exports->validateList($list);
        $payload = $this->exports->buildExportPayload($request, $list);

        $slug = $list === FcMigrateStudentsExportService::LIST_ELIGIBLE ? 'ready-to-migrate' : 'migrated-roster';
        $fileName = 'fc-migrate-'.$slug.'-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(
            new FcRosterListExport(
                collect($payload['rows']),
                $payload['columns'],
                $payload['filterLine'],
                $list === FcMigrateStudentsExportService::LIST_ELIGIBLE ? 'Ready to migrate' : 'Migrated roster',
                $payload['title'],
                $payload['truncated'],
                $payload['totalMatching'],
            ),
            $fileName
        );
    }

    public function migrate(Request $request)
    {
        $request->validate([
            'selected_pks' => 'required|string',
        ]);

        $pks = array_values(array_unique(array_filter(array_map(
            'intval',
            explode(',', (string) $request->input('selected_pks', ''))
        ))));

        if ($pks === []) {
            return back()->with('error', 'Please select at least one eligible record to migrate.');
        }

        DB::beginTransaction();

        try {
            $batchSize = 500;
            $now = Carbon::now();
            $studentMasterColumns = Schema::getColumnListing('student_master');
            $migratedCount = 0;
            // user_credentials.pk of every migrated login — fed to the Officer Trainee role
            // backfill after the transaction commits.
            $affectedCredentialPks = [];

            $this->eligibleRosterQuery()
                ->whereIn('r.pk', $pks)
                ->orderBy('r.pk')
                ->chunk($batchSize, function ($chunkedRecords) use ($now, $studentMasterColumns, &$migratedCount, &$affectedCredentialPks) {
                    $studentsToInsert = [];
                    $studentsToUpdate = [];
                    $credentialsToInsert = [];
                    $credentialsToUpdate = [];
                    $courseMapsToInsert = [];
                    $updateIds = [];

                    // Usernames must be strings (OT codes like 4117353 are numeric in DB).
                    $userIds = [];
                    foreach ($chunkedRecords as $chunkRecord) {
                        if (!empty($chunkRecord->user_id)) {
                            $userIds[] = $this->normalizeLoginUsername($chunkRecord->user_id);
                        }
                    }
                    $userIds = array_values(array_unique($userIds));

                    // Pre-fetch existing records
                    $existingStudents = DB::table('student_master')
                        ->whereIn('user_id', $userIds)
                        ->get()
                        ->keyBy(fn ($row) => $this->normalizeLoginUsername($row->user_id));

                    $mobiles = [];
                    $emails = [];
                    foreach ($chunkedRecords as $chunkRecord) {
                        $mobile = trim((string) ($chunkRecord->contact_no ?? ''));
                        if ($mobile !== '') {
                            $mobiles[] = $mobile;
                        }
                        $email = strtolower(trim((string) ($chunkRecord->email ?? '')));
                        if ($email !== '') {
                            $emails[] = $email;
                        }
                    }
                    $mobiles = array_values(array_unique($mobiles));
                    $emails = array_values(array_unique($emails));

                    if ($userIds === [] && $mobiles === [] && $emails === []) {
                        $credentialRows = collect();
                    } else {
                        $credentialRows = DB::table('user_credentials')
                            ->where(function ($q) use ($userIds, $mobiles, $emails) {
                                if ($userIds !== []) {
                                    $q->whereIn('user_name', $userIds);
                                }
                                if ($mobiles !== []) {
                                    $userIds !== []
                                        ? $q->orWhereIn('mobile_no', $mobiles)
                                        : $q->whereIn('mobile_no', $mobiles);
                                }
                                if ($emails !== []) {
                                    ($userIds !== [] || $mobiles !== [])
                                        ? $q->orWhereIn('email_id', $emails)
                                        : $q->whereIn('email_id', $emails);
                                }
                            })
                            ->get();
                    }

                    $existingCredentials = $credentialRows->keyBy(
                        fn ($row) => $this->normalizeLoginUsername($row->user_name)
                    );
                    $existingCredentialsByMobile = $credentialRows
                        ->filter(function ($row) {
                            return trim((string) ($row->mobile_no ?? '')) !== '';
                        })
                        ->keyBy(function ($row) {
                            return trim((string) ($row->mobile_no ?? ''));
                        });
                    $existingCredentialsByEmail = $credentialRows
                        ->filter(function ($row) {
                            return strtolower(trim((string) ($row->email_id ?? ''))) !== '';
                        })
                        ->keyBy(function ($row) {
                            return strtolower(trim((string) ($row->email_id ?? '')));
                        });

                    // Process each record
                    foreach ($chunkedRecords as $record) {
                        if (empty($record->user_id)) {
                            continue;
                        }
                        $migratedCount++;

                        $userName = $this->normalizeLoginUsername($record->user_id);

                        $alternateEmail = $this->rosterValue($record, 'alternative_email')
                            ?? $this->rosterValue($record, 'pemail_id');

                        // 1. Handle student_master table
                        $existingStudent = $existingStudents[$userName] ?? null;

                        if (!$existingStudent) {
                            $studentsToInsert[$userName] = $this->buildStudentDataFromRoster(
                                $record,
                                $now,
                                $studentMasterColumns
                            );
                        } else {
                            // Check if any data has changed and needs update
                            $updateData = [];

                            // Define fields to check for changes
                            $fieldsToCheck = [
                            'email', 'contact_no', 'display_name', 'password', 'schema_id', 
                            'final_submit', 'submit_date', 'first_name', 'middle_name', 
                            'last_name', 'admission_status', 'rank', 'exam_year', 
                            'service_master_pk', 'web_auth', 'dob', 'status', 'course_master_pk',
                            'finance_bookEntityCode', 'refund_status', 'enrollment', 'admission_category_pk',
                            'gender', 'photo_path', 'address', 'country_master_pk', 'state_master_pk',
                            'city', 'pin_code', 'merital_status', 'religion_master_pk', 'background',
                            'father_fname', 'father_mname', 'father_lname', 'father_profession',
                            'mother_name', 'family_annual_income', 'university_medium', 'pre_university_medium',
                            'upsc_exam_medium', 'upsc_viva_medium', 'academic_medium', 'height', 'weight',
                            'blood_group', 'dietary', 'signature_path', 'postal_address', 'postal_country_pk',
                            'postal_state_pk', 'postal_city', 'postal_pin_code', 'fax', 'domicile_state_pk',
                            'state_district_mapping_pk', 'town_village', 'pcontact_no', 'pemail_id', 'pfax',
                            'generated_OT_code', 'enrollment_no', 'anniversary_date', 'cadre_master_pk',
                            'current_sem', 'spouse_name', 'spouse_dob', 'designation', 'department',
                            'passport_no', 'rr_scs', 'last_service_pk', 'birth_place', 'birth_city_village_name',
                            'city_type', 'pcity_type', 'pass_in_char', 'highest_stream_pk', 'emergency_contact_person',
                            'emergency_contact_person_mobile', 'passport_issue_date', 'passport_expire_date',
                            'fc_exemption_master_pk', 'conform_student', 'father_husband', 'csestatus',
                            'aadhar_card', 'pan_card', 'instagram_id', 'twitter_id', 'guardian_contact',
                            'guardian_email', 'birth_state', 'medical_history', 'guardian_firstname',
                            'guardian_middlename', 'guardian_lastname', 'highattitude_trek', 'mother_firstname',
                            'mother_middlename', 'mother_lastname', 'mother_qualification', 'mother_profession',
                            'father_qualification', 'nationality', 'pdistrict_id', 'mdistrict_id', 'highattremarks',
                            'isspouse', 'hindiname', 'id_card', 'idcard_created_by', 'idcard_date', 'cgname',
                            'ph', 'cgno', 'no_of_attempt', 'birth_distict', 'mother_annualincom', 'mother_Lang', 'status', 'course_master_pk',
                                // Add other fields as needed...
                            ];

                            foreach ($fieldsToCheck as $field) {
                                if (!property_exists($record, $field)) {
                                    continue;
                                }
                                if ($record->{$field} != ($existingStudent->{$field} ?? null)) {
                                    $updateData[$field] = $record->{$field};
                                }
                            }

                            // If there are changes, add to update array
                            if (!empty($updateData)) {
                                $studentsToUpdate[$existingStudent->pk] = $updateData;
                            }
                        }

                        // 2. Handle user_credentials table (match username, mobile, or email — same as list/eligible)
                        $existingCredential = $this->resolveExistingCredential(
                            $record,
                            $existingCredentials,
                            $existingCredentialsByMobile,
                            $existingCredentialsByEmail
                        );

                        if (!$existingCredential) {
                            // fc_registration_master → user_credentials (created at migration only)
                            $credentialsToInsert[$userName] = [
                                'user_name' => $userName,
                                'first_name' => $this->rosterValue($record, 'first_name'),
                                'last_name' => $this->rosterValue($record, 'last_name'),
                                'jbp_password' => $this->rosterValue($record, 'password'),
                                'email_id' => $this->rosterValue($record, 'email'),
                                'mobile_no' => $this->rosterValue($record, 'contact_no'),
                                'alternate_mailid' => $alternateEmail,
                                'reg_date' => $this->rosterValue($record, 'submit_date', $now),
                                'jbp_enabled' => 1,
                                'login_status' => 1,
                                'schemaid' => 1,
                                'user_id' => 0, // Will be updated after student insertion
                                'last_login' => $now,
                                'security_question' => 'What Is Your Web Authentication Code?',
                                'security_answer' => $this->rosterValue($record, 'web_auth'),
                                'entity_id' => 5,
                                'image_path' => $this->rosterValue($record, 'photo_path'),
                                'user_category' => 'S',
                                'Active_inactive' => 1,
                                'remember_token' => null,
                                'updated_date' => $now,
                            ];
                        } else {
                            // Check if credentials need update
                            $updateCredentialData = [];

                            $credentialFieldsToCheck = [
                                'first_name',
                                'last_name',
                                'jbp_password',
                                'email_id',
                                'mobile_no',
                                'alternate_mailid',
                                'security_answer',
                                'image_path'
                            ];

                            foreach ($credentialFieldsToCheck as $field) {
                                $sourceField = match ($field) {
                                    'jbp_password' => 'password',
                                    'email_id' => 'email',
                                    'mobile_no' => 'contact_no',
                                    'alternate_mailid' => 'alternative_email',
                                    'security_answer' => 'web_auth',
                                    'image_path' => 'photo_path',
                                    default => $field
                                };

                                $sourceValue = $field === 'alternate_mailid'
                                    ? $alternateEmail
                                    : $this->rosterValue($record, $sourceField);

                                if ($sourceValue !== null && $sourceValue != $existingCredential->$field) {
                                    $updateCredentialData[$field] = $sourceValue;
                                }
                            }

                            // If there are changes, add to update array (keyed by credentials pk — may differ from roster user_id)
                            if (!empty($updateCredentialData)) {
                                $credentialsToUpdate[(int) $existingCredential->pk] = $updateCredentialData;
                            }
                        }

                        // 3. Prepare course mapping data
                        if ($record->course_master_pk) {
                            $studentPk = $existingStudent->pk ?? null;

                            $courseMapsToInsert[] = [
                                'user_id' => $userName, // For reference
                                'course_master_pk' => $record->course_master_pk,
                                'student_master_pk' => $studentPk, // Will be updated if null
                                'active_inactive' => 1,
                                'created_date' => $now,
                                'modified_date' => $now,
                            ];
                        }

                        $updateIds[] = $record->pk;
                    }

                    // Batch insert students
                    $insertedStudents = [];
                    if (!empty($studentsToInsert)) {
                        foreach ($studentsToInsert as $userId => $studentData) {
                            try {
                                $studentId = DB::table('student_master')->insertGetId($studentData);
                                $insertedStudents[$userId] = $studentId;
                            } catch (\Exception $e) {
                                continue;
                            }
                        }

                        // Update credentials with the correct student IDs
                        foreach ($credentialsToInsert as $userId => &$credential) {
                            if (isset($insertedStudents[$userId])) {
                                $credential['user_id'] = $insertedStudents[$userId];
                            }
                        }

                        // Update course mappings with the correct student IDs
                        foreach ($courseMapsToInsert as &$mapping) {
                            if (isset($insertedStudents[$mapping['user_id']])) {
                                $mapping['student_master_pk'] = $insertedStudents[$mapping['user_id']];
                            }
                        }
                    }

                    // Batch update students
                    foreach ($studentsToUpdate as $studentId => $updateData) {
                        try {
                            DB::table('student_master')
                                ->where('pk', $studentId)
                                ->update($updateData);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // Resolve student PKs for this batch (new inserts + existing rows)
                    $studentPkByUserId = [];
                    foreach ($userIds as $userId) {
                        if (isset($insertedStudents[$userId])) {
                            $studentPkByUserId[$userId] = $insertedStudents[$userId];
                        } elseif (isset($existingStudents[$userId])) {
                            $studentPkByUserId[$userId] = $existingStudents[$userId]->pk;
                        }
                    }

                    // Batch insert credentials only when student_master row exists
                    if (!empty($credentialsToInsert)) {
                        foreach ($credentialsToInsert as $userId => $credentialData) {
                            if (!isset($studentPkByUserId[$userId])) {
                                continue;
                            }
                            $credentialData['user_id'] = $studentPkByUserId[$userId];
                            try {
                                DB::table('user_credentials')->insertOrIgnore($credentialData);
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                    // Batch update credentials
                    foreach ($credentialsToUpdate as $credentialsPk => $updateData) {
                        try {
                            DB::table('user_credentials')
                                ->where('pk', '=', (int) $credentialsPk)
                                ->update($updateData);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // Link user_credentials.user_id → student_master.pk (roster username → student row)
                    foreach ($studentPkByUserId as $userId => $studentPk) {
                        $loginName = $this->normalizeLoginUsername($userId);
                        if ($loginName === '' || !is_numeric($studentPk)) {
                            continue;
                        }

                        DB::table('user_credentials')
                            ->where('user_name', '=', $loginName)
                            ->update(['user_id' => (int) $studentPk]);

                        // Also link when credentials exist under another user_name but same mobile/email
                        $rosterRecord = $chunkedRecords->first(
                            fn ($r) => $this->normalizeLoginUsername($r->user_id ?? '') === $loginName
                        );
                        if ($rosterRecord) {
                            $matched = $this->resolveExistingCredential(
                                $rosterRecord,
                                $existingCredentials,
                                $existingCredentialsByMobile,
                                $existingCredentialsByEmail
                            );
                            if ($matched && $this->normalizeLoginUsername($matched->user_name) !== $loginName) {
                                DB::table('user_credentials')
                                    ->where('pk', '=', (int) $matched->pk)
                                    ->update(['user_id' => (int) $studentPk]);
                            }
                        }
                    }

                    // Re-key form rows saved with roster pk placeholder → user_credentials.pk.
                    // The pre-loaded $existingCredentials only covers credentials that existed
                    // BEFORE this batch. For credentials inserted in this same batch we fall back
                    // to a fresh DB lookup by username so that newly-created accounts are also
                    // re-keyed immediately (preventing a split-identity where form data sits under
                    // the roster pk while reports resolve the user to user_credentials.pk).
                    foreach ($chunkedRecords as $record) {
                        if (empty($record->user_id) || empty($record->pk)) {
                            continue;
                        }
                        $existingForRekey = $this->resolveExistingCredential(
                            $record,
                            $existingCredentials,
                            $existingCredentialsByMobile,
                            $existingCredentialsByEmail
                        );

                        // Fallback: credential may have been inserted in this same batch and
                        // therefore is absent from the pre-loaded collection.
                        if (! $existingForRekey) {
                            $userName = $this->normalizeLoginUsername($record->user_id ?? '');
                            if ($userName !== '') {
                                $freshCred = DB::table('user_credentials')
                                    ->where('user_name', $userName)
                                    ->first(['pk']);
                                if ($freshCred) {
                                    $existingForRekey = $freshCred;
                                }
                            }
                        }

                        if ($existingForRekey && ! empty($existingForRekey->pk)) {
                            $affectedCredentialPks[] = (int) $existingForRekey->pk;
                            $this->rekeyFcFormUserIdFromRosterPk((int) $record->pk, (int) $existingForRekey->pk);
                        }
                    }

                    // Filter and insert course mappings
                    $validCourseMaps = array_filter($courseMapsToInsert, function ($map) {
                        return isset($map['student_master_pk']) && is_numeric($map['student_master_pk']);
                    });

                    // Pre-fetch the (student, course) pairs already mapped for this batch in a
                    // single query instead of an exists() lookup per mapping. Outcome is identical
                    // — the same existing combinations are skipped — but N SELECTs collapse to 1.
                    $existingCourseMaps = [];
                    if (!empty($validCourseMaps)) {
                        $mapStudentPks = array_values(array_unique(array_map(
                            static fn ($map) => (int) $map['student_master_pk'],
                            $validCourseMaps
                        )));
                        $mapCoursePks = array_values(array_unique(array_map(
                            static fn ($map) => (int) $map['course_master_pk'],
                            $validCourseMaps
                        )));

                        DB::table('student_master_course__map')
                            ->whereIn('student_master_pk', $mapStudentPks)
                            ->whereIn('course_master_pk', $mapCoursePks)
                            ->get(['student_master_pk', 'course_master_pk'])
                            ->each(function ($row) use (&$existingCourseMaps) {
                                $existingCourseMaps[$row->student_master_pk . '_' . $row->course_master_pk] = true;
                            });
                    }

                    // Remove temporary user_id field and check for duplicates
                    $finalCourseMaps = [];
                    $processedMappings = []; // Track processed combinations to avoid duplicates

                    foreach ($validCourseMaps as $map) {
                        $mapKey = $map['student_master_pk'] . '_' . $map['course_master_pk'];

                        // Skip if we've already processed this combination in the current batch
                        if (isset($processedMappings[$mapKey])) {
                            continue;
                        }

                        // Skip if the mapping already exists in the database (pre-fetched above)
                        if (isset($existingCourseMaps[$mapKey])) {
                            continue;
                        }

                        unset($map['user_id']);
                        $finalCourseMaps[] = $map;
                        $processedMappings[$mapKey] = true;
                    }

                    // Batch insert course mappings in a single statement; insertOrIgnore remains
                    // the final safety net against a concurrent duplicate. If the bulk insert
                    // fails for any reason, fall back to per-row inserts so one bad row cannot
                    // drop the whole batch (preserving the previous behaviour).
                    if (!empty($finalCourseMaps)) {
                        try {
                            DB::table('student_master_course__map')->insertOrIgnore($finalCourseMaps);
                        } catch (\Exception $e) {
                            foreach ($finalCourseMaps as $mapData) {
                                try {
                                    DB::table('student_master_course__map')->insertOrIgnore($mapData);
                                } catch (\Exception $inner) {
                                    continue;
                                }
                            }
                        }
                    }


                });

            DB::commit();

            // Officer Trainee role backfill for the migrated logins.
            // Runs AFTER commit (never inside the migration transaction) and is wrapped so a
            // role-assignment failure can never roll back an already-committed migration. The
            // service is idempotent — it inserts into model_has_roles only for user_credentials
            // rows of category 'S' that are missing the role — so re-runs are safe.
            $affectedCredentialPks = array_values(array_unique(array_filter($affectedCredentialPks)));
            if ($affectedCredentialPks !== []) {
                try {
                    $this->officerTraineeRole->assignToUserPks($affectedCredentialPks);
                } catch (\Throwable $e) {
                    Log::warning('FC migration: Officer Trainee role assignment failed: ' . $e->getMessage());
                }
            }

            if ($migratedCount === 0) {
                return back()->with('error', 'No eligible records were migrated. Check selection and filters.');
            }

            // Credentials were just written, so the cached roster↔credentials match
            // set is stale — drop it so both tabs recount immediately.
            \App\Services\FC\FcMigrateStudentsExportService::flushMatchedRosterPks();

            return back()->with('success', "Migration completed successfully for {$migratedCount} record(s).");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Rows eligible for admin migration (same rules as DataTable "Ready to migrate").
     */
    private function eligibleRosterQuery()
    {
        // Override the limited display-only column list with r.* so the migration
        // logic can access all fc_registration_master fields (e.g. course_master_pk,
        // service_master_pk, contact_no, password, dob …).
        return $this->exports->eligibleQuery(request())
            ->select([
                'r.*',
                's.service_name',
                's.service_short_name',
                'c.course_name',
                'c.couse_short_name as course_short_name',
            ]);
    }

    /**
     * FC usernames may be numeric OT codes; always use string for user_credentials.user_name lookups.
     */
    private function normalizeLoginUsername(mixed $userId): string
    {
        return trim((string) $userId);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, object>  $byUserName
     * @param  \Illuminate\Support\Collection<string, object>  $byMobile
     * @param  \Illuminate\Support\Collection<string, object>  $byEmail
     */
    private function resolveExistingCredential(
        object $record,
        $byUserName,
        $byMobile,
        $byEmail
    ): ?object {
        $userName = $this->normalizeLoginUsername($record->user_id ?? '');
        if ($userName !== '' && $byUserName->has($userName)) {
            return $byUserName->get($userName);
        }

        $mobile = trim((string) ($record->contact_no ?? ''));
        if ($mobile !== '' && $byMobile->has($mobile)) {
            return $byMobile->get($mobile);
        }

        $email = strtolower(trim((string) ($record->email ?? '')));
        if ($email !== '' && $byEmail->has($email)) {
            return $byEmail->get($email);
        }

        return null;
    }

    /**
     * FC forms may store fc_registration_master.pk in user_id before migration; switch to user_credentials.pk.
     */
    private function rekeyFcFormUserIdFromRosterPk(int $rosterPk, int $credentialsPk): void
    {
        if ($rosterPk < 1 || $credentialsPk < 1 || $rosterPk === $credentialsPk) {
            return;
        }

        // Schema existence is resolved once per request (see rekeyTables()), not per record,
        // so the ~30 information_schema lookups no longer repeat for every migrated user.
        foreach ($this->rekeyTables() as $table) {
            try {
                DB::table($table)->where('user_id', $rosterPk)->update(['user_id' => $credentialsPk]);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Populate course_master_pk (INT) on fc_pre_history and fc_ot_details rows for this user.
        $courseFlags = $this->courseBackfillFlags();
        if ($courseFlags['enabled']) {
            $rosterCoursePk = (int) (DB::table('fc_registration_master')
                ->where('pk', $rosterPk)
                ->value('course_master_pk') ?? 0);

            if ($rosterCoursePk > 0) {
                if ($courseFlags['pre_history']) {
                    DB::table('fc_pre_history')
                        ->where('user_id', $credentialsPk)
                        ->whereNull('course_master_pk')
                        ->update(['course_master_pk' => $rosterCoursePk]);
                }

                // fc_ot_details: set course_master_pk on rows for this user that are still null
                if ($courseFlags['ot_details']) {
                    DB::table('fc_ot_details')
                        ->where('user_id', $credentialsPk)
                        ->whereNull('course_master_pk')
                        ->update(['course_master_pk' => $rosterCoursePk]);
                }
            }
        }
    }

    /**
     * Tables whose user_id must switch from the roster pk to user_credentials.pk after migration.
     * Existence is checked once and cached for the request lifetime (the schema never changes
     * mid-run), replacing ~60 information_schema lookups per migrated record with a one-time cost.
     * The candidate list and its filter rules are identical to the previous inline version.
     *
     * @return array<int, string>
     */
    private function rekeyTables(): array
    {
        if ($this->rekeyTablesResolved !== null) {
            return $this->rekeyTablesResolved;
        }

        $candidates = [
            'student_masters',
            'student_master_firsts',
            'student_master_seconds',
            'student_master_spouse_masters',
            'student_knowledge_hindi_masters',
            'student_master_hobbies_details',
            'student_master_module_masters',
            'student_master_exempted_masters',
            'student_fc_scale_masters',
            'student_confirm_masters',
            'student_master_incomplet_masters',
            'new_registration_bank_details_masters',
            'registration_bank_details_masters',
            'student_travel_plan_masters',
            'student_master_qualification_details',
            'student_master_higher_educational_details',
            'student_master_employment_details',
            'student_master_language_knowns',
            'student_skill_details_masters',
            'student_master_academic_distinctions',
            'student_sports_fitness_teach_masters',
            'student_sports_trg_teach_masters',
            'fc_joining_related_documents_details_masters',
            // Joining documents uploaded via the dynamic registration form. Missing
            // from this list previously, which orphaned trainees' uploaded documents
            // under the roster pk after migration (invisible in the documents report).
            'fc_joining_documents_user_uploads',
            // Other dynamic-form target tables keyed by user_id.
            'student_iosr_reasonable_adjust_masters',
            'student_cloth_size_master_details',
            'fc_ot_details',
            'fc_otactivity_details',
            'fc_pre_history',
            'fc_path_report',
        ];

        return $this->rekeyTablesResolved = array_values(array_filter(
            $candidates,
            static function ($table) {
                return \Illuminate\Support\Facades\Schema::hasTable($table)
                    && \Illuminate\Support\Facades\Schema::hasColumn($table, 'user_id');
            }
        ));
    }

    /**
     * Resolve (once per request) whether the course_master_pk backfill targets exist.
     *
     * @return array{enabled: bool, pre_history: bool, ot_details: bool}
     */
    private function courseBackfillFlags(): array
    {
        if ($this->courseBackfillFlagsResolved !== null) {
            return $this->courseBackfillFlagsResolved;
        }

        $enabled = \Illuminate\Support\Facades\Schema::hasTable('fc_registration_master')
            && \Illuminate\Support\Facades\Schema::hasTable('course_master');

        return $this->courseBackfillFlagsResolved = [
            'enabled' => $enabled,
            'pre_history' => $enabled && \Illuminate\Support\Facades\Schema::hasTable('fc_pre_history'),
            'ot_details' => $enabled && \Illuminate\Support\Facades\Schema::hasTable('fc_ot_details'),
        ];
    }

    /**
     * Read a roster column only when it exists on fc_registration_master (avoids undefined stdClass properties).
     */
    private function rosterValue(object $record, string $field, mixed $default = null): mixed
    {
        return property_exists($record, $field) ? $record->{$field} : $default;
    }

    private function buildStudentDataFromRoster(object $record, Carbon $now, array $studentMasterColumns): array
    {
        $fields = [
            'email', 'contact_no', 'display_name', 'password', 'schema_id', 'submit_date',
            'first_name', 'middle_name', 'last_name', 'admission_status', 'rank', 'exam_year',
            'web_auth', 'dob', 'status', 'course_master_pk', 'finance_bookEntityCode', 'refund_status',
            'enrollment', 'admission_category_pk', 'gender', 'photo_path', 'address', 'country_master_pk',
            'state_master_pk', 'city', 'pin_code', 'merital_status', 'religion_master_pk', 'background',
            'father_fname', 'father_mname', 'father_lname', 'father_profession', 'mother_name',
            'family_annual_income', 'university_medium', 'pre_university_medium', 'upsc_exam_medium',
            'upsc_viva_medium', 'academic_medium', 'height', 'weight', 'blood_group', 'dietary',
            'signature_path', 'postal_address', 'postal_country_pk', 'postal_state_pk', 'postal_city',
            'postal_pin_code', 'fax', 'domicile_state_pk', 'state_district_mapping_pk', 'town_village',
            'pcontact_no', 'pemail_id', 'pfax', 'generated_OT_code', 'enrollment_no', 'anniversary_date',
            'cadre_master_pk', 'current_sem', 'spouse_name', 'spouse_dob', 'designation', 'department',
            'passport_no', 'rr_scs', 'last_service_pk', 'birth_place', 'birth_city_village_name',
            'city_type', 'pcity_type', 'pass_in_char', 'highest_stream_pk', 'emergency_contact_person',
            'emergency_contact_person_mobile', 'passport_issue_date', 'passport_expire_date',
            'fc_exemption_master_pk', 'conform_student', 'father_husband', 'csestatus', 'aadhar_card',
            'pan_card', 'instagram_id', 'twitter_id', 'guardian_contact', 'guardian_email', 'birth_state',
            'medical_history', 'guardian_firstname', 'guardian_middlename', 'guardian_lastname',
            'highattitude_trek', 'mother_firstname', 'mother_middlename', 'mother_lastname',
            'mother_qualification', 'mother_profession', 'father_qualification', 'nationality',
            'pdistrict_id', 'mdistrict_id', 'highattremarks', 'isspouse', 'hindiname', 'id_card',
            'idcard_created_by', 'idcard_date', 'cgname', 'ph', 'cgno',
        ];

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->rosterValue($record, $field);
        }

        $data['user_id'] = $this->normalizeLoginUsername($this->rosterValue($record, 'user_id', ''));
        $data['final_submit'] = $this->rosterValue($record, 'final_submit', 0);
        $data['service_master_pk'] = (int) ($this->rosterValue($record, 'service_master_pk', 0) ?? 0);
        $data['created_date'] = $this->rosterValue($record, 'created_date', $now);

        // Only columns that exist on student_master; omit nulls so NOT NULL defaults (e.g. last_service_pk = 0) apply.
        $data = array_intersect_key($data, array_flip($studentMasterColumns));
        $data = array_filter($data, static fn ($value) => $value !== null);

        if (in_array('service_master_pk', $studentMasterColumns, true) && !isset($data['service_master_pk'])) {
            $data['service_master_pk'] = (int) ($this->rosterValue($record, 'service_master_pk', 0) ?? 0);
        }

        return $data;
    }
}
