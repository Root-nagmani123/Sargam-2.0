@extends('admin.layouts.master')

@section('title', 'Edit Profile - Sargam | Lal Bahadur')

@section('content')
    @php
        $profileSteps = [
            1 => ['title' => 'Personal Details', 'description' => 'Identity and profile basics'],
            2 => ['title' => 'Employment', 'description' => 'Work, department, and access data'],
            3 => ['title' => 'Contact', 'description' => 'Addresses and communication details'],
            4 => ['title' => 'Additional', 'description' => 'Uploads and supporting information'],
        ];
    @endphp

    <style>
        .profile-page {
            --profile-primary: #274989;
            --profile-primary-soft: #edf4ff;
            --profile-border: #e6edf5;
            --profile-text: #334155;
            --profile-muted: #64748b;
        }

        .profile-cover {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            background: linear-gradient(135deg, #19386d 0%, #2f5ba6 48%, #5f92db 100%);
            box-shadow: 0 1.5rem 3rem rgba(39, 73, 137, 0.18);
        }

        .profile-cover::before,
        .profile-cover::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-cover::before {
            width: 18rem;
            height: 18rem;
            right: -5rem;
            bottom: -7rem;
        }

        .profile-cover::after {
            width: 10rem;
            height: 10rem;
            right: 18%;
            top: -3rem;
            background: rgba(255, 255, 255, 0.08);
        }

        .profile-cover-content {
            position: relative;
            z-index: 1;
            padding: 2rem;
        }

        .profile-cover-badge,
        .profile-cover-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
        }

        .profile-cover-badge {
            padding: 0.45rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .profile-cover-chip {
            padding: 0.45rem 0.8rem;
            font-size: 0.82rem;
        }

        .profile-cover-stat {
            min-width: 220px;
            border-radius: 1.25rem;
            padding: 1rem 1.2rem;
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
        }

        .profile-card,
        .profile-form-card {
            border-radius: 1.4rem;
            border: 1px solid var(--profile-border);
            box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.07);
            background: #fff;
        }

        .profile-sidebar {
            position: sticky;
            top: 1.5rem;
        }

        .profile-avatar-wrap {
            width: 126px;
            height: 126px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #fff;
            background: linear-gradient(145deg, #f8fbff, #eef3fb);
            box-shadow: 0 1rem 2rem rgba(39, 73, 137, 0.16);
        }

        .profile-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-meta-list .list-group-item {
            padding: 0.95rem 0;
            background: transparent;
            border-color: var(--profile-border);
        }

        .profile-helper-note {
            border-radius: 1rem;
            border: 1px solid var(--profile-border);
            background: linear-gradient(180deg, #fbfcff 0%, #f5f8fc 100%);
        }

        .profile-form-header {
            padding: 1.5rem 1.5rem 0;
        }

        .profile-progress-badge {
            min-width: 220px;
            padding: 0.95rem 1rem;
            border: 1px solid var(--profile-border);
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f5f8fc 100%);
        }

        .profile-tabs-wrapper {
            padding: 1.25rem 1.5rem 0;
        }

        .profile-tabs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
            gap: 0.75rem;
            border-bottom: 0;
        }

        .profile-tabs .nav-link {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            border: 1px solid var(--profile-border);
            color: var(--profile-text);
            padding: 1rem;
            border-radius: 1rem;
            background: #fff;
            transition: all 0.2s ease;
            min-height: 84px;
            text-align: left;
            box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.04);
        }

        .profile-tabs .nav-link.active {
            color: var(--profile-primary);
            border-color: rgba(39, 73, 137, 0.24);
            background: var(--profile-primary-soft);
            box-shadow: inset 0 0 0 1px rgba(39, 73, 137, 0.03);
        }

        .profile-tabs .nav-link:hover {
            border-color: rgba(39, 73, 137, 0.32);
            background: #f8fbff;
            transform: translateY(-1px);
        }

        .profile-tabs .nav-link.is-loading {
            opacity: 0.78;
        }

        .profile-tab-step {
            width: 2.4rem;
            height: 2.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #edf2f8;
            color: var(--profile-primary);
            font-size: 0.84rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-tabs .nav-link.active .profile-tab-step {
            background: var(--profile-primary);
            color: #fff;
        }

        .profile-tab-content {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .profile-tab-title {
            font-weight: 600;
        }

        .profile-tab-subtitle {
            color: var(--profile-muted);
            font-size: 0.78rem;
            line-height: 1.3;
        }

        .profile-tabs .nav-link.active .profile-tab-subtitle {
            color: rgba(39, 73, 137, 0.78);
        }

        .profile-section-wrapper {
            padding: 1.25rem 1.5rem 0;
        }

        .profile-section-header {
            border: 1px solid var(--profile-border);
            border-bottom: 0;
            border-radius: 1.2rem 1.2rem 0 0;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfe 100%);
        }

        .profile-step-content {
            border: 1px solid var(--profile-border);
            border-radius: 0 0 1.2rem 1.2rem;
            background: #fff;
            overflow: hidden;
        }

        .profile-step-pane {
            min-height: 420px;
            padding: 1.5rem;
            background: linear-gradient(180deg, #fbfcff 0%, #ffffff 160px);
        }

        .profile-step-pane .row {
            --bs-gutter-x: 1.25rem;
        }

        .profile-step-pane > p {
            margin-bottom: 0.5rem;
            color: var(--profile-primary);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            font-weight: 700;
            text-transform: uppercase;
        }

        .profile-step-pane hr {
            margin-top: 0;
            margin-bottom: 1.25rem;
            border-color: var(--profile-border);
            opacity: 1;
        }

        .profile-step-pane .mb-3 {
            margin-bottom: 1.25rem !important;
        }

        .profile-step-pane .form-label {
            margin-bottom: 0.5rem;
            color: #1f2937;
            font-weight: 600;
        }

        .profile-step-pane .form-control,
        .profile-step-pane .form-select {
            min-height: 48px;
            border-radius: 0.9rem;
            border-color: #d6dfeb;
            box-shadow: none;
            padding: 0.75rem 0.95rem;
        }

        .profile-step-pane textarea.form-control {
            min-height: 110px;
        }

        .profile-step-pane .form-control:focus,
        .profile-step-pane .form-select:focus {
            border-color: rgba(39, 73, 137, 0.55);
            box-shadow: 0 0 0 0.25rem rgba(39, 73, 137, 0.12);
        }

        .profile-step-pane .form-check {
            min-height: 100%;
            padding: 0.9rem 1rem 0.9rem 2.4rem;
            border: 1px solid var(--profile-border);
            border-radius: 1rem;
            background: #fff;
        }

        .profile-step-pane fieldset .form-check {
            min-height: auto;
            border-style: dashed;
            background: #f8fbff;
        }

        .profile-step-pane .form-check-input {
            margin-top: 0.35rem;
        }

        .profile-step-pane .form-check-input:checked {
            background-color: var(--profile-primary);
            border-color: var(--profile-primary);
        }

        .profile-step-pane .form-check-label {
            color: var(--profile-text);
            font-weight: 500;
        }

        .profile-step-pane small.text-muted {
            display: block;
            margin-top: 0.2rem;
            font-size: 0.8rem;
        }

        .profile-step-pane a.btn {
            margin-top: 0.75rem;
            border-radius: 0.8rem;
            padding-inline: 1rem;
        }

        .profile-step-pane .text-danger {
            font-size: 0.82rem;
        }

        .profile-loading-state {
            min-height: 340px;
            border-radius: 1.2rem;
            border: 1px dashed var(--profile-border);
            background: linear-gradient(180deg, #fcfdff 0%, #f7faff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0.85rem;
            text-align: center;
            color: var(--profile-muted);
        }

        .profile-loading-spinner {
            width: 3rem;
            height: 3rem;
        }

        .profile-action-row {
            border-top: 1px solid var(--profile-border);
            padding: 1.15rem 1.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0) 0%, #fbfcfe 100%);
            gap: 1rem;
        }

        @media (max-width: 991.98px) {
            .profile-cover-content,
            .profile-form-header,
            .profile-tabs-wrapper,
            .profile-section-wrapper,
            .profile-action-row {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }

            .profile-cover-content {
                padding-top: 1.5rem;
                padding-bottom: 1.5rem;
            }

            .profile-sidebar {
                position: static;
            }

            .profile-step-pane {
                min-height: 320px;
                padding: 1.25rem;
            }
        }

        @media (max-width: 575.98px) {
            .profile-cover-stat,
            .profile-progress-badge {
                min-width: 100%;
            }

            .profile-section-header {
                padding: 1rem 1.1rem;
            }

            .profile-action-row .btn {
                width: 100%;
            }

            .profile-tabs {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container-fluid profile-page">
        <x-breadcrum
            title="Edit Profile"
            :items="[
                'Home',
                ['label' => 'General', 'url' => route('admin.dashboard')],
                'Edit Profile',
            ]"
        />
        <x-session_message />

        <div class="profile-cover mb-4">
            <div class="profile-cover-content">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-8">
                        <span class="profile-cover-badge">Member Profile Workspace</span>
                        <h2 class="h3 text-white fw-semibold mt-3 mb-2">Edit and refine member information in one place</h2>
                        <p class="text-white-50 mb-3 mb-lg-4">Review personal details, employment data, contact records, roles, and supporting files from a cleaner step-based workspace.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="profile-cover-chip">5 guided sections</span>
                            <span class="profile-cover-chip">In-place validation</span>
                            <span class="profile-cover-chip">Protected document uploads</span>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="profile-cover-stat">
                            <div class="small text-white-50 text-uppercase mb-1">Member Snapshot</div>
                            <div class="fs-5 fw-semibold">{{ $member->first_name }} {{ $member->last_name }}</div>
                            <div class="small text-white-50 mt-1">Employee ID: {{ $member->emp_id ?? 'Not assigned yet' }}</div>
                            <div class="small text-white-50">The updated layout keeps the same validation and save behavior behind the scenes.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3 col-lg-4">
                <div class="profile-sidebar">
                    <div class="card profile-card h-100 border-0">
                        <div class="card-body p-4">
                            <div class="text-center">
                                <div class="profile-avatar-wrap">
                                    <img src="{{ get_profile_pic() }}" data-fallback-src="{{ asset('images/dummypic.jpeg') }}" alt="Profile Picture" class="profile-avatar-image">
                                </div>
                                <span class="badge rounded-pill bg-light text-primary border mb-3">Profile Overview</span>
                                <h5 class="mb-1 fw-semibold text-dark">{{ $member->first_name }} {{ $member->last_name }}</h5>
                                <p class="text-muted small mb-0">Use the guided sections to refresh profile data without leaving this page.</p>
                            </div>

                            <div class="list-group list-group-flush profile-meta-list mt-4">
                                <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                                    <span class="text-muted small">Official Email</span>
                                    <span class="fw-semibold text-end text-break">{{ Auth::user()->email_id ?? 'No email' }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                                    <span class="text-muted small">Mobile</span>
                                    <span class="fw-semibold text-end">{{ Auth::user()->mobile_no ?? 'No mobile' }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                                    <span class="text-muted small">Sections</span>
                                    <span class="fw-semibold text-end">{{ count($profileSteps) }} tabs</span>
                                </div>
                            </div>

                            <div class="profile-helper-note p-3 mt-4">
                                <div class="fw-semibold small text-dark mb-1">Editing tip</div>
                                <p class="small text-muted mb-0">All sections are checked before the final update, so any missing data is highlighted right where it belongs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-lg-8">
                <form id="member-form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="emp_id" id="emp_id" value="{{ $member->pk }}">

                    <div class="profile-form-card border-0">
                        <div class="profile-form-header">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                                <div>
                                    <div class="text-uppercase small fw-semibold text-primary mb-2">Profile Editor</div>
                                    <h4 class="mb-1">Review every section before saving</h4>
                                    <p class="text-muted mb-0">The layout is refreshed for easier scanning, but the same validation and update flow still powers the form.</p>
                                </div>
                                <div class="profile-progress-badge">
                                    <div class="small text-uppercase text-muted mb-1">Current Section</div>
                                    <div class="fw-semibold text-dark" id="profile-current-step">Personal Details</div>
                                    <div class="small text-muted" id="profile-current-step-counter">Step 1 of {{ count($profileSteps) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-tabs-wrapper">
                            <ul class="nav nav-pills profile-tabs" id="profileTab" role="tablist">
                                @foreach ($profileSteps as $stepNumber => $step)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="profile-step-{{ $stepNumber }}-tab" data-bs-toggle="tab" data-bs-target="#profile-step-{{ $stepNumber }}" type="button" role="tab" aria-controls="profile-step-{{ $stepNumber }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-step-label="{{ $step['title'] }}" data-step-number="{{ $stepNumber }}">
                                            <span class="profile-tab-step">{{ str_pad((string) $stepNumber, 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="profile-tab-content">
                                                <span class="profile-tab-title">{{ $step['title'] }}</span>
                                                <span class="profile-tab-subtitle">{{ $step['description'] }}</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="profile-section-wrapper">
                            <div class="profile-section-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <div class="small text-uppercase fw-semibold text-primary mb-2">Active Workspace</div>
                                    <h5 class="mb-1" id="profile-section-title">Personal Details</h5>
                                    <p class="text-muted mb-0">Update the fields in this section and continue when you are ready. Missing data will be highlighted automatically.</p>
                                </div>
                                <span class="badge rounded-pill bg-light text-primary border px-3 py-2" id="profile-section-badge">Step 1 of {{ count($profileSteps) }}</span>
                            </div>

                            <div class="tab-content profile-step-content">
                                <div class="tab-pane fade show active profile-step-pane" id="profile-step-1" role="tabpanel" aria-labelledby="profile-step-1-tab">
                                    <div class="profile-loading-state">
                                        <div class="spinner-border text-primary profile-loading-spinner" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="fw-semibold text-dark">Loading personal details</div>
                                        <div class="small">Please wait while we prepare the first section.</div>
                                    </div>
                                </div>
                                <div class="tab-pane fade profile-step-pane" id="profile-step-2" role="tabpanel" aria-labelledby="profile-step-2-tab"></div>
                                <div class="tab-pane fade profile-step-pane" id="profile-step-3" role="tabpanel" aria-labelledby="profile-step-3-tab"></div>
                                <div class="tab-pane fade profile-step-pane" id="profile-step-4" role="tabpanel" aria-labelledby="profile-step-4-tab"></div>
                                <div class="tab-pane fade profile-step-pane" id="profile-step-5" role="tabpanel" aria-labelledby="profile-step-5-tab"></div>
                            </div>
                        </div>

                        <div class="profile-action-row">
                            <div class="small text-muted">Changes are validated across all sections before the profile is finally updated.</div>
                            <div class="d-flex flex-wrap gap-2 ms-auto">
                                <a href="{{ route('member.index') }}" class="btn btn-light border px-4">Cancel</a>
                                <button type="button" id="profile-update-btn" class="btn btn-primary px-4">Update Profile</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const form = $("#member-form");
            const empId = $('#emp_id').val();
            const totalSteps = {{ count($profileSteps) }};
            let employeePK = empId;
            const loadedSteps = {};
            const loadPromises = {};

            $('.profile-avatar-image').on('error', function () {
                const fallbackSrc = $(this).data('fallback-src');
                if (fallbackSrc && this.src !== fallbackSrc) {
                    this.src = fallbackSrc;
                }
            });

            function getPaneByStep(stepNumber) {
                return $(`#profile-step-${stepNumber}`);
            }

            function getStepButton(stepNumber) {
                return $(`#profileTab button[data-bs-target="#profile-step-${stepNumber}"]`);
            }

            function getStepLabel(stepNumber) {
                return getStepButton(stepNumber).data('step-label') || `Section ${stepNumber}`;
            }

            function updateStepMeta(stepNumber) {
                const stepLabel = getStepLabel(stepNumber);
                $('#profile-current-step').text(stepLabel);
                $('#profile-current-step-counter').text(`Step ${stepNumber} of ${totalSteps}`);
                $('#profile-section-title').text(stepLabel);
                $('#profile-section-badge').text(`Step ${stepNumber} of ${totalSteps}`);
            }

            function getLoadingMarkup(stepNumber) {
                const stepLabel = getStepLabel(stepNumber);
                return `
                    <div class="profile-loading-state">
                        <div class="spinner-border text-primary profile-loading-spinner" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="fw-semibold text-dark">Loading ${stepLabel}</div>
                        <div class="small">Please wait while we fetch the latest information for this section.</div>
                    </div>
                `;
            }

            function clearErrors(stepElement) {
                stepElement.find("div.text-danger").remove();
                stepElement.find("div.validation-error").remove();
                stepElement.find(".is-invalid").removeClass("is-invalid");
            }

            function showErrors(stepElement, errors) {
                clearErrors(stepElement);
                $.each(errors, function (field, messages) {
                    const input = stepElement.find(`[name="${field}"]`);
                    const message = messages[0];
                    const errorDiv = $('<div class="text-danger mt-1"></div>').text(message);
                    if (input.length) {
                        input.addClass("is-invalid").first().after(errorDiv);
                    } else {
                        stepElement.prepend(errorDiv);
                    }
                });
            }

            function injectEmpIdToPane(stepPane) {
                if (!stepPane.find('input[name="emp_id"]').length) {
                    stepPane.append(`<input type="hidden" name="emp_id" value="${employeePK}">`);
                } else {
                    stepPane.find('input[name="emp_id"]').val(employeePK);
                }
            }

            function loadStepContent(stepNumber) {
                if (loadedSteps[stepNumber]) {
                    return Promise.resolve();
                }

                if (loadPromises[stepNumber]) {
                    return loadPromises[stepNumber];
                }

                const stepPane = getPaneByStep(stepNumber);
                const stepButton = getStepButton(stepNumber);
                stepPane.html(getLoadingMarkup(stepNumber));
                stepButton.addClass('is-loading');

                loadPromises[stepNumber] = $.ajax({
                    url: `/member/edit-step/${stepNumber}/${employeePK}`,
                    method: "GET",
                    success: function (html) {
                        stepPane.html(html);
                        injectEmpIdToPane(stepPane);
                        loadedSteps[stepNumber] = true;
                    },
                    error: function (xhr) {
                        toastr.error(`Failed to load section ${stepNumber} (HTTP ${xhr.status})`);
                        stepPane.html(`<div class="alert alert-danger">Failed to load section ${stepNumber}</div>`);
                    },
                    complete: function () {
                        stepButton.removeClass('is-loading');
                        delete loadPromises[stepNumber];
                    }
                });

                return loadPromises[stepNumber];
            }

            function validateStep(stepNumber) {
                const stepPane = getPaneByStep(stepNumber);
                let stepData = stepPane.find(':input').serialize();
                stepData += `&emp_id=${employeePK}`;

                return $.ajax({
                    url: `/member/update-validate-step/${stepNumber}/${employeePK}`,
                    method: "POST",
                    data: stepData + '&_token={{ csrf_token() }}',
                    success: function (success) {
                        if (success.pk) {
                            employeePK = success.pk;
                            $('#emp_id').val(employeePK);
                        }
                        clearErrors(stepPane);
                    },
                    error: function (xhr) {
                        const status = xhr.status;
                        const errors = xhr.responseJSON?.errors || {};

                        if (status === 422) {
                            showErrors(stepPane, errors);
                        } else {
                            toastr.error(xhr.responseJSON?.message || `Unexpected error (${status}) occurred.`);
                        }
                    }
                });
            }

            async function saveProfile() {
                const updateButton = $('#profile-update-btn');
                updateButton.prop('disabled', true).text('Updating...');

                try {
                    for (let step = 1; step <= 5; step++) {
                        await loadStepContent(step);
                    }

                    for (let step = 1; step <= 5; step++) {
                        try {
                            await validateStep(step);
                        } catch (e) {
                            $(`#profileTab button[data-bs-target="#profile-step-${step}"]`).tab('show');
                            updateButton.prop('disabled', false).text('Update Profile');
                            return;
                        }
                    }

                    const formData = new FormData(form[0]);
                    formData.set('emp_id', employeePK);

                    await $.ajax({
                        url: "{{ route('member.update') }}",
                        method: "POST",
                        data: formData,
                        contentType: false,
                        processData: false
                    });

                    toastr.success("Profile updated successfully!");
                    window.location.href = "{{ route('member.profile.edit', Auth::user()->user_id) }}";
                } catch (e) {
                    const status = e.status;
                    const errors = e.responseJSON?.errors || {};

                    if (status === 422) {
                        showErrors(getPaneByStep(5), errors);
                    } else {
                        toastr.error(e.responseJSON?.message || `Unexpected error (${status || 'N/A'}) occurred.`);
                    }
                    updateButton.prop('disabled', false).text('Update Profile');
                }
            }

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const target = $(e.target).data('bs-target');
                const stepNumber = Number(String(target).replace('#profile-step-', ''));
                if (stepNumber) {
                    updateStepMeta(stepNumber);
                    loadStepContent(stepNumber);
                }
            });

            $('#profile-update-btn').on('click', function () {
                saveProfile();
            });

            updateStepMeta(1);
            loadStepContent(1);
        });
    </script>
@endsection
