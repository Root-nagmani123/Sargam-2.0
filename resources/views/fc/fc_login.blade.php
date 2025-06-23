@extends('fc.layouts.master')

@section('title', 'FC Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')

    <!-- Main Content Box -->
    <main style="flex: 1;">
        <div class="container mt-5">
            <div class="row">
                <!-- Form Content -->
                <div class="col-md-6 col-lg-6 offset-md-2 offset-lg-1 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <!--display errors if any -->
                            @if ($errors->any())
                                <div class="alert alert-danger mb-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form class="row g-3" method="POST" action="{{ route('fc.login.verify') }}">
                                @csrf
                                <h3 class="text-center mb-4 fw-bold" style="color: #004a93;">Login to Foundation Course</h3>
                                <hr>

                                <!-- Username -->
                                <div class="col-md-12">
                                    <label class="form-label">User Name</label>
                                    <input type="text" class="form-control" placeholder="Enter your User Name"
                                        name="reg_name" required>
                                </div>

                                <!-- Password -->
                                <div class="col-md-12">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" placeholder="Enter Password"
                                            name="reg_password" id="password" required>
                                        <button type="button" class="btn btn-primary"
                                            onclick="togglePassword('password', this)"
                                            style="background-color: #004a93;border-color: #004a93;">
                                            <i class="material-icons menu-icon me-3 fs-3">visibility</i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted mt-2"><a href="{{route('fc.password.forgot')}}" class="text-primary">Forget Password</a></small>
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
            </div>
        </div>
    </main>
    <!-- Toggle Password Visibility Script -->
    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "visibility_off";
            } else {
                input.type = "password";
                icon.textContent = "visibility";
            }
        }
    </script>
@endsection
