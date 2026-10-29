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
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    const  DEPARTMENT= 'Protocol';

    public function permissions()
    {
        $department = DepartmentMaster::where('department_name', self::DEPARTMENT)->first();
        $employees = User::getEmployeesAndFacultyForComplaint($department->pk);

        // Same photo source as the header (get_profile_pic): employee_master.profile_picture
        // under storage/, falling back to the app's default picture
        $pictures = EmployeeMaster::whereIn('pk', $employees->pluck('employee_pk'))->pluck('profile_picture', 'pk');
        $employees->each(function ($employee) use ($pictures) {
            $picture = $pictures[$employee->employee_pk] ?? null;
            $employee->photo_url = $picture ? asset('storage/' . $picture) : asset('images/dummypic.jpeg');
        });

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
        // Only protocol.* permissions are shown on this screen, so only those are replaced;
        // the user's direct permissions for other modules are left untouched
        $protocolIds = Permission::where('name', 'LIKE', 'protocol.%')->pluck('id');
        $selected = $protocolIds->intersect(array_map('intval', $request->input('permissions', [])));

        DB::transaction(function () use ($employee, $protocolIds, $selected) {
            $employee->permissions()->detach($protocolIds->all());
            $employee->permissions()->attach($selected->all());
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['status' => 'ok']);
    }

}