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
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Modules\Protocol\Services\ProtocolDirectory;

class PermissionController extends Controller
{
    const  DEPARTMENT= 'Protocol';

    /** The employees this screen lists, and so the only users it may grant to */
    private function protocolEmployees()
    {
        $department = DepartmentMaster::where('department_name', self::DEPARTMENT)->first();
        return app(ProtocolDirectory::class)->employees($department->pk);
    }

    private function abortUnlessListed(User $user): void
    {
        abort_unless($this->protocolEmployees()->pluck('user_credentials_pk')->contains($user->getKey()), 403);
    }

    public function permissions()
    {
        $employees = $this->protocolEmployees();

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
        $user = User::findOrFail($user);
        $this->abortUnlessListed($user);
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
        $this->abortUnlessListed($employee);

        // Only protocol.* permissions are shown on this screen, so only those are replaced;
        // the user's direct permissions for other modules are left untouched
        $protocolIds = Permission::where('name', 'LIKE', 'protocol.%')->pluck('id');
        $selected = $protocolIds->intersect(array_map('intval', $request->input('permissions', [])));

        $before = $employee->permissions()->whereIn('id', $protocolIds)->pluck('id');

        DB::transaction(function () use ($employee, $protocolIds, $selected) {
            $employee->permissions()->detach($protocolIds->all());
            $employee->permissions()->attach($selected->all());
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Audit trail: who changed whose Protocol permissions, and what
        $names = Permission::whereIn('id', $before->merge($selected))->pluck('name', 'id');
        Log::info('Protocol permissions updated', [
            'by_user_credentials_pk' => Auth::id(),
            'target_user_credentials_pk' => $employee->getKey(),
            'granted' => $selected->diff($before)->map(fn ($id) => $names[$id] ?? $id)->values()->all(),
            'revoked' => $before->diff($selected)->map(fn ($id) => $names[$id] ?? $id)->values()->all(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['status' => 'ok']);
    }

}