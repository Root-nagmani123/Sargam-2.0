<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CourseMaster, FacultyMaster, CounsellorGroup};
use App\DataTables\CounsellorGroupDataTable;

class CounsellorGroupController extends Controller
{
    public function index(CounsellorGroupDataTable $dataTable)
    {
        return $dataTable->render('admin.counsellor_group.index');
    }

    /**
     * Show the form for creating a new counsellor group.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $faculties = FacultyMaster::pluck('full_name', 'pk')->toArray();
        return view('admin.counsellor_group.create', compact('courses', 'faculties'));
    }

    /**
     * Show the form for editing an existing counsellor group.
     *
     * @param string $id Encrypted counsellor group ID
     * @return \Illuminate\View\View
     */
    public function edit(string $id)
    {
        $counsellorGroup = CounsellorGroup::find(decrypt($id));
        $courses = CourseMaster::pluck('course_name', 'pk')->toArray();
        $faculties = FacultyMaster::pluck('full_name', 'pk')->toArray();
        return view('admin.counsellor_group.create', compact('counsellorGroup', 'courses', 'faculties'));
    }

    /**
     * Store or update a counsellor group in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'course_master_pk' => 'required|exists:course_master,pk',
                'counsellor_group_name' => 'required|string|max:100',
                'faculty_master_pk' => 'nullable|exists:faculty_master,pk',
                'active_inactive' => 'nullable|in:1,2'
            ]);

            if ($request->pk) {
                $counsellorGroup = CounsellorGroup::find(decrypt($request->pk));
                $message = 'Counsellor Group updated successfully.';
            } else {
                $counsellorGroup = new CounsellorGroup();
                $message = 'Counsellor Group created successfully.';
            }

            $counsellorGroup->course_master_pk = $request->course_master_pk;
            $counsellorGroup->counsellor_group_name = $request->counsellor_group_name;
            $counsellorGroup->faculty_master_pk = $request->faculty_master_pk ?? null;
            $counsellorGroup->active_inactive = $request->active_inactive ?? 1;
            $counsellorGroup->save();

            return redirect()->route('counsellor.group.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Delete a counsellor group.
     *
     * @param string $id Encrypted counsellor group ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(string $id)
    {
        try {
            $counsellorGroup = CounsellorGroup::findOrFail(decrypt($id));
            $counsellorGroup->delete();
            
            return redirect()->route('counsellor.group.index')->with('success', 'Counsellor Group deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

