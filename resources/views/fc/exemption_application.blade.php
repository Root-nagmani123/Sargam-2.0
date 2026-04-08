@extends('fc.layouts.master')

@section('title', 'Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main id="content" class="flex-grow-1 py-4 py-md-5">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item" style="font-size:20px;">Home</li>
                    <li class="breadcrumb-item" style="font-size:20px;">Exemption Category</li>
                    <li class="breadcrumb-item active" aria-current="page" style="font-size:20px;">Exemption Application</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-lg rounded-4 mx-auto" style="max-width: 960px;">
                <div class="card-body p-4 p-md-5">
                    <header class="mb-4">
                        <h1 class="h3 fw-bold text-primary mb-2">{{ $exemption->Exemption_name }}</h1>
                        <p class="text-muted small mb-0">Please fill in all required information for your exemption application.</p>
                    </header>

                    <form method="POST" action="{{ route('fc.exemption.apply', $exemption->pk) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="exemption_category" value="{{ $exemption->pk }}">

                        <div class="row g-3 g-md-4">
                            <div class="col-md-6">
                                <label for="ex_mobile" class="form-label fw-semibold">Mobile Number <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 @error('ex_mobile') is-invalid @enderror"
                                    id="ex_mobile" name="ex_mobile" placeholder="Enter mobile number"
                                    value="{{ old('ex_mobile') }}" inputmode="numeric" autocomplete="tel" required>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_web_code" class="form-label fw-semibold">Web Authentication Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 @error('reg_web_code') is-invalid @enderror"
                                    id="reg_web_code" name="reg_web_code" placeholder="Enter web auth code"
                                    value="{{ old('reg_web_code') }}" autocomplete="one-time-code" required>
                            </div>

                            @if (stripos($exemption->Exemption_name, 'completed foundation course') !== false)
                                <div class="col-md-6">
                                    <label for="course" class="form-label fw-semibold">Course <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-3 @error('course') is-invalid @enderror"
                                        id="course" name="course" placeholder="Enter your course" value="{{ old('course') }}"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label for="year" class="form-label fw-semibold">Year <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select rounded-3 @error('year') is-invalid @enderror" id="year"
                                        name="year" required>
                                        <option value="" selected disabled>Select Year</option>
                                        @for ($y = date('Y'); $y >= 1970; $y--)
                                            <option value="{{ $y }}" @selected(old('year') == $y)>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            @endif

                            @php
                                $exName = strtolower($exemption->Exemption_name);
                            @endphp

                            @if (str_contains($exName, 'reappearing') || str_contains($exName, 'civil services'))
                                <div class="col-md-6">
                                    <label for="roll_number" class="form-label fw-semibold">Roll Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-3 @error('roll_number') is-invalid @enderror"
                                        id="roll_number" name="roll_number" placeholder="Enter your UPSC Roll Number"
                                        value="{{ old('roll_number') }}" required>
                                </div>
                            @endif

                            @if (stripos($exemption->Exemption_name, 'medical') !== false)
                                <div class="col-12">
                                    <label for="medical_doc" class="form-label fw-semibold">
                                        Upload Medical Exemption Document <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control rounded-3 @error('medical_doc') is-invalid @enderror"
                                        id="medical_doc" name="medical_doc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                    <div class="form-text">Supported formats: PDF, Word (.doc, .docx), JPG, JPEG, PNG. Max file
                                        size: 5 MB.</div>
                                </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label fw-semibold mb-2">Verification <span class="text-danger">*</span></label>
                                <div class="bg-light border border-light rounded-4 p-4 text-center">
                                    <div class="d-flex flex-column align-items-center gap-3">
                                        <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                                            <img src="{{ captcha_src() }}" alt="Captcha" id="captchaImage"
                                                class="img-fluid border rounded-3 shadow-sm bg-white p-2" style="max-height: 52px;">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                                onclick="refreshCaptcha()">
                                                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
                                            </button>
                                        </div>
                                        <div class="w-100" style="max-width: 280px;">
                                            <input type="text" name="captcha"
                                                class="form-control form-control-sm text-center rounded-3 @error('captcha') is-invalid @enderror"
                                                placeholder="Enter captcha code" autocomplete="off" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check mt-1">
                                    <input class="form-check-input rounded border-primary" type="checkbox" id="declaration"
                                        required>
                                    <label class="form-check-label small text-body-secondary" for="declaration">
                                        I hereby declare that the information provided above is true and correct. I
                                        understand that any false information may lead to rejection of my exemption
                                        application.
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 d-flex flex-wrap justify-content-center gap-3 pt-2">
                                <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-semibold"
                                    style="background-color: #004a93; border-color: #004a93;">
                                    Submit Application
                                </button>
                                <a href="{{ route('fc.choose.path') }}"
                                    onclick="return confirm('Are you sure you want to cancel your application? This action cannot be undone.')"
                                    class="btn btn-danger rounded-3 px-4 py-2 fw-semibold">
                                    Cancel Application
                                </a>
                            </div>
                        </div>
                    </form>
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
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('already_applied'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: 'Notice',
                    text: '{{ session('already_applied') }}',
                    icon: 'info',
                    confirmButtonColor: '#004a93',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif

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
