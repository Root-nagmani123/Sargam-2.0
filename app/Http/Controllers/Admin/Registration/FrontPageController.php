<?php

namespace App\Http\Controllers\Admin\Registration;

// namespace App\Models;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FrontPage;



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
}
