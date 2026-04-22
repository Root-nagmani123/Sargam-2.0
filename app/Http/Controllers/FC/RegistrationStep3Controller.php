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
        $username = Auth::user()->username;

        $step2 = StudentMasterSecond::where('username', $username)
            ->where('step2_completed', 1)->first();
        if (! $step2) {
            return redirect()->route('fc-reg.registration.step2')
                ->with('error', 'Please complete Step 2 first.');
        }

        $step   = $this->formService->getStep('step3');
        $groups = $this->formService->getStepGroups('step3');

        // If no dynamic groups configured, fall back to original view
        if ($groups->isEmpty()) {
            return $this->showStep3Legacy($username);
        }

        // Fetch existing data for each group
        $existingRows   = [];
        $groupLookups   = [];
        $completedGroups = [];

        foreach ($groups as $group) {
            $rows = $this->formService->getExistingGroupRows($group, $username);
            $existingRows[$group->group_name] = $rows;
            $completedGroups[$group->group_name] = $rows->isNotEmpty();
            $fieldsForLookups = $group->activeGroupFields->isNotEmpty()
                ? $group->activeGroupFields
                : $group->groupFields;
            $groupLookups[$group->group_name] = $this->formService->getGroupLookupData($fieldsForLookups);
        }

        return view('fc.registration.dynamic-step3', compact(
            'step', 'groups', 'existingRows', 'groupLookups', 'completedGroups'
        ));
    }

    // ── SAVE a dynamic group ─────────────────────────────────────────
    public function saveGroup(Request $request, FcFormFieldGroup $group)
    {
        $username = Auth::user()->username;

        // Build validation rules from the group's field definitions
        $rules     = $this->formService->buildGroupValidationRules($group);
        $validated = $request->validate($rules);

        $rows = $validated[$group->group_name] ?? [];

        // For upsert (single-row) groups, wrap data if flat
        if ($group->save_mode === 'upsert' && ! isset($rows[0])) {
            $rows = [$validated[$group->group_name] ?? $validated];
        }

        $this->formService->saveGroupData($group, $username, $rows);

        // Check if this is the last group (module choice) — mark step3 done
        $step       = $group->step;
        $allGroups  = $step->activeFieldGroups()->orderBy('display_order')->get();
        $lastGroup  = $allGroups->last();

        if ($group->id === $lastGroup->id) {
            StudentMaster::where('username', $username)->update(['step3_done' => 1]);
            return redirect()->route('fc-reg.registration.bank')
                ->with('success', 'Step 3 completed. Please fill in bank details.');
        }

        return back()->with('success', "{$group->group_label} saved.");
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEGACY METHODS (fallback when no dynamic config exists)
    // ═══════════════════════════════════════════════════════════════════

    private function showStep3Legacy(string $username)
    {
        $qualifications    = StudentMasterQualificationDetails::where('username', $username)->get();
        $higherEdus        = StudentMasterHigherEducationalDetails::where('username', $username)->get();
        $employments       = StudentMasterEmploymentDetails::where('username', $username)->get();
        $spouse            = StudentMasterSpouseMaster::where('username', $username)->first();
        $languages         = StudentMasterLanguageKnown::where('username', $username)->with('language')->get();
        $hindi             = StudentKnowledgeHindiMaster::where('username', $username)->first();
        $hobbies           = StudentMasterHobbiesDetails::where('username', $username)->first();
        $skills            = StudentSkillDetailsMaster::where('username', $username)->get();
        $distinctions      = StudentMasterAcademicDistinction::where('username', $username)->get();
        $sportsPlayed      = StudentSportsFitnessTeachMaster::where('username', $username)->with('sport')->get();
        $sportsTrg         = StudentSportsTrgTeachMaster::where('username', $username)->with('sport')->get();
        $moduleChoice      = StudentMasterModuleMaster::where('username', $username)->first();

        $qualificationMasters = QualificationMaster::all();
        $boardMasters         = BoardNameMaster::all();
        $streamMasters        = HighestStreamMaster::all();
        $jobTypes             = JobTypeMaster::all();
        $languageMasters      = LanguageMaster::orderBy('language_name')->get();
        $sportsMasters        = SportsMaster::orderBy('sport_name')->get();
        $degreeMasters        = DB::table('degree_master')->orderBy('degree_name')->get();

        return view('fc.registration.step3', compact(
            'qualifications','higherEdus','employments','spouse','languages','hindi',
            'hobbies','skills','distinctions','sportsPlayed','sportsTrg','moduleChoice',
            'qualificationMasters','boardMasters','streamMasters','jobTypes','languageMasters','sportsMasters',
            'degreeMasters'
        ));
    }

    // --- Legacy save methods (kept for backward compatibility) ---

    public function saveQualifications(Request $request)
    {
        $username  = Auth::user()->username;
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
        DB::transaction(function () use ($username, $validated) {
            StudentMasterQualificationDetails::where('username', $username)->delete();
            foreach ($validated['qualifications'] as $q) {
                StudentMasterQualificationDetails::create(array_merge($q, ['username' => $username]));
            }
        });
        return back()->with('success', 'Qualification details saved.');
    }

    public function saveHigherEducation(Request $request)
    {
        $username  = Auth::user()->username;
        $validated = $request->validate([
            'higher_edus'                   => 'nullable|array',
            'higher_edus.*.degree_type'     => 'required|exists:degree_master,pk',
            'higher_edus.*.subject_name'    => 'nullable|string|max:200',
            'higher_edus.*.university_name' => 'required|string|max:300',
            'higher_edus.*.year_of_passing' => 'required|digits:4',
            'higher_edus.*.percentage_cgpa' => 'nullable|string|max:20',
        ]);
        DB::transaction(function () use ($username, $validated) {
            StudentMasterHigherEducationalDetails::where('username', $username)->delete();
            foreach ($validated['higher_edus'] ?? [] as $h) {
                StudentMasterHigherEducationalDetails::create(array_merge($h, ['username' => $username]));
            }
        });
        return back()->with('success', 'Higher education details saved.');
    }

    public function saveEmployment(Request $request)
    {
        $username  = Auth::user()->username;
        $validated = $request->validate([
            'employments'                     => 'nullable|array',
            'employments.*.organisation_name' => 'required|string|max:300',
            'employments.*.designation'       => 'required|string|max:200',
            'employments.*.job_type_id'       => 'nullable|exists:job_type_masters,id',
            'employments.*.from_date'         => 'required|date',
            'employments.*.to_date'           => 'nullable|date|after:employments.*.from_date',
            'employments.*.is_current'        => 'nullable|boolean',
        ]);
        DB::transaction(function () use ($username, $validated) {
            StudentMasterEmploymentDetails::where('username', $username)->delete();
            foreach ($validated['employments'] ?? [] as $e) {
                $e['is_current'] = $e['is_current'] ?? 0;
                StudentMasterEmploymentDetails::create(array_merge($e, ['username' => $username]));
            }
        });
        return back()->with('success', 'Employment details saved.');
    }

    public function saveSpouse(Request $request)
    {
        $username  = Auth::user()->username;
        $validated = $request->validate([
            'spouse_name'         => 'nullable|string|max:200',
            'spouse_dob'          => 'nullable|date|before:today',
            'spouse_occupation'   => 'nullable|string|max:200',
            'spouse_organisation' => 'nullable|string|max:300',
            'no_of_children'      => 'nullable|string|max:10',
            'children_details'    => 'nullable|string|max:500',
        ]);
        $validated['username'] = $username;
        StudentMasterSpouseMaster::updateOrCreate(['username' => $username], $validated);
        return back()->with('success', 'Spouse details saved.');
    }

    public function saveLanguages(Request $request)
    {
        $username  = Auth::user()->username;
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
        DB::transaction(function () use ($username, $validated) {
            StudentMasterLanguageKnown::where('username', $username)->delete();
            foreach ($validated['languages'] as $l) {
                $l['can_read']  = $l['can_read'] ?? 0;
                $l['can_write'] = $l['can_write'] ?? 0;
                $l['can_speak'] = $l['can_speak'] ?? 0;
                StudentMasterLanguageKnown::create(array_merge($l, ['username' => $username]));
            }
            StudentKnowledgeHindiMaster::updateOrCreate(['username' => $username], [
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
        $username  = Auth::user()->username;
        $validated = $request->validate([
            'hobbies'           => 'nullable|string',
            'special_skills'    => 'nullable|string',
            'extra_curricular'  => 'nullable|string',
            'skills'            => 'nullable|array',
            'skills.*.skill_name'   => 'required|string|max:200',
            'skills.*.skill_level'  => 'nullable|string|max:100',
            'skills.*.year_acquired'=> 'nullable|digits:4',
        ]);
        DB::transaction(function () use ($username, $validated) {
            StudentMasterHobbiesDetails::updateOrCreate(['username' => $username], [
                'username'          => $username,
                'hobbies'           => $validated['hobbies'] ?? null,
                'special_skills'    => $validated['special_skills'] ?? null,
                'extra_curricular'  => $validated['extra_curricular'] ?? null,
            ]);
            StudentSkillDetailsMaster::where('username', $username)->delete();
            foreach ($validated['skills'] ?? [] as $s) {
                StudentSkillDetailsMaster::create(array_merge($s, ['username' => $username]));
            }
        });
        return back()->with('success', 'Hobbies and skills saved.');
    }

    public function saveDistinctions(Request $request)
    {
        $username  = Auth::user()->username;
        $validated = $request->validate([
            'distinctions'                     => 'nullable|array',
            'distinctions.*.distinction_type'  => 'required|string|max:200',
            'distinctions.*.description'       => 'nullable|string|max:500',
            'distinctions.*.year'              => 'nullable|digits:4',
            'distinctions.*.awarding_body'     => 'nullable|string|max:200',
        ]);
        DB::transaction(function () use ($username, $validated) {
            StudentMasterAcademicDistinction::where('username', $username)->delete();
            foreach ($validated['distinctions'] ?? [] as $d) {
                StudentMasterAcademicDistinction::create(array_merge($d, ['username' => $username]));
            }
        });
        return back()->with('success', 'Academic distinctions saved.');
    }

    public function saveSports(Request $request)
    {
        $username  = Auth::user()->username;
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
        DB::transaction(function () use ($username, $validated) {
            StudentSportsFitnessTeachMaster::where('username', $username)->delete();
            foreach ($validated['sports_played'] ?? [] as $sp) {
                StudentSportsFitnessTeachMaster::create(array_merge($sp, ['username' => $username]));
            }
            StudentSportsTrgTeachMaster::where('username', $username)->delete();
            foreach ($validated['sports_training'] ?? [] as $st) {
                StudentSportsTrgTeachMaster::create(array_merge($st, ['username' => $username]));
            }
        });
        return back()->with('success', 'Sports details saved.');
    }

    public function saveModuleChoice(Request $request)
    {
        $username  = Auth::user()->username;
        $validated = $request->validate([
            'chosen_module' => 'required|string|max:100',
            'second_module' => 'nullable|string|max:100',
        ]);
        $validated['username'] = $username;
        StudentMasterModuleMaster::updateOrCreate(['username' => $username], $validated);
        StudentMaster::where('username', $username)->update(['step3_done' => 1]);
        return redirect()->route('fc-reg.registration.bank')
            ->with('success', 'Step 3 completed. Please fill in bank details.');
    }
}
