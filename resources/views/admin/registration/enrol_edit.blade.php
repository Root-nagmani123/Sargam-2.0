@extends('admin.layouts.master')

@section('title', 'Edit Student - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Edit Student Information" />
    <x-session_message />

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-header d-flex justify-content-between align-items-center bg-warning-subtle">
                <h5 class="mb-0">Student Details</h5>
                <span class="badge bg-primary">ID: {{ $student->pk }}</span>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('enrollment.update', $student->pk) }}" id="editStudentForm"
                    class="protected-form" data-ignore-global-validation="true">
                    @csrf
                    <!-- Add this hidden field -->
                    <input type="hidden" name="student_id" value="{{ $student->pk }}">

                    <h6 class="mb-3 text-primary">Personal Information</h6>
                    <div class="row g-3 mb-4">
                        <!-- Display Name -->
                        <div class="col-md-6">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input type="text" id="display_name" name="display_name"
                                class="form-control @error('display_name') is-invalid @enderror"
                                value="{{ old('display_name', $student->display_name) }}">
                            @error('display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Name Fields -->
                        <div class="col-md-4">
                            <label for="first_name" class="form-label required">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                class="form-control @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name', $student->first_name) }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name"
                                class="form-control @error('middle_name') is-invalid @enderror"
                                value="{{ old('middle_name', $student->middle_name) }}">
                            @error('middle_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="last_name" class="form-label required">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                class="form-control @error('last_name') is-invalid @enderror"
                                value="{{ old('last_name', $student->last_name) }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" id="dob" name="dob"
                                class="form-control @error('dob') is-invalid @enderror"
                                value="{{ old('dob', $student->dob ? \Carbon\Carbon::parse($student->dob)->format('Y-m-d') : '') }}">
                            @error('dob')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Service -->
                        <div class="col-md-6">
                            <label for="service_master_pk" class="form-label required">Service</label>
                            <select id="service_master_pk" name="service_master_pk"
                                class="form-select @error('service_master_pk') is-invalid @enderror" required>
                                <option value="">-- Select Service --</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->pk }}"
                                        {{ old('service_master_pk', $student->service_master_pk) == $service->pk ? 'selected' : '' }}>
                                        {{ $service->service_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h6 class="mb-3 text-primary">Contact Information</h6>
                    <div class="row g-3 mb-4">
                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Primary Email</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $student->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Alternative Email -->
                        <div class="col-md-6">
                            <label for="alternative_email" class="form-label">Alternative Email</label>
                            <input type="email" id="alternative_email" name="alternative_email"
                                class="form-control @error('alternative_email') is-invalid @enderror"
                                value="{{ old('alternative_email', $student->alternative_email) }}">
                            @error('alternative_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Contact Number -->
                        <div class="col-md-6">
                            <label for="contact_no" class="form-label required">Primary Contact Number</label>
                            <input type="text" id="contact_no" name="contact_no"
                                class="form-control @error('contact_no') is-invalid @enderror"
                                value="{{ old('contact_no', $student->contact_no) }}" required>
                            @error('contact_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Alternative Mobile Number -->
                        <div class="col-md-6">
                            <label for="alternative_mobile_no" class="form-label">Alternative Mobile Number</label>
                            <input type="text" id="alternative_mobile_no" name="alternative_mobile_no"
                                class="form-control @error('alternative_mobile_no') is-invalid @enderror"
                                value="{{ old('alternative_mobile_no', $student->alternative_mobile_no) }}">
                            @error('alternative_mobile_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $student->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h6 class="mb-3 text-primary">System & Other Information</h6>
                    <div class="row g-3 mb-4">
                        <!-- Web Auth -->
                        <div class="col-md-6">
                            <label for="web_auth" class="form-label">Web Auth</label>
                            <input type="text" id="web_auth" name="web_auth"
                                class="form-control @error('web_auth') is-invalid @enderror"
                                value="{{ old('web_auth', $student->web_auth) }}">
                            @error('web_auth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- OT Code -->
                        <div class="col-md-6">
                            <label for="generated_OT_code" class="form-label">OT Code</label>
                            <input type="text" id="generated_OT_code" name="generated_OT_code"
                                class="form-control @error('generated_OT_code') is-invalid @enderror"
                                value="{{ old('generated_OT_code', $student->generated_OT_code) }}">
                            @error('generated_OT_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- User ID (Read-only as it should be unique) -->
                        <div class="col-md-6">
                            <label for="user_id" class="form-label required">User ID</label>
                            <input type="text" id="user_id" name="user_id"
                                class="form-control @error('user_id') is-invalid @enderror"
                                value="{{ old('user_id', $student->user_id) }}" required readonly>
                            <small class="text-muted">User ID cannot be changed</small>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Exam Year -->
                        <div class="col-md-6">
                            <label for="exam_year" class="form-label">Exam Year</label>
                            <input type="text" id="exam_year" name="exam_year"
                                class="form-control @error('exam_year') is-invalid @enderror"
                                value="{{ old('exam_year', $student->exam_year) }}" maxlength="4" placeholder="YYYY">
                            @error('exam_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Rank -->
                        <div class="col-md-6">
                            <label for="rank" class="form-label">Rank</label>
                            <input type="text" id="rank" name="rank"
                                class="form-control @error('rank') is-invalid @enderror"
                                value="{{ old('rank', $student->rank) }}">
                            @error('rank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Admission Status -->
                        {{-- <div class="col-md-6">
                            <label for="admission_status" class="form-label">Admission Status</label>
                            <select id="admission_status" name="admission_status"
                                class="form-select @error('admission_status') is-invalid @enderror">
                                <option value="0"
                                    {{ old('admission_status', $student->admission_status) == 0 ? 'selected' : '' }}>Not
                                    Admitted</option>
                                <option value="1"
                                    {{ old('admission_status', $student->admission_status) == 1 ? 'selected' : '' }}>
                                    Admitted</option>
                            </select>
                            @error('admission_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div> --}}

                        <!-- Read-only System Information -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('enrollment.create') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" onclick="return submitEditForm(event, this)">
                                Update Student
                            </button>
                        </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Run this BEFORE any other scripts
        (function() {
            // Get our form
            var form = document.getElementById('editStudentForm');
            if (!form) return;

            // Clone and replace to remove all event listeners
            var newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);

            // Fix the action if it was changed
            newForm.action = '{{ route('enrollment.update', $student->pk) }}';

            console.log('Form protected. Action:', newForm.action);
        })();

        // Custom submit handler
        function submitEditForm(event, button) {
            event.stopImmediatePropagation();
            event.preventDefault();

            var form = document.getElementById('editStudentForm');

            // Force correct URL
            form.action = '{{ route('enrollment.update', $student->pk) }}';

            console.log('Submitting to:', form.action);

            // Submit the form
            form.submit();

            return false;
        }
        $(document).ready(function() {

            // Initialize Select2 for service dropdown
            if ($.fn.select2) {
                $('#service_master_pk').select2({
                    placeholder: "-- Select Service --",
                    width: '100%'
                });
            }

            // Form validation
            $('#editStudentForm').submit(function(e) {
                let isValid = true;
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                // Validate required fields
                const requiredFields = ['first_name', 'last_name', 'email', 'contact_no',
                    'service_master_pk'
                ];
                requiredFields.forEach(field => {
                    const element = $('#' + field);
                    if (!element.val().trim()) {
                        element.addClass('is-invalid');
                        element.after('<div class="invalid-feedback">This field is required</div>');
                        isValid = false;
                    }
                });

                // Validate email format
                const email = $('#email').val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailRegex.test(email)) {
                    $('#email').addClass('is-invalid');
                    $('#email').after(
                        '<div class="invalid-feedback">Please enter a valid email address</div>');
                    isValid = false;
                }

                // Validate alternative email if provided
                const altEmail = $('#alternative_email').val().trim();
                if (altEmail && !emailRegex.test(altEmail)) {
                    $('#alternative_email').addClass('is-invalid');
                    $('#alternative_email').after(
                        '<div class="invalid-feedback">Please enter a valid email address</div>');
                    isValid = false;
                }

                // Validate exam year if provided
                const examYear = $('#exam_year').val().trim();
                if (examYear && !/^\d{4}$/.test(examYear)) {
                    $('#exam_year').addClass('is-invalid');
                    $('#exam_year').after(
                        '<div class="invalid-feedback">Please enter a valid 4-digit year (YYYY)</div>');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    $('.is-invalid').first().focus();
                }
            });

            // Auto-generate display name from name fields
            $('#first_name, #middle_name, #last_name').on('blur', function() {
                const firstName = $('#first_name').val().trim();
                const middleName = $('#middle_name').val().trim();
                const lastName = $('#last_name').val().trim();

                if (firstName || lastName) {
                    let displayName = firstName;
                    if (middleName) displayName += ' ' + middleName;
                    if (lastName) displayName += ' ' + lastName;

                    // Only update if display name is empty
                    const currentDisplayName = $('#display_name').val().trim();
                    if (!currentDisplayName) {
                        $('#display_name').val(displayName.toUpperCase());
                    }
                }
            });

            // Format date for display
            const dobInput = document.getElementById('dob');
            if (dobInput && dobInput.value) {
                const date = new Date(dobInput.value);
                if (!isNaN(date.getTime())) {
                    dobInput.value = date.toISOString().split('T')[0];
                }
            }

            // Auto-capitalize display name
            $('#display_name').on('blur', function() {
                const value = $(this).val().trim();
                if (value) {
                    $(this).val(value.toUpperCase());
                }
            });
        });
    </script>
@endsection

<style>
    .form-label.required:after {
        content: " *";
        color: #dc3545;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    .card-header.bg-warning-subtle {
        border-bottom: 2px solid #ffc107;
    }

    .card.border-info {
        border-left: 4px solid #0dcaf0 !important;
    }

    h6.text-primary {
        border-bottom: 2px solid #0d6efd;
        padding-bottom: 8px;
        margin-bottom: 20px;
    }
</style>
