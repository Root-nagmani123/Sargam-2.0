<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
class PermissionController extends Controller
{
    public function index(PermissionDataTable $dataTable)
    {
        return $dataTable->render('admin.user_management.permissions.index');
    }

    public function create()
    {
        return view('admin.user_management.permissions.create', [
            'all_permissions' => Permission::all(),
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        Permission::create($request->all());
        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully');
    }

    public function edit(Permission $permission)
    {
        return view('admin.user_management.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $permission->update($request->all());
        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully');
    }

}
