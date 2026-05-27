@extends('admin.layouts.master')

@section('title', 'View Course')

@section('setup_content')
    @php
        $assistantList = collect($assistantCoordinatorsData ?? [])->values();
        $assistantCount = $assistantList->count();
        $totalFaculty = $assistantCount + 1;
        $coordinatorPhoto = $coordinatorFaculty?->photo_uplode_path;
        $coordinatorName = ($coordinatorName && $coordinatorName !== 'Not Assigned') ? $coordinatorName : null;
        $coordinatorDisplay = $coordinatorName ?: 'NA';
        $startDate = filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('d M Y') : null;
        $endDate = filled($course->end_date) ? \Carbon\Carbon::parse($course->end_date)->format('d M Y') : null;
        $updatedAt = \Carbon\Carbon::parse($course->Modified_date ?? $course->updated_at ?? now())->format('d M Y, h:i A');
        $isActive = filled($course->end_date) && \Carbon\Carbon::parse($course->end_date)->startOfDay()->gte(now()->startOfDay());
        $statusLabel = $isActive ? 'Active' : 'Archived';
        $primaryContactCount = $coordinatorName ? 1 : 0;

        $totalMembers = $totalFaculty;
        if (method_exists($course, 'studentMasterCourseMap')) {
            $mappedCount = (int) $course->studentMasterCourseMap()->count();
            if ($mappedCount > 0) {
                $totalMembers = $mappedCount;
            }
        }

        $display = static function ($value, $fallback = 'NA') {
            return filled($value) ? $value : $fallback;
        };

        $assistantRoleLabel = static function ($role) {
            $role = trim((string) $role);
            if ($role === '' || strcasecmp($role, 'Not Specified') === 0) {
                return 'Assistant Coordinator';
            }
            if (preg_match('/^for\s+/i', $role)) {
                return $role;
            }
            return 'For ' . $role;
        };
    @endphp

    <div class="container-fluid" id="programme-show-top">
 <x-breadcrum title="{{ $course->course_name }}" />

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm rounded-3 h-100 programme-show-stat-card hover-lift">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="programme-show-stat-icon bg-primary-subtle text-primary">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">calendar_month</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Course Year</div>
                            <div class="fw-bold text-dark">{{ $display($course->course_year) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm rounded-3 h-100 programme-show-stat-card hover-lift">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="programme-show-stat-icon bg-success-subtle text-success">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">people</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Total Members</div>
                            <div class="fw-bold text-dark">{{ $totalMembers }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm rounded-3 h-100 programme-show-stat-card hover-lift">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="programme-show-stat-icon bg-warning-subtle text-warning">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">calendar_month</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Availability</div>
                            <div class="fw-bold text-dark">{{ $display($course->duration) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm rounded-3 h-100 programme-show-stat-card hover-lift">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="programme-show-stat-icon programme-show-stat-icon--purple">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">person</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Coordinator</div>
                            <div class="fw-bold text-dark text-truncate" title="{{ $coordinatorDisplay }}">{{ $coordinatorDisplay }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm rounded-3 h-100 programme-show-stat-card hover-lift">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="programme-show-stat-icon bg-success-subtle text-success">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">check_circle</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Status</div>
                            <div class="fw-bold {{ $isActive ? 'text-success' : 'text-secondary' }}">{{ $statusLabel }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-2">Course Information</h2>
                        <hr class="programme-show-divider mb-4">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Course Code</div>
                                    <div class="programme-show-field__value">{{ $display($course->course_code) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Course Name</div>
                                    <div class="programme-show-field__value">{{ $display($course->course_name) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Course Category</div>
                                    <div class="programme-show-field__value">{{ $display($course->course_category ?? null) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Course Type</div>
                                    <div class="programme-show-field__value">{{ $display($course->course_type) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Duration</div>
                                    <div class="programme-show-field__value">{{ $display($course->duration) }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Coordinator</div>
                                    <div class="programme-show-field__value">{{ $primaryContactCount }}</div>
                                </div>
                            </div>
                        </div>

                        @if ($course->description)
                            <div class="mt-4 pt-2">
                                <div class="programme-show-field">
                                    <div class="programme-show-field__label">Description</div>
                                    <div class="programme-show-field__value fw-normal text-body-secondary">{{ $course->description }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-2">Faculty Team</h2>
                        <hr class="programme-show-divider mb-4">

                        <p class="small text-body-secondary mb-3">
                            Primary Contacts: {{ str_pad((string) $primaryContactCount, 2, '0', STR_PAD_LEFT) }}
                        </p>

                        <div class="programme-show-contact-card programme-show-contact-card--primary mb-4">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                @include('admin.programme.partials.person-avatar', [
                                    'name' => $coordinatorName ?: 'Coordinator',
                                    'photo' => $coordinatorPhoto,
                                    'size' => 'programme-person-avatar--lg',
                                ])
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate">{{ $coordinatorName ?: 'Not assigned' }}</div>
                                    <div class="small text-body-secondary">Course Coordinator</div>
                                </div>
                            </div>
                            <span class="badge rounded-pill bg-white text-dark border programme-show-contact-badge flex-shrink-0">
                                Primary Contact
                            </span>
                        </div>

                        <p class="small text-body-secondary mb-3">
                            Assistant Coordinators: {{ str_pad((string) $assistantCount, 2, '0', STR_PAD_LEFT) }}
                        </p>

                        @forelse ($assistantList as $assistant)
                            <div class="programme-show-contact-card programme-show-contact-card--assistant{{ $loop->last ? '' : ' mb-3' }}">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    @include('admin.programme.partials.person-avatar', [
                                        'name' => $assistant['name'] ?? 'Assistant',
                                        'photo' => $assistant['photo'] ?? null,
                                        'size' => 'programme-person-avatar--lg',
                                    ])
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark text-truncate">{{ $assistant['name'] ?? 'Not Assigned' }}</div>
                                        <div class="small text-body-secondary">{{ $assistantRoleLabel($assistant['role'] ?? '') }}</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3 border bg-body-tertiary text-center py-4 px-3">
                                <i class="bi bi-people text-body-secondary fs-2 mb-2 d-block" aria-hidden="true"></i>
                                <p class="mb-0 small text-body-secondary">No assistant coordinators assigned for this course.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if ($course->objectives || $course->learning_outcomes || $course->prerequisites)
                    <div class="card border-0 shadow-sm rounded-3 mt-4">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold text-dark mb-2">Additional Information</h2>
                            <hr class="programme-show-divider mb-4">
                            <div class="row g-4">
                                @if ($course->objectives)
                                    <div class="col-md-4">
                                        <div class="programme-show-field">
                                            <div class="programme-show-field__label">Course Objectives</div>
                                            <div class="programme-show-field__value fw-normal text-body-secondary">{{ $course->objectives }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if ($course->learning_outcomes)
                                    <div class="col-md-4">
                                        <div class="programme-show-field">
                                            <div class="programme-show-field__label">Learning Outcomes</div>
                                            <div class="programme-show-field__value fw-normal text-body-secondary">{{ $course->learning_outcomes }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if ($course->prerequisites)
                                    <div class="col-md-4">
                                        <div class="programme-show-field">
                                            <div class="programme-show-field__label">Prerequisites</div>
                                            <div class="programme-show-field__value fw-normal text-body-secondary">{{ $course->prerequisites }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 programme-show-summary-card">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-2">Record Summary</h2>
                        <hr class="programme-show-divider mb-4">

                        <div class="programme-show-field mb-3">
                            <div class="programme-show-field__label">Course Code</div>
                            <div class="programme-show-field__value">{{ $display($course->course_code) }}</div>
                        </div>
                        <div class="programme-show-field mb-3">
                            <div class="programme-show-field__label">Created By</div>
                            <div class="programme-show-field__value">{{ $display($course->created_by ?? null) }}</div>
                        </div>
                        <div class="programme-show-field mb-3">
                            <div class="programme-show-field__label">Start Date</div>
                            <div class="programme-show-field__value">{{ $startDate ?: 'NA' }}</div>
                        </div>
                        <div class="programme-show-field mb-3">
                            <div class="programme-show-field__label">End Date</div>
                            <div class="programme-show-field__value">{{ $endDate ?: 'NA' }}</div>
                        </div>
                        <div class="programme-show-field mb-0">
                            <div class="programme-show-field__label">Last Updated</div>
                            <div class="programme-show-field__value">{{ $updatedAt }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function getFirstNameInitial(name) {
                var parts = (name || '').trim().split(/\s+/);
                return parts[0] ? parts[0].charAt(0).toUpperCase() : '?';
            }

            function activateAvatarFallback(img) {
                var wrap = img.closest('.programme-person-avatar');
                if (!wrap) {
                    return;
                }
                var name = wrap.getAttribute('data-person-name') || img.getAttribute('alt') || '';
                var fallback = wrap.querySelector('.programme-person-avatar__fallback');
                if (fallback) {
                    fallback.textContent = getFirstNameInitial(name.replace(/^Photo of\s+/i, ''));
                }
                wrap.classList.add('is-fallback');
            }

            document.querySelectorAll('.programme-person-avatar__img').forEach(function(image) {
                if (image.complete && image.naturalWidth === 0) {
                    activateAvatarFallback(image);
                    return;
                }
                image.addEventListener('error', function() {
                    activateAvatarFallback(this);
                });
            });

            document.querySelectorAll('[data-print-trigger]').forEach(function(button) {
                button.addEventListener('click', function() {
                    var originalHtml = this.dataset.originalHtml || this.innerHTML;
                    this.dataset.originalHtml = originalHtml;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Preparing...';
                    this.disabled = true;

                    window.setTimeout(function() {
                        window.print();
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }, 400);
                });
            });

            document.querySelectorAll('.programme-show-back-top').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var target = document.getElementById('programme-show-top');
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });
    </script>
@endsection
