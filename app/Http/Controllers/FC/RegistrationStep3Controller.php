<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{
    StudentMasterSecond, StudentMaster, FcFormFieldGroup,
    StudentMasterQualificationDetails, StudentMasterHigherEducationalDetails,
    StudentMasterEmploymentDetails, StudentMasterSpouseMaster,
    StudentMasterLanguageKnown, StudentKnowledgeHindiMaster,
    StudentMasterHobbiesDetails, StudentSkillDetailsMaster,
    StudentMasterAcademicDistinction, StudentSportsFitnessTeachMaster,
    StudentSportsTrgTeachMaster, StudentMasterModuleMaster,
    StudentMasterFirst, FcPreHistory,
    QualificationMaster, BoardNameMaster, HighestStreamMaster,
    JobTypeMaster, LanguageMaster, SportsMaster
};
use App\Services\FC\DynamicFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrationStep3Controller extends Controller
{
    public function __construct(private DynamicFormService $formService) {}

    // ── SHOW Step 3 (tabbed form) ────────────────────────────────────
    public function showStep3()
    {
        $userId = Auth::id();

        $step2 = StudentMasterSecond::forUser($userId)
            ->where('step2_completed', 1)->first();
        if (! $step2) {
            return redirect()->route('fc-reg.registration.step2')
                ->with('error', 'Please complete Step 2 first.');
        }

        $step   = $this->formService->getStep('step3');
        $groups = $this->formService->getStepGroups('step3');

        // If no dynamic groups configured, fall back to original view
        if ($groups->isEmpty()) {
            return $this->showStep3Legacy($userId);
        }

        // Fetch existing data for each group
        $existingRows   = [];
        $groupLookups   = [];
        $completedGroups = [];

        foreach ($groups as $group) {
            $rows = $this->formService->getExistingGroupRows($group, $userId);
            $existingRows[$group->group_name] = $rows;
            $completedGroups[$group->group_name] = $rows->isNotEmpty();
            $fieldsForLookups = $group->activeGroupFields->isNotEmpty()
                ? $group->activeGroupFields
                : $group->groupFields;
            $groupLookups[$group->group_name] = $this->formService->getGroupLookupData($fieldsForLookups);
        }

        return view('fc.registration.dynamic-step3', compact(
            'step', 'groups', 'existingRows', 'groupLookups', 'completedGroups',
        ));
    }

    // ── SAVE a dynamic group ─────────────────────────────────────────
    public function saveGroup(Request $request, FcFormFieldGroup $group)
    {
        $userId = Auth::id();

        // Build validation rules from the group's field definitions
        $rules     = $this->formService->buildGroupValidationRules($group);
        $validated = $request->validate($rules);

        $rows = $validated[$group->group_name] ?? [];

        // For upsert (single-row) groups, wrap data if flat
        if ($group->save_mode === 'upsert' && ! isset($rows[0])) {
            $rows = [$validated[$group->group_name] ?? $validated];
        }

        $this->formService->saveGroupData($group, $userId, $rows, $request);

        // Check if this is the last group (module choice) — mark step3 done
        $step       = $group->step;
        $allGroups  = $step->activeFieldGroups()->orderBy('display_order')->get();
        $lastGroup  = $allGroups->last();

        if ($group->id === $lastGroup->id) {
            StudentMaster::forUser($userId)->update(['step3_done' => 1]);
            return redirect()->route('fc-reg.registration.bank')
                ->with('success', 'Step 3 completed. Please fill in bank details.');
        }

        return back()->with('success', "{$group->group_label} saved.");
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEGACY METHODS (fallback when no dynamic config exists)
    // ═══════════════════════════════════════════════════════════════════

    private function showStep3Legacy(int $userId)
    {
        $qualifications    = StudentMasterQualificationDetails::forUser($userId)->get();
        $higherEdus        = StudentMasterHigherEducationalDetails::forUser($userId)->get();
        $employments       = StudentMasterEmploymentDetails::forUser($userId)->get();
        $spouse            = StudentMasterSpouseMaster::forUser($userId)->first();
        $languages         = StudentMasterLanguageKnown::forUser($userId)->with('language')->get();
        $hindi             = StudentKnowledgeHindiMaster::forUser($userId)->first();
        $hobbies           = StudentMasterHobbiesDetails::forUser($userId)->first();
        $skills            = StudentSkillDetailsMaster::forUser($userId)->get();
        $distinctions      = StudentMasterAcademicDistinction::forUser($userId)->get();
        $sportsPlayed      = StudentSportsFitnessTeachMaster::forUser($userId)->with('sport')->get();
        $sportsTrg         = StudentSportsTrgTeachMaster::forUser($userId)->with('sport')->get();
        $moduleChoice      = StudentMasterModuleMaster::forUser($userId)->first();

        $qualificationMasters = QualificationMaster::all();
        $boardMasters         = BoardNameMaster::all();
        $streamMasters        = HighestStreamMaster::all();
        $jobTypes             = JobTypeMaster::all();
        $languageMasters      = LanguageMaster::orderBy('language_name')->get();
        $sportsMasters        = SportsMaster::orderBy('sport_name')->get();
        $degreeMasters        = DB::table('degree_master')->orderBy('degree_name')->get();

        $preMedicalCourse = $this->registrationSessionName($userId);
        $preMedical = FcPreHistory::where(fc_user_col('fc_pre_history'), fc_user_val('fc_pre_history', $userId))->where('course', $preMedicalCourse)->first();

        return view('fc.registration.step3', compact(
            'qualifications','higherEdus','employments','spouse','languages','hindi',
            'hobbies','skills','distinctions','sportsPlayed','sportsTrg','moduleChoice',
            'preMedical', 'preMedicalCourse',
            'qualificationMasters','boardMasters','streamMasters','jobTypes','languageMasters','sportsMasters',
            'degreeMasters'
        ));
    }

    /**
     * Session / course label for FC pre-history (matches fc_pre_history.course used post-arrival).
     */
    private function registrationSessionName(int $userId): string
    {
        $first = StudentMasterFirst::with('session')->forUser($userId)->first();

        return trim((string) ($first?->session?->session_name ?? ''));
    }

    /**
     * Legacy flat form (step3.blade.php). When Step 3 uses dynamic groups, pre-medical is saved via
     * {@see saveGroup()} on the `pre_medical_history` group instead.
     */
    public function savePreMedicalHistory(Request $request)
    {
        $userId = Auth::id();
        $course = $this->registrationSessionName($userId);

        $validated = $request->validate([
            'allergy_illness' => 'nullable|string|max:60000',
            'prolonged_medication' => 'nullable|string|max:60000',
            'hospital_history' => 'nullable|string|max:60000',
            'altitude_illness' => 'nullable|string|max:60000',
            'additional_info' => 'nullable|string|max:60000',
            'pre_med_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $existing = FcPreHistory::where(fc_user_col('fc_pre_history'), fc_user_val('fc_pre_history', $userId))->where('course', $course)->first();

        $docPath = $existing?->doc_path;
        if ($request->hasFile('pre_med_doc') && $request->file('pre_med_doc')->isValid()) {
            $file = $request->file('pre_med_doc');
            $stored = $file->storeAs(
                'fc/pre_history',
                $userId.'_'.uniqid('', true).'.'.$file->getClientOriginalExtension(),
                'public'
            );
            $docPath = 'storage/'.$stored;
        }

        FcPreHistory::updateOrCreate(
            [fc_user_col('fc_pre_history') => fc_user_val('fc_pre_history', $userId), 'course' => $course],
            [
                'allergy_illness' => $validated['allergy_illness'] ?? null,
                'prolonged_medication' => $validated['prolonged_medication'] ?? null,
                'hospital_history' => $validated['hospital_history'] ?? null,
                'altitude_illness' => $validated['altitude_illness'] ?? null,
                'additional_info' => $validated['additional_info'] ?? null,
                'doc_path' => $docPath,
                'status' => 1,
            ]
        );

        return back()->with('success', 'Pre-medical history saved.');
    }

    // --- Legacy save methods (kept for backward compatibility) ---

    public function saveQualifications(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'qualifications'                    => 'required|array|min:1',
            'qualifications.*.qualification_id' => 'required|exists:qualification_master,pk',
            'qualifications.*.degree_name'      => 'required|string|max:200',
            'qualifications.*.board_id'         => 'nullable|exists:university_board_name_master,pk',
            'qualifications.*.institution_name' => 'required|string|max:300',
            'qualifications.*.year_of_passing'  => 'required|digits:4',
            'qualifications.*.percentage_cgpa'  => 'required|string|max:20',
            'qualifications.*.stream_id'        => 'nullable|exists:stream_master,pk',
            'qualifications.*.subject_details'  => 'nullable|string|max:500',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentMasterQualificationDetails::forUser($userId)->delete();
            foreach ($validated['qualifications'] as $q) {
                StudentMasterQualificationDetails::create(array_merge($q, [fc_user_col('student_master_qualification_details') => fc_user_val('student_master_qualification_details', $userId)]));
            }
        });
        return back()->with('success', 'Qualification details saved.');
    }

    public function saveHigherEducation(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'higher_edus'                   => 'nullable|array',
            'higher_edus.*.degree_type'     => 'required|exists:degree_master,pk',
            'higher_edus.*.subject_name'    => 'nullable|string|max:200',
            'higher_edus.*.university_name' => 'required|string|max:300',
            'higher_edus.*.year_of_passing' => 'required|digits:4',
            'higher_edus.*.percentage_cgpa' => 'nullable|string|max:20',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentMasterHigherEducationalDetails::forUser($userId)->delete();
            foreach ($validated['higher_edus'] ?? [] as $h) {
                StudentMasterHigherEducationalDetails::create(array_merge($h, [fc_user_col('student_master_higher_educational_details') => fc_user_val('student_master_higher_educational_details', $userId)]));
            }
        });
        return back()->with('success', 'Higher education details saved.');
    }

    public function saveEmployment(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'employments'                     => 'nullable|array',
            'employments.*.organisation_name' => 'required|string|max:300',
            'employments.*.designation'       => 'required|string|max:200',
            'employments.*.job_type_id'       => 'nullable|exists:job_type_masters,id',
            'employments.*.from_date'         => 'required|date',
            'employments.*.to_date'           => 'nullable|date|after:employments.*.from_date',
            'employments.*.is_current'        => 'nullable|boolean',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentMasterEmploymentDetails::forUser($userId)->delete();
            foreach ($validated['employments'] ?? [] as $e) {
                $e['is_current'] = $e['is_current'] ?? 0;
                StudentMasterEmploymentDetails::create(array_merge($e, [fc_user_col('student_master_employment_details') => fc_user_val('student_master_employment_details', $userId)]));
            }
        });
        return back()->with('success', 'Employment details saved.');
    }

    public function saveSpouse(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'spouse_name'         => 'nullable|string|max:200',
            'spouse_dob'          => 'nullable|date|before:today',
            'spouse_occupation'   => 'nullable|string|max:200',
            'spouse_organisation' => 'nullable|string|max:300',
            'no_of_children'      => 'nullable|string|max:10',
            'children_details'    => 'nullable|string|max:500',
        ]);
        $validated[fc_user_col('student_master_spouse_masters')] = fc_user_val('student_master_spouse_masters', $userId);
        StudentMasterSpouseMaster::updateOrCreate([fc_user_col('student_master_spouse_masters') => fc_user_val('student_master_spouse_masters', $userId)], $validated);
        return back()->with('success', 'Spouse details saved.');
    }

    public function saveLanguages(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'languages'                    => 'required|array|min:1',
            'languages.*.language_id'      => 'required|' . $this->formService->languageMasterExistsRule(),
            'languages.*.can_read'         => 'nullable|boolean',
            'languages.*.can_write'        => 'nullable|boolean',
            'languages.*.can_speak'        => 'nullable|boolean',
            'languages.*.proficiency'      => 'nullable|in:Basic,Intermediate,Fluent',
            'medium_of_study'              => 'nullable|string|max:100',
            'hindi_medium_school'          => 'nullable|boolean',
            'hindi_subject_studied'        => 'nullable|boolean',
            'highest_hindi_exam'           => 'nullable|string|max:150',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentMasterLanguageKnown::forUser($userId)->delete();
            foreach ($validated['languages'] as $l) {
                $l['can_read']  = $l['can_read'] ?? 0;
                $l['can_write'] = $l['can_write'] ?? 0;
                $l['can_speak'] = $l['can_speak'] ?? 0;
                StudentMasterLanguageKnown::create(array_merge($l, [fc_user_col('student_master_language_knowns') => fc_user_val('student_master_language_knowns', $userId)]));
            }
            StudentKnowledgeHindiMaster::updateOrCreate([fc_user_col('student_knowledge_hindi_masters') => fc_user_val('student_knowledge_hindi_masters', $userId)], [
                'medium_of_study'       => $validated['medium_of_study'] ?? null,
                'hindi_medium_school'   => $validated['hindi_medium_school'] ?? 0,
                'hindi_subject_studied' => $validated['hindi_subject_studied'] ?? 0,
                'highest_hindi_exam'    => $validated['highest_hindi_exam'] ?? null,
            ]);
        });
        return back()->with('success', 'Language details saved.');
    }

    public function saveHobbies(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'hobbies'           => 'nullable|string',
            'special_skills'    => 'nullable|string',
            'extra_curricular'  => 'nullable|string',
            'skills'            => 'nullable|array',
            'skills.*.skill_name'   => 'required|string|max:200',
            'skills.*.skill_level'  => 'nullable|string|max:100',
            'skills.*.year_acquired'=> 'nullable|digits:4',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentMasterHobbiesDetails::updateOrCreate([fc_user_col('student_master_hobbies_details') => fc_user_val('student_master_hobbies_details', $userId)], [
                fc_user_col('student_master_hobbies_details') => fc_user_val('student_master_hobbies_details', $userId),
                'hobbies'           => $validated['hobbies'] ?? null,
                'special_skills'    => $validated['special_skills'] ?? null,
                'extra_curricular'  => $validated['extra_curricular'] ?? null,
            ]);
            StudentSkillDetailsMaster::forUser($userId)->delete();
            foreach ($validated['skills'] ?? [] as $s) {
                StudentSkillDetailsMaster::create(array_merge($s, [fc_user_col('student_skill_details_masters') => fc_user_val('student_skill_details_masters', $userId)]));
            }
        });
        return back()->with('success', 'Hobbies and skills saved.');
    }

    public function saveDistinctions(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'distinctions'                     => 'nullable|array',
            'distinctions.*.distinction_type'  => 'required|string|max:200',
            'distinctions.*.description'       => 'nullable|string|max:500',
            'distinctions.*.year'              => 'nullable|digits:4',
            'distinctions.*.awarding_body'     => 'nullable|string|max:200',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentMasterAcademicDistinction::forUser($userId)->delete();
            foreach ($validated['distinctions'] ?? [] as $d) {
                StudentMasterAcademicDistinction::create(array_merge($d, [fc_user_col('student_master_academic_distinctions') => fc_user_val('student_master_academic_distinctions', $userId)]));
            }
        });
        return back()->with('success', 'Academic distinctions saved.');
    }

    public function saveSports(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'sports_played'              => 'nullable|array',
            'sports_played.*.sport_id'   => 'required|exists:sports_masters,id',
            'sports_played.*.level'      => 'nullable|string|max:100',
            'sports_played.*.role'       => 'nullable|string|max:100',
            'sports_played.*.year'       => 'nullable|digits:4',
            'sports_training'                     => 'nullable|array',
            'sports_training.*.sport_id'          => 'required|exists:sports_masters,id',
            'sports_training.*.training_institute'=> 'nullable|string|max:300',
            'sports_training.*.duration'          => 'nullable|string|max:100',
            'sports_training.*.year'              => 'nullable|digits:4',
        ]);
        DB::transaction(function () use ($userId, $validated) {
            StudentSportsFitnessTeachMaster::forUser($userId)->delete();
            foreach ($validated['sports_played'] ?? [] as $sp) {
                StudentSportsFitnessTeachMaster::create(array_merge($sp, [fc_user_col('student_sports_fitness_teach_masters') => fc_user_val('student_sports_fitness_teach_masters', $userId)]));
            }
            StudentSportsTrgTeachMaster::forUser($userId)->delete();
            foreach ($validated['sports_training'] ?? [] as $st) {
                StudentSportsTrgTeachMaster::create(array_merge($st, [fc_user_col('student_sports_trg_teach_masters') => fc_user_val('student_sports_trg_teach_masters', $userId)]));
            }
        });
        return back()->with('success', 'Sports details saved.');
    }

    public function saveModuleChoice(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'chosen_module' => 'required|string|max:100',
            'second_module' => 'nullable|string|max:100',
        ]);
        $validated[fc_user_col('student_master_module_masters')] = fc_user_val('student_master_module_masters', $userId);
        StudentMasterModuleMaster::updateOrCreate([fc_user_col('student_master_module_masters') => fc_user_val('student_master_module_masters', $userId)], $validated);
        StudentMaster::forUser($userId)->update(['step3_done' => 1]);
        return redirect()->route('fc-reg.registration.bank')
            ->with('success', 'Step 3 completed. Please fill in bank details.');
    }
}
