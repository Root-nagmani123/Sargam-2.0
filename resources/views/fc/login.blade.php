@extends('fc.layouts.master')

@section('title', 'Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main style="flex: 1;">
        <div class="container mt-5">
            <div class="row">
                <!-- Form Content -->
                <div class="col-md-6 col-lg-6 offset-md-2 offset-lg-1 mx-auto">
                    <div class="card">
                        <div class="card-body">
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
                            <form class="row g-3" method="POST" action="{{ route('registration.verify') }}">
                                @csrf
                                <h3 class="text-center mb-4 fw-bold" style="color: #004a93;">User Authentication -
                                    Foundation Course Registration</h3>
                                <hr>
                                <!-- Mobile -->
                                <div class="col-md-12">
                                    <label class="form-label">Mobile No.</label>
                                    <input type="number" class="form-control"
                                        placeholder="Enter your registered mobile number" name="reg_mobile" required>
                                </div>
                                <!-- Web Code -->
                                <div class="col-md-12">
                                    <label class="form-label">Web Code</label>
                                    <input type="text" class="form-control" placeholder="Enter Web Code"
                                        name="reg_web_code" required>
                                    <small class="text-muted">Web Code is sent to your registered mobile
                                        number/Email</small>
                                </div>
                                <!-- Password -->
                                <div class="col-md-12">
                                    <label class="form-label">Verification</label>
                                    <div class="d-flex align-items-center gap-3 mb-2 ">
                                        <img src="{{ captcha_src() }}" alt="captcha" id="captchaImage"
                                            class="border rounded w-100" height="60">
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            onclick="refreshCaptcha()" id="reload-registration">Refresh</button>
                                    </div>
                                    <input type="text" class="form-control" name="captcha"
                                        placeholder="Enter captcha code">
                                </div>
                                <!-- Submit -->
                                <div class="d-flex justify-content-center">
                                    <button
                                        class="btn btn-primary me-2 d-flex align-items-center justify-content-center w-100"
                                        type="submit"
                                        style="width: 150px;background-color: #004a93; border-color: #004a93;">
                                        <i class="material-icons me-2">login</i>
                                        Login
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        function refreshCaptcha() {
            document.getElementById('captchaImage').src = '{{ captcha_src() }}' + '?' + Math.random();
        }
    </script>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

     @if ($errors->any())
        <script>
            let errorMessages = '';
            @foreach ($errors->all() as $error)
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
