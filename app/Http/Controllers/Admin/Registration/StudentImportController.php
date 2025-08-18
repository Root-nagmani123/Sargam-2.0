<?php

// namespace App\Http\Controllers\Admin\Registration;

// use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;

// class StudentImportController extends Controller
// {
//     public function migrate()
//     {
//         DB::beginTransaction();
//         try {
//             $batchSize = 500;

//             DB::table('fc_registration_master')
//                 ->where('final_submit', 3)
//                 ->where('is_registered', 0)
//                 ->orderBy('pk')
//                 ->chunk($batchSize, function ($chunkedRecords) {

//                     // Get already registered user_ids
//                     $userIds = $chunkedRecords->pluck('user_id')->toArray();
//                     $alreadyRegistered = DB::table('student_master')
//                         ->whereIn('user_id', $userIds)
//                         ->pluck('user_id')
//                         ->toArray();

//                     $updateIds = [];

//                     foreach ($chunkedRecords as $record) {
//                         if (in_array($record->user_id, $alreadyRegistered)) {
//                             continue;
//                         }

//                         // $hashedPassword = Hash::make($record->password);

//                         // Insert into student_master
//                         $studentId = DB::table('student_master')->insertGetId([
//                             'email'                      => $record->email,
//                             'contact_no'                 => $record->contact_no,
//                             'user_id'                    => $record->user_id,
//                             'display_name'               => $record->display_name,
//                             'password'                   => $record->password,
//                             'schema_id'                  => $record->schema_id,
//                             'final_submit'               => $record->final_submit ?? 0,
//                             'submit_date'                => $record->submit_date,
//                             'created_date'               => $record->created_date ?? now(),
//                             'first_name'                 => $record->first_name,
//                             'middle_name'                => $record->middle_name,
//                             'last_name'                  => $record->last_name,
//                             'admission_status'           => $record->admission_status,
//                             'rank'                       => $record->rank,
//                             'exam_year'                  => $record->exam_year,
//                             'service_master_pk'          => $record->service_master_pk,
//                             'web_auth'                   => $record->web_auth,
//                             'dob'                        => $record->dob,
//                             'status'                     => $record->status,
//                             'course_master_pk'           => $record->course_master_pk,
//                             'finance_bookEntityCode'     => $record->finance_bookEntityCode,
//                             'refund_status'              => $record->refund_status,
//                             'enrollment'                 => $record->enrollment,
//                             'admission_category_pk'      => $record->admission_category_pk,
//                             'gender'                     => $record->gender,
//                             'photo_path'                 => $record->photo_path,
//                             'address'                    => $record->address,
//                             'country_master_pk'          => $record->country_master_pk,
//                             'state_master_pk'            => $record->state_master_pk,
//                             'city'                       => $record->city,
//                             'pin_code'                   => $record->pin_code,
//                             'merital_status'             => $record->merital_status,
//                             'religion_master_pk'         => $record->religion_master_pk,
//                             'background'                 => $record->background,
//                             'father_fname'               => $record->father_fname,
//                             'father_mname'               => $record->father_mname,
//                             'father_lname'               => $record->father_lname,
//                             'father_profession'          => $record->father_profession,
//                             'mother_name'                => $record->mother_name,
//                             'family_annual_income'       => $record->family_annual_income,
//                             'university_medium'          => $record->university_medium,
//                             'pre_university_medium'      => $record->pre_university_medium,
//                             'upsc_exam_medium'           => $record->upsc_exam_medium,
//                             'upsc_viva_medium'           => $record->upsc_viva_medium,
//                             'academic_medium'            => $record->academic_medium,
//                             'height'                     => $record->height,
//                             'weight'                     => $record->weight,
//                             'blood_group'                => $record->blood_group,
//                             'dietary'                    => $record->dietary,
//                             'signature_path'             => $record->signature_path,
//                             'postal_address'             => $record->postal_address,
//                             'postal_country_pk'          => $record->postal_country_pk,
//                             'postal_state_pk'            => $record->postal_state_pk,
//                             'postal_city'                => $record->postal_city,
//                             'postal_pin_code'            => $record->postal_pin_code,
//                             'fax'                        => $record->fax,
//                             'domicile_state_pk'          => $record->domicile_state_pk,
//                             'state_district_mapping_pk'  => $record->state_district_mapping_pk,
//                             'town_village'               => $record->town_village,
//                             'pcontact_no'                => $record->pcontact_no,
//                             'pemail_id'                  => $record->pemail_id,
//                             'pfax'                       => $record->pfax,
//                             'generated_OT_code'          => $record->generated_OT_code,
//                             'enrollment_no'              => $record->enrollment_no,
//                             'anniversary_date'           => $record->anniversary_date,
//                             'cadre_master_pk'            => $record->cadre_master_pk,
//                             'current_sem'                => $record->current_sem,
//                             'spouse_name'                => $record->spouse_name,
//                             'spouse_dob'                 => $record->spouse_dob,
//                             'designation'                => $record->designation,
//                             'department'                 => $record->department,
//                             'passport_no'                => $record->passport_no,
//                             'rr_scs'                     => $record->rr_scs,
//                             'no_of_attempts'             => $record->no_of_attempts,
//                             'last_service_pk'            => $record->last_service_pk,
//                             'birth_place'                => $record->birth_place,
//                             'birth_city_village_name'    => $record->birth_city_village_name,
//                             'city_type'                  => $record->city_type,
//                             'pcity_type'                 => $record->pcity_type,
//                             'pass_in_char'               => $record->pass_in_char,
//                             'highest_stream_pk'          => $record->highest_stream_pk,
//                             'emergency_contact_person'   => $record->emergency_contact_person,
//                             'emergency_contact_person_mobile' => $record->emergency_contact_person_mobile,
//                             'passport_issue_date'        => $record->passport_issue_date,
//                             'passport_expire_date'       => $record->passport_expire_date,
//                             'fc_exemption_master_pk'     => $record->fc_exemption_master_pk,
//                             'mother_Language'            => $record->mother_Language,
//                             'conform_student'            => $record->conform_student,
//                             'father_husband'             => $record->father_husband,
//                             'csestatus'                  => $record->csestatus,
//                             'aadhar_card'                => $record->aadhar_card,
//                             'pan_card'                   => $record->pan_card,
//                             'instagram_id'               => $record->instagram_id,
//                             'twitter_id'                 => $record->twitter_id,
//                             'guardian_contact'           => $record->guardian_contact,
//                             'guardian_email'             => $record->guardian_email,
//                             'birth_state'                => $record->birth_state,
//                             'birth_district'             => $record->birth_district,
//                             'medical_history'            => $record->medical_history,
//                             'guardian_firstname'         => $record->guardian_firstname,
//                             'guardian_middlename'        => $record->guardian_middlename,
//                             'guardian_lastname'          => $record->guardian_lastname,
//                             'highattitude_trek'          => $record->highattitude_trek,
//                             'mother_firstname'           => $record->mother_firstname,
//                             'mother_middlename'          => $record->mother_middlename,
//                             'mother_lastname'            => $record->mother_lastname,
//                             'mother_qualification'       => $record->mother_qualification,
//                             'mother_profession'          => $record->mother_profession,
//                             'mother_annualincome'        => $record->mother_annualincome,
//                             'father_qualification'       => $record->father_qualification,
//                             'nationality'                => $record->nationality,
//                             'pdistrict_id'               => $record->pdistrict_id,
//                             'mdistrict_id'               => $record->mdistrict_id,
//                             'highattremarks'             => $record->highattremarks,
//                             'isspouse'                   => $record->isspouse,
//                             'hindiname'                  => $record->hindiname,
//                             'id_card'                    => $record->id_card,
//                             'ph'                         => $record->ph,
//                             'cgno'                       => $record->cgno,
//                         ]);

//                         // Insert into user_credentials
//                         DB::table('user_credentials')->insert([
//                             'user_name'         => $record->user_id,
//                             'first_name'        => $record->first_name,
//                             'last_name'         => $record->last_name,
//                             'jbp_password'      => $record->password,
//                             'email_id'          => $record->email,
//                             'mobile_no'         => $record->contact_no,
//                             'alternate_mailid'  => null,
//                             'reg_date'          => $record->submit_date ?? now(),
//                             'jbp_enabled'       => 'student',
//                             'login_status'      => 1,
//                             'schemaid'          => 1,
//                             'user_id'           => $studentId,
//                             'last_login'        => now(),
//                             'security_question' => null,
//                             'security_answer'   => null,
//                             'entity_id'         => 5,
//                             'image_path'        => $record->photo_path,
//                             'user_category'     => null,
//                             'Active_inactive'   => 1,
//                             'remember_token'    => null,
//                             'updated_date'      => now(),
//                         ]);

//                         // Insert into student_master_course_map
//                         DB::table('student_master_course_map')->insert([
//                             'student_master_pk' => $studentId,
//                             'course_master_pk'  => $record->course_master_pk,
//                             'active_inactive'   => 1,
//                             'created_date'      => now(),
//                             'modified_date'     => now(),
//                         ]);

//                         $updateIds[] = $record->pk;
//                     }

//                     // Mark migrated in fc_registration_master
//                     if (!empty($updateIds)) {
//                         DB::table('fc_registration_master')
//                             ->whereIn('pk', $updateIds)
//                             ->update(['is_registered' => 1]);
//                     }
//                 });

//             DB::commit();
//             return back()->with('success', 'FC registration records migrated successfully.');
//         } catch (\Exception $e) {
//             DB::rollBack();
//             return back()->with('error', 'Migration failed: ' . $e->getMessage());
//         }
//     }
// }        'idcard_created_by'          => $record->idcard_created_by,
//                             'idcard_date'                => $record->idcard_date,
//                             'cgname'                     => $record->cgname,
//                             'ph'                         => $record->ph,
//                             'cgno'                       => $record->cgno,
//                         ]);

//                         // Insert into user_credentials
//                         DB::table('user_credentials')->insert([
//                             'user_name'         => $record->user_id,
//                             'first_name'        => $record->first_name,
//                             'last_name'         => $record->last_name,
//                             'jbp_password'      => $record->password,
//                             'email_id'          => $record->email,
//                             'mobile_no'         => $record->contact_no,
//                             'alternate_mailid'  => null,
//                             'reg_date'          => $record->submit_date ?? now(),
//                             'jbp_enabled'       => 'student',
//                             'login_status'      => 1,
//                             'schemaid'          => 1,
//                             'user_id'           => $studentId,
//                             'last_login'        => now(),
//                             'security_question' => null,
//                             'security_answer'   => null,
//                             'entity_id'         => 5,
//                             'image_path'        => $record->photo_path,
//                             'user_category'     => null,
//                             'Active_inactive'   => 1,
//                             'remember_token'    => null,
//                             'updated_date'      => now(),
//                         ]);

//                         // Insert into student_master_course_map
//                         DB::table('student_master_course_map')->insert([
//                             'student_master_pk' => $studentId,
//                             'course_master_pk'  => $record->course_master_pk,
//                             'active_inactive'   => 1,
//                             'created_date'      => now(),
//                             'modified_date'     => now(),
//                         ]);

//                         $updateIds[] = $record->pk;
//                     }

//                     // Mark migrated in fc_registration_master
//                     if (!empty($updateIds)) {
//                         DB::table('fc_registration_master')
//                             ->whereIn('pk', $updateIds)
//                             ->update(['is_registered' => 1]);
//                     }
//                 });

//             DB::commit();
//             return back()->with('success', 'FC registration records migrated successfully.');
//         } catch (\Exception $e) {
//             DB::rollBack();
//             return back()->with('error', 'Migration failed: ' . $e->getMessage());
//         }
//     }
// }


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

    /**
     * Migrate FC registration records to student_master and related tables.

     */
    // public function migrate()
    // {
    //     DB::beginTransaction();

    //     try {
    //         $batchSize = 500;
    //         $now = Carbon::now();

    //         DB::table('fc_registration_master')
    //             // ->where('final_submit', 3)
    //             // ->where('is_registered', 0)
    //             ->orderBy('pk')
    //             ->chunk($batchSize, function ($chunkedRecords) use ($now) {

    //                 $userIds = $chunkedRecords->pluck('user_id')->toArray();
    //                 $alreadyRegistered = DB::table('student_master')
    //                     ->whereIn('user_id', $userIds)
    //                     ->pluck('user_id')
    //                     ->toArray();

    //                 $studentsToInsert = [];
    //                 $credentialsToInsert = [];
    //                 $courseMapToInsert = [];
    //                 $updateIds = [];
    //                 foreach ($chunkedRecords as $record) {
    //                     if (in_array($record->user_id, $alreadyRegistered)) {
    //                         continue;
    //                     }

    //                     $studentData = [
    //                         'email'                      => $record->email,
    //                         'contact_no'                 => $record->contact_no,
    //                         'user_id'                    => $record->user_id ?? 0,
    //                         'display_name'               => $record->display_name,
    //                         'password'                   => Hash::make($record->password),
    //                         'schema_id'                  => $record->schema_id,
    //                         'final_submit'               => $record->final_submit ?? 0,
    //                         'submit_date'                => $record->submit_date,
    //                         'created_date'               => $record->created_date ?? $now,
    //                         'first_name'                 => $record->first_name,
    //                         'middle_name'                => $record->middle_name,
    //                         'last_name'                  => $record->last_name,
    //                         'admission_status'           => $record->admission_status,
    //                         'rank'                       => $record->rank,
    //                         'exam_year'                  => $record->exam_year,
    //                         'service_master_pk' => $record->service_master_pk ?? 0,
    //                         'web_auth'                   => $record->web_auth,
    //                         'dob'                        => $record->dob,
    //                         'status'                     => $record->status,
    //                         'course_master_pk'           => $record->course_master_pk,
    //                         'finance_bookEntityCode'     => $record->finance_bookEntityCode,
    //                         'refund_status'              => $record->refund_status,
    //                         'enrollment'                 => $record->enrollment,
    //                         'admission_category_pk'      => $record->admission_category_pk,
    //                         'gender'                     => $record->gender,
    //                         'photo_path'                 => $record->photo_path,
    //                         'address'                    => $record->address,
    //                         'country_master_pk'          => $record->country_master_pk,
    //                         'state_master_pk'            => $record->state_master_pk,
    //                         'city'                       => $record->city,
    //                         'pin_code'                   => $record->pin_code,
    //                         'merital_status'             => $record->merital_status,
    //                         'religion_master_pk'         => $record->religion_master_pk,
    //                         'background'                 => $record->background,
    //                         'father_fname'               => $record->father_fname,
    //                         'father_mname'               => $record->father_mname,
    //                         'father_lname'               => $record->father_lname,
    //                         'father_profession'          => $record->father_profession,
    //                         'mother_name'                => $record->mother_name,
    //                         'family_annual_income'       => $record->family_annual_income,
    //                         'university_medium'          => $record->university_medium,
    //                         'pre_university_medium'      => $record->pre_university_medium,
    //                         'upsc_exam_medium'           => $record->upsc_exam_medium,
    //                         'upsc_viva_medium'           => $record->upsc_viva_medium,
    //                         'academic_medium'            => $record->academic_medium,
    //                         'height'                     => $record->height,
    //                         'weight'                     => $record->weight,
    //                         'blood_group'                => $record->blood_group,
    //                         'dietary'                    => $record->dietary,
    //                         'signature_path'             => $record->signature_path,
    //                         'postal_address'             => $record->postal_address,
    //                         'postal_country_pk'          => $record->postal_country_pk,
    //                         'postal_state_pk'            => $record->postal_state_pk,
    //                         'postal_city'                => $record->postal_city,
    //                         'postal_pin_code'            => $record->postal_pin_code,
    //                         'fax'                        => $record->fax,
    //                         'domicile_state_pk'          => $record->domicile_state_pk,
    //                         'state_district_mapping_pk'  => $record->state_district_mapping_pk,
    //                         'town_village'               => $record->town_village,
    //                         'pcontact_no'                => $record->pcontact_no,
    //                         'pemail_id'                  => $record->pemail_id,
    //                         'pfax'                       => $record->pfax,
    //                         'generated_OT_code'          => $record->generated_OT_code,
    //                         'enrollment_no'              => $record->enrollment_no,
    //                         'anniversary_date'           => $record->anniversary_date,
    //                         'cadre_master_pk'            => $record->cadre_master_pk,
    //                         'current_sem'                => $record->current_sem,
    //                         'spouse_name'                => $record->spouse_name,
    //                         'spouse_dob'                 => $record->spouse_dob,
    //                         'designation'                => $record->designation,
    //                         'department'                 => $record->department,
    //                         'passport_no'                => $record->passport_no,
    //                         'rr_scs'                     => $record->rr_scs,
    //                         // 'no_of_attempts'             => $record->no_of_attempts,
    //                         'last_service_pk'            => $record->last_service_pk,
    //                         'birth_place'                => $record->birth_place,
    //                         'birth_city_village_name'    => $record->birth_city_village_name,
    //                         'city_type'                  => $record->city_type,
    //                         'pcity_type'                 => $record->pcity_type,
    //                         'pass_in_char'               => $record->pass_in_char,
    //                         'highest_stream_pk'          => $record->highest_stream_pk,
    //                         'emergency_contact_person'   => $record->emergency_contact_person,
    //                         'emergency_contact_person_mobile' => $record->emergency_contact_person_mobile,
    //                         'passport_issue_date'        => $record->passport_issue_date,
    //                         'passport_expire_date'       => $record->passport_expire_date,
    //                         'fc_exemption_master_pk'     => $record->fc_exemption_master_pk,
    //                         // 'mother_Language'            => $record->mother_Language,
    //                         'conform_student'            => $record->conform_student,
    //                         'father_husband'             => $record->father_husband,
    //                         'csestatus'                  => $record->csestatus,
    //                         'aadhar_card'                => $record->aadhar_card,
    //                         'pan_card'                   => $record->pan_card,
    //                         'instagram_id'               => $record->instagram_id,
    //                         'twitter_id'                 => $record->twitter_id,
    //                         'guardian_contact'           => $record->guardian_contact,
    //                         'guardian_email'             => $record->guardian_email,
    //                         'birth_state'                => $record->birth_state,
    //                         // 'birth_district'             => $record->birth_district,
    //                         'medical_history'            => $record->medical_history,
    //                         'guardian_firstname'         => $record->guardian_firstname,
    //                         'guardian_middlename'        => $record->guardian_middlename,
    //                         'guardian_lastname'          => $record->guardian_lastname,
    //                         'highattitude_trek'          => $record->highattitude_trek,
    //                         'mother_firstname'           => $record->mother_firstname,
    //                         'mother_middlename'          => $record->mother_middlename,
    //                         'mother_lastname'            => $record->mother_lastname,
    //                         'mother_qualification'       => $record->mother_qualification,
    //                         'mother_profession'          => $record->mother_profession,
    //                         // 'mother_annualincome'        => $record->mother_annualincome,
    //                         'father_qualification'       => $record->father_qualification,
    //                         'nationality'                => $record->nationality,
    //                         'pdistrict_id'               => $record->pdistrict_id,
    //                         'mdistrict_id'               => $record->mdistrict_id,
    //                         'highattremarks'             => $record->highattremarks,
    //                         'isspouse'                   => $record->isspouse,
    //                         'hindiname'                  => $record->hindiname,
    //                         'id_card'                    => $record->id_card,
    //                         'idcard_created_by'          => $record->idcard_created_by,
    //                         'idcard_date'                => $record->idcard_date,
    //                         'cgname'                     => $record->cgname,
    //                         'ph'                         => $record->ph,
    //                         'cgno'                       => $record->cgno,
    //                     ];

    //                     $studentsToInsert[] = $studentData;

    //                     $credentialsToInsert[] = [
    //                         'user_name'         => $record->user_id,
    //                         'first_name'        => $record->first_name,
    //                         'last_name'         => $record->last_name,
    //                         'jbp_password'      => $record->password,
    //                         'email_id'          => $record->email,
    //                         'mobile_no'         => $record->contact_no,
    //                         'alternate_mailid'  => null,
    //                         'reg_date'          => $record->submit_date ?? $now,
    //                         'jbp_enabled'       => 1,
    //                         'login_status'      => 1,
    //                         'schemaid'          => 1,
    //                         'user_id'           => null, // Will fill after inserting students
    //                         'last_login'        => $now,
    //                         'security_question' => null,
    //                         'security_answer'   => null,
    //                         'entity_id'         => 5,
    //                         'image_path'        => $record->photo_path,
    //                         'user_category'     => null,
    //                         'Active_inactive'   => 1,
    //                         'remember_token'    => null,
    //                         'updated_date'      => $now,
    //                     ];

    //                     $courseMapToInsert[] = [
    //                         'student_master_pk' => null, // Will fill after inserting students
    //                         'course_master_pk'  => $record->course_master_pk,
    //                         'active_inactive'   => 1,
    //                         'created_date'      => $now,
    //                         'modified_date'     => $now,
    //                     ];

    //                     $updateIds[] = $record->pk;
    //                 }

    //                 // Bulk insert for performance
    //                 foreach ($studentsToInsert as $index => $studentData) {
    //                     $studentId = DB::table('student_master')->insertGetId($studentData);
    //                     $credentialsToInsert[$index]['user_id'] = $studentId;
    //                     $courseMapToInsert[$index]['student_master_pk'] = $studentId;
    //                 }
    //                 if (!empty($credentialsToInsert)) {
    //                     // DB::table('user_credentials')->insert($credentialsToInsert);
    //                     DB::table('user_credentials')->insertOrIgnore($credentialsToInsert);

    //                     // DB::table('student_master_course_map')->insert($courseMapToInsert);
    //                     DB::table('student_master_course__map')->insertOrIgnore($courseMapToInsert);
    //                 }

    //                 if (!empty($updateIds)) {
    //                     DB::table('fc_registration_master')
    //                         ->whereIn('pk', $updateIds)
    //                         ->update(['is_registered' => 1]);
    //                 }
    //             });

    //         DB::commit();
    //         return back()->with('success', 'FC registration records migrated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Migration failed: ' . $e->getMessage());
    //     }
    // }



    //finalll 
    // public function migrate()
    // {
    //     DB::beginTransaction();

    //     try {
    //         $batchSize = 500;
    //         $now = Carbon::now();

    //         DB::table('fc_registration_master')
    //             ->orderBy('pk')
    //             ->chunk($batchSize, function ($chunkedRecords) use ($now) {
    //                 $userIds = $chunkedRecords->pluck('user_id')->toArray();

    //                 // Get already registered students to skip
    //                 $alreadyRegistered = DB::table('student_master')
    //                     ->whereIn('user_id', $userIds)
    //                     ->pluck('user_id')
    //                     ->toArray();

    //                 $studentsToInsert = [];
    //                 $credentialsToInsert = [];
    //                 $courseMapToInsert = [];
    //                 $updateIds = [];

    //                 foreach ($chunkedRecords as $record) {
    //                     if (in_array($record->user_id, $alreadyRegistered)) {
    //                         continue;
    //                     }

    //                     // Prepare student data
    //                     $studentsToInsert[] = [
    //                         'email'                      => $record->email,
    //                         'contact_no'                 => $record->contact_no,
    //                         'user_id'                    => $record->user_id ?? 0,
    //                         'display_name'               => $record->display_name,
    //                         'password'                   => Hash::make($record->password),
    //                         'schema_id'                  => $record->schema_id,
    //                         'final_submit'               => $record->final_submit ?? 0,
    //                         'submit_date'                => $record->submit_date,
    //                         'created_date'               => $record->created_date ?? $now,
    //                         'first_name'                 => $record->first_name,
    //                         'middle_name'                => $record->middle_name,
    //                         'last_name'                  => $record->last_name,
    //                         'admission_status'           => $record->admission_status,
    //                         'rank'                       => $record->rank,
    //                         'exam_year'                  => $record->exam_year,
    //                         'service_master_pk' => $record->service_master_pk ?? 0,
    //                         'web_auth'                   => $record->web_auth,
    //                         'dob'                        => $record->dob,
    //                         'status'                     => $record->status,
    //                         'course_master_pk'           => $record->course_master_pk,
    //                         'finance_bookEntityCode'     => $record->finance_bookEntityCode,
    //                         'refund_status'              => $record->refund_status,
    //                         'enrollment'                 => $record->enrollment,
    //                         'admission_category_pk'      => $record->admission_category_pk,
    //                         'gender'                     => $record->gender,
    //                         'photo_path'                 => $record->photo_path,
    //                         'address'                    => $record->address,
    //                         'country_master_pk'          => $record->country_master_pk,
    //                         'state_master_pk'            => $record->state_master_pk,
    //                         'city'                       => $record->city,
    //                         'pin_code'                   => $record->pin_code,
    //                         'merital_status'             => $record->merital_status,
    //                         'religion_master_pk'         => $record->religion_master_pk,
    //                         'background'                 => $record->background,
    //                         'father_fname'               => $record->father_fname,
    //                         'father_mname'               => $record->father_mname,
    //                         'father_lname'               => $record->father_lname,
    //                         'father_profession'          => $record->father_profession,
    //                         'mother_name'                => $record->mother_name,
    //                         'family_annual_income'       => $record->family_annual_income,
    //                         'university_medium'          => $record->university_medium,
    //                         'pre_university_medium'      => $record->pre_university_medium,
    //                         'upsc_exam_medium'           => $record->upsc_exam_medium,
    //                         'upsc_viva_medium'           => $record->upsc_viva_medium,
    //                         'academic_medium'            => $record->academic_medium,
    //                         'height'                     => $record->height,
    //                         'weight'                     => $record->weight,
    //                         'blood_group'                => $record->blood_group,
    //                         'dietary'                    => $record->dietary,
    //                         'signature_path'             => $record->signature_path,
    //                         'postal_address'             => $record->postal_address,
    //                         'postal_country_pk'          => $record->postal_country_pk,
    //                         'postal_state_pk'            => $record->postal_state_pk,
    //                         'postal_city'                => $record->postal_city,
    //                         'postal_pin_code'            => $record->postal_pin_code,
    //                         'fax'                        => $record->fax,
    //                         'domicile_state_pk'          => $record->domicile_state_pk,
    //                         'state_district_mapping_pk'  => $record->state_district_mapping_pk,
    //                         'town_village'               => $record->town_village,
    //                         'pcontact_no'                => $record->pcontact_no,
    //                         'pemail_id'                  => $record->pemail_id,
    //                         'pfax'                       => $record->pfax,
    //                         'generated_OT_code'          => $record->generated_OT_code,
    //                         'enrollment_no'              => $record->enrollment_no,
    //                         'anniversary_date'           => $record->anniversary_date,
    //                         'cadre_master_pk'            => $record->cadre_master_pk,
    //                         'current_sem'                => $record->current_sem,
    //                         'spouse_name'                => $record->spouse_name,
    //                         'spouse_dob'                 => $record->spouse_dob,
    //                         'designation'                => $record->designation,
    //                         'department'                 => $record->department,
    //                         'passport_no'                => $record->passport_no,
    //                         'rr_scs'                     => $record->rr_scs,
    //                         // 'no_of_attempts'             => $record->no_of_attempts,
    //                         'last_service_pk'            => $record->last_service_pk,
    //                         'birth_place'                => $record->birth_place,
    //                         'birth_city_village_name'    => $record->birth_city_village_name,
    //                         'city_type'                  => $record->city_type,
    //                         'pcity_type'                 => $record->pcity_type,
    //                         'pass_in_char'               => $record->pass_in_char,
    //                         'highest_stream_pk'          => $record->highest_stream_pk,
    //                         'emergency_contact_person'   => $record->emergency_contact_person,
    //                         'emergency_contact_person_mobile' => $record->emergency_contact_person_mobile,
    //                         'passport_issue_date'        => $record->passport_issue_date,
    //                         'passport_expire_date'       => $record->passport_expire_date,
    //                         'fc_exemption_master_pk'     => $record->fc_exemption_master_pk,
    //                         // 'mother_Language'            => $record->mother_Language,
    //                         'conform_student'            => $record->conform_student,
    //                         'father_husband'             => $record->father_husband,
    //                         'csestatus'                  => $record->csestatus,
    //                         'aadhar_card'                => $record->aadhar_card,
    //                         'pan_card'                   => $record->pan_card,
    //                         'instagram_id'               => $record->instagram_id,
    //                         'twitter_id'                 => $record->twitter_id,
    //                         'guardian_contact'           => $record->guardian_contact,
    //                         'guardian_email'             => $record->guardian_email,
    //                         'birth_state'                => $record->birth_state,
    //                         // 'birth_district'             => $record->birth_district,
    //                         'medical_history'            => $record->medical_history,
    //                         'guardian_firstname'         => $record->guardian_firstname,
    //                         'guardian_middlename'        => $record->guardian_middlename,
    //                         'guardian_lastname'          => $record->guardian_lastname,
    //                         'highattitude_trek'          => $record->highattitude_trek,
    //                         'mother_firstname'           => $record->mother_firstname,
    //                         'mother_middlename'          => $record->mother_middlename,
    //                         'mother_lastname'            => $record->mother_lastname,
    //                         'mother_qualification'       => $record->mother_qualification,
    //                         'mother_profession'          => $record->mother_profession,
    //                         // 'mother_annualincome'        => $record->mother_annualincome,
    //                         'father_qualification'       => $record->father_qualification,
    //                         'nationality'                => $record->nationality,
    //                         'pdistrict_id'               => $record->pdistrict_id,
    //                         'mdistrict_id'               => $record->mdistrict_id,
    //                         'highattremarks'             => $record->highattremarks,
    //                         'isspouse'                   => $record->isspouse,
    //                         'hindiname'                  => $record->hindiname,
    //                         'id_card'                    => $record->id_card,
    //                         'idcard_created_by'          => $record->idcard_created_by,
    //                         'idcard_date'                => $record->idcard_date,
    //                         'cgname'                     => $record->cgname,
    //                         'ph'                         => $record->ph,
    //                         'cgno'                       => $record->cgno,
    //                         // ... include all other student fields from your original code
    //                     ];

    //                     // Prepare credentials data
    //                     $credentialsToInsert[] = [
    //                         'user_name'         => $record->user_id,
    //                         'first_name'        => $record->first_name,
    //                         'last_name'         => $record->last_name,
    //                         'jbp_password'      => $record->password,
    //                         'email_id'          => $record->email,
    //                         'mobile_no'         => $record->contact_no,
    //                         'alternate_mailid'  => null,
    //                         'reg_date'          => $record->submit_date ?? $now,
    //                         'jbp_enabled'       => 1,
    //                         'login_status'      => 1,
    //                         'schemaid'          => 1,
    //                         'user_id'           => null, // Will fill after inserting students
    //                         'last_login'        => $now,
    //                         'security_question' => null,
    //                         'security_answer'   => null,
    //                         'entity_id'         => 5,
    //                         'image_path'        => $record->photo_path,
    //                         'user_category'     => null,
    //                         'Active_inactive'   => 1,
    //                         'remember_token'    => null,
    //                         'updated_date'      => $now,
    //                         // ... include all other credential fields
    //                     ];

    //                     // Prepare course mapping
    //                     $courseMapToInsert[] = [
    //                         'student_master_pk' => $record->user_id, // Will fill after inserting students --> student master pk
    //                         'course_master_pk'  => $record->course_master_pk,
    //                         'active_inactive'   => 1,
    //                         'created_date'      => $now,
    //                         'modified_date'     => $now,
    //                     ];

    //                     $updateIds[] = $record->pk;
    //                 }

    //                 // Process students
    //                 $studentInsertResults = [];
    //                 foreach ($studentsToInsert as $index => $studentData) {
    //                     try {
    //                         $studentId = DB::table('student_master')->insertGetId($studentData);
    //                         $studentInsertResults[$index] = $studentId;
    //                     } catch (\Exception $e) {
    //                         // Remove corresponding credentials and course maps if student failed
    //                         unset($credentialsToInsert[$index]);
    //                         unset($courseMapToInsert[$index]);
    //                         continue;
    //                     }
    //                 }

    //                 // Process credentials for successfully inserted students
    //                 foreach ($credentialsToInsert as $index => $credentialData) {
    //                     if (isset($studentInsertResults[$index])) {
    //                         try {
    //                             $credentialData['user_id'] = $studentInsertResults[$index];
    //                             DB::table('user_credentials')->insert($credentialData);
    //                         } catch (\Exception $e) {
    //                             // Skip failed credentials but continue processing
    //                             continue;
    //                         }
    //                     }
    //                 }

    //                 // Process course maps for successfully inserted students
    //                 foreach ($courseMapToInsert as $index => $courseMapData) {
    //                     if (isset($studentInsertResults[$index])) {
    //                         try {
    //                             $courseMapData['student_master_pk'] = $studentInsertResults[$index];
    //                             DB::table('student_master_course__map')->insert($courseMapData);
    //                         } catch (\Exception $e) {
    //                             // Skip failed course maps but continue processing
    //                             continue;
    //                         }
    //                     }
    //                 }

    //                 // Mark source records as registered for successfully processed students
    //                 if (!empty($studentInsertResults) && !empty($updateIds)) {
    //                     DB::table('fc_registration_master')
    //                         ->whereIn('pk', $updateIds)
    //                         ->update(['is_registered' => 1]);
    //                 }
    //             });

    //         DB::commit();
    //         return back()->with('success', 'Migration completed successfully (some records may have been skipped)');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Critical migration failure: ' . $e->getMessage());
    //     }
    // }

    // public function migrate()
    // {
    //     DB::beginTransaction();

    //     try {
    //         $batchSize = 500;
    //         $now = Carbon::now();

    //         DB::table('fc_registration_master')
    //             ->orderBy('pk')
    //             ->chunk($batchSize, function ($chunkedRecords) use ($now) {
    //                 $userIds = $chunkedRecords->pluck('user_id')->toArray();

    //                 // Get already registered students to skip
    //                 // $alreadyRegistered = DB::table('student_master')
    //                 //     ->whereIn('user_id', $userIds)
    //                 //     ->pluck('user_id')
    //                 //     ->toArray();

    //                 // Get already registered students to skip (check both tables)
    //                 $alreadyRegisteredStudents = DB::table('student_master')
    //                     ->whereIn('user_id', $userIds)
    //                     ->pluck('user_id')
    //                     ->toArray();

    //                 $alreadyRegisteredUsers = DB::table('user_credentials')
    //                     ->whereIn('user_name', $userIds)
    //                     ->pluck('user_name')
    //                     ->toArray();

    //                 $alreadyRegistered = array_unique(array_merge($alreadyRegisteredStudents, $alreadyRegisteredUsers));

    //                 $studentsToInsert = [];
    //                 $credentialsToInsert = [];
    //                 $courseMapToInsert = [];
    //                 $updateIds = [];

    //                 foreach ($chunkedRecords as $record) {
    //                     if (empty($record->user_id) || in_array($record->user_id, $alreadyRegistered)) {
    //                         continue;
    //                     }
    //                     // Prepare student data
    //                     $studentsToInsert[] = [
    //                         'email'                      => $record->email,
    //                         'contact_no'                 => $record->contact_no,
    //                         'user_id'                    => $record->user_id ?? 0,
    //                         'display_name'               => $record->display_name,
    //                         'password'                   => $record->password,
    //                         'schema_id'                  => $record->schema_id,
    //                         'final_submit'               => $record->final_submit ?? 0,
    //                         'submit_date'                => $record->submit_date,
    //                         'created_date'               => $record->created_date ?? $now,
    //                         'first_name'                 => $record->first_name,
    //                         'middle_name'                => $record->middle_name,
    //                         'last_name'                  => $record->last_name,
    //                         'admission_status'           => $record->admission_status,
    //                         'rank'                       => $record->rank,
    //                         'exam_year'                  => $record->exam_year,
    //                         'service_master_pk'         => $record->service_master_pk ?? 0,
    //                         'web_auth'                   => $record->web_auth,
    //                         'dob'                        => $record->dob,
    //                         'status'                     => $record->status,
    //                         'course_master_pk'           => $record->course_master_pk,
    //                         'finance_bookEntityCode'     => $record->finance_bookEntityCode,
    //                         'refund_status'              => $record->refund_status,
    //                         'enrollment'                 => $record->enrollment,
    //                         'admission_category_pk'      => $record->admission_category_pk,
    //                         'gender'                     => $record->gender,
    //                         'photo_path'                 => $record->photo_path,
    //                         'address'                    => $record->address,
    //                         'country_master_pk'          => $record->country_master_pk,
    //                         'state_master_pk'            => $record->state_master_pk,
    //                         'city'                       => $record->city,
    //                         'pin_code'                   => $record->pin_code,
    //                         'merital_status'             => $record->merital_status,
    //                         'religion_master_pk'         => $record->religion_master_pk,
    //                         'background'                 => $record->background,
    //                         'father_fname'               => $record->father_fname,
    //                         'father_mname'               => $record->father_mname,
    //                         'father_lname'               => $record->father_lname,
    //                         'father_profession'          => $record->father_profession,
    //                         'mother_name'                => $record->mother_name,
    //                         'family_annual_income'       => $record->family_annual_income,
    //                         'university_medium'          => $record->university_medium,
    //                         'pre_university_medium'      => $record->pre_university_medium,
    //                         'upsc_exam_medium'           => $record->upsc_exam_medium,
    //                         'upsc_viva_medium'           => $record->upsc_viva_medium,
    //                         'academic_medium'            => $record->academic_medium,
    //                         'height'                     => $record->height,
    //                         'weight'                     => $record->weight,
    //                         'blood_group'                => $record->blood_group,
    //                         'dietary'                    => $record->dietary,
    //                         'signature_path'             => $record->signature_path,
    //                         'postal_address'             => $record->postal_address,
    //                         'postal_country_pk'          => $record->postal_country_pk,
    //                         'postal_state_pk'            => $record->postal_state_pk,
    //                         'postal_city'                => $record->postal_city,
    //                         'postal_pin_code'            => $record->postal_pin_code,
    //                         'fax'                        => $record->fax,
    //                         'domicile_state_pk'          => $record->domicile_state_pk,
    //                         'state_district_mapping_pk'  => $record->state_district_mapping_pk,
    //                         'town_village'               => $record->town_village,
    //                         'pcontact_no'                => $record->pcontact_no,
    //                         'pemail_id'                  => $record->pemail_id,
    //                         'pfax'                       => $record->pfax,
    //                         'generated_OT_code'          => $record->generated_OT_code,
    //                         'enrollment_no'              => $record->enrollment_no,
    //                         'anniversary_date'           => $record->anniversary_date,
    //                         'cadre_master_pk'            => $record->cadre_master_pk,
    //                         'current_sem'                => $record->current_sem,
    //                         'spouse_name'                => $record->spouse_name,
    //                         'spouse_dob'                 => $record->spouse_dob,
    //                         'designation'                => $record->designation,
    //                         'department'                 => $record->department,
    //                         'passport_no'                => $record->passport_no,
    //                         'rr_scs'                     => $record->rr_scs,
    //                         'last_service_pk'            => $record->last_service_pk,
    //                         'birth_place'                => $record->birth_place,
    //                         'birth_city_village_name'    => $record->birth_city_village_name,
    //                         'city_type'                  => $record->city_type,
    //                         'pcity_type'                 => $record->pcity_type,
    //                         'pass_in_char'               => $record->pass_in_char,
    //                         'highest_stream_pk'          => $record->highest_stream_pk,
    //                         'emergency_contact_person'   => $record->emergency_contact_person,
    //                         'emergency_contact_person_mobile' => $record->emergency_contact_person_mobile,
    //                         'passport_issue_date'        => $record->passport_issue_date,
    //                         'passport_expire_date'       => $record->passport_expire_date,
    //                         'fc_exemption_master_pk'     => $record->fc_exemption_master_pk,
    //                         'conform_student'            => $record->conform_student,
    //                         'father_husband'             => $record->father_husband,
    //                         'csestatus'                  => $record->csestatus,
    //                         'aadhar_card'                => $record->aadhar_card,
    //                         'pan_card'                   => $record->pan_card,
    //                         'instagram_id'               => $record->instagram_id,
    //                         'twitter_id'                 => $record->twitter_id,
    //                         'guardian_contact'           => $record->guardian_contact,
    //                         'guardian_email'             => $record->guardian_email,
    //                         'birth_state'                => $record->birth_state,
    //                         'medical_history'            => $record->medical_history,
    //                         'guardian_firstname'         => $record->guardian_firstname,
    //                         'guardian_middlename'        => $record->guardian_middlename,
    //                         'guardian_lastname'          => $record->guardian_lastname,
    //                         'highattitude_trek'          => $record->highattitude_trek,
    //                         'mother_firstname'           => $record->mother_firstname,
    //                         'mother_middlename'          => $record->mother_middlename,
    //                         'mother_lastname'            => $record->mother_lastname,
    //                         'mother_qualification'       => $record->mother_qualification,
    //                         'mother_profession'          => $record->mother_profession,
    //                         'father_qualification'       => $record->father_qualification,
    //                         'nationality'                => $record->nationality,
    //                         'pdistrict_id'               => $record->pdistrict_id,
    //                         'mdistrict_id'               => $record->mdistrict_id,
    //                         'highattremarks'             => $record->highattremarks,
    //                         'isspouse'                   => $record->isspouse,
    //                         'hindiname'                  => $record->hindiname,
    //                         'id_card'                    => $record->id_card,
    //                         'idcard_created_by'          => $record->idcard_created_by,
    //                         'idcard_date'                => $record->idcard_date,
    //                         'cgname'                     => $record->cgname,
    //                         'ph'                         => $record->ph,
    //                         'cgno'                       => $record->cgno,
    //                     ];

    //                     // Prepare credentials data
    //                     $credentialsToInsert[] = [
    //                         'user_name'         => $record->user_id,
    //                         'first_name'        => $record->first_name,
    //                         'last_name'         => $record->last_name,
    //                         'jbp_password'      => $record->password,
    //                         'email_id'          => $record->email,
    //                         'mobile_no'         => $record->contact_no,
    //                         'alternate_mailid'  => $record->pemail_id,
    //                         'reg_date'          => $record->submit_date ?? $now,
    //                         'jbp_enabled'       => 1,
    //                         'login_status'      => 1,
    //                         'schemaid'          => 1,
    //                         'user_id'           => null, // Will be updated after student insertion
    //                         'last_login'        => $now,
    //                         'security_question' => null,
    //                         'security_answer'   => null,
    //                         'entity_id'         => 5,
    //                         'image_path'        => $record->photo_path,
    //                         'user_category'     => null,
    //                         'Active_inactive'   => 1,
    //                         'remember_token'    => null,
    //                         'updated_date'      => $now,
    //                     ];

    //                     // Prepare course mapping (without student_master_pk for now)
    //                     $courseMapToInsert[] = [
    //                         'course_master_pk'  => $record->course_master_pk,
    //                         'active_inactive'   => 1,
    //                         'created_date'      => $now,
    //                         'modified_date'     => $now,
    //                     ];

    //                     $updateIds[] = $record->pk;
    //                 }

    //                 // Process students and get their IDs
    //                 $studentIds = [];
    //                 foreach ($studentsToInsert as $index => $studentData) {
    //                     try {
    //                         $studentId = DB::table('student_master')->insertGetId($studentData);
    //                         $studentIds[$index] = $studentId;

    //                         // Update credentials with the new student ID
    //                         if (isset($credentialsToInsert[$index])) {
    //                             $credentialsToInsert[$index]['user_id'] = $studentId;
    //                         }

    //                         // Update course map with the new student ID
    //                         if (isset($courseMapToInsert[$index])) {
    //                             $courseMapToInsert[$index]['student_master_pk'] = $studentId;
    //                         }
    //                     } catch (\Exception $e) {
    //                         // Remove corresponding entries if student insertion failed
    //                         unset($credentialsToInsert[$index]);
    //                         unset($courseMapToInsert[$index]);
    //                         continue;
    //                     }
    //                 }

    //                 // Batch insert credentials
    //                 if (!empty($credentialsToInsert)) {
    //                     DB::table('user_credentials')->insert($credentialsToInsert);
    //                 }

    //                 // Batch insert course maps
    //                 if (!empty($courseMapToInsert)) {
    //                     DB::table('student_master_course__map')->insert($courseMapToInsert);
    //                 }

    //                 // Mark source records as registered for successfully processed students
    //                 // if (!empty($studentIds) && !empty($updateIds)) {
    //                 //     DB::table('fc_registration_master')
    //                 //         ->whereIn('pk', $updateIds)
    //                 //         ->update(['is_registered' => 1]);
    //                 // }
    //             });

    //         DB::commit();
    //         return back()->with('success', 'Migration completed successfully (some records may have been skipped)');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Critical migration failure: ' . $e->getMessage());
    //     }
    // }





    // public function migrate()
    // {
    //     DB::beginTransaction();

    //     try {
    //         $batchSize = 500;
    //         $now = Carbon::now();

    //         DB::table('fc_registration_master')
    //             // ->where('fc_exemption_master_pk', 0) // Only process registered records
    //             // ->where('admission_status', '=', 1) // Ensure admission_status is not null
    //             ->orderBy('pk')
    //             ->chunk($batchSize, function ($chunkedRecords) use ($now) {
    //                 $studentsToInsert = [];
    //                 $credentialsToInsert = [];
    //                 $courseMapToInsert = [];
    //                 $updateIds = [];

    //                 foreach ($chunkedRecords as $record) {
    //                     if (empty($record->user_id)) {
    //                         continue; // Skip if user_id is empty
    //                     }

    //                     // 1. Handle student_master table
    //                     $studentId = DB::table('student_master')
    //                         ->where('user_id', $record->user_id)
    //                         ->value('pk');

    //                     if (!$studentId) {
    //                         // Insert new student if doesn't exist
    //                         $studentData = [
    //                             'email'                      => $record->email,
    //                             'contact_no'                 => $record->contact_no,
    //                             'user_id'                    => $record->user_id ?? 0,
    //                             'display_name'               => $record->display_name,
    //                             'password'                   => $record->password,
    //                             'schema_id'                  => $record->schema_id,
    //                             'final_submit'               => $record->final_submit ?? 0,
    //                             'submit_date'                => $record->submit_date,
    //                             'created_date'               => $record->created_date ?? $now,
    //                             'first_name'                 => $record->first_name,
    //                             'middle_name'                => $record->middle_name,
    //                             'last_name'                  => $record->last_name,
    //                             'admission_status'           => $record->admission_status,
    //                             'rank'                       => $record->rank,
    //                             'exam_year'                  => $record->exam_year,
    //                             'service_master_pk'         => $record->service_master_pk ?? 0,
    //                             'web_auth'                   => $record->web_auth,
    //                             'dob'                        => $record->dob,
    //                             'status'                     => $record->status,
    //                             'course_master_pk'           => $record->course_master_pk,
    //                             'finance_bookEntityCode'     => $record->finance_bookEntityCode,
    //                             'refund_status'              => $record->refund_status,
    //                             'enrollment'                 => $record->enrollment,
    //                             'admission_category_pk'      => $record->admission_category_pk,
    //                             'gender'                     => $record->gender,
    //                             'photo_path'                 => $record->photo_path,
    //                             'address'                    => $record->address,
    //                             'country_master_pk'          => $record->country_master_pk,
    //                             'state_master_pk'            => $record->state_master_pk,
    //                             'city'                       => $record->city,
    //                             'pin_code'                   => $record->pin_code,
    //                             'merital_status'             => $record->merital_status,
    //                             'religion_master_pk'         => $record->religion_master_pk,
    //                             'background'                 => $record->background,
    //                             'father_fname'               => $record->father_fname,
    //                             'father_mname'               => $record->father_mname,
    //                             'father_lname'               => $record->father_lname,
    //                             'father_profession'          => $record->father_profession,
    //                             'mother_name'                => $record->mother_name,
    //                             'family_annual_income'       => $record->family_annual_income,
    //                             'university_medium'          => $record->university_medium,
    //                             'pre_university_medium'      => $record->pre_university_medium,
    //                             'upsc_exam_medium'           => $record->upsc_exam_medium,
    //                             'upsc_viva_medium'           => $record->upsc_viva_medium,
    //                             'academic_medium'            => $record->academic_medium,
    //                             'height'                     => $record->height,
    //                             'weight'                     => $record->weight,
    //                             'blood_group'                => $record->blood_group,
    //                             'dietary'                    => $record->dietary,
    //                             'signature_path'             => $record->signature_path,
    //                             'postal_address'             => $record->postal_address,
    //                             'postal_country_pk'          => $record->postal_country_pk,
    //                             'postal_state_pk'            => $record->postal_state_pk,
    //                             'postal_city'                => $record->postal_city,
    //                             'postal_pin_code'            => $record->postal_pin_code,
    //                             'fax'                        => $record->fax,
    //                             'domicile_state_pk'          => $record->domicile_state_pk,
    //                             'state_district_mapping_pk'  => $record->state_district_mapping_pk,
    //                             'town_village'               => $record->town_village,
    //                             'pcontact_no'                => $record->pcontact_no,
    //                             'pemail_id'                  => $record->pemail_id,
    //                             'pfax'                       => $record->pfax,
    //                             'generated_OT_code'          => $record->generated_OT_code,
    //                             'enrollment_no'              => $record->enrollment_no,
    //                             'anniversary_date'           => $record->anniversary_date,
    //                             'cadre_master_pk'            => $record->cadre_master_pk,
    //                             'current_sem'                => $record->current_sem,
    //                             'spouse_name'                => $record->spouse_name,
    //                             'spouse_dob'                 => $record->spouse_dob,
    //                             'designation'                => $record->designation,
    //                             'department'                 => $record->department,
    //                             'passport_no'                => $record->passport_no,
    //                             'rr_scs'                     => $record->rr_scs,
    //                             'last_service_pk'            => $record->last_service_pk,
    //                             'birth_place'                => $record->birth_place,
    //                             'birth_city_village_name'    => $record->birth_city_village_name,
    //                             'city_type'                  => $record->city_type,
    //                             'pcity_type'                 => $record->pcity_type,
    //                             'pass_in_char'               => $record->pass_in_char,
    //                             'highest_stream_pk'          => $record->highest_stream_pk,
    //                             'emergency_contact_person'   => $record->emergency_contact_person,
    //                             'emergency_contact_person_mobile' => $record->emergency_contact_person_mobile,
    //                             'passport_issue_date'        => $record->passport_issue_date,
    //                             'passport_expire_date'       => $record->passport_expire_date,
    //                             'fc_exemption_master_pk'     => $record->fc_exemption_master_pk,
    //                             'conform_student'            => $record->conform_student,
    //                             'father_husband'             => $record->father_husband,
    //                             'csestatus'                  => $record->csestatus,
    //                             'aadhar_card'                => $record->aadhar_card,
    //                             'pan_card'                   => $record->pan_card,
    //                             'instagram_id'               => $record->instagram_id,
    //                             'twitter_id'                 => $record->twitter_id,
    //                             'guardian_contact'           => $record->guardian_contact,
    //                             'guardian_email'             => $record->guardian_email,
    //                             'birth_state'                => $record->birth_state,
    //                             'medical_history'            => $record->medical_history,
    //                             'guardian_firstname'         => $record->guardian_firstname,
    //                             'guardian_middlename'        => $record->guardian_middlename,
    //                             'guardian_lastname'          => $record->guardian_lastname,
    //                             'highattitude_trek'          => $record->highattitude_trek,
    //                             'mother_firstname'           => $record->mother_firstname,
    //                             'mother_middlename'          => $record->mother_middlename,
    //                             'mother_lastname'            => $record->mother_lastname,
    //                             'mother_qualification'       => $record->mother_qualification,
    //                             'mother_profession'          => $record->mother_profession,
    //                             'father_qualification'       => $record->father_qualification,
    //                             'nationality'                => $record->nationality,
    //                             'pdistrict_id'               => $record->pdistrict_id,
    //                             'mdistrict_id'               => $record->mdistrict_id,
    //                             'highattremarks'             => $record->highattremarks,
    //                             'isspouse'                   => $record->isspouse,
    //                             'hindiname'                  => $record->hindiname,
    //                             'id_card'                    => $record->id_card,
    //                             'idcard_created_by'          => $record->idcard_created_by,
    //                             'idcard_date'                => $record->idcard_date,
    //                             'cgname'                     => $record->cgname,
    //                             'ph'                         => $record->ph,
    //                             'cgno'                       => $record->cgno,
    //                             // ... include all other student fields
    //                         ];

    //                         $studentId = DB::table('student_master')->insertGetId($studentData);
    //                     }

    //                     // 2. Handle user_credentials table
    //                     $credentialExists = DB::table('user_credentials')
    //                         ->where('user_name', $record->user_id)
    //                         ->exists();

    //                     if (!$credentialExists) {
    //                         $credentialsToInsert[] = [
    //                             'user_name'         => $record->user_id,
    //                             'first_name'        => $record->first_name,
    //                             'last_name'         => $record->last_name,
    //                             'jbp_password'      => $record->password,
    //                             'email_id'          => $record->email,
    //                             'mobile_no'         => $record->contact_no,
    //                             'alternate_mailid'  => $record->pemail_id,
    //                             'reg_date'          => $record->submit_date ?? $now,
    //                             'jbp_enabled'       => 1,
    //                             'login_status'      => 1,
    //                             'schemaid'          => 1,
    //                             'user_id'           => null, // Will be updated after student insertion
    //                             'last_login'        => $now,
    //                             'security_question' => 'What Is Your Web Authentication Code?',
    //                             'security_answer'   => $record->web_auth,
    //                             'entity_id'         => 5,
    //                             'image_path'        => $record->photo_path,
    //                             'user_category'     => 'S',
    //                             'Active_inactive'   => 1,
    //                             'remember_token'    => null,
    //                             'updated_date'      => $now,
    //                             // ... include all other credential fields
    //                         ];
    //                     }

    //                     // 3. Always handle student_master_course__map
    //                     $courseMapExists = DB::table('student_master_course__map')
    //                         ->where('student_master_pk', $studentId)
    //                         ->where('course_master_pk', $record->course_master_pk)
    //                         ->exists();

    //                     if (!$courseMapExists) {
    //                         $courseMapToInsert[] = [
    //                             'student_master_pk' => $studentId,
    //                             'course_master_pk' => $record->course_master_pk,
    //                             'active_inactive' => 1,
    //                             'created_date' => $now,
    //                             'modified_date' => $now,
    //                         ];
    //                     }

    //                     $updateIds[] = $record->pk;
    //                 }

    //                 // Batch insert credentials
    //                 if (!empty($credentialsToInsert)) {
    //                     DB::table('user_credentials')->insert($credentialsToInsert);
    //                 }

    //                 // Batch insert course mappings
    //                 if (!empty($courseMapToInsert)) {
    //                     DB::table('student_master_course__map')->insert($courseMapToInsert);
    //                 }

    //                 // Mark source records as processed
    //                 if (!empty($updateIds)) {
    //                     DB::table('fc_registration_master')
    //                         ->whereIn('pk', $updateIds)
    //                         ->update(['is_registered' => 1]);
    //                 }
    //             });

    //         DB::commit();
    //         return back()->with('success', 'Migration completed successfully');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Migration failed: ' . $e->getMessage());
    //     }
    // }


    public function migrate()
    {
        DB::beginTransaction();

        try {
            $batchSize = 500;
            $now = Carbon::now();

            DB::table('fc_registration_master')
                ->where('fc_exemption_master_pk', 0) // Only process unregistered records
                ->where('admission_status', 1) // Ensure admission_status is 1
                ->orderBy('pk')
                ->chunk($batchSize, function ($chunkedRecords) use ($now) {
                    $studentsToInsert = [];
                    $credentialsToInsert = [];
                    $courseMapToInsert = [];
                    $updateIds = [];

                    // Get all user_ids from current batch
                    $userIds = array_filter($chunkedRecords->pluck('user_id')->toArray());

                    // Pre-fetch existing records
                    $existingStudents = DB::table('student_master')
                        ->whereIn('user_id', $userIds)
                        ->pluck('pk', 'user_id')
                        ->toArray();

                    $existingCredentials = DB::table('user_credentials')
                        ->whereIn('user_name', $userIds)
                        ->pluck('user_name')
                        ->toArray();

                    $existingCourseMaps = DB::table('student_master_course__map')
                        ->whereIn('student_master_pk', array_values($existingStudents))
                        ->select('student_master_pk', 'course_master_pk')
                        ->get()
                        ->keyBy(function ($item) {
                            return $item->student_master_pk . '-' . $item->course_master_pk;
                        })
                        ->toArray();

                    foreach ($chunkedRecords as $record) {
                        if (empty($record->user_id)) {
                            continue;
                        }

                        // 1. Handle student_master table
                        $studentId = $existingStudents[$record->user_id] ?? null;

                        if (!$studentId) {
                            // Insert new student if doesn't exist
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
                                // ... include all other student fields from your original code
                            ];

                            try {
                                $studentId = DB::table('student_master')->insertGetId($studentData);
                                $existingStudents[$record->user_id] = $studentId;
                            } catch (\Exception $e) {
                                continue; // Skip if student insertion fails
                            }
                        }

                        // 2. Handle user_credentials table
                        if (!in_array($record->user_id, $existingCredentials)) {
                            $credentialsToInsert[] = [
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
                                'user_id' => $studentId,
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
                            $existingCredentials[] = $record->user_id;
                        }

                        // 3. Handle student_master_course__map
                        $courseMapKey = $studentId . '-' . $record->course_master_pk;
                        if (!isset($existingCourseMaps[$courseMapKey])) {
                            $courseMapToInsert[] = [
                                'student_master_pk' => $studentId,
                                'course_master_pk' => $record->course_master_pk,
                                'active_inactive' => 1,
                                'created_date' => $now,
                                'modified_date' => $now,
                            ];
                            $existingCourseMaps[$courseMapKey] = true;
                        }

                        $updateIds[] = $record->pk;
                    }

                    // Batch insert credentials with duplicate handling
                    foreach (array_chunk($credentialsToInsert, 100) as $chunk) {
                        try {
                            DB::table('user_credentials')->insertOrIgnore($chunk);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // Batch insert course mappings with duplicate handling
                    foreach (array_chunk($courseMapToInsert, 100) as $chunk) {
                        try {
                            DB::table('student_master_course__map')->insertOrIgnore($chunk);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    // Mark source records as processed
                    if (!empty($updateIds)) {
                        DB::table('fc_registration_master')
                            ->whereIn('pk', $updateIds)
                            ->update(['is_registered' => 1]);
                    }
                });
            DB::commit();
            return back()->with('success', 'Migration completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }
}
