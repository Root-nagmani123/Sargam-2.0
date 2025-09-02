<?php

namespace App\Http\Controllers\Admin\Registration;

// namespace App\Models;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FrontPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\PathPage;
use App\Models\PathPageFaq;
use App\Models\ExemptionCategory;
use App\Models\FoundationCourseStatus;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class FrontPageController extends Controller
{
    public function index()
    {
        $data = FrontPage::first(); // fetch latest/only record
        return view('admin.forms.home_page', compact('data'));
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
    public function foundationIndex()
    {
        $data = FrontPage::first(); // Fetch the first row from front_pages table
        return view('fc.front_page', compact('data'));
    }

    //Authentication method
    public function authindex()
    {
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
            // return back()
            //     ->withErrors(['web_auth' => 'Invalid contact number or web auth code.'])
            //     ->withInput();
            return back()
                ->withErrors(['web_auth' => 'Invalid contact number or web auth code.'])
                ->withInput();
        }

        // Step 3: Check if already registered (application_type = 1)
        $alreadyRegistered = DB::table('fc_registration_master')
            ->where('contact_no', $request->reg_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->where('is_registered', 1) // Only block if truly registered
            ->exists();


        if ($alreadyRegistered) {
            return redirect()->route('fc.choose.path')->with([
                'warning' => 'You have already registered. Please proceed with exemption or contact support.'
            ]);
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

        return redirect()->route('credential.registration.create')->with([
            'sweet_success' => 'You have been successfully authenticated. Please create your credentials.'
        ]);
    }

    // Show the registration form
    public function credential_index()
    {
        return view('fc.credentials'); // Adjust blade path if needed
    }

    // Store user credentials
    public function credential_store(Request $request)
    {
        // @dd($request->all());
        $request->validate([
            // 'reg_name' => 'required|string|max:100|unique:user_credentials,user_name',
            'reg_name' => [
                'required',
                'string',
                'min:6',
                'max:20',
                'regex:/^(?=.{6,20}$)(?!.*[_.]{2})[a-zA-Z][a-zA-Z0-9._]*[a-zA-Z0-9]$/',
                'unique:user_credentials,user_name',
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
            'reg_name.regex' => 'username must start with a letter and can contain letters, numbers, dots, and underscores. 
            No consecutive dots/underscores or ending with special characters.',
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
            ->where('contact_no', $request->ex_mobile)
            ->where('web_auth', $request->reg_web_code)
            ->first();

        $currentAppType = $registration->application_type ?? 0;
        $isRegistered   = $registration->is_registered ?? 0;

        // Prevent duplicate registration
        if ($isRegistered == 1) {
            return back()->withErrors(['reg_mobile' => 'This mobile number is already registered.'])->withInput();
        }

        // Determine if we need to update the type to "registered"
        $newAppType = ($currentAppType == 0 || $currentAppType == 2) ? 1 : $currentAppType;

        // Update only if necessary
        if ($newAppType != $currentAppType || $isRegistered == 0) {

            DB::table('fc_registration_master')
                ->where('contact_no', $request->reg_mobile)
                // ->where('web_auth', $request->reg_web_code)
                ->update([
                    'application_type' => $newAppType,
                    'is_registered' => 1, //  mark registration
                ]);
        }

        $hashedPassword = Hash::make($request->reg_password);

        DB::table('user_credentials')->updateOrInsert(
            ['mobile_no' => $request->reg_mobile], // Condition to find existing user by mobile
            [
                'user_name' => $request->reg_name,
                'jbp_password' => $hashedPassword,
                'reg_date' => now(),
                'Active_inactive' => 1,
                'last_login' => now(), // Optional: include this if needed
            ]
        );

        // return redirect()->route('fc.login')->with('success', 'Credentials created successfully.');
        return redirect()->route('fc.login')->with('sweet_success', 'Credentials created successfully.');
    }

    // Show the login form
    public function showLoginForm()
    {
        return view('fc.fc_login'); // Adjust to your login blade path
    }

    //user login verification
    public function verifyLogin(Request $request)
    {
        // Validate input
        $request->validate([
            'reg_name' => 'required|string',
            'reg_password' => 'required|string',
        ]);

        //trim extra spaces
        $regName = trim($request->reg_name);

        // Fetch user record from user_credentials
        $user = DB::table('user_credentials')
            ->where('user_name', $regName)
            ->where('Active_inactive', 1)
            ->first();

        // Check if user exists and password matches
        if ($user && Hash::check($request->reg_password, $user->jbp_password)) {
            // You may store user session here (optional)
            $form = DB::table('local_form')
                ->where('visible', 1)
                ->orderBy('id')
                ->first();

            if (!$form) {
                return back()->withErrors(['form_error' => 'No active form available.'])->withInput();
            }

            // Step 5: Store user session (optional)
            session([
                'fc_user_id' => $user->pk,
                'fc_user_name' => $user->user_name
            ]);

            // Step 6: Redirect with form id
            // return redirect()->route('forms.show', $form->id) // Assuming you have a route named 'forms.show'
            //     ->with('success', 'Login successful!');
            // return redirect()->route('fc.register_form')->with('success', 'Login successful!');
            return redirect()->route('forms.show', ['formId' => 30])->with('success', 'Login successful!');
        }


        // If invalid credentials
        return back()->withErrors(['login' => 'Invalid username or password.'])->withInput();
    }

    //choose path
    // public function choosePath()
    // {
    //     $pathPage = PathPage::with(['faqs' => function ($query) {
    //         $query->take(5); // Load only 5 FAQs
    //     }])->first();

    //     return view('fc.path', compact('pathPage'));
    // }


    public function choosePath()
    {
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

        return view('fc.path', compact('pathPage', 'showRegistration', 'showExemption'));
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
        $userMobile = session('fc_user_mobile');
        $webAuth = session('fc_user_web_auth');
        // Check if user has already applied for exemption
        // $hasApplied = DB::table('fc_registration_master')
        //     ->where('contact_no', $userMobile)
        //     ->where('web_auth', $webAuth)
        //     ->where('fc_exemption_master_pk', '!=', 0) // Check if exemption is applied
        //     ->exists();

        // $hasApplied = $hasApplied ? true : false; // Convert to boolean
        // Fetch exemption categories
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
            ->get();

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

        return view('fc.exemption_application', compact('exemption'));
    }

    // apply exemption store
    public function apply_exemptionstore(Request $request)
    {
        $rules = [
            'ex_mobile' => 'required|digits_between:7,15',
            'reg_web_code' => 'required|string',
            'exemption_category' => 'required|exists:fc_exemption_master,Pk',
            'captcha' => 'required|captcha',
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

        if ($exemption && strtolower($exemption->Exemption_name) === 'medical') {
            $rules['medical_doc'] = 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120';
            $messages['medical_doc.required'] = 'Medical exemption document is required for medical exemptions.';
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

        // Check if exemption already applied
        // $hasApplied = DB::table('fc_registration_master')
        //     ->where('contact_no', $request->ex_mobile)
        //     ->where('web_auth', $request->reg_web_code)
        //     ->where('fc_exemption_master_pk', '!=', 0)
        //     ->exists();


        // if ($hasApplied) {
        // return redirect()->back()
        //     ->withInput()
        //     ->with('has_applied', true); // This session value will trigger the modal
        //     if ($hasApplied) {
        //         return redirect()->back()
        //             ->withInput()
        //             ->with('already_applied', 'You have already applied for an exemption.');
        //     }
        // }

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
                // 'updated_at' => now(),
                // 'created_at' => now(), // This won't update if already exists
            ]
        );

        //  Safely increment exemption_count if registration found
        if ($registration && $registration->pk) {
            DB::table('fc_registration_master')
                ->where('pk', $registration->pk)
                ->update([
                    'exemption_count' => DB::raw('COALESCE(exemption_count, 0) + 1'),
                ]);
        }
        // Determine and update application_type if needed
        $currentAppType = $registration->application_type ?? null;

        if ($currentAppType === 0 || $currentAppType === '0' || $currentAppType === 1 || $currentAppType === '1') {
            DB::table('fc_registration_master')
                ->where('contact_no', $request->ex_mobile)
                ->where('web_auth', $request->reg_web_code)
                ->update(['application_type' => 2]);
        }

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
        return redirect()->route('fc.login')
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

    public function student_status()
    {
        // Get counts for each status using model constants
        $notResponded = FoundationCourseStatus::where('admission_status', FoundationCourseStatus::STATUS_NOT_RESPONDED)
            ->count();

        $registered = FoundationCourseStatus::where('admission_status', FoundationCourseStatus::STATUS_REGISTERED)
            ->count();

        $exemption = FoundationCourseStatus::where('application_type', FoundationCourseStatus::APPLICATION_EXEMPTION)
            ->count();

        $incomplete = FoundationCourseStatus::where('final_submit', FoundationCourseStatus::SUBMISSION_DRAFT)
            ->count();

        // Get service-wise counts with eager loading
        // $services = FoundationCourseStatus::with('service')
        //     ->select('service_master_pk', \DB::raw('count(*) as count'))
        //     ->groupBy('service_master_pk')
        //     ->get();
        $services = FoundationCourseStatus::with(['service' => function ($query) {
            $query->select('pk', 'service_short_name'); // Only select needed columns
        }])
            ->select('service_master_pk', DB::raw('count(*) as count'))
            ->groupBy('service_master_pk')
            ->get();
        // @dd($services);

        // Get data for each tab with pagination
        // $notRespondedData = FoundationCourseStatus::where('admission_status', FoundationCourseStatus::STATUS_NOT_RESPONDED)
        //     ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
        //     ->paginate(10);
        $notRespondedData = FoundationCourseStatus::with('service')
            ->where('admission_status', 0) // Directly using the status value
            ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
            ->paginate(10);

        $registeredData = FoundationCourseStatus::where('admission_status', FoundationCourseStatus::STATUS_REGISTERED)
            ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
            ->paginate(10);

        $exemptionData = FoundationCourseStatus::where('application_type', FoundationCourseStatus::APPLICATION_EXEMPTION)
            ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
            ->paginate(10);

        $incompleteData = FoundationCourseStatus::where('final_submit', FoundationCourseStatus::SUBMISSION_DRAFT)
            ->select('first_name', 'middle_name', 'last_name', 'service_master_pk', 'rank')
            ->paginate(10);

        return view('fc.status', compact(
            'notResponded',
            'registered',
            'exemption',
            'incomplete',
            'services',
            'notRespondedData',
            'registeredData',
            'exemptionData',
            'incompleteData'
        ));
    }
}
