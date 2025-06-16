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


class FrontPageController extends Controller
{
    public function index()
    {
        $data = FrontPage::first(); // fetch latest/only record
        // dd($frontPage);
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
            'coordinator_signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except(['important_updates', 'coordinator_signature']);
        $data['important_updates'] = html_entity_decode($request->input('important_updates'));

        if ($request->hasFile('coordinator_signature')) {
            $file = $request->file('coordinator_signature');
            $filename = 'signatures/' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('signatures'), $filename);
            $data['coordinator_signature'] = 'signatures/' . basename($filename);
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
            return back()
                ->withErrors(['web_auth' => 'Invalid contact number or web auth code.'])
                ->withInput();
        }

        // Step 3: Find the first visible form
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
            'fc_user_name' => $registration->name ?? null,
        ]);

        // Step 5: Redirect to form
        return redirect()->route('credential.registration.create')->with([
            'success' => 'Login successful. You can now create your credentials.'
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
        $request->validate([
            'reg_name' => 'required|string|max:100|unique:user_credentials,user_name',
            'reg_password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[\W_]).+$/', // at least one special character
            ],
            'reg_confirm_password' => 'required|same:reg_password',
        ], [
            'reg_name.unique' => 'The username has already been taken.',
            'reg_password.min' => 'The password must be at least 6 characters.',
            'reg_password.regex' => 'The password must contain at least one special character.',
            'reg_confirm_password.same' => 'The confirm password and password must match.',
        ]);


        // @dd($request->all());

        DB::table('user_credentials')->insert([
            'user_name' => $request->reg_name,
            'jbp_password' => Hash::make($request->reg_password), // Store hashed password
            'reg_date' => now(),
            'last_login' => now(),
            'Active_inactive' => 1,
        ]);

        return redirect()->route('fc.login')->with('success', 'Credentials created successfully.');
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

        // Fetch user record from user_credentials
        $user = DB::table('user_credentials')
            ->where('user_name', $request->reg_name)
            ->where('Active_inactive', 1)
            ->first();

        // Check if user exists and password matches
        if ($user && Hash::check($request->reg_password, $user->jbp_password)) {
            // You may store user session here (optional)
            session(['fc_user_id' => $user->pk, 'fc_user_name' => $user->user_name]);

            // Redirect to dashboard or form page
            return redirect()->route('fc.choose.path')->with('success', 'Login successful!');
        }

        // If invalid credentials
        return back()->withErrors(['login' => 'Invalid username or password.'])->withInput();
    }

    //choose path
    public function choosePath()
    {
        $pathPage = PathPage::with(['faqs' => function ($query) {
            $query->take(5); // Load only 5 FAQs
        }])->first();

        return view('fc.path', compact('pathPage'));
    }


    // path page method

    public function pathPageForm()
    {
        $pathPage = PathPage::with('faqs')->first(); // Only one record expected
        return view('admin.forms.path', compact('pathPage'));
    }

    public function pathPageSave(Request $request)
    {
        $request->validate([
            'register_course' => 'required|string',
            'apply_exemption' => 'required|string',
            'already_registered' => 'required|string',
            'faq_header.*' => 'nullable|string',
            'faq_content.*' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pathPage = PathPage::first();

            if ($pathPage) {
                $pathPage->update([
                    'register_course' => $request->register_course,
                    'apply_exemption' => $request->apply_exemption,
                    'already_registered' => $request->already_registered,
                ]);

                // Delete old FAQs
                $pathPage->faqs()->delete();
            } else {
                $pathPage = PathPage::create([
                    'register_course' => $request->register_course,
                    'apply_exemption' => $request->apply_exemption,
                    'already_registered' => $request->already_registered,
                ]);
            }

            // Insert new FAQs
            foreach ($request->faq_header ?? [] as $index => $header) {
                if ($header || ($request->faq_content[$index] ?? null)) {
                    PathPageFaq::create([
                        'path_page_id' => $pathPage->id,
                        'header' => $header,
                        'content' => $request->faq_content[$index] ?? '',
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Path Page saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error occurred while saving.');
        }
    }

    //destroy method for path page faq
    public function destroyFaq($id)
    {
        DB::table('path_page_faqs')->where('id', $id)->delete();

        return back()->with('success', 'FAQ deleted successfully.');
    }

    //all faqs method
    public function allFaqs()
    {
        $faqs = PathPageFaq::all(); // Fetch all FAQs
        return view('fc.all-faqs', compact('faqs'));
    }

    // Show exemption category form
    public function show_exemption_category()
    {
        $data = ExemptionCategory::first(); // Assuming only one row exists

        return view('admin.forms.exemption_category', compact('data'));
    }


    //save exemption admin category
    public function save_exemption_category(Request $request)
    {
        $request->validate([
            'cse_heading' => 'required|string|max:255',
            'cse_subheading' => 'required|string',

            'attended_heading' => 'required|string|max:255',
            'attended_subheading' => 'required|string',
            'medical_heading' => 'required|string|max:255',
            'medical_subheading' => 'required|string',

            'optout_heading' => 'required|string|max:255',
            'optout_subheading' => 'required|string',

            'important_notice' => 'nullable|string',
        ]);

        $data = $request->only([
            'cse_heading',
            'cse_subheading',
            'attended_heading',
            'attended_subheading',
            'medical_heading',
            'medical_subheading',
            'optout_heading',
            'optout_subheading',
            'important_notice',
        ]);

        ExemptionCategory::updateOrCreate(['pk' => 1], $data);

        return back()->with('success', 'Exemption Category saved successfully.');
    }

    //exemption category view
    // app/Http/Controllers/FrontPageController.php

public function showExemptionCategory()
{
    $data = DB::table('exemption_categories_data')->where('pk', 1)->first(); // Fetch the only row
    return view('fc.exemption_category', compact('data'));
}

}
