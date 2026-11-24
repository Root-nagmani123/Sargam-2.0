<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LdapPoc\LdapUserCreationTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Standalone LDAP user-creation POC page. Not linked from any menu and not
 * part of FC Registration. 404 unless LDAP_POC_ENABLED=true; Super Admin only.
 */
class LdapPocController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(config('ldap_poc.enabled'), 404);
            abort_unless(hasRole('Super Admin'), 403);

            return $next($request);
        });
    }

    public function index(LdapUserCreationTestService $service)
    {
        return view('admin.ldap-poc.index', [
            'summary' => $service->settingsSummary(),
            'registry' => $service->registryEntries(),
            'report' => session('ldap_poc_report'),
        ]);
    }

    public function diagnose(LdapUserCreationTestService $service)
    {
        return $this->back($service->diagnose());
    }

    public function create(Request $request, LdapUserCreationTestService $service)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:64'],
            'last_name' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:254'],
            'password' => ['nullable', 'string', 'max:128', 'confirmed'],
            'enable' => ['nullable', 'boolean'],
        ]);

        $report = $service->createTestUser(
            [
                'username' => $data['username'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'enable' => (bool) ($data['enable'] ?? false),
            ],
            $data['password'] ?? null,
            $this->actor()
        );

        return $this->back($report);
    }

    public function destroy(Request $request, LdapUserCreationTestService $service)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:20'],
            'confirm_username' => ['required', 'same:username'],
        ], [
            'confirm_username.same' => 'Type the username exactly to confirm deletion.',
        ]);

        return $this->back($service->deleteTestUser($data['username'], $this->actor()));
    }

    private function back(array $report)
    {
        // Laravel never flashes "password"/"password_confirmation" back as old input.
        return redirect()->route('admin.ldap-poc.index')
            ->withInput(request()->except(['password', 'password_confirmation']))
            ->with('ldap_poc_report', $report);
    }

    private function actor(): string
    {
        $user = Auth::user();

        return ($user->user_name ?? 'unknown').' (pk '.($user->pk ?? '?').')';
    }
}
