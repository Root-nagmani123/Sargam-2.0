@extends('admin.layouts.master')

@section('title', 'View Course')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="{{ asset('css/programme-admin.css') }}?v={{ @filemtime(public_path('css/programme-admin.css')) ?: time() }}">
@endpush

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
        $durationLabel = null;
        if (filled($course->start_year) && filled($course->end_date)) {
            $start = \Carbon\Carbon::parse($course->start_year)->startOfDay();
            $end = \Carbon\Carbon::parse($course->end_date)->startOfDay();
            if ($end->gte($start)) {
                $totalDays = $start->diffInDays($end) + 1;
                $weeks = (int) ceil($totalDays / 7);
                $durationLabel = $weeks . ' ' . \Illuminate\Support\Str::plural('Week', $weeks);
            }
        }
        $ptTimeLabel = null;
        if (filled($course->pt_start_time) && filled($course->pt_end_time)) {
            $ptTimeLabel = \Carbon\Carbon::parse($course->pt_start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($course->pt_end_time)->format('h:i A');
        }
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

    <div class="container-fluid prog-page programme-show-page" id="programme-show-top">
    {{-- The hero carries the course name (§3d), so the crumb stays short. It also
         must NOT be an interpolated attribute: `title="{{ $name }}"` escapes once
         into the attribute and the component escapes again, which is why a name
         containing ' and & rendered as &#039; and &amp; here. --}}
    <x-breadcrum title="Course Details" />

        {{-- Detail = hero + facts grid (docs/new-design-index-page.md §3d). The
             hero carries the record name, the headline facts and the current
             state; the badge is white on the tinted hero rather than a *-subtle
             tint, which would read as a smudge. --}}
        <div class="prog-hero">
            <div class="min-w-0">
                <h1 class="prog-hero-name">{{ $display($course->course_name) }}</h1>
                <div class="prog-facts prog-facts--hero">
                    <div class="prog-fact">
                        <span class="prog-fact__label">Short Name</span>
                        <div class="prog-fact__value">{{ $display($course->couse_short_name) }}</div>
                    </div>
                    <div class="prog-fact">
                        <span class="prog-fact__label">Course Year</span>
                        <div class="prog-fact__value">{{ $display($course->course_year) }}</div>
                    </div>
                    <div class="prog-fact">
                        <span class="prog-fact__label">Coordinator</span>
                        <div class="prog-fact__value">{{ $coordinatorDisplay }}</div>
                    </div>
                    <div class="prog-fact">
                        <span class="prog-fact__label">Total Members</span>
                        <div class="prog-fact__value">{{ $totalMembers }}</div>
                    </div>
                </div>
            </div>

            <span class="status-pill badge rounded-1 {{ $isActive ? 'is-active' : 'is-archived' }}">
                {{ $statusLabel }}
            </span>
        </div>

        {{-- Course Information — only what the hero does not already state.
             The course name is the hero title, and Short Name / Course Year /
             Coordinator are hero facts, so repeating them here (as the old
             "Course Information" + "Record Summary" pair did) just made the
             page say the same thing three times. --}}
        <div class="prog-card">
            <div class="prog-section"><h2 class="prog-section-title">Course Information</h2></div>
            <div class="prog-facts">
                <div class="prog-fact">
                    <span class="prog-fact__label">Supporting Section</span>
                    <div class="prog-fact__value {{ filled($supportingSectionName ?? null) ? '' : 'is-empty' }}">{{ filled($supportingSectionName ?? null) ? $supportingSectionName : '—' }}</div>
                </div>
                <div class="prog-fact">
                    <span class="prog-fact__label">Created By</span>
                    <div class="prog-fact__value {{ filled($createdByName ?? null) ? '' : 'is-empty' }}">{{ filled($createdByName ?? null) ? $createdByName : '—' }}</div>
                </div>

                <div class="prog-fact prog-fact--wide">
                    <span class="prog-fact__label">Description</span>
                    <div class="prog-fact__value {{ filled($course->description) ? '' : 'is-empty' }}">{{ filled($course->description) ? $course->description : '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="prog-card">
            <div class="prog-section"><h2 class="prog-section-title">Schedule</h2></div>
            <div class="prog-facts">
                <div class="prog-fact">
                    <span class="prog-fact__label">Start Date</span>
                    <div class="prog-fact__value {{ $startDate ? '' : 'is-empty' }}">{{ $startDate ?: '—' }}</div>
                </div>
                <div class="prog-fact">
                    <span class="prog-fact__label">End Date</span>
                    <div class="prog-fact__value {{ $endDate ? '' : 'is-empty' }}">{{ $endDate ?: '—' }}</div>
                </div>
                <div class="prog-fact">
                    <span class="prog-fact__label">Duration</span>
                    <div class="prog-fact__value {{ $durationLabel ? '' : 'is-empty' }}">{{ $durationLabel ?: '—' }}</div>
                </div>
                <div class="prog-fact">
                    <span class="prog-fact__label">PT Timing</span>
                    <div class="prog-fact__value {{ $ptTimeLabel ? '' : 'is-empty' }}">{{ $ptTimeLabel ?: '—' }}</div>
                </div>
                <div class="prog-fact">
                    <span class="prog-fact__label">Last Updated</span>
                    <div class="prog-fact__value">{{ $updatedAt }}</div>
                </div>
            </div>
        </div>

        {{-- Faculty Team --}}
        <div class="prog-card">
            <div class="prog-section"><h2 class="prog-section-title">Faculty Team</h2></div>
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
                            <span class="badge rounded-1 bg-white text-dark border programme-show-contact-badge flex-shrink-0">
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

        @if ($course->objectives || $course->learning_outcomes || $course->prerequisites)
            <div class="prog-card">
                <div class="prog-section"><h2 class="prog-section-title">Additional Information</h2></div>
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
        @endif
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
