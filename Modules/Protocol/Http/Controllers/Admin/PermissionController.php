<?php

namespace Modules\Protocol\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Http\Requests\ReviewProtocolRequest;
use App\Models\HostelBuildingMaster;
use Spatie\Permission\Models\Permission;
use App\Models\{DepartmentMaster,EmployeeMaster,User};
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    const  DEPARTMENT= 'Protocol';

    public function permissions()
    {
        $department = DepartmentMaster::where('department_name', self::DEPARTMENT)->first();
        $employees = User::getEmployeesAndFacultyForComplaint($department->pk);
        return view('protocol::admin.permissions', compact('employees'));
    }

    // GET — return this employee's permission state
    public function show($user)
    {
        $user = User::find($user);
        $allPermissions = Permission::where('name','LIKE','protocol.%')->select('name','id')->get();
        $granted = $user->permissions()->pluck('id')->toArray();
        $permissions = $allPermissions->map(fn ($p) => [
            ...$p->toArray(),
            'checked' => in_array($p->id, $granted),
            'locked'  => false,
        ]);

        return response()->json(['permissions' => $permissions]);
    }

    public function update(Request $request, User $employee)
    {
        $employee->permissions()->sync($request->input('permissions', []));
        return response()->json(['status' => 'ok']);
    }

}