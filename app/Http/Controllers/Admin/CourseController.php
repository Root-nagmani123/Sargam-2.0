<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ProgrammeRequest;
use App\Models\EmployeeMaster;

class CourseController extends Controller
{
    public function index()
    {
        return view('admin.programme.index');
    }

    public function create()
    {
        $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeList();
        $deputationEmployeeList = $deputationEmployeeList->map(function ($item) {
            $item['name'] = $item->first_name . ' ' . $item->last_name;
            return $item;
        });
        $deputationEmployeeList = $deputationEmployeeList->toArray();
        $deputationEmployeeList = array_column($deputationEmployeeList, 'name', 'pk');
        
        return view('admin.programme.create', compact('deputationEmployeeList'));
    }

    public function edit()
    {
        return view('admin.programme.edit');
    }

    public function store(ProgrammeRequest $request)
    {
        try {
            $validated = $request->validated();
            \Log::info($validated);
            
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
