<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SubjectModuleMaster;
use Illuminate\Http\Request;


class SubjectModuleController extends Controller
{
    public function index()
    {
        $search = request('search');

        $perPage = (int) request('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $modules = SubjectModuleMaster::when($search, function ($q) use ($search) {
                $q->where('module_name', 'like', "%$search%");
            })
            ->orderBy('created_date', 'desc')
            ->paginate($perPage)
            ->appends(['search' => $search, 'per_page' => $perPage]);

        $smModuleEditData = [];
        foreach ($modules as $module) {
            $smModuleEditData[$module->pk] = [
                'module_name' => $module->module_name,
                'active_inactive' => $module->active_inactive,
            ];
        }
        if (request()->filled('open_edit_module')) {
            $extra = SubjectModuleMaster::find(request('open_edit_module'));
            if ($extra) {
                $smModuleEditData[$extra->pk] = [
                    'module_name' => $extra->module_name,
                    'active_inactive' => $extra->active_inactive,
                ];
            }
        }

        return view('admin.subject_module.index', compact('modules', 'smModuleEditData'));
    }

    public function create()
    {
        return redirect()->route('subject-module.index', ['open_add_module' => 1]);
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
        SubjectModuleMaster::findOrFail($id);

        return redirect()->route('subject-module.index', ['open_edit_module' => $id]);
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
