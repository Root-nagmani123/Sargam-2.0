<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\PermissionSetting;

class PermissionSettingController extends Controller
{
    public function index()
    {
        $permissionsettings = PermissionSetting::all();
        return view('mess.permissionsettings.index', compact('permissionsettings'));
    }

    public function create()
    {
        return view('mess.permissionsettings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);
        PermissionSetting::create($request->all());
        return redirect()->route('admin.mess.permissionsettings.index')->with('success', 'Permission setting added successfully');
    }
}
