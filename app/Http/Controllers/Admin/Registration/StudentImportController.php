<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class StudentImportController extends Controller
{

    public function index()
    {
        // $students = DB::table('students')->orderBy('created_date', 'desc')->get();
        return view('admin.registration.import_students');
    }


    //final
    public function migrate()
    {
        DB::beginTransaction();

        try {
            $batchSize = 500;
            $now = Carbon::now();

            DB::table('fc_registration_master')
                ->where('fc_exemption_master_pk', 0)
                ->where('admission_status', 1)
                ->orderBy('pk')
                ->chunk($batchSize, function ($chunkedRecords) use ($now) {
                    $studentsToInsert = [];
                    $studentsToUpdate = [];
                    $credentialsToInsert = [];
                    $credentialsToUpdate = [];
                    $courseMapsToInsert = [];
                    $updateIds = [];

                    // Get all user_ids from current batch
                    $userIds = array_filter($chunkedRecords->pluck('user_id')->toArray());

                    // Pre-fetch existing records
                    $existingStudents = DB::table('student_master')
                        ->whereIn('user_id', $userIds)
                        ->get()
                        ->keyBy('user_id');

                    $existingCredentials = DB::table('user_credentials')
                        ->whereIn('user_name', $userIds)
                        ->get()
                        ->keyBy('user_name');

                    // Process each record
                    foreach ($chunkedRecords as $record) {
                        if (empty($record->user_id)) {
                            continue;
                        }

                        // 1. Handle student_master table
                        $existingStudent = $existingStudents[$record->user_id] ?? null;

                        if (!$existingStudent) {
                            // Prepare student data for insertion
                            $studentData = [
                                'email'                      => $record->email,
                                'contact_no'                 => $record->contact_no,
                                'user_id'                    => $record->user_id ?? 0,
                                'display_name'               => $record->display_name,
                                'password'                   => $record->password,
                                'schema_id'                  => $record->schema_id,
                                'final_submit'               => $record->final_submit ?? 0,
                                'submit_date'                => $record->submit_date,
                                'created_date'               => $record->created_date ?? $now,
                                'first_name'                 => $record->first_name,
                                'middle_name'                => $record->middle_name,
                                'last_name'                  => $record->last_name,
                                'admission_status'           => $record->admission_status,
                                'rank'                       => $record->rank,
                                'exam_year'                  => $record->exam_year,
                                'service_master_pk'         => $record->service_master_pk ?? 0,
                                'web_auth'                   => $record->web_auth,
                                'dob'                        => $record->dob,
                                'status'                     => $record->status,
                                'course_master_pk'           => $record->course_master_pk,
                                'finance_bookEntityCode'     => $record->finance_bookEntityCode,
                                'refund_status'              => $record->refund_status,
                                'enrollment'                 => $record->enrollment,
                                'admission_category_pk'      => $record->admission_category_pk,
                                'gender'                     => $record->gender,
                                'photo_path'                 => $record->photo_path,
                                'address'                    => $record->address,
                                'country_master_pk'          => $record->country_master_pk,
                                'state_master_pk'            => $record->state_master_pk,
                                'city'                       => $record->city,
                                'pin_code'                   => $record->pin_code,
                                'merital_status'             => $record->merital_status,
                                'religion_master_pk'         => $record->religion_master_pk,
                                'background'                 => $record->background,
                                'father_fname'               => $record->father_fname,
                                'father_mname'               => $record->father_mname,
                                'father_lname'               => $record->father_lname,
                                'father_profession'          => $record->father_profession,
                                'mother_name'                => $record->mother_name,
                                'family_annual_income'       => $record->family_annual_income,
                                'university_medium'          => $record->university_medium,
                                'pre_university_medium'      => $record->pre_university_medium,
                                'upsc_exam_medium'           => $record->upsc_exam_medium,
                                'upsc_viva_medium'           => $record->upsc_viva_medium,
                                'academic_medium'            => $record->academic_medium,
                                'height'                     => $record->height,
                                'weight'                     => $record->weight,
                                'blood_group'                => $record->blood_group,
                                'dietary'                    => $record->dietary,
                                'signature_path'             => $record->signature_path,
                                'postal_address'             => $record->postal_address,
                                'postal_country_pk'          => $record->postal_country_pk,
                                'postal_state_pk'            => $record->postal_state_pk,
                                'postal_city'                => $record->postal_city,
                                'postal_pin_code'            => $record->postal_pin_code,
                                'fax'                        => $record->fax,
                                'domicile_state_pk'          => $record->domicile_state_pk,
                                'state_district_mapping_pk'  => $record->state_district_mapping_pk,
                                'town_village'               => $record->town_village,
                                'pcontact_no'                => $record->pcontact_no,
                                'pemail_id'                  => $record->pemail_id,
                                'pfax'                       => $record->pfax,
                                'generated_OT_code'          => $record->generated_OT_code,
                                'enrollment_no'              => $record->enrollment_no,
                                'anniversary_date'           => $record->anniversary_date,
                                'cadre_master_pk'            => $record->cadre_master_pk,
                                'current_sem'                => $record->current_sem,
                                'spouse_name'                => $record->spouse_name,
                                'spouse_dob'                 => $record->spouse_dob,
                                'designation'                => $record->designation,
                                'department'                 => $record->department,
                                'passport_no'                => $record->passport_no,
                                'rr_scs'                     => $record->rr_scs,
                                'last_service_pk'            => $record->last_service_pk,
                                'birth_place'                => $record->birth_place,
                                'birth_city_village_name'    => $record->birth_city_village_name,
                                'city_type'                  => $record->city_type,
                                'pcity_type'                 => $record->pcity_type,
                                'pass_in_char'               => $record->pass_in_char,
                                'highest_stream_pk'          => $record->highest_stream_pk,
                                'emergency_contact_person'   => $record->emergency_contact_person,
                                'emergency_contact_person_mobile' => $record->emergency_contact_person_mobile,
                                'passport_issue_date'        => $record->passport_issue_date,
                                'passport_expire_date'       => $record->passport_expire_date,
                                'fc_exemption_master_pk'     => $record->fc_exemption_master_pk,
                                'conform_student'            => $record->conform_student,
                                'father_husband'             => $record->father_husband,
                                'csestatus'                  => $record->csestatus,
                                'aadhar_card'                => $record->aadhar_card,
                                'pan_card'                   => $record->pan_card,
                                'instagram_id'               => $record->instagram_id,
                                'twitter_id'                 => $record->twitter_id,
                                'guardian_contact'           => $record->guardian_contact,
                                'guardian_email'             => $record->guardian_email,
                                'birth_state'                => $record->birth_state,
                                'medical_history'            => $record->medical_history,
                                'guardian_firstname'         => $record->guardian_firstname,
                                'guardian_middlename'        => $record->guardian_middlename,
                                'guardian_lastname'          => $record->guardian_lastname,
                                'highattitude_trek'          => $record->highattitude_trek,
                                'mother_firstname'           => $record->mother_firstname,
                                'mother_middlename'          => $record->mother_middlename,
                                'mother_lastname'            => $record->mother_lastname,
                                'mother_qualification'       => $record->mother_qualification,
                                'mother_profession'          => $record->mother_profession,
                                'father_qualification'       => $record->father_qualification,
                                'nationality'                => $record->nationality,
                                'pdistrict_id'               => $record->pdistrict_id,
                                'mdistrict_id'               => $record->mdistrict_id,
                                'highattremarks'             => $record->highattremarks,
                                'isspouse'                   => $record->isspouse,
                                'hindiname'                  => $record->hindiname,
                                'id_card'                    => $record->id_card,
                                'idcard_created_by'          => $record->idcard_created_by,
                                'idcard_date'                => $record->idcard_date,
                                'cgname'                     => $record->cgname,
                                'ph'                         => $record->ph,
                                'cgno'                       => $record->cgno,
                                // Add other fields as needed...
                            ];

                            $studentsToInsert[$record->user_id] = $studentData;
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
                                if (isset($record->$field) && $record->$field != $existingStudent->$field) {
                                    $updateData[$field] = $record->$field;
                                }
                            }

                            // If there are changes, add to update array
                            if (!empty($updateData)) {
                                $studentsToUpdate[$existingStudent->pk] = $updateData;
                            }
                        }

                        // 2. Handle user_credentials table
                        $existingCredential = $existingCredentials[$record->user_id] ?? null;

                        if (!$existingCredential) {
                            // Prepare credentials for insertion
                            $credentialsToInsert[$record->user_id] = [
                                'user_name' => $record->user_id,
                                'first_name' => $record->first_name,
                                'last_name' => $record->last_name,
                                'jbp_password' => $record->password,
                                'email_id' => $record->email,
                                'mobile_no' => $record->contact_no,
                                'alternate_mailid' => $record->pemail_id,
                                'reg_date' => $record->submit_date ?? $now,
                                'jbp_enabled' => 1,
                                'login_status' => 1,
                                'schemaid' => 1,
                                'user_id' => 0, // Will be updated after student insertion
                                'last_login' => $now,
                                'security_question' => 'What Is Your Web Authentication Code?',
                                'security_answer' => $record->web_auth,
                                'entity_id' => 5,
                                'image_path' => $record->photo_path,
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
                                    'alternate_mailid' => 'pemail_id',
                                    'security_answer' => 'web_auth',
                                    'image_path' => 'photo_path',
                                    default => $field
                                };

                                if (isset($record->$sourceField) && $record->$sourceField != $existingCredential->$field) {
                                    $updateCredentialData[$field] = $record->$sourceField;
                                }
                            }

                            // If there are changes, add to update array
                            if (!empty($updateCredentialData)) {
                                $credentialsToUpdate[$record->user_id] = $updateCredentialData;
                            }
                        }

                        // 3. Prepare course mapping data
                        if ($record->course_master_pk) {
                            $studentPk = $existingStudent->pk ?? null;

                            $courseMapsToInsert[] = [
                                'user_id' => $record->user_id, // For reference
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

                    // Batch insert credentials
                    if (!empty($credentialsToInsert)) {
                        foreach ($credentialsToInsert as $credentialData) {
                            try {
                                DB::table('user_credentials')->insertOrIgnore($credentialData);
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                    // Batch update credentials
                    foreach ($credentialsToUpdate as $userName => $updateData) {
                        try {
                            DB::table('user_credentials')
                                ->where('user_name', $userName)
                                ->update($updateData);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // Filter and insert course mappings
                    $validCourseMaps = array_filter($courseMapsToInsert, function ($map) {
                        return isset($map['student_master_pk']) && is_numeric($map['student_master_pk']);
                    });

                    // Remove temporary user_id field and check for duplicates
                    $finalCourseMaps = [];
                    $processedMappings = []; // Track processed combinations to avoid duplicates

                    foreach ($validCourseMaps as $map) {
                        $mapKey = $map['student_master_pk'] . '_' . $map['course_master_pk'];

                        // Skip if we've already processed this combination in the current batch
                        if (isset($processedMappings[$mapKey])) {
                            continue;
                        }

                        // Double-check if the mapping already exists in the database
                        $exists = DB::table('student_master_course__map')
                            ->where('student_master_pk', $map['student_master_pk'])
                            ->where('course_master_pk', $map['course_master_pk'])
                            ->exists();

                        // Only insert if it doesn't exist
                        if (!$exists) {
                            unset($map['user_id']);
                            $finalCourseMaps[] = $map;
                            $processedMappings[$mapKey] = true;
                        }
                    }

                    // Batch insert course mappings with final duplicate check
                    if (!empty($finalCourseMaps)) {
                        foreach ($finalCourseMaps as $mapData) {
                            try {
                                // Use insertOrIgnore as a final safety net
                                DB::table('student_master_course__map')->insertOrIgnore($mapData);
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }


                    // Mark source records as processed
                    if (!empty($updateIds)) {
                        DB::table('fc_registration_master')
                            ->whereIn('pk', $updateIds)
                            ->update(['is_registered' => 1]);
                    }
                });

            // Find and delete records with fc_exemption_master_pk != 0 from all three tables
            $exemptionRecords = DB::table('fc_registration_master')
                ->where('fc_exemption_master_pk', '!=', 0)
                ->get();

            if ($exemptionRecords->isNotEmpty()) {
                $userIds = $exemptionRecords->pluck('user_id')->filter()->toArray();

                if (!empty($userIds)) {
                    // Find corresponding student records
                    $studentsToDelete = DB::table('student_master')
                        ->whereIn('user_id', $userIds)
                        ->get();

                    if ($studentsToDelete->isNotEmpty()) {
                        $studentIds = $studentsToDelete->pluck('pk')->toArray();

                        // Delete from student_master_course__map
                        DB::table('student_master_course__map')
                            ->whereIn('student_master_pk', $studentIds)
                            ->delete();

                        // Delete from user_credentials
                        DB::table('user_credentials')
                            ->whereIn('user_name', $userIds)
                            ->delete();

                        // Delete from student_master
                        DB::table('student_master')
                            ->whereIn('pk', $studentIds)
                            ->delete();
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Migration completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }
}
