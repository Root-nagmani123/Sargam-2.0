<?php

namespace App\Http\Controllers\Admin\Registration;

// namespace App\Models;
use App\Http\Controllers\Controller;
use App\Services\FC\FcRegistrationIntentService;
use App\Services\FC\FcRegistrationStatusService;
use App\Services\FC\FcRosterApplicationGuardService;
use App\Services\FC\FcRosterAuthService;
use App\Support\FcEncryptedFormId;
use Illuminate\Http\Request;
use App\Models\FC\FcForm;
use App\Models\FrontPage;
use App\Models\FacultyMaster;
use App\Models\DesignationMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\PathPage;
use App\Models\PathPageFaq;
use App\Models\ExemptionCategory;
use App\Models\FoundationCourseStatus;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;


class FrontPageController extends Controller
{
    /** Matches validation rule max:5120 (kilobytes) for medical_doc uploads. */
    private const MEDICAL_DOC_MAX_KB = 5120;

    public function __construct(
        private FcRegistrationIntentService $fcRegistrationIntent,
        private FcRegistrationStatusService $fcRegistrationStatus,
        private FcRosterApplicationGuardService $rosterGuard,
    ) {
    }

    private function redirectToChoosePathWithWarning(string $message)
    {
        return redirect()
            ->route('fc.choose.path', $this->intentQueryForFcFormLinks())
            ->with('warning', $message);
    }

    public function index()
    {
        $data = FrontPage::first(); // fetch latest/only record

        // Faculty list for the Coordinator Name dropdown (same source as Create Course).
        // Keyed by full_name so the saved value remains a name string and the public
        // front page (which prints coordinator_name directly) keeps working unchanged.
        $facultyList = FacultyMaster::orderBy('full_name')->pluck('full_name', 'full_name')->toArray();

        // Preserve a previously saved coordinator name even if it is no longer in the faculty list.
        if ($data && !empty($data->coordinator_name) && !isset($facultyList[$data->coordinator_name])) {
            $facultyList = [$data->coordinator_name => $data->coordinator_name] + $facultyList;
        }

        // Map of coordinator (faculty) name => their designation name, so the
        // Coordinator Designation field can auto-fill when a coordinator is picked.
        // Chain: faculty_master.employee_master_pk -> employee_master.designation_master_pk -> designation_master.
        $coordinatorDesignations = FacultyMaster::query()
            ->join('employee_master', 'faculty_master.employee_master_pk', '=', 'employee_master.pk')
            ->join('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->whereNotNull('faculty_master.full_name')
            ->pluck('designation_master.designation_name', 'faculty_master.full_name')
            ->toArray();

        // Options for the Coordinator Designation searchable dropdown. Keyed by name so the
        // saved value stays a string (the public front page prints coordinator_designation directly).
        $designationList = DesignationMaster::where('active_inactive', 1)
            ->orderBy('designation_name')
            ->pluck('designation_name', 'designation_name')
            ->toArray();

        // Ensure every designation that a coordinator can auto-fill is selectable (even if inactive).
        foreach ($coordinatorDesignations as $desigName) {
            if (!empty($desigName) && !isset($designationList[$desigName])) {
                $designationList[$desigName] = $desigName;
            }
        }

        // Preserve the currently saved designation even if it is inactive or no longer in the master.
        if ($data && !empty($data->coordinator_designation) && !isset($designationList[$data->coordinator_designation])) {
            $designationList[$data->coordinator_designation] = $data->coordinator_designation;
        }
        asort($designationList);

        return view('admin.forms.home_page', compact('data', 'facultyList', 'coordinatorDesignations', 'designationList'));
    }

    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'course_start_date' => 'nullable|date',
            'course_end_date' => 'nullable|date',
            'registration_start_date' => 'nullable|date',
            'registration_end_date' => 'nullable|date',
            'important_updates' => 'nullable|string',
            'course_title' => 'nullable|string|max:255',
            'coordinator_name' => 'nullable|string|max:255',
            'coordinator_designation' => 'nullable|string|max:255',
            'coordinator_info' => 'nullable|string|max:255',
            'coordinator_signature' => 'nullable|mimes:jpeg,png,jpg,gif,pdf|max:5120',
        ]);

        $data = $request->except(['important_updates', 'coordinator_signature']);
        $data['important_updates'] = html_entity_decode($request->input('important_updates'));

        // File Upload
        if ($request->hasFile('coordinator_signature')) {
            $file = $request->file('coordinator_signature');
            $filePath = $file->store('signatures', 'public'); // stored in storage/app/public/signatures
            $data['coordinator_signature'] = $filePath;
        }


        $frontPage = FrontPage::first();

        if ($frontPage) {
            $frontPage->update($data);
        } else {
            FrontPage::create($data);
        }

        return redirect()->back()->with('success', 'Front Page content saved successfully.');
    }
    // foundation page
    public function foundationIndex(Request $request)
    {
        $this->fcRegistrationIntent->ingestFormQuery($request);
        $data = FrontPage::first();
        $pathPage = PathPage::first();
        $intentQuery = $this->intentQueryForFcFormLinks();
        // Only show programme name when this visit used a form-specific landing URL (?form= token).
        $programmeIntentLabel = $this->fcRegistrationIntent->requestHasFormToken($request)
            ? $this->resolvedIntendedProgrammeName()
            : null;

        return view('fc.front_page', compact('data', 'pathPage', 'intentQuery', 'programmeIntentLabel'));
    }

    /**
     * Public programme name for the active intended form (never the raw id).
     */
    private function resolvedIntendedProgrammeName(): ?string
    {
        $id = session(FcRegistrationIntentService::SESSION_FORM_ID);
        if (! is_numeric($id) || (int) $id < 1) {
            return null;
        }

        return FcForm::query()
            ->whereKey((int) $id)
            ->where('is_active', true)
            ->value('form_name');
    }

    //Authentication method
    public function authindex(Request $request)
    {
        $this->fcRegistrationIntent->ingestFormQuery($request);

        return view('fc.login');
    }

    //validates user exists 
    public function verify(Request $request)
    {
        // Step 1: Validate input, including captcha
        $request->validate([
            'reg_mobile'   => 'required|digits:10',
            'reg_web_code' => 'required|string',
            'captcha'      => 'required|captcha',
        ], [
            'captcha.captcha' => 'Invalid captcha.',
        ]);

        // Step 2: Check if user exists in the registration table
        $registration = DB::table('fc_registration_master')
            ->where('contact_no', $request->reg_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->first();
        // @dd($registration);
        if (!$registration) {
            return back()
                ->withErrors(['web_auth' => 'Invalid contact number or web auth code.'])
                ->withInput();
        }

        if ($blocked = $this->rosterGuard->registrationBlockedReason($registration)) {
            return $this->redirectToChoosePathWithWarning($blocked);
        }


        // Step 4: Find the first visible form
        $form = DB::table('local_form')
            ->where('visible', 1)
            ->orderBy('id')
            ->first();

        if (!$form) {
            return back()
                ->withErrors(['formid' => 'No visible form found.']);
        }

        // Step 4: Store session if needed (optional)
        session([
            'fc_user_id' => $registration->pk,
            'fc_user_mobile' => $registration->contact_no,
            'fc_user_web_auth' => $registration->web_auth,
            'fc_user_name' => $registration->name ?? null,
        ]);

        // Step 5: Redirect to form
        // return redirect()->route('credential.registration.create')->with([
        //     'success' => 'You have been successfully authenticated. Please create your credentials.'
        // ]);

        return redirect()->route('credential.registration.create', $this->intentQueryForFcFormLinks())->with([
            'sweet_success' => 'You have been successfully authenticated. Please create your credentials.',
        ]);
    }

    // Show the registration form
    public function credential_index(Request $request)
    {
        $this->fcRegistrationIntent->ingestFormQuery($request);

        $mobile = session('fc_user_mobile');
        $webAuth = session('fc_user_web_auth');
        if ($mobile && $webAuth) {
            $registration = DB::table('fc_registration_master')
                ->where('contact_no', $mobile)
                ->where('web_auth', $webAuth)
                ->first();

            if ($registration && ($blocked = $this->rosterGuard->registrationBlockedReason($registration))) {
                return $this->redirectToChoosePathWithWarning($blocked);
            }
        }

        return view('fc.credentials');
    }

    // Store user credentials
    public function credential_store(Request $request)
    {
        $request->merge([
            'reg_name' => Str::lower(trim((string) $request->input('reg_name', ''))),
        ]);

        $request->validate([
            'reg_name' => [
                'required',
                'string',
                'min:6',
                'max:20',
                'lowercase',
                'regex:/^(?=.{6,20}$)(?!.*[_.]{2})[a-z][a-z0-9._]*[a-z0-9]$/',
                'unique:user_credentials,user_name',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $takenOnRoster = DB::table('fc_registration_master')
                        ->where('user_id', $value)
                        ->where('contact_no', '!=', request('reg_mobile'))
                        ->exists();
                    if ($takenOnRoster) {
                        $fail('The username has already been taken.');
                    }
                },
            ],

            'reg_mobile' => 'required|digits:10',

            'reg_password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[\W_]).+$/', // at least one special character
            ],
            'reg_confirm_password' => 'required|same:reg_password',
        ], [
            'reg_name.regex' => 'Username must be lowercase, start with a letter, and may contain letters, numbers, dots, and underscores. No consecutive dots/underscores or ending with a special character.',
            'reg_name.lowercase' => 'Username must not contain capital letters.',
            'reg_name.unique' => 'The username has already been taken.',
            'reg_mobile.required' => 'Mobile number is required.',
            'reg_mobile.digits' => 'Mobile number must be 10 digits.',
            'reg_password.min' => 'The password must be at least 6 characters.',
            'reg_password.regex' => 'The password must contain at least one special character.',
            'reg_confirm_password.same' => 'The confirm password and password must match.',
        ], [
            // Define custom field labels
            'reg_name' => 'Username',
            'reg_mobile' => 'Mobile number',
            'reg_password' => 'Password',
            'reg_confirm_password' => 'Confirm password',
        ]);


        $registration = DB::table('fc_registration_master')
            ->where('contact_no', $request->reg_mobile)
            ->first();

        if (!$registration) {
            return back()->withErrors(['reg_mobile' => 'No foundation course registration found for this mobile number.'])->withInput();
        }

        if ($blocked = $this->rosterGuard->registrationBlockedReason($registration)) {
            return $this->redirectToChoosePathWithWarning($blocked);
        }

        // Stage credentials only; application_type = 1 is set with is_registered after steps 1 & 2.
        DB::table('fc_registration_master')
            ->where('contact_no', $request->reg_mobile)
            ->update([
                'user_id' => $request->reg_name,
                'password' => Hash::make($request->reg_password),
            ]);

        // Email the username and password to the trainee (best-effort: never block account creation).
        $this->sendCredentialsEmail($registration->email ?? null, $request->reg_name, $request->reg_password, $registration->pk ?? null);

        return redirect()->route('fc.login', $this->intentQueryForFcFormLinks())->with(
            'sweet_success',
            'Credentials saved successfully. Please log in to complete your registration form.'
        );
    }

    // Show the login form
    public function showLoginForm(Request $request)
    {
        $this->fcRegistrationIntent->ingestFormQuery($request);

        return view('fc.fc_login'); // Adjust to your login blade path
    }

    // Log the foundation-course (staged) user out and return to the FC login page.
    // session()->invalidate() flushes the staged roster pk, so the user is fully signed out.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('fc.login');
    }

    /**
     * Email the FC login credentials (username + password) to the trainee.
     * Best-effort: any mail failure is logged but never blocks account creation.
     */
    protected function sendCredentialsEmail(?string $email, string $username, string $password, ?int $registrationPk = null): void
    {
        $email = trim((string) $email);
        if ($email === '') {
            return;
        }

        try {
            $fromAddress = config('mail.from.address') ?: 'no-reply@lbsnaa.gov.in';
            $fromName = config('mail.from.name') ?: 'LBSNAA Foundation Course';

            $body = "Dear Candidate,\n\n"
                . "Your login credentials for the LBSNAA Foundation Course registration portal have been created successfully.\n\n"
                . "Username: {$username}\n"
                . "Password: {$password}\n\n"
                . "Please keep these credentials confidential and do not share them with anyone.\n\n"
                . "Regards,\n"
                . "Lal Bahadur Shastri National Academy of Administration, Mussoorie";

            Mail::raw($body, function ($mail) use ($email, $fromAddress, $fromName) {
                $mail->from($fromAddress, $fromName)
                    ->to($email)
                    ->subject('Your Foundation Course Login Credentials');
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send FC credentials email: ' . $e->getMessage(), [
                'registration_pk' => $registrationPk,
            ]);
        }
    }

    //user login verification
    public function verifyLogin(Request $request, FcRosterAuthService $rosterAuth)
    {
        // Validate input
        $request->validate([
            'reg_name' => 'required|string',
            'reg_password' => 'required|string',
        ]);

        $regName = $rosterAuth->normalizeLoginUsername($request->reg_name);

        $intentFormId = session(FcRegistrationIntentService::SESSION_FORM_ID);
        $intentSetAt = session(FcRegistrationIntentService::SESSION_FORM_SET_AT);
        $intentFormId = is_numeric($intentFormId) ? (int) $intentFormId : null;
        $intentSetAt = is_numeric($intentSetAt) ? (int) $intentSetAt : null;

        if ($rosterAuth->usernameExistsInUserCredentials($regName)) {
            return back()->withErrors([
                'reg_name' => 'User already exists. Your account has been migrated — please use the main login page.',
            ])->withInput();
        }

        // FC login: authenticate only against fc_registration_master (main /login uses user_credentials).
        $roster = $rosterAuth->findStagedRosterByLogin($regName);
        if ($roster && $rosterAuth->verifyStagedPassword($roster, $request->reg_password)) {
            $this->fcRegistrationIntent->forgetIntent();
            $rosterAuth->establishStagedSession($roster);

            return $this->fcRegistrationIntent->redirectAfterFcWebLogin($intentFormId, $intentSetAt);
        }

        return back()->withErrors(['login' => 'Invalid username or password.'])->withInput();
    }

    // FC Form Show (session-based auth for FC users)
    public function fcFormShow($formId)
    {
        $form = DB::table('local_form')->where('id', $formId)->first();
        if (!$form) abort(404, 'Form not found');

        $parentFormId = ($form->parent_id && $form->parent_id != 0) ? $form->parent_id : $form->id;

        $childForms = DB::table('local_form')
            ->where('parent_id', $parentFormId)
            ->where('visible', 1)
            ->orderBy('sortorder')
            ->get();

        if (($form->parent_id == 0 || $form->parent_id == null) && $childForms->isNotEmpty()) {
            return redirect()->route('fc.form.show', $childForms->first()->id);
        }

        $fields = DB::table('form_data')
            ->where('formid', $form->id)
            ->orderBy('id')
            ->orderBy('row_index')
            ->orderBy('col_index')
            ->get();

        $fieldsBySection = [];
        $gridFields = [];
        foreach ($fields as $field) {
            if ($field->format === 'table') {
                $fieldsBySection[$field->section_id][$field->row_index][$field->col_index] = $field;
            } else {
                $gridFields[$field->section_id][] = $field;
            }
        }

        $sections = DB::table('form_sections')
            ->where('formid', $form->id)
            ->get();

        $headersBySection = [];
        foreach ($fields as $field) {
            if ($field->format === 'table') {
                $headersBySection[$field->section_id][$field->col_index] = $field->header;
            }
        }

        $fcUserId = session('fc_user_id');
        $submissions = DB::table('fc_registration_master')
            ->where('formid', $form->id)
            ->where('uid', $fcUserId)
            ->get()
            ->keyBy('fieldname');

        $data = DB::table('registration_logo')->first();
        $fcMode = true;

        return view('admin.forms.show', compact(
            'form', 'data', 'childForms', 'sections',
            'fieldsBySection', 'gridFields', 'headersBySection',
            'submissions', 'fcMode'
        ));
    }

    // FC Form Submit (session-based auth for FC users)
    public function fcFormSubmit(Request $request, $formId)
    {
        try {
            $userId = session('fc_user_id');
            $timestamp = now()->timestamp;

            $existingSubmission = DB::table('fc_registration_master')
                ->where('formid', $formId)
                ->where('uid', $userId)
                ->first();

            $dynamicFields = [];
            foreach ($request->all() as $key => $value) {
                if (Str::startsWith($key, 'field_')) {
                    if ($value instanceof UploadedFile) {
                        $request->validate([$key => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120']);
                        $filename = time() . '_' . $value->getClientOriginalName();
                        $path = $value->storeAs('form-uploads', $filename, 'public');
                        $dynamicFields[Str::replaceFirst('field_', '', $key)] = $path;
                    } elseif (is_array($value)) {
                        $dynamicFields[Str::replaceFirst('field_', '', $key)] = implode(',', $value);
                    } else {
                        $dynamicFields[Str::replaceFirst('field_', '', $key)] = $value;
                    }
                }
            }

            if (!empty($dynamicFields)) {
                $dynamicFields['formid'] = $formId;
                $dynamicFields['uid'] = $userId;
                $dynamicFields['timecreated'] = $timestamp;

                if ($existingSubmission) {
                    DB::table('fc_registration_master')
                        ->where('formid', $formId)
                        ->where('uid', $userId)
                        ->update($dynamicFields);
                } else {
                    DB::table('fc_registration_master')->insert($dynamicFields);
                }
            }

            $tableDataInserted = false;
            $headers_table_data_values = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/^header_(\d+)_(\d+)$/', $key, $matches)) {
                    $sectionId = (int)$matches[1];
                    $colIndex = (int)$matches[2];
                    $headers_table_data_values[$sectionId]['headers'][$colIndex] = $value;
                }
                if (preg_match('/^table_(\d+)_(\d+)_(\d+)$/', $key, $matches)) {
                    $sectionId = (int)$matches[1];
                    $rowIndex = (int)$matches[2];
                    $colIndex = (int)$matches[3];
                    $headers_table_data_values[$sectionId]['values'][$rowIndex][$colIndex] = $value;
                }
            }

            DB::table('form_submission_tabledata')
                ->where('formid', $formId)
                ->where('uid', $userId)
                ->delete();

            foreach ($headers_table_data_values as $sectionId => $sectionData) {
                $headers = $sectionData['headers'] ?? [];
                $values = $sectionData['values'] ?? [];
                foreach ($values as $rowIndex => $cols) {
                    foreach ($cols as $colIndex => $columnValue) {
                        $columnKey = $headers[$colIndex] ?? 'column_' . $colIndex;
                        $fieldType = 'text';
                        $filePath = null;
                        $valueKey = "table_{$sectionId}_{$rowIndex}_{$colIndex}";

                        if ($request->hasFile($valueKey)) {
                            $filePath = $request->file($valueKey)->store('form-uploads' . $formId . $userId, 'public');
                            $fieldType = 'file';
                            $columnValue = null;
                        } elseif (is_array($columnValue)) {
                            $fieldType = 'checkbox';
                            $columnValue = implode(',', array_map('trim', $columnValue));
                        } elseif (in_array($columnValue, ['on', 'off', '1', '0'], true)) {
                            $fieldType = 'checkbox';
                            $columnValue = ($columnValue === 'on' || $columnValue === '1') ? 1 : 0;
                        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $columnValue)) {
                            $fieldType = 'date';
                        } elseif (filter_var($columnValue, FILTER_VALIDATE_EMAIL)) {
                            $fieldType = 'email';
                        } elseif (in_array($columnValue, ['male', 'female', 'other'], true)) {
                            $fieldType = 'radio';
                        } elseif (strlen($columnValue) > 100) {
                            $fieldType = 'textarea';
                        }

                        DB::table('form_submission_tabledata')->insert([
                            'formid' => $formId,
                            'uid' => $userId,
                            'section_id' => $sectionId,
                            'row_index' => $rowIndex,
                            'col_index' => $colIndex,
                            'column_key' => $columnKey,
                            'field_type' => $fieldType,
                            'column_value' => $columnValue,
                            'file_path' => $filePath,
                            'timecreated' => $timestamp,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $tableDataInserted = true;
                    }
                }
            }

            if (!$dynamicFields && !$tableDataInserted) {
                return redirect()->back()->with('error', 'Nothing to submit.');
            }

            return redirect()->back()->with('success', 'Form submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while submitting the form. Please try again later.');
        }
    }

    //choose path
    // public function choosePath()
    // {
    //     $pathPage = PathPage::with(['faqs' => function ($query) {
    //         $query->take(5); // Load only 5 FAQs
    //     }])->first();

    //     return view('fc.path', compact('pathPage'));
    // }


    public function choosePath(Request $request)
    {
        $this->fcRegistrationIntent->ingestFormQuery($request);

        $pathPage = PathPage::first();

        // Defaults
        $showRegistration = false;
        $showExemption    = false;

        if ($pathPage) {
            $today = Carbon::today();

            // Only allow registration if course dates are valid
            if (
                $pathPage->course_start_date &&
                $pathPage->course_end_date &&
                $pathPage->registration_start_date &&
                $pathPage->registration_end_date &&
                $pathPage->course_end_date->gt($pathPage->course_start_date) &&
                $today->between($pathPage->registration_start_date, $pathPage->registration_end_date)
            ) {
                $showRegistration = true;
            }

            // Only allow exemption if registration start exists and within course
            if (
                $pathPage->exemption_start_date &&
                $pathPage->exemption_end_date &&
                $today->between($pathPage->exemption_start_date, $pathPage->exemption_end_date)
            ) {
                $showExemption = true;
            }
        }

        $intentQuery = $this->intentQueryForFcFormLinks();

        return view('fc.path', compact('pathPage', 'showRegistration', 'showExemption', 'intentQuery'));
    }

    /**
     * @return array<string, string>
     */
    private function intentQueryForFcFormLinks(): array
    {
        $id = session(FcRegistrationIntentService::SESSION_FORM_ID);
        if (! is_numeric($id) || (int) $id < 1) {
            return [];
        }

        return ['form' => FcEncryptedFormId::encode((int) $id)];
    }



    // path page method

    public function pathPageForm()
    {
        $pathPage = PathPage::with('faqs')->first(); // Only one record expected
        return view('admin.forms.path', compact('pathPage'));
    }

    // Save or update path page
    public function pathPageSave(Request $request)
    {

        $oneDayBeforeEnd   = $request->course_end_date
            ? Carbon::parse($request->course_end_date)->subDay()->toDateString()
            : null;

        $oneDayBeforeStart = $request->course_start_date
            ? Carbon::parse($request->course_start_date)->subDay()->toDateString()
            : null;

        $request->validate([
            'register_course'    => 'required|string',
            'apply_exemption'    => 'required|string',
            'already_registered' => 'required|string',
            'guidelines'         => 'required|string',

            // Course Dates
            'course_start_date'  => ['required', 'date', 'after_or_equal:today'],
            'course_end_date'    => ['required', 'date', 'after:course_start_date', 'after_or_equal:today'],

            // Registration Dates
            'registration_start_date' => [
                'nullable',
                'date',
                'before:course_start_date', // strictly before course start date
            ],
            'registration_end_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
                'after_or_equal:registration_start_date',
                'before_or_equal:' . $oneDayBeforeStart, // course_start_date - 1 day
            ],


            // Exemption Dates
            'exemption_start_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'exemption_end_date' => [
                'nullable',
                'date',
                'after_or_equal:exemption_start_date',
                'before_or_equal:' . $oneDayBeforeEnd, // <= course_end_date - 1
            ],

            // FAQs
            'faq_header.*'  => 'nullable|string',
            'faq_content.*' => 'nullable|string',
        ]);


        DB::beginTransaction();

        try {
            $existingPage = PathPage::first();

            $pathPage = PathPage::updateOrCreate(
                ['id' => $existingPage?->id],
                [
                    'register_course'       => $request->register_course,
                    'apply_exemption'       => $request->apply_exemption,
                    'already_registered'    => $request->already_registered,
                    'guidelines'            => $request->guidelines,

                    'course_start_date'     => $request->course_start_date ? Carbon::parse($request->course_start_date)->format('Y-m-d') : null,
                    'course_end_date'       => $request->course_end_date ? Carbon::parse($request->course_end_date)->format('Y-m-d') : null,

                    'registration_start_date' => $request->registration_start_date ? Carbon::parse($request->registration_start_date)->format('Y-m-d') : null,
                    'registration_end_date'   => $request->registration_end_date ? Carbon::parse($request->registration_end_date)->format('Y-m-d') : null,

                    'exemption_start_date' => $request->exemption_start_date ? Carbon::parse($request->exemption_start_date)->format('Y-m-d') : null,
                    'exemption_end_date'   => $request->exemption_end_date ? Carbon::parse($request->exemption_end_date)->format('Y-m-d') : null,
                ]
            );

            // Remove old FAQs
            $pathPage->faqs()->delete();

            // Re-insert FAQs
            foreach ($request->faq_header ?? [] as $index => $header) {
                $content = $request->faq_content[$index] ?? null;
                if ($header || $content) {
                    PathPageFaq::create([
                        'path_page_id' => $pathPage->id,
                        'header'       => $header,
                        'content'      => $content,
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Path Page saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PathPage Save Error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while saving the Path Page.');
        }
    }


    //destroy method for path page faq
    public function destroyFaq($id)
    {
        DB::table('fc_path_page_faqs')->where('id', $id)->delete();

        return back()->with('success', 'FAQ deleted successfully.');
    }

    //all faqs method
    public function allFaqs()
    {
        $faqs = PathPageFaq::all(); // Fetch all FAQs
        return view('fc.all-faqs', compact('faqs'));
    }


    public function showExemptionCategory(Request $request)
    {
        $this->fcRegistrationIntent->ingestFormQuery($request);

        $userMobile = session('fc_user_mobile');
        $webAuth = session('fc_user_web_auth');
        if ($userMobile && $webAuth) {
            $registration = DB::table('fc_registration_master')
                ->where('contact_no', $userMobile)
                ->where('web_auth', $webAuth)
                ->first();

            if ($registration && ($blocked = $this->rosterGuard->exemptionBlockedReason($registration))) {
                return $this->redirectToChoosePathWithWarning($blocked);
            }
        }

        $exemptions = DB::table('fc_exemption_master')
            ->where('is_notice', false)
            ->where('visible', true)
            ->orderBy('pk')
            ->get();

        // Fetch the important notice
        $notice = DB::table('fc_exemption_master')
            ->where('is_notice', true)
            ->first();

        return view('fc.exemption_category', compact('exemptions', 'notice'));
    }




    public function exemptionIndex()
    {

        $headings = ExemptionCategory::with(['creator', 'updater'])
            ->where('is_notice', 0)
            ->paginate(10);

        $notice = ExemptionCategory::with(['creator', 'updater'])
            ->where('is_notice', true)
            ->first();

        return view('admin.forms.exemption_category', compact('headings', 'notice'));
    }


    public function exemptionCreate()
    {
        return view('admin.forms.exemption.exemption_cat_create');
    }

    // Store new heading and subheading
    public function exemptionStore(Request $request)
    {
        $request->validate([
            'Exemption_name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);


        DB::table('fc_exemption_master')->insert([
            'Exemption_name' => $request->Exemption_name,
            'description' => $request->description,
            'is_notice' => false,
            'created_by' => auth()->check() ? auth()->id() : null,
            // 'modified_by' => auth()->check() ? auth()->id() : null,
            'Created_date' => now(),
        ]);

        return redirect()->route('admin.exemptionIndex')->with('success', 'Exemption category added successfully.');
    }

    // Show form to edit heading/description
    public function exemptionEdit($id)
    {

        $item = DB::table('fc_exemption_master')
            ->where('pk', $id)
            ->where('is_notice', false)
            ->first();

        if (!$item) {
            return redirect()->route('admin.exemptionIndex')->with('error', 'Entry not found.');
        }

        return view('admin.forms.exemption.exemption_cat_edit', compact('item'));
    }

    // Update heading/description
    public function exemptionUpdate(Request $request, $id)
    {
        $request->validate([
            'Exemption_name' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        DB::table('fc_exemption_master')->where('pk', $id)->update([
            'Exemption_name' => $request->Exemption_name,
            'description' => $request->description,
            'modified_by' => auth()->check() ? auth()->user()->id : null,
            'Modified_date' => now(),
        ]);


        return redirect()->route('admin.exemptionIndex')->with('success', 'Exemption category updated successfully.');
    }

    // Update or create the important notice
    public function exemptionUpdateNotice(Request $request)
    {
        $request->validate([
            'important_notice' => 'required|string',
        ]);

        $existingNotice = DB::table('fc_exemption_master')->where('is_notice', true)->first();

        if ($existingNotice) {
            // Update existing notice
            DB::table('fc_exemption_master')->where('pk', $existingNotice->pk)->update([
                'description' => $request->important_notice,
                'Modified_by' => auth()->check() ? auth()->user()->id : null,
                'Modified_date' => now(),
            ]);
        } else {
            // Insert new notice
            DB::table('fc_exemption_master')->insert([
                'Exemption_name' => 'Important Notice',
                'description' => $request->important_notice,
                'is_notice' => true,
                'created_by' => auth()->check() ? auth()->user()->id : null,
                'Created_date' => now(),
            ]);
        }

        return redirect()->route('admin.exemptionIndex')->with('success', 'Important notice updated successfully.');
    }

    //show exemption application form
    public function exemptionApplication($id)
    {
        $exemption = DB::table('fc_exemption_master')
            ->where('pk', $id)
            ->where('visible', 1)
            ->first();

        if (!$exemption) {
            abort(404, 'Exemption category not found.');
        }

        return view('fc.exemption_application', [
            'exemption' => $exemption,
            'medicalDocMaxKb' => self::MEDICAL_DOC_MAX_KB,
            'medicalDocMaxBytes' => self::MEDICAL_DOC_MAX_KB * 1024,
        ]);
    }

    // apply exemption store
    public function apply_exemptionstore(Request $request)
    {
        $rules = [
            'ex_mobile' => 'required|digits_between:7,15',
            'reg_web_code' => 'required|string',
            'exemption_category' => 'required|exists:fc_exemption_master,Pk',
            'captcha' => 'required|captcha',
            'course' => 'nullable|string|max:255',   // Blade field -> DB previous_fc_course_name
            'year' => 'nullable|digits:4',           // Blade field -> DB fc_date
            'institution_name' => 'nullable|string|max:255', // Blade field -> DB previous_fc_institution_name
            'roll_number' => 'nullable|string|max:50', // Blade field -> DB appearing_roll_no
        ];

        // Custom error messages
        $messages = [
            'ex_mobile.required' => 'Mobile number is required.',
            'ex_mobile.digits_between' => 'Mobile number must be between 7 and 15 digits.',

            'reg_web_code.required' => 'Web authentication code is required.',
            'reg_web_code.string' => 'Web authentication code must be a valid string.',

            'exemption_category.required' => 'Please select an exemption category.',
            'exemption_category.exists' => 'The selected exemption category is invalid.',

            'captcha.required' => 'Captcha is required.',
            'captcha.captcha' => 'The captcha you entered is incorrect. Please try again.',
            'course.string' => 'Course name must be a valid string.',
            'year.digits' => 'Year must be a valid 4-digit year.',
            'institution_name.string' => 'Institution name must be a valid string.',
            'roll_number.string' => 'Roll number must be a valid string.',
        ];

        $exemption = DB::table('fc_exemption_master')
            ->where('Pk', $request->exemption_category)
            ->first();

        if ($request->hasFile('medical_doc')) {
            $ext = strtolower($request->file('medical_doc')->getClientOriginalExtension());
            if (in_array($ext, ['xls', 'xlsx'])) {
                return redirect()->back()
                    ->withErrors(['medical_doc' => 'Excel files are not allowed.'])
                    ->withInput();
            }
        }

        if ($exemption && stripos($exemption->Exemption_name, 'completed foundation course') !== false) {
            $rules['course'] = 'required|string|max:255';
            $rules['year'] = 'required|digits:4';
            $rules['institution_name'] = 'required|string|max:255';
            $messages['course.required'] = 'Course name is required for this exemption category.';
            $messages['year.required'] = 'Year is required for this exemption category.';
            $messages['institution_name.required'] = 'Institution name is required for this exemption category.';
        }

        if ($exemption && strtolower($exemption->Exemption_name) === 'medical') {
            $rules['medical_doc'] = 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:'.self::MEDICAL_DOC_MAX_KB;
            $messages['medical_doc.required'] = 'Medical exemption document is required for medical exemptions.';
            $messages['medical_doc.max'] = 'Medical document must not be larger than '.(self::MEDICAL_DOC_MAX_KB / 1024).' MB.';
            $messages['medical_doc.mimes'] = 'Medical document must be PDF, Word (.doc, .docx), JPG, JPEG, or PNG.';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('captcha_refresh', true);
        }

        // Check against fc_registration_master
        $registration = DB::table('fc_registration_master')
            ->where('contact_no', $request->ex_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->first();

        if (!$registration) {
            return redirect()->back()
                ->withErrors(['reg_web_code' => 'Invalid Web Code or Mobile Number.'])
                ->withInput();
        }

        if ($blocked = $this->rosterGuard->exemptionBlockedReason($registration)) {
            return $this->redirectToChoosePathWithWarning($blocked);
        }

        $username = $registration->user_id ?? null;

        $medicalDocPath = null;
        if ($request->hasFile('medical_doc') && $request->file('medical_doc')->isValid()) {
            $medicalDocPath = $request->file('medical_doc')->store('medical_docs', 'public');
        }

        // Update if exists, otherwise insert
        DB::table('fc_registration_master')->updateOrInsert(
            [
                'contact_no' => $request->ex_mobile,
                'web_auth' => $request->reg_web_code
            ],
            [
                'user_id' => $username,
                'fc_exemption_master_pk' => $request->exemption_category,
                'medical_exemption_doc' => $medicalDocPath,
                'previous_fc_course_name' => !empty($request->course) ? $request->course : null,
                'fc_date'                 => !empty($request->year) ? $request->year : null,
                // 'previous_fc_institution_name' => !empty($request->institution_name) ? $request->institution_name : null,
                'appearing_roll_no'       => !empty($request->roll_number) ? $request->roll_number : null,

            ]
        );

        DB::table('fc_registration_master')
            ->where('contact_no', $request->ex_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->update([
                'application_type' => FcRosterApplicationGuardService::APPLICATION_EXEMPTION,
                'exemption_count' => DB::raw('GREATEST(COALESCE(exemption_count, 0), 1)'),
            ]);

        return redirect()->route('fc.thank_you')->with('success', 'Exemption form submitted successfully.');
    }


    //show reset password form
    public function showForgotPasswordForm()
    {
        return view('fc.forgot_password');
    }

    //reset password method
    public function resetPassword(Request $request)
    {
        // @dd($request->all());
        $request->validate([
            'mobile_number' => 'required|digits:10',
            'new_password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[\W_]).+$/'
            ],
            'confirm_password' => 'required|same:new_password',
        ]);

        // Check user exists by mobile number
        $user = DB::table('user_credentials')
            ->where('mobile_no', $request->mobile_number)
            ->first();
        if (!$user) {
            // return back()->withErrors(['mobile_number' => 'Mobile number not found.'])->withInput();
            return back()
                ->withErrors(['mobile_number' => 'Mobile number not found.'])
                ->withInput();
        }

        // Update password
        DB::table('user_credentials')
            ->where('mobile_no', $request->mobile_number)
            ->update([
                'jbp_password' => Hash::make($request->new_password),
            ]);

        // return redirect()->route('fc.login')->with('success', 'Password reset successful. Please login with new credentials.');
        return redirect()->route('fc.login', $this->intentQueryForFcFormLinks())
            ->with('sweet_success', 'Password reset successful. Please login with new credentials.');
    }

    //verify web auth forgot password
    public function verifyWebAuth(Request $request)
    {

        // @dd($request->all());
        $request->validate([
            'mobile_number' => 'required|digits:10',
            'web_auth' => 'required|string',
        ]);

        // Step 1: Check if the mobile number and web_auth exist in fc_registration_master
        $user = DB::table('fc_registration_master')
            ->where('contact_no', $request->mobile_number)
            ->where('web_auth', $request->web_auth)
            ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.']);
        }

        // Step 2: Get the corresponding username from user_credentials
        $credentials = DB::table('user_credentials')
            ->where('mobile_no', $request->mobile_number)
            ->first();

        if (!$credentials) {
            return response()->json(['success' => false, 'message' => 'User credentials not found.']);
        }

        return response()->json([
            'success' => true,
            'user_name' => $credentials->user_name,
        ]);
    }

    // app/Http/Controllers/FoundationCourseController.php



    // public function student_status()
    // {
    //     // Get counts for each status
    //     $notResponded = DB::table('fc_registration_master')
    //                     ->where('admission_status', 0)
    //                     ->count();

    //     $registered = DB::table('fc_registration_master')
    //                   ->where('admission_status', 1)
    //                   ->count();

    //     $exemption = DB::table('fc_registration_master')
    //                  ->where('application_type', 2)
    //                  ->count();

    //     $incomplete = DB::table('fc_registration_master')
    //                   ->where('final_submit', 1)
    //                   ->count();

    //     // Get service-wise counts
    //     $services = DB::table('fc_registration_master')
    //                 ->select('service_master_pk', DB::raw('count(*) as count'))
    //                 ->groupBy('service_master_pk')
    //                 ->get();

    //     // Get data for each tab
    //     $notRespondedData = DB::table('fc_registration_master')
    //                         ->where('admission_status', 0)
    //                         ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
    //                         ->get();

    //     $registeredData = DB::table('fc_registration_master')
    //                      ->where('admission_status', 1)
    //                      ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
    //                      ->get();

    //     $exemptionData = DB::table('fc_registration_master')
    //                     ->where('application_type', 2)
    //                     ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
    //                     ->get();

    //     $incompleteData = DB::table('fc_registration_master')
    //                     ->where('final_submit', 1)
    //                     ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
    //                     ->get();

    //     return view('fc.foundation_course_status', compact(
    //         'notResponded',
    //         'registered',
    //         'exemption',
    //         'incomplete',
    //         'services',
    //         'notRespondedData',
    //         'registeredData',
    //         'exemptionData',
    //         'incompleteData'
    //     ));
    // }

    public function student_status(Request $request)
    {
        $payload = $this->buildStatusPayload($request);

        return view('fc.status', [
            'activeTab' => $payload['activeTab'],
            'counts' => $this->fcRegistrationStatus->counts(),
            'courseMeta' => $this->fcRegistrationStatus->courseMeta(),
            'tabMeta' => $payload['tabMeta'],
            'serviceList' => $payload['serviceList'],
            'participants' => $payload['participants'],
            'loggedUserCount' => auth()->check() ? 1 : 0,
        ]);
    }

    public function student_statusFragment(Request $request)
    {
        $payload = $this->buildStatusPayload($request);

        return response()->json([
            'html' => view('fc.status._results', [
                'activeTab' => $payload['activeTab'],
                'tabMeta' => $payload['tabMeta'],
                'serviceList' => $payload['serviceList'],
                'participants' => $payload['participants'],
            ])->render(),
            'tab' => $payload['activeTab'],
            'theme' => $payload['tabMeta']['theme'],
            'list_title' => $payload['tabMeta']['list_title'],
        ]);
    }

    private function buildStatusPayload(Request $request): array
    {
        $activeTab = $this->fcRegistrationStatus->resolveTab($request->query('tab'));
        $page = max(1, (int) $request->query('page', 1));
        $tabMeta = $this->fcRegistrationStatus->tabMeta($activeTab);

        $serviceList = $activeTab === FcRegistrationStatusService::TAB_SERVICE
            ? $this->fcRegistrationStatus->serviceWiseCounts()
            : collect();

        $participants = $activeTab !== FcRegistrationStatusService::TAB_SERVICE
            ? $this->fcRegistrationStatus->participantsForTab($activeTab, 25, $page)
            : null;

        return compact('activeTab', 'tabMeta', 'serviceList', 'participants');
    }
}
