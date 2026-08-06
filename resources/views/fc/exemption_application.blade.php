@extends('fc.layouts.master')

@section('title', 'Exemption Application - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

{{-- Declared outside the section so these flags are in scope for @push('scripts') too. --}}
@php
    $exName = strtolower($exemption->Exemption_name);
    $isCompletedFc = str_contains($exName, 'completed foundation course');
    $isReappearing = str_contains($exName, 'reappearing') || str_contains($exName, 'civil services');
    $isMedical = str_contains($exName, 'medical');
@endphp

@section('content')
    <div class="fc-page">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb fc-breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('fc.choose.path') }}">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('fc.exemption_category.index') }}">Exemption Category</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Exemption Application</li>
                </ol>
            </nav>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-9">
                    <div class="fc-card fc-card--tricolor">
                        <div class="fc-card-body">
                            <header class="fc-page-head">
                                <h1 class="fc-page-title">{{ $exemption->Exemption_name }}</h1>
                                <p class="fc-page-sub">
                                    Please fill in all required information for your exemption application.
                                </p>
                            </header>

                    <form method="POST" action="{{ route('fc.exemption.apply', $exemption->pk) }}" enctype="multipart/form-data"
                        id="exemptionApplicationForm" novalidate autocomplete="off">
                        @csrf
                        <input type="hidden" name="exemption_category" value="{{ $exemption->pk }}">

                        <div class="row g-3 g-md-4">
                            <div class="col-md-6">
                                <label for="ex_mobile" class="form-label fw-semibold">Mobile Number <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 @error('ex_mobile') is-invalid @enderror"
                                    id="ex_mobile" name="ex_mobile" placeholder="Enter mobile number"
                                    value="{{ old('ex_mobile') }}" inputmode="numeric" autocomplete="off" required>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_web_code" class="form-label fw-semibold">Web Authentication Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 @error('reg_web_code') is-invalid @enderror"
                                    id="reg_web_code" name="reg_web_code" placeholder="Enter web auth code"
                                    value="{{ old('reg_web_code') }}" autocomplete="off" required>
                            </div>

                            @if (stripos($exemption->Exemption_name, 'completed foundation course') !== false)
                                <div class="col-md-6">
                                    <label for="course" class="form-label fw-semibold">Already Completed Foundation Course <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-3 @error('course') is-invalid @enderror"
                                        id="course" name="course" placeholder="Enter your course" value="{{ old('course') }}"
                                        required aria-describedby="courseFormatHelp">
                                    <div id="courseFormatHelp" class="form-text text-muted small fw-semibold">
                                        Please enter the course in the prescribed format, e.g. FC-100 or FC-99.
                                    </div>
                                </div>

                                        @if ($isCompletedFc)
                                            <div class="col-md-6">
                                                <label for="course" class="fc-label">
                                                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                                                    Already Completed Foundation Course
                                                    <span class="fc-req" aria-hidden="true">*</span>
                                                </label>
                                                <input type="text"
                                                    class="form-control fc-input @error('course') is-invalid @enderror"
                                                    id="course" name="course" placeholder="Enter your course"
                                                    value="{{ old('course') }}" required>
                                            </div>

                                <div class="col-md-6">
                                    <label for="institution_name" class="form-label fw-semibold">Institution
                                        Name <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control rounded-3 @error('institution_name') is-invalid @enderror"
                                        id="institution_name" name="institution_name"
                                        placeholder="Enter institution name" value="{{ old('institution_name') }}"
                                        required aria-describedby="institutionNameHelp">
                                    <div id="institutionNameHelp" class="form-text text-muted small fw-semibold">
                                        Please enter the abbreviated name of the institution, e.g. MCHRD, YESDA or LBSNAA.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="fc_prev_comp_doc" class="form-label fw-semibold">
                                        Upload Foundation Course Completion Certificate <span class="text-danger">*</span>
                                    </label>
                                    <input type="file"
                                        class="form-control rounded-3 fc-file-upload @error('fc_prev_comp_doc') is-invalid @enderror"
                                        id="fc_prev_comp_doc" name="fc_prev_comp_doc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                        data-max-bytes="{{ $medicalDocMaxBytes ?? 5242880 }}" required>
                                    <div class="form-text">Preferably a <strong>PDF</strong>. Word (.doc, .docx), JPG, JPEG, PNG
                                        also accepted. Max file size: {{ ($medicalDocMaxKb ?? 5120) / 1024 }} MB.</div>
                                    <div id="fc_prev_comp_doc_client_error" class="invalid-feedback d-block @if (!$errors->has('fc_prev_comp_doc')) d-none @endif">
                                        {{ $errors->first('fc_prev_comp_doc') }}
                                    </div>
                                </div>
                            @endif

                                            <div class="col-md-6">
                                                <label for="institution_name" class="fc-label">
                                                    <i class="bi bi-building" aria-hidden="true"></i>
                                                    Institution Name <span class="fc-req" aria-hidden="true">*</span>
                                                </label>
                                                <input type="text"
                                                    class="form-control fc-input @error('institution_name') is-invalid @enderror"
                                                    id="institution_name" name="institution_name"
                                                    placeholder="Enter institution name"
                                                    value="{{ old('institution_name') }}" required>
                                            </div>
                                        @endif

                                        @if ($isReappearing)
                                            <div class="col-md-6">
                                                <label for="roll_number" class="fc-label">
                                                    <i class="bi bi-hash" aria-hidden="true"></i>
                                                    Roll Number (Mains-2026) <span class="fc-req" aria-hidden="true">*</span>
                                                </label>
                                                <input type="text"
                                                    class="form-control fc-input @error('roll_number') is-invalid @enderror"
                                                    id="roll_number" name="roll_number"
                                                    placeholder="Enter your UPSC Roll Number"
                                                    value="{{ old('roll_number') }}" required>
                                            </div>
                                        @endif

                            @if (stripos($exemption->Exemption_name, 'medical') !== false)
                                <div class="col-12">
                                    <label for="medical_doc" class="form-label fw-semibold">
                                        Upload Medical Exemption Document <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control rounded-3 fc-file-upload @error('medical_doc') is-invalid @enderror"
                                        id="medical_doc" name="medical_doc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                        data-max-bytes="{{ $medicalDocMaxBytes ?? 5242880 }}" required>
                                    <div class="form-text">Preferably a <strong>PDF</strong>. Word (.doc, .docx), JPG, JPEG, PNG
                                        also accepted. Max file size: {{ ($medicalDocMaxKb ?? 5120) / 1024 }} MB.</div>
                                    <div id="medical_doc_client_error" class="invalid-feedback d-block @if (!$errors->has('medical_doc')) d-none @endif">
                                        {{ $errors->first('medical_doc') }}
                                    </div>
                                </div>

                                <div class="ds-form-section">
                                    <h2 class="ds-form-section-title">Verification</h2>

                                    <label for="captcha" class="fc-label">
                                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                                        Enter the code shown <span class="fc-req" aria-hidden="true">*</span>
                                    </label>
                                    <div class="fc-captcha">
                                        <div class="fc-captcha-row">
                                            <img src="{{ captcha_src() }}" alt="Captcha challenge" id="captchaImage">
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="refreshCaptchaBtn">
                                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh
                                            </button>
                                        </div>
                                        <div class="fc-captcha-input">
                                            <input type="text" id="captcha" name="captcha"
                                                class="form-control fc-input text-center @error('captcha') is-invalid @enderror"
                                                placeholder="Enter captcha code" autocomplete="off" required>
                                        </div>
                                    </div>

                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="declaration" required>
                                        <label class="form-check-label small text-body-secondary" for="declaration">
                                            I hereby declare that the information provided above is true and correct. I
                                            understand that any false information may lead to rejection of my exemption
                                            application.
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    <button type="submit" id="exemptionSubmitBtn" class="btn btn-primary px-4">
                                        <i class="bi bi-send" aria-hidden="true"></i> Submit Application
                                    </button>
                                    <a href="{{ route('fc.choose.path') }}" id="cancelApplicationBtn"
                                        class="btn btn-outline-danger px-4">
                                        <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel Application
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Instant client-side reject of script / double-extension file names on the
         medical upload (.fc-file-upload). Server-side SafeUploadedDocument +
         SingleFileExtension remain the authority. --}}
    @include('fc.registration.partials.fc-upload-extension-guard')
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Client-side size / type check for every upload on this form (medical document,
         Foundation Course completion certificate). Was bound to #medical_doc alone; it now
         walks the form's .fc-file-upload inputs so each category's upload is covered with
         identical messages. App\Rules\SafeUploadedDocument stays the server-side authority. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('exemptionApplicationForm');
            var submitBtn = document.getElementById('exemptionSubmitBtn');
            if (!form) {
                return;
            }

            var fileInputs = Array.prototype.slice.call(form.querySelectorAll('input[type="file"].fc-file-upload'));
            if (!fileInputs.length) {
                return;
            }

            var allowedExt = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

            function errorElFor(input) {
                return document.getElementById(input.id + '_client_error');
            }

            function showFileError(input, message) {
                var errorEl = errorElFor(input);
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('d-none');
                }
                input.classList.add('is-invalid');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'File not allowed',
                        text: message,
                        icon: 'error',
                        confirmButtonColor: '#004a93',
                        confirmButtonText: 'OK'
                    });
                }
            }

            function clearFileError(input) {
                var errorEl = errorElFor(input);
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.classList.add('d-none');
                }
                input.classList.remove('is-invalid');
            }

            function validateFile(input) {
                var file = input.files && input.files[0];
                if (!file) {
                    return null;
                }
                var maxBytes = parseInt(input.getAttribute('data-max-bytes') || '5242880', 10);
                var maxMbLabel = (maxBytes / 1024 / 1024).toFixed(0);
                var parts = file.name.split('.');
                var ext = parts.length > 1 ? parts.pop().toLowerCase() : '';
                if (allowedExt.indexOf(ext) === -1) {
                    return 'Only PDF, Word (.doc, .docx), JPG, JPEG, and PNG files are allowed.';
                }
                if (file.size > maxBytes) {
                    var sizeMb = (file.size / 1024 / 1024).toFixed(2);
                    return 'File is too large (' + sizeMb + ' MB). Maximum allowed size is ' + maxMbLabel + ' MB.';
                }
                return null;
            }

            fileInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    var err = validateFile(this);
                    if (err) {
                        this.value = '';
                        showFileError(this, err);
                        return;
                    }
                    clearFileError(this);
                });
            });

            form.addEventListener('submit', function (e) {
                for (var i = 0; i < fileInputs.length; i++) {
                    var err = validateFile(fileInputs[i]);
                    if (err) {
                        e.preventDefault();
                        e.stopPropagation();
                        showFileError(fileInputs[i], err);
                        return false;
                    }
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting…';
                }
            });
        });
    </script>

    @if (session('already_applied'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Notice',
                    text: @json(session('already_applied')),
                    icon: 'info',
                    confirmButtonColor: FC_BRAND,
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var errorMessages = @json($errors->all());
                if (typeof Swal !== 'undefined' && errorMessages.length) {
                    Swal.fire({
                        title: 'Validation Error',
                        text: errorMessages.join('\n'),
                        icon: 'error',
                        confirmButtonColor: FC_BRAND,
                        confirmButtonText: 'OK'
                    });
                }
                @if (session('captcha_refresh'))
                    refreshCaptcha();
                @endif
            });
        </script>
    @elseif (session('captcha_refresh'))
        <script>
            document.addEventListener('DOMContentLoaded', refreshCaptcha);
        </script>
    @endif
@endpush
