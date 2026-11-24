<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>LDAP User Creation POC - Sargam 2.0</title>
<style>
    body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 0; background: #f4f5f7; color: #1d2330; font-size: 14px; }
    main { max-width: 980px; margin: 0 auto; padding: 16px; }
    h1 { font-size: 20px; margin: 8px 0 4px; }
    h2 { font-size: 16px; margin: 0 0 10px; }
    .banner { background: #fff4d6; border: 1px solid #e8c766; padding: 10px 12px; border-radius: 6px; margin: 10px 0 16px; }
    .card { background: #fff; border: 1px solid #dde1e7; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #eceff3; vertical-align: top; word-break: break-word; }
    th { width: 34%; color: #4a5263; font-weight: 600; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px 14px; }
    label { display: block; font-weight: 600; margin-bottom: 3px; }
    input[type=text], input[type=email], input[type=password] { width: 100%; box-sizing: border-box; padding: 7px 8px; border: 1px solid #c5cbd5; border-radius: 5px; font: inherit; }
    .hint { color: #5e6676; font-size: 12px; margin-top: 3px; }
    button { padding: 8px 14px; border-radius: 5px; border: 1px solid #1f5fbf; background: #2468d4; color: #fff; font: inherit; cursor: pointer; }
    button.secondary { background: #fff; color: #1f5fbf; }
    button.danger { background: #b42318; border-color: #9a1d14; }
    .ok { color: #106b3a; } .fail { color: #b42318; } .warn { color: #8a5a00; } .skip { color: #5e6676; }
    .result-ok { border-left: 5px solid #1f8a4c; } .result-fail { border-left: 5px solid #b42318; }
    .pill { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #eceff3; }
    code { background: #f0f2f5; padding: 1px 4px; border-radius: 3px; font-size: 12px; }
    ul.errors { color: #b42318; margin: 0 0 12px; }
</style>
</head>
<body>
<main>
    <h1>LDAP User Creation — isolated POC</h1>
    <div class="banner">
        <strong>Test only.</strong> Not connected to FC Registration and does not change the LDAP login.
        Accounts are created in the test container below with the <code>{{ $summary['username_prefix'] }}</code> prefix,
        marked <code>{{ config('ldap_poc.marker') }}</code>. They are never deleted automatically.
    </div>

    @if ($errors->any())
        <ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    {{-- ===================== Result of the last action ===================== --}}
    @if ($report)
        <div class="card {{ $report['ok'] ? 'result-ok' : 'result-fail' }}">
            @php
                $titles = ['create' => ['LDAP test user created successfully.', 'User creation failed.'],
                           'delete' => ['Test user deleted.', 'Deletion failed.'],
                           'diagnose' => ['Diagnostics passed.', 'Diagnostics found a problem.']];
                [$okTitle, $failTitle] = $titles[$report['action']] ?? ['Done.', 'Failed.'];
            @endphp
            <h2 class="{{ $report['ok'] ? 'ok' : 'fail' }}">{{ $report['ok'] ? $okTitle : $failTitle }}</h2>

            @if ($report['ok'] && $report['action'] === 'create' && !empty($report['user']))
                <table>
                    <tr><th>Username</th><td>{{ $report['user']['username'] }}</td></tr>
                    <tr><th>userPrincipalName</th><td>{{ $report['user']['upn'] }}</td></tr>
                    <tr><th>LDAP DN</th><td><code>{{ $report['user']['dn'] }}</code></td></tr>
                    <tr><th>Creation timestamp</th><td>{{ $report['user']['created_at'] }}</td></tr>
                    <tr><th>Password set</th><td>{{ $report['user']['password_set'] ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Enabled</th><td>{{ $report['user']['enabled'] ? 'Yes' : 'No (disabled)' }}</td></tr>
                    <tr><th>Login verified (bind as new user)</th><td>{{ $report['user']['login_verified'] ? 'Yes' : 'No' }}</td></tr>
                </table>
            @endif

            @if ($report['error'])
                <table>
                    <tr><th>Failed at</th><td>{{ $report['error']['stage'] }}</td></tr>
                    <tr><th>Category</th><td><strong>{{ $report['error']['category'] }}</strong></td></tr>
                    <tr><th>LDAP error code</th><td>{{ $report['error']['code'] ?? 'n/a (stopped before contacting LDAP)' }}</td></tr>
                    @if (!empty($report['error']['ad_code']))
                        <tr><th>AD sub-code</th><td><code>{{ $report['error']['ad_code'] }}</code></td></tr>
                    @endif
                    <tr><th>Error message</th><td>{{ $report['error']['message'] }}</td></tr>
                    @if (!empty($report['error']['diagnostic']))
                        <tr><th>Server diagnostic</th><td><code>{{ $report['error']['diagnostic'] }}</code></td></tr>
                    @endif
                    <tr><th>Likely cause</th><td>{{ $report['error']['cause'] }}</td></tr>
                    <tr><th>Recommended fix</th><td>{{ $report['error']['fix'] }}</td></tr>
                </table>
            @endif

            @foreach ($report['warnings'] as $w)
                <p class="warn">&#9888; {{ $w }}</p>
            @endforeach

            <h2 style="margin-top:14px">Steps</h2>
            <table>
                @foreach ($report['steps'] as $s)
                    <tr><th>{{ $s['stage'] }} <span class="pill {{ $s['status'] }}">{{ $s['status'] }}</span></th><td>{{ $s['detail'] }}</td></tr>
                @endforeach
            </table>

            @if (!empty($report['container']))
                <p class="hint">Container create-user right:
                    {{ $report['container']['can_create_user'] === null ? 'unknown' : ($report['container']['can_create_user'] ? 'yes' : 'NO') }}</p>
            @endif
            @if (!empty($report['password_policy']))
                <p class="hint">Domain password policy — min length: {{ $report['password_policy']['min_length'] ?? '?' }},
                    complexity: {{ $report['password_policy']['complexity_required'] ? 'required' : 'not required' }},
                    history: {{ $report['password_policy']['history_length'] ?? '?' }}</p>
            @endif
            @if (!empty($report['attributes_sent']))
                <details><summary>Attributes sent (password never included)</summary>
                    <table>@foreach ($report['attributes_sent'] as $k => $v)
                        <tr><th>{{ $k }}</th><td>{{ is_array($v) ? implode(', ', $v) : $v }}</td></tr>
                    @endforeach</table>
                </details>
            @endif
            <p class="hint">{{ $report['at'] }}</p>
        </div>
    @endif

    {{-- ===================== Configuration ===================== --}}
    <div class="card">
        <h2>Configuration (from the existing LDAP login settings)</h2>
        <table>
            <tr><th>PHP ldap extension</th><td class="{{ $summary['ext_ldap_loaded'] ? 'ok' : 'fail' }}">{{ $summary['ext_ldap_loaded'] ? 'Loaded' : 'NOT loaded' }}</td></tr>
            <tr><th>Test container (LDAP_POC_CONTAINER_DN)</th><td>{{ $summary['container_dn'] ?? 'not set' }}</td></tr>
            <tr><th>Username prefix</th><td><code>{{ $summary['username_prefix'] }}</code></td></tr>
            <tr><th>UPN suffix</th><td>{{ $summary['upn_suffix'] }}</td></tr>
            <tr><th>Transport</th><td class="{{ $summary['secure_channel'] ? 'ok' : 'warn' }}">
                port {{ $summary['port'] }}, {{ $summary['use_ssl'] ? 'SSL' : ($summary['use_tls'] ? 'StartTLS' : 'plain LDAP (passwords cannot be set)') }}</td></tr>
        </table>
        @foreach ($summary['problems'] as $p)
            <p class="fail">&#10007; {{ $p }}</p>
        @endforeach
        <form method="post" action="{{ route('admin.ldap-poc.diagnose') }}" style="margin-top:10px">
            @csrf
            <button type="submit" class="secondary">Run read-only diagnostics</button>
            <span class="hint">Connects, binds, checks the container, the create-user right and the password policy. Writes nothing.</span>
        </form>
    </div>

    {{-- ===================== Create ===================== --}}
    <div class="card">
        <h2>Create a test LDAP user</h2>
        <form method="post" action="{{ route('admin.ldap-poc.create') }}" autocomplete="off">
            @csrf
            <div class="grid">
                <div>
                    <label for="username">Username (sAMAccountName)</label>
                    <input type="text" id="username" name="username" required maxlength="20"
                           value="{{ old('username', $summary['username_prefix'].'.test1') }}">
                    <div class="hint">Must start with <code>{{ $summary['username_prefix'] }}</code>; max 20 chars.</div>
                </div>
                <div>
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" required maxlength="64" value="{{ old('first_name', 'Poc') }}">
                </div>
                <div>
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" required maxlength="64" value="{{ old('last_name', 'Testuser') }}">
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required maxlength="254" value="{{ old('email', 'erppoc.test1@example.invalid') }}">
                    <div class="hint">Use a dummy address.</div>
                </div>
                <div>
                    <label for="password">Password (optional)</label>
                    <input type="password" id="password" name="password" maxlength="128" autocomplete="new-password">
                    <div class="hint">Leave empty to only test account creation. Requires SSL/TLS to be applied.</div>
                </div>
                <div>
                    <label for="password_confirmation">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" maxlength="128" autocomplete="new-password">
                </div>
            </div>
            <p><label style="display:inline;font-weight:normal"><input type="checkbox" name="enable" value="1" {{ old('enable') ? 'checked' : '' }}>
                Enable the account after the password is set (and verify it can log in)</label></p>
            <button type="submit">Create test user</button>
        </form>
    </div>

    {{-- ===================== Created accounts / cleanup ===================== --}}
    <div class="card">
        <h2>Test accounts created by this POC</h2>
        @if (empty($registry))
            <p class="hint">None yet.</p>
        @else
            <table>
                @foreach ($registry as $r)
                    <tr>
                        <th>{{ $r['username'] }}
                            @if ($r['deleted_at'] ?? null)<span class="pill">deleted</span>@endif</th>
                        <td><code>{{ $r['dn'] }}</code><br>
                            created {{ $r['created_at'] }} by {{ $r['created_by'] }};
                            password {{ $r['password_set'] ? 'set' : 'not set' }}, {{ $r['enabled'] ? 'enabled' : 'disabled' }}
                            @if ($r['deleted_at'] ?? null)<br>deleted {{ $r['deleted_at'] }} by {{ $r['deleted_by'] ?? '?' }}@endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        <h2 style="margin-top:16px">Cleanup (manual)</h2>
        <form method="post" action="{{ route('admin.ldap-poc.delete') }}" autocomplete="off"
              onsubmit="return confirm('Permanently delete this test account from LDAP?');">
            @csrf
            <div class="grid">
                <div>
                    <label for="del_username">Test username</label>
                    <input type="text" id="del_username" name="username" required maxlength="20">
                </div>
                <div>
                    <label for="confirm_username">Type it again to confirm</label>
                    <input type="text" id="confirm_username" name="confirm_username" required maxlength="20">
                </div>
            </div>
            <p class="hint">Refused unless the account is in the POC registry, sits directly in the test container, still has the
                objectGUID recorded at creation, still carries the marker, is in no groups and has no adminCount.</p>
            <button type="submit" class="danger">Delete test user</button>
        </form>
    </div>
</main>
</body>
</html>
