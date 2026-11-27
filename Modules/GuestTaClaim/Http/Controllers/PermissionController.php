<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use Illuminate\Http\Request;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Http\Requests\UpdatePermissionsRequest;
use Modules\GuestTaClaim\Models\UserPermission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $model = config('guest-ta-claim.user_model');
        $nameCol = config('guest-ta-claim.user_name_column', 'first_name');
        $key = (new $model)->getKeyName();
        $dept = $request->query('dept', 'all');
        $departments = Permission::departments();

        $users = $model::query()
            ->when($request->filled('q'), fn ($q) => $q->where($nameCol, 'like', '%'.$request->q.'%'))
            ->when(isset($departments[$dept]), fn ($q) => $q->whereIn($key,
                UserPermission::where('permission', $departments[$dept]['permission']->value)->select('user_id')))
            ->orderBy($nameCol)
            ->paginate(30)->withQueryString();

        $selectedId = (int) $request->query('user', $users->first()?->getKey());
        $selected = $selectedId ? $model::find($selectedId) : null;
        $held = $selected ? $this->perms()->permissionsOf($selected->getKey()) : [];
        $viewData = compact('users', 'selected', 'held', 'dept', 'departments', 'nameCol');

        if ($request->expectsJson()) {
            return response()->json([
                'employeeListHtml' => view('guesttaclaim::permissions.partials.employee-list', $viewData)->render(),
                'permissionEditorHtml' => view('guesttaclaim::permissions.partials.permission-editor', $viewData)->render(),
            ]);
        }

        return view('guesttaclaim::permissions.index', $viewData);
    }

    public function update(UpdatePermissionsRequest $request, int $userId)
    {
        $model = config('guest-ta-claim.user_model');
        $user = $model::findOrFail($userId);
        $this->perms()->sync($userId, $request->input('permissions', []), (int) $request->user()->getKey());
        $message = 'Permissions saved for '.$user->{config('guest-ta-claim.user_name_column', 'name')}.'.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('guest-ta-claim.permissions.index', $request->only('dept', 'q') + ['user' => $userId])
            ->with('gtc_success', $message);
    }
}
