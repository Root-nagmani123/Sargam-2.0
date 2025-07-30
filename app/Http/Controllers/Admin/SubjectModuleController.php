<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SubjectModuleMaster;
use Illuminate\Http\Request;


class SubjectModuleController extends Controller
{
    function __construct() {
        $this->middleware('permission:subject-module.index', ['only' => ['index']]);
        $this->middleware('permission:subject-module.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:subject-module.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:subject-module.delete', ['only' => ['destroy']]);
    }
    public function index()
    {
        $modules = SubjectModuleMaster::all();
        return view('admin.subject_module.index', compact('modules'));
    }

    public function create()
    {
        return view('admin.subject_module.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'module_name' => 'required|string|max:200',
        'active_inactive' => 'required|in:0,1',
    ]);

    SubjectModuleMaster::create([
        'module_name' => $request->module_name,
        'active_inactive' => $request->active_inactive,
        'created_by' => auth()->id() ?? 1, // fallback to static ID if not using auth
        'created_date' => now(),
        'modified_date' => now(),
    ]);

    return redirect()->route('subject-module.index')->with('success', 'Module added successfully.');
}


    public function edit($id)
    {
        $module = SubjectModuleMaster::findOrFail($id);
        return view('admin.subject_module.edit', compact('module'));
    }

    public function update(Request $request, $id)
    {
        // Validate incoming data
        $request->validate([
            'module_name' => 'required|string|max:200',
            'active_inactive' => 'required|in:0,1',
        ]);
    
        // Find the module by ID
        $module = SubjectModuleMaster::findOrFail($id);
    
        // Update module details
        $module->module_name = $request->module_name;
        $module->active_inactive = $request->active_inactive;
        $module->modified_date = now();  // You can set a timestamp for the modification
    
        // Save the changes
        $module->save();
    
        // Redirect to the module index page with success message
        return redirect()->route('subject-module.index')->with('success', 'Module updated successfully.');
    }
    

    public function destroy($id)
    {
        SubjectModuleMaster::destroy($id);
        return back()->with('success', 'Module deleted.');
    }
}
