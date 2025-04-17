<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ProgrammeRequest;
class CourseController extends Controller
{
    public function index()
    {
        return view('admin.programme.index');
    }

    public function create()
    {
        return view('admin.programme.create');
    }

    public function edit()
    {
        return view('admin.programme.edit');
    }

    public function store(ProgrammeRequest $request)
    {
        try {
            $validated = $request->validated();
            
            $validated['courseyear'] = date('Y-m', strtotime($validated['courseyear']));
            $validated['startdate'] = date('Y-m-d', strtotime($validated['startdate']));
            $validated['enddate'] = date('Y-m-d', strtotime($validated['enddate']));

            dd($validated);
            // $course = Course::create($validated);

            return redirect()->route('programme.index')->with('success', 'Course created successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
