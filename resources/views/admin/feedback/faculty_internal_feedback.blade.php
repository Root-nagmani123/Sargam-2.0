@extends('admin.layouts.timetable')
@section('title', 'Faculty Internal Feedback')
@section('content')
<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">

{{-- Flatpickr (dual-month date range picker) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>

<style>
    .session-feedback-main { --sf-primary: #004a93; }
    .session-feedback-card { overflow: hidden; }
    .sf-toolbar, .sf-filterbar { margin-bottom: 20px; }

    .sf-toolbar .nav-pills .nav-link {
        font-weight: 600;
        font-size: .875rem;
        color: var(--sf-primary);
        background: #eef3f9;
        padding: .45rem 1.1rem;
        border: 1px solid #dbe6f2;
        transition: all .15s ease;
    }
    .sf-toolbar .nav-pills .nav-link:hover { background: #e2edf8; }
    .sf-toolbar .nav-pills .nav-link.active {
        color: #fff;
        background: var(--sf-primary);
        border-color: var(--sf-primary);
        box-shadow: 0 .25rem .6rem rgba(0,74,147,.25);
    }
    .sf-toolbar .nav-pills .nav-link .badge {
        background: rgba(255,255,255,.25);
        font-weight: 700;
    }
    .sf-toolbar .nav-pills .nav-link:not(.active) .badge {
        background: var(--sf-primary);
        color: #fff;
    }

    .sf-filterbar .form-select-sm { min-width: 140px; }
    .sf-filterbar .sf-icon-btn {
        width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #d6dee8; border-radius: .5rem; background: #fff; color: #5a6b7e;
    }
    .sf-filterbar .sf-icon-btn:hover { background: #f3f7fc; }

    .sf-table-wrap { position: relative; }
    .sf-table { margin-bottom: 0; }
    .sf-table thead th {
        background: #f6f8fb;
        color: #6c7689;
        font-size: .82rem;
        font-weight: 600;
        border-top: 1px solid #eef1f5;
        border-bottom: 1px solid #e7ecf2;
        white-space: nowrap;
        vertical-align: middle;
        padding: .9rem 1rem;
    }
    .sf-table tbody td {
        vertical-align: middle;
        padding: .85rem 1rem;
        border-bottom: 1px solid #eef1f5;
        font-size: .9rem;
        color: #344256;
    }
    .sf-table tbody tr { transition: background-color .12s ease; }
    .sf-table tbody tr:hover { background-color: #f8fafc; }

    #pending-tab-pane .star-rating { display: inline-flex; flex-direction: row-reverse; }
    #pending-tab-pane .star-rating input { position: absolute; opacity: 0; width: 0; height: 0; }
    #pending-tab-pane .star-rating label {
        font-size: 1.4rem; line-height: 1; cursor: pointer; padding: 0 .1rem;
        color: #d8dde4; transition: color .15s ease; margin: 0;
    }
    #pending-tab-pane .star-rating label:hover,
    #pending-tab-pane .star-rating label:hover ~ label,
    #pending-tab-pane .star-rating input:checked ~ label { color: #f5b301; }

    .star-rating-display .bi { font-size: 1rem; }

    .give-feedback-btn { transition: all .15s ease; white-space: nowrap; }
    .give-feedback-btn:hover { transform: translateY(-1px); box-shadow: 0 .25rem .5rem rgba(0,74,147,.18); }

    .sf-remark {
        min-width: 200px; width: 100%; resize: none; font-size: .85rem;
        height: 38px; line-height: 1.4; overflow: hidden; white-space: nowrap;
    }

    .sf-pagination .page-link {
        color: var(--sf-primary);
        border-radius: .5rem;
        margin: 0 .12rem;
        border: 1px solid #e2e8f0;
        font-size: .85rem;
        min-width: 34px;
        text-align: center;
    }
    .sf-pagination .page-item.active .page-link {
        background: var(--sf-primary);
        border-color: var(--sf-primary);
        color: #fff;
    }
    .sf-pagination .page-item.disabled .page-link { color: #adb5bd; }
    .sf-pagesize { width: auto; }

    .sf-daterange-wrap { display: inline-block; }
    .sf-daterange-input {
        width: auto; min-width: 190px; padding-left: 2.1rem; cursor: pointer; background: #fff;
    }
    .sf-daterange-input:focus { box-shadow: none; border-color: var(--sf-primary); }
    .sf-daterange-icon {
        position: absolute; left: .7rem; top: 50%; transform: translateY(-50%);
        color: #6c7689; pointer-events: none;
    }

    .sf-filterbar .sf-search-input { width: 220px; }

    .sf-cols-modal { border: 0; border-radius: 16px; box-shadow: 0 1.5rem 3rem rgba(16,42,76,.20); }
    .sf-cols-modal .modal-title { color: #1f2a37; }
    .sf-cols-grid { display: flex; flex-wrap: wrap; gap: .65rem; }
    .sf-col-chip {
        display: inline-flex; align-items: center; gap: .55rem; margin: 0;
        flex: 0 0 calc((100% - 1.3rem) / 3);
        border: 1px solid #d6dee8; border-radius: .65rem;
        padding: .55rem .8rem; cursor: pointer;
        font-size: .9rem; color: #344256; background: #fff;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        transition: background-color .12s ease, border-color .12s ease;
    }
    .sf-col-chip:hover { background: #f6f8fb; border-color: #c3d0de; }
    .sf-col-chip input { margin: 0; flex: 0 0 auto; }
    .sf-col-chip:has(input:checked) { border-color: #0d6efd; background: #f1f6fc; }
    @media (max-width: 575.98px) {
        .sf-col-chip { flex-basis: calc((100% - .65rem) / 2); }
    }

    .flatpickr-calendar.sf-flatpickr { box-shadow: 0 .5rem 1.75rem rgba(16,42,76,.18); border-radius: .75rem; }
    .sf-flatpickr .flatpickr-day.selected,
    .sf-flatpickr .flatpickr-day.startRange,
    .sf-flatpickr .flatpickr-day.endRange {
        background: #004a93; border-color: #004a93; color: #fff;
    }
    .sf-flatpickr .flatpickr-day.inRange {
        background: #e7f0fa; border-color: #e7f0fa;
        box-shadow: -5px 0 0 #e7f0fa, 5px 0 0 #e7f0fa;
    }
    .sf-flatpickr .flatpickr-day.today { border-color: #004a93; }
    .sf-flatpickr .flatpickr-day:hover { background: #eef3f9; }
</style>

<div class="container-fluid session-feedback-main p-0 py-2 px-2">
    <!-- Toolbar -->
    <div class="sf-toolbar">
        <div class="row align-items-center g-3">
            <div class="col-12 col-lg-auto me-lg-auto">
                <ul class="nav nav-pills flex-wrap gap-2 feedback-nav-tabs" id="feedbackTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-inline-flex align-items-center" id="pending-tab"
                            data-bs-toggle="tab" data-bs-target="#pending-tab-pane" type="button" role="tab"
                            aria-selected="true">Pending
                            <span class="badge rounded-1 ms-2" id="pending-count">{{ $pendingData->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-inline-flex align-items-center" id="submitted-tab"
                            data-bs-toggle="tab" data-bs-target="#submitted-tab-pane" type="button" role="tab"
                            aria-selected="false">Submitted
                            <span class="badge rounded-1 ms-2" id="submitted-count">{{ $submittedData->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>
            @if(!$isAdmin)
            <div class="col-12 col-lg-auto d-flex gap-2">
                <button type="submit" form="vertical-wizard" class="btn btn-primary rounded-1">
                    Bulk Feedback Submit
                </button>
            </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 session-feedback-card">

        <!-- Filter bar -->
        <div class="card-header sf-filterbar">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold me-1" style="font-size: .85rem; color: #6c7689;">Filters</span>

                <div class="sf-daterange-wrap position-relative">
                    <i class="bi bi-calendar3 sf-daterange-icon"></i>
                    <input type="text" id="sf-daterange" class="form-control rounded-1 sf-daterange-input"
                        placeholder="Time Period" readonly>
                </div>

                <select id="sf-rating-filter" class="form-select rounded-1 w-auto">
                    <option value="">Ratings</option>
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars &amp; up</option>
                    <option value="3">3 Stars &amp; up</option>
                </select>

                <button type="button" id="sf-reset-filters" class="btn btn-outline-secondary rounded-1 fw-semibold">
                    Reset Filters
                </button>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1 d-inline-flex align-items-center"
                        style="border:1px solid #d6dee8; color:#6c757d;" id="sf-columns-btn"
                        data-bs-toggle="modal" data-bs-target="#sfColumnsModal">
                        <i class="bi bi-layout-three-columns me-1"></i> Columns
                    </button>
                    <input type="search" id="sf-search-input"
                        class="form-control rounded-1 sf-search-input d-none" placeholder="Search topic, faculty…">
                    <button type="button" id="sf-search-btn" class="sf-icon-btn" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <input type="date" id="date-filter" class="d-none">
            <button type="button" id="clear-date-filter" class="d-none" style="display:none;"></button>
        </div>

        <!-- Tabs Content -->
        <div class="tab-content" id="feedbackTabsContent">

            <!-- Pending Feedback Tab -->
            <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel"
                aria-labelledby="pending-tab" tabindex="0">
                @if ($pendingData->count() > 0)
                    <form id="vertical-wizard" method="POST"
                        action="{{ route('feedback.submit.facultyInternalFeedback') }}">
                        @csrf
                        <div class="card-body p-0">
                            <div class="table-responsive feedback-table-wrap sf-table-wrap">
                                <table class="table align-middle sf-table text-nowrap">
                                    <thead>
                                        <tr>
                                            <th class="text-center">S. No.</th>
                                            <th>Date &amp; time</th>
                                            <th>Topic Detail</th>
                                            <th>Faculty Name</th>
                                            @if($isAdmin)<th>Reviewed By</th>@endif
                                            <th class="text-center">Content Ratings</th>
                                            <th class="text-center">Presentation Ratings</th>
                                            <th>Remarks</th>
                                            @if(!$isAdmin)<th class="text-center">Action</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody id="pending-feedback-body">
                                        @php $pendingIndex = 0; @endphp
                                        @foreach ($pendingData as $feedback)
                                            <tr data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}">
                                                <td class="text-center fw-semibold text-muted">{{ ++$pendingIndex }}</td>
                                                <td class="text-nowrap">
                                                    <span class="fw-medium">{{ \Carbon\Carbon::parse($feedback->from_date)->format('d/m/Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ $feedback->class_session }}</small>
                                                </td>
                                                <td class="fw-medium text-wrap" style="min-width: 180px;">{{ $feedback->subject_topic }}</td>
                                                <td class="text-nowrap">{{ $feedback->main_faculty_name }}</td>
                                                @if($isAdmin)
                                                    <td class="text-nowrap">{{ $feedback->supporting_faculty_name }}</td>
                                                @endif

                                                @php
                                                    $fbType    = $feedback->faculty_feedback_type ?? 'both';
                                                    $showRating = in_array($fbType, ['rating', 'both']);
                                                    $showRemark = in_array($fbType, ['remark', 'both']);
                                                @endphp

                                                {{-- Content Rating --}}
                                                <td class="text-center">
                                                    @if(!$isAdmin && $showRating)
                                                        <div class="star-rating">
                                                            @for ($i = 5; $i >= 1; $i--)
                                                                <input type="radio"
                                                                    id="content-{{ $i }}-{{ $loop->index }}"
                                                                    name="content[{{ $loop->index }}]"
                                                                    value="{{ $i }}">
                                                                <label for="content-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                            @endfor
                                                        </div>
                                                    @elseif($isAdmin)
                                                        <span class="text-muted small">Pending</span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>

                                                {{-- Presentation Rating --}}
                                                <td class="text-center">
                                                    @if(!$isAdmin && $showRating)
                                                        <div class="star-rating">
                                                            @for ($i = 5; $i >= 1; $i--)
                                                                <input type="radio"
                                                                    id="presentation-{{ $i }}-{{ $loop->index }}"
                                                                    name="presentation[{{ $loop->index }}]"
                                                                    value="{{ $i }}">
                                                                <label for="presentation-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                            @endfor
                                                        </div>
                                                    @elseif($isAdmin)
                                                        <span class="text-muted small">Pending</span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>

                                                {{-- Remarks --}}
                                                <td style="min-width: 220px;">
                                                    @if(!$isAdmin && $showRemark)
                                                        <textarea class="form-control form-control-sm rounded-1 sf-remark"
                                                            name="remarks[{{ $loop->index }}]" rows="1"
                                                            placeholder="Enter remark"></textarea>
                                                    @elseif($isAdmin)
                                                        <span class="text-muted small">—</span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>

                                                {{-- Action (faculty only) --}}
                                                @if(!$isAdmin)
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-outline-primary btn-sm rounded-1 fw-semibold give-feedback-btn individual-feedback-submit-btn d-inline-flex align-items-center"
                                                        onclick="submitFacultyFeedback({{ $loop->index }})">
                                                        <i class="bi bi-send me-1"></i> Give Feedback
                                                    </button>

                                                    <input type="hidden" name="feedback_pk[{{ $loop->index }}]"
                                                        value="{{ $feedback->feedback_pk }}">
                                                    <input type="hidden" name="Ratting_checkbox[{{ $loop->index }}]"
                                                        value="{{ $showRating ? 1 : 0 }}">
                                                    <input type="hidden" name="Remark_checkbox[{{ $loop->index }}]"
                                                        value="{{ $showRemark ? 1 : 0 }}">
                                                </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div id="table-loader"
                                    style="display:none; position:absolute; top:0; left:0; width:100%; height:100%;
                                           background:rgba(255,255,255,0.7); justify-content:center; align-items:center; z-index:10;">
                                    <div style="text-align:center;">
                                        <svg class="spinner" width="32" height="32" viewBox="0 0 50 50">
                                            <circle cx="25" cy="25" r="20" stroke="#004A93"
                                                stroke-width="5" fill="rgba(255,255,255,0)" />
                                        </svg>
                                        <p style="margin-top:0.5rem; font-weight:500;">Submitting...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top px-3 px-md-4 py-3">
                            <div class="row align-items-center g-2">
                                <div class="col-12 col-md-auto me-md-auto">
                                    <nav aria-label="Feedback pagination">
                                        <ul class="pagination pagination-sm mb-0 sf-pagination flex-wrap"></ul>
                                    </nav>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <div class="d-flex align-items-center justify-content-md-end gap-2 text-muted small">
                                        <span>Rows per page</span>
                                        <select class="form-select form-select-sm rounded-1 sf-pagesize">
                                            <option selected>10</option>
                                            <option>20</option>
                                            <option>50</option>
                                            <option>100</option>
                                        </select>
                                        <span class="sf-showing-info ms-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="text-center py-5 px-3">
                        <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3"
                            style="width:72px;height:72px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 2.25rem;"></i>
                        </span>
                        <h5 class="fw-semibold mb-1">No pending feedback</h5>
                        <p class="text-muted mb-0">All feedback has been submitted.</p>
                    </div>
                @endif
            </div>

            <!-- Submitted Feedback Tab -->
            <div class="tab-pane fade" id="submitted-tab-pane" role="tabpanel"
                aria-labelledby="submitted-tab" tabindex="0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle sf-table text-wrap">
                            <thead>
                                <tr>
                                    <th class="text-center">S. No.</th>
                                    <th width="15%">Date &amp; time</th>
                                    <th>Topic Detail</th>
                                    <th>Faculty Name</th>
                                    @if($isAdmin)<th>Reviewed By</th>@endif
                                    <th class="text-center">Content Ratings</th>
                                    <th class="text-center">Presentation Ratings</th>
                                    <th>Remarks</th>
                                    <th width="15%">Submitted On</th>
                                </tr>
                            </thead>
                            <tbody id="submitted-feedback-body">
                                @if ($submittedData->count() > 0)
                                    @php $submittedIndex = 0; @endphp
                                    @foreach ($submittedData as $feedback)
                                        <tr data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}"
                                            data-rating="{{ $feedback->content ?? 0 }}">
                                            <td class="text-center fw-semibold text-muted">{{ ++$submittedIndex }}</td>
                                            <td class="text-nowrap">
                                                <span class="fw-medium">{{ \Carbon\Carbon::parse($feedback->from_date)->format('d/m/Y') }}</span>
                                                <br>
                                                <small class="text-muted">{{ $feedback->class_session }}</small>
                                            </td>
                                            <td class="fw-medium">{{ $feedback->subject_topic }}</td>
                                            <td>{{ $feedback->main_faculty_name }}</td>
                                            @if($isAdmin)
                                                <td>{{ $feedback->supporting_faculty_name }}</td>
                                            @endif

                                            {{-- Content Rating --}}
                                            <td class="text-center">
                                                @if ($feedback->content)
                                                    <div class="star-rating-display">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            @if ($i <= $feedback->content)
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            @else
                                                                <i class="bi bi-star text-secondary"></i>
                                                            @endif
                                                        @endfor
                                                        <div><small class="text-muted">({{ $feedback->content }}/5)</small></div>
                                                    </div>
                                                @else
                                                    <span class="badge bg-light text-muted border">N/A</span>
                                                @endif
                                            </td>

                                            {{-- Presentation Rating --}}
                                            <td class="text-center">
                                                @if ($feedback->presentation)
                                                    <div class="star-rating-display">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            @if ($i <= $feedback->presentation)
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            @else
                                                                <i class="bi bi-star text-secondary"></i>
                                                            @endif
                                                        @endfor
                                                        <div><small class="text-muted">({{ $feedback->presentation }}/5)</small></div>
                                                    </div>
                                                @else
                                                    <span class="badge bg-light text-muted border">N/A</span>
                                                @endif
                                            </td>

                                            {{-- Remarks --}}
                                            <td style="min-width: 180px;">
                                                @if ($feedback->remark)
                                                    <div class="remarks-text small text-secondary"
                                                        style="max-height: 60px; overflow-y: auto;">
                                                        {{ $feedback->remark }}
                                                    </div>
                                                @else
                                                    <span class="badge bg-light text-muted border">N/A</span>
                                                @endif
                                            </td>

                                            {{-- Submitted Date --}}
                                            <td class="text-nowrap">
                                                <span class="fw-medium">{{ \Carbon\Carbon::parse($feedback->submitted_date)->format('d/m/Y') }}</span>
                                                <br>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($feedback->submitted_date)->format('h:i A') }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                            <h5 class="mt-3">No submitted feedback yet</h5>
                                            <p class="text-muted">Your submitted feedback will appear here.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @if ($submittedData->count() > 0)
                        <div class="card-footer bg-white border-top px-3 px-md-4 py-3">
                            <div class="row align-items-center g-2">
                                <div class="col-12 col-md-auto me-md-auto">
                                    <nav aria-label="Submitted feedback pagination">
                                        <ul class="pagination pagination-sm mb-0 sf-pagination flex-wrap"></ul>
                                    </nav>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <div class="d-flex align-items-center justify-content-md-end gap-2 text-muted small">
                                        <span>Rows per page</span>
                                        <select class="form-select form-select-sm rounded-1 sf-pagesize">
                                            <option selected>10</option>
                                            <option>20</option>
                                            <option>50</option>
                                            <option>100</option>
                                        </select>
                                        <span class="sf-showing-info ms-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const feedbackTabs = document.getElementById('feedbackTabs');
    const pendingTabBtn = feedbackTabs ? feedbackTabs.querySelector('button[data-bs-target="#pending-tab-pane"]') : null;
    if (pendingTabBtn) {
        new bootstrap.Tab(pendingTabBtn);
    }

    $('#vertical-wizard').validate({
        rules: {
            'feedback_pk[]': { required: true }
        },
        errorPlacement: function(error, element) {
            if (element.attr("name").includes("presentation") || element.attr("name").includes("content")) {
                error.insertAfter(element.closest('td'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function(form) {
            $('#table-loader').show();
            $(form).find('button[type="submit"], .individual-feedback-submit-btn').prop('disabled', true);

            let hasFeedback = false;
            $('tr[data-feedback-date]').each(function() {
                const contentChecked = $(this).find('input[name^="content"]:checked').length;
                const presentationChecked = $(this).find('input[name^="presentation"]:checked').length;
                const remarks = $(this).find('textarea[name^="remarks"]').val() || '';

                if (contentChecked > 0 || presentationChecked > 0 || remarks.trim() !== '') {
                    hasFeedback = true;
                }
            });

            if (!hasFeedback) {
                $('#table-loader').hide();
                $(form).find('button[type="submit"], .individual-feedback-submit-btn').prop('disabled', false);
                alert('Please provide feedback for at least one session.');
                return false;
            }

            form.submit();
        }
    });

    $('#vertical-wizard').on('submit', function(e) {
        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.prop('disabled')) {
            e.preventDefault();
            return false;
        }
    });

    if (window.location.hash === '#submitted') {
        const submittedTab = new bootstrap.Tab(document.getElementById('submitted-tab'));
        submittedTab.show();
    }

    $('button[data-bs-toggle="tab"]').on('click', function() {
        const tabId = $(this).attr('id');
        if (tabId === 'submitted-tab') {
            window.location.hash = 'submitted';
        } else if (tabId === 'pending-tab') {
            window.location.hash = 'pending';
        }
    });

    /* ===== Filters ===== */
    const sfFilters = { start: null, end: null, minRating: 0, search: '' };
    let sfPage = 1, sfTotalPages = 1;

    function sfPageSize() {
        const v = parseInt($('.tab-pane.active').find('.sf-pagesize').val(), 10);
        return v > 0 ? v : 8;
    }
    function sfPad(n) { return (n < 10 ? '0' : '') + n; }
    function sfFmt(d) { return d.getFullYear() + '-' + sfPad(d.getMonth() + 1) + '-' + sfPad(d.getDate()); }

    let sfPicker = null;
    if (window.flatpickr) {
        sfPicker = flatpickr('#sf-daterange', {
            mode: 'range',
            showMonths: 2,
            dateFormat: 'Y-m-d',
            clickOpens: true,
            appendTo: document.body,
            onReady: function(selectedDates, dateStr, inst) {
                inst.calendarContainer.classList.add('sf-flatpickr');
            },
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    sfFilters.start = sfFmt(selectedDates[0]);
                    sfFilters.end = sfFmt(selectedDates[1]);
                } else if (selectedDates.length === 1) {
                    sfFilters.start = sfFilters.end = sfFmt(selectedDates[0]);
                } else {
                    sfFilters.start = sfFilters.end = null;
                }
                sfApplyFilters();
            }
        });
    }

    function sfRowRating($row) {
        const attr = $row.attr('data-rating');
        if (attr !== undefined && attr !== '') return parseInt(attr, 10) || 0;
        const checked = $row.find('input[name^="content"]:checked').val();
        return checked ? parseInt(checked, 10) : 0;
    }

    function sfApplyFilters(resetPage) {
        if (resetPage !== false) sfPage = 1;

        const activeTab = $('.tab-pane.active');
        const tbody = activeTab.find('tbody');
        const dataRows = tbody.find('tr[data-feedback-date]');
        const emptyStateRow = tbody.find('tr:not([data-feedback-date])');

        const matched = [];
        dataRows.each(function() {
            const $row = $(this);
            let ok = true;
            const rowDate = $row.attr('data-feedback-date');
            if (sfFilters.start && rowDate < sfFilters.start) ok = false;
            if (ok && sfFilters.end && rowDate > sfFilters.end) ok = false;
            if (ok && sfFilters.minRating > 0 && sfRowRating($row) < sfFilters.minRating) ok = false;
            if (ok && sfFilters.search && $row.text().toLowerCase().indexOf(sfFilters.search) === -1) ok = false;
            if (ok) matched.push(this);
        });

        const pageSize = sfPageSize();
        const total = matched.length;
        sfTotalPages = Math.max(1, Math.ceil(total / pageSize));
        if (sfPage > sfTotalPages) sfPage = sfTotalPages;
        if (sfPage < 1) sfPage = 1;
        const startIdx = (sfPage - 1) * pageSize;
        const endIdx = startIdx + pageSize;

        dataRows.hide();
        $(matched).slice(startIdx, endIdx).show();
        emptyStateRow.toggle(total === 0);

        if (activeTab.attr('id') === 'pending-tab-pane') {
            $('#pending-count').text(total);
        } else if (activeTab.attr('id') === 'submitted-tab-pane') {
            $('#submitted-count').text(total);
        }

        const from = total === 0 ? 0 : startIdx + 1;
        const to = Math.min(endIdx, total);
        activeTab.find('.sf-showing-info').text('Showing ' + from + '–' + to + ' of ' + total + ' items');
        sfRenderPagination(activeTab, sfTotalPages);
    }

    function sfPageList(current, total) {
        const pages = [];
        const delta = 2;
        const left = Math.max(2, current - delta);
        const right = Math.min(total - 1, current + delta);
        pages.push(1);
        if (left > 2) pages.push('…');
        for (let i = left; i <= right; i++) pages.push(i);
        if (right < total - 1) pages.push('…');
        if (total > 1) pages.push(total);
        return pages;
    }

    function sfPageItem(page, label, disabled, active) {
        return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';
    }

    function sfRenderPagination($tab, totalPages) {
        const $ul = $tab.find('.sf-pagination').empty();
        $ul.append(sfPageItem('prev', '<i class="bi bi-chevron-left"></i>', sfPage <= 1, false));
        sfPageList(sfPage, totalPages).forEach(function(p) {
            if (p === '…') {
                $ul.append('<li class="page-item disabled"><span class="page-link border-0 bg-transparent">…</span></li>');
            } else {
                $ul.append(sfPageItem(p, p, false, p === sfPage));
            }
        });
        $ul.append(sfPageItem('next', '<i class="bi bi-chevron-right"></i>', sfPage >= totalPages, false));
    }

    $('#sf-rating-filter').on('change', function() {
        sfFilters.minRating = parseInt($(this).val(), 10) || 0;
        sfApplyFilters();
    });

    $('#sf-search-btn').on('click', function() {
        const $input = $('#sf-search-input');
        $input.toggleClass('d-none');
        if (!$input.hasClass('d-none')) $input.trigger('focus');
    });
    $('#sf-search-input').on('input', function() {
        sfFilters.search = $(this).val().trim().toLowerCase();
        sfApplyFilters();
    });

    $('#sf-reset-filters').on('click', function() {
        if (sfPicker) sfPicker.clear();
        sfFilters.start = sfFilters.end = null;
        sfFilters.minRating = 0;
        sfFilters.search = '';
        $('#sf-rating-filter').val('');
        $('#sf-search-input').val('').addClass('d-none');
        sfApplyFilters();
    });

    function sfBuildColumnsMenu() {
        const table = $('.tab-pane.active').find('table').first();
        const $grid = $('#sf-columns-grid').empty();
        table.find('thead th').each(function(i) {
            const name = $.trim($(this).text()) || ('Column ' + (i + 1));
            const visible = $(this).is(':visible');
            $grid.append(
                '<label class="sf-col-chip" title="' + name + '">' +
                '<input type="checkbox" class="form-check-input sf-col-toggle" data-col="' + i + '" ' +
                (visible ? 'checked' : '') + '><span>' + name + '</span></label>'
            );
        });
    }
    $('#sfColumnsModal').on('show.bs.modal', sfBuildColumnsMenu);
    $(document).on('change', '.sf-col-toggle', function() {
        const idx = parseInt($(this).data('col'), 10);
        const show = $(this).is(':checked');
        const table = $('.tab-pane.active').find('table').first();
        table.find('tr').each(function() {
            $(this).children().eq(idx).toggle(show);
        });
    });

    $(document).on('change', '.sf-pagesize', function() {
        sfApplyFilters();
    });

    $(document).on('click', '.sf-pagination .page-link', function(e) {
        e.preventDefault();
        const $li = $(this).closest('.page-item');
        if ($li.hasClass('disabled') || $li.hasClass('active')) return;
        const val = $(this).data('page');
        if (val === 'prev') sfPage = Math.max(1, sfPage - 1);
        else if (val === 'next') sfPage = Math.min(sfTotalPages, sfPage + 1);
        else sfPage = parseInt(val, 10) || 1;
        sfApplyFilters(false);
    });

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
        sfApplyFilters();
    });

    sfApplyFilters();
});

function submitFacultyFeedback(index) {
    $('#table-loader').show();
    $('.individual-feedback-submit-btn, .bulk-feedback-submit-btn').prop('disabled', true);

    const token = document.querySelector('meta[name="csrf-token"]') ?
        document.querySelector('meta[name="csrf-token"]').getAttribute('content') :
        '{{ csrf_token() }}';

    const form = $('<form>', {
        method: 'POST',
        action: '{{ route('feedback.submit.facultyInternalFeedback') }}',
        id: 'individual-form-' + index
    }).append('<input type="hidden" name="_token" value="' + token + '">');

    form.append('<input type="hidden" name="submit_index" value="' + index + '">');

    $(`input[name="feedback_pk[${index}]"]`).clone().appendTo(form);
    $(`input[name="Ratting_checkbox[${index}]"]`).clone().appendTo(form);
    $(`input[name="Remark_checkbox[${index}]"]`).clone().appendTo(form);

    const contentChecked = $(`input[name="content[${index}]"]:checked`);
    if (contentChecked.length > 0) contentChecked.clone().appendTo(form);

    const presentationChecked = $(`input[name="presentation[${index}]"]:checked`);
    if (presentationChecked.length > 0) presentationChecked.clone().appendTo(form);

    $(`textarea[name="remarks[${index}]"]`).clone().appendTo(form);

    $('body').append(form);
    setTimeout(function() { form.submit(); }, 100);
}
</script>

{{-- Column Visibility modal --}}
<div class="modal fade" id="sfColumnsModal" tabindex="-1" aria-labelledby="sfColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content sf-cols-modal">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="sfColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <hr class="mt-0 mb-0 mx-3">
            <div class="modal-body">
                <div class="sf-cols-grid" id="sf-columns-grid"></div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
