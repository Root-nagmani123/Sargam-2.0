@extends('fc.layouts.master')

@section('title', 'Registration - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<style>
    .cred-rules { list-style: none; padding-left: 0; }
    .cred-rules li { color: #6c757d; line-height: 1.6; }
    .cred-rules li.ok { color: #198754; }
    .cred-rules li.bad { color: #dc3545; }
    .cred-rules .rule-icon { display: inline-block; width: 1.1em; font-weight: 700; }
</style>

<!-- Main Content Box -->
<main style="flex:1;">
    <div class="container mt-5 mb-5">
    <div class="row">
        <!-- Form Content -->
        <div class="col-md-6 col-lg-6 offset-md-2 offset-lg-1 mx-auto">
            <div class="card">
                <div class="card-body">

                    {{-- @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif --}}

                <!--display errors if any -->
                {{-- @if ($errors->any())
                                <div class="alert alert-danger mb-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                @endforeach
                </ul>
            </div>
            @endif --}}
            <form class="row g-3" method="POST" action="{{ route('credential.registration.store') }}">
                @csrf
                <h3 class="text-center mb-4 fw-bold" style="color: #004a93;">Create Your Login Credentials
                </h3>
                <hr>

                <!-- Username -->
                <div class="col-md-12">
                    <label class="form-label">User Name</label>
                    <input type="text" class="form-control" placeholder="Enter your User Name" name="reg_name"
                        id="reg_name" value="{{ strtolower((string) old('reg_name', '')) }}" required
                        autocomplete="username" autocapitalize="none" spellcheck="false"
                        pattern="[a-z][a-z0-9._]{5,19}"
                        title="Lowercase letters, numbers, dots and underscores only (6–20 characters).">
                    <div class="form-text text-muted">Use lowercase only — capital letters are converted automatically.</div>
                    <ul class="small mt-2 mb-0 cred-rules" id="username-rules">
                        <li data-rule="len"><span class="rule-icon">•</span> 6–20 characters long</li>
                        <li data-rule="chars"><span class="rule-icon">•</span> Only lowercase letters, numbers, dot (.) and underscore (_)</li>
                        <li data-rule="start"><span class="rule-icon">•</span> Must start with a letter</li>
                        <li data-rule="end"><span class="rule-icon">•</span> Must end with a letter or number</li>
                        <li data-rule="consec"><span class="rule-icon">•</span> No consecutive dots/underscores (no “..” or “__”)</li>
                    </ul>
                    <div class="small text-muted mt-2">Example: <code>rahul.kumar</code> &nbsp;or&nbsp; <code>arjun_singh1</code></div>
                </div>

                <!-- Mobile Number (hidden — taken from the registration link; required by the backend lookup) -->
                <input type="hidden" name="reg_mobile" value="{{ old('reg_mobile', session('fc_user_mobile')) }}">


                <!-- Password -->
                <div class="col-md-12">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" placeholder="Enter Password" name="reg_password"
                            id="password" required autocomplete="new-password">
                        <button type="button" class="btn btn-primary" onclick="togglePassword('password', this)"
                            style="background-color: #004a93;border-color: #004a93;">
                            <i class="material-icons menu-icon me-3 fs-3">visibility</i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="col-md-12">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" placeholder="Enter Confirm Password"
                            name="reg_confirm_password" id="confirm_password" required autocomplete="new-password">
                        <button type="button" class="btn btn-primary" onclick="togglePassword('confirm_password', this)"
                            style="background-color: #004a93;border-color: #004a93;">
                            <i class="material-icons menu-icon me-3 fs-3">visibility</i>
                        </button>
                    </div>
                    <ul class="small mt-2 mb-0 cred-rules" id="password-rules">
                        <li data-rule="len"><span class="rule-icon">•</span> At least 6 characters long</li>
                        <li data-rule="special"><span class="rule-icon">•</span> At least one special character (e.g. ! @ # $ _)</li>
                        <li data-rule="match"><span class="rule-icon">•</span> Password and Confirm Password match</li>
                    </ul>
                    <div class="small text-muted mt-2">Example: <code>Sargam@2026</code> &nbsp;(pick your own — do not reuse this example)</div>
                </div>

                <!-- Submit -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100"
                        style="background-color: #004a93;border-color: #004a93;">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

</main>
<!-- Toggle Password Visibility Script -->
<script>
(function () {
    var usernameInput = document.getElementById('reg_name');
    if (!usernameInput) {
        return;
    }
    function toLowerUsername() {
        var pos = usernameInput.selectionStart;
        usernameInput.value = usernameInput.value.toLowerCase();
        if (pos !== null) {
            usernameInput.setSelectionRange(pos, pos);
        }
    }
    usernameInput.addEventListener('input', toLowerUsername);
    usernameInput.addEventListener('paste', function () {
        setTimeout(toLowerUsername, 0);
    });
})();

// Live rule checklist for Username & Password (mirrors the server-side validation).
(function () {
    function mark(listId, results, touched) {
        var list = document.getElementById(listId);
        if (!list) { return; }
        Object.keys(results).forEach(function (key) {
            var li = list.querySelector('[data-rule="' + key + '"]');
            if (!li) { return; }
            var icon = li.querySelector('.rule-icon');
            li.classList.remove('ok', 'bad');
            if (!touched) { icon.textContent = '•'; return; }
            if (results[key]) { li.classList.add('ok'); icon.textContent = '✓'; }
            else { li.classList.add('bad'); icon.textContent = '✗'; }
        });
    }

    // Re-run on every kind of edit (typing, delete/backspace, paste, autofill, blur)
    // so the checklist never gets out of sync with the field values.
    function bind(el, fn) {
        if (!el) { return; }
        ['input', 'keyup', 'change', 'paste', 'blur'].forEach(function (ev) {
            el.addEventListener(ev, function () { setTimeout(fn, 0); });
        });
    }

    var uname = document.getElementById('reg_name');
    function checkUsername() {
        var v = (uname.value || '');
        mark('username-rules', {
            len: v.length >= 6 && v.length <= 20,
            chars: /^[a-z0-9._]*$/.test(v),
            start: /^[a-z]/.test(v),
            end: /[a-z0-9]$/.test(v),
            consec: !/[_.]{2}/.test(v)
        }, v.length > 0);
    }
    bind(uname, checkUsername);
    if (uname) { checkUsername(); }

    var pwd = document.getElementById('password');
    var cpwd = document.getElementById('confirm_password');
    function checkPassword() {
        var p = (pwd.value || ''), c = (cpwd.value || '');
        mark('password-rules', {
            len: p.length >= 6,
            special: /[^A-Za-z0-9]/.test(p),
            match: p.length > 0 && p === c
        }, p.length > 0 || c.length > 0);
    }
    // Editing EITHER password field re-checks all three rules (incl. match).
    bind(pwd, checkPassword);
    bind(cpwd, checkPassword);
    if (pwd && cpwd) { checkPassword(); }
})();

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "visibility"; //  Eye icon means 'Now visible'
    } else {
        input.type = "password";
        icon.textContent = "visibility_off"; //  Eye-off icon means 'Now hidden'
    }
}
</script>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if (session('sweet_success'))
<script>
Swal.fire({
    title: 'Success!',
    text: '{{ session('sweet_success') }}',
    icon: 'success',
    confirmButtonColor: '#004a93',
    confirmButtonText: 'OK'
});
</script>
@endif

@if ($errors->any())
<script>
let errorMessages = '';
@foreach($errors->all() as $error)
errorMessages += `{{ $error }}\n`;
@endforeach

Swal.fire({
    title: 'Validation Error',
    text: errorMessages.trim(),
    icon: 'error',
    confirmButtonColor: '#004a93',
    confirmButtonText: 'OK'
});
</script>
@endif

@endpush