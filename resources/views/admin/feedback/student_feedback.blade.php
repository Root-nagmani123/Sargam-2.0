@extends('admin.layouts.timetable')
@section('title', 'Student Feedbacks')
@section('content')
<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">

{{-- Flatpickr (dual-month date range picker) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>

<style>
    /* ===== Session Feedback – scoped UI enhancements ===== */
    .session-feedback-main { --sf-primary: #004a93; }
    .session-feedback-card { overflow: hidden; }
    .sf-toolbar, .sf-filterbar { margin-bottom: 20px; }

    /* Toolbar status pills (tabs) */
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

    /* Filter bar */
    .sf-filterbar .form-select-sm { min-width: 140px; }
    .sf-filterbar .sf-icon-btn {
        width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #d6dee8; border-radius: .5rem; background: #fff; color: #5a6b7e;
    }
    .sf-filterbar .sf-icon-btn:hover { background: #f3f7fc; }

    /* Table */
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

    /* Inline star rating (interactive) */
    #pending-tab-pane .star-rating { display: inline-flex; flex-direction: row-reverse; }
    #pending-tab-pane .star-rating input { position: absolute; opacity: 0; width: 0; height: 0; }
    #pending-tab-pane .star-rating label {
        font-size: 1.4rem; line-height: 1; cursor: pointer; padding: 0 .1rem;
        color: #d8dde4; transition: color .15s ease; margin: 0;
    }
    #pending-tab-pane .star-rating label:hover,
    #pending-tab-pane .star-rating label:hover ~ label,
    #pending-tab-pane .star-rating input:checked ~ label { color: #f5b301; }

    /* Read-only star display */
    .star-rating-display .bi { font-size: 1rem; }

    /* Give feedback button */
    .give-feedback-btn { transition: all .15s ease; white-space: nowrap; }
    .give-feedback-btn:hover { transform: translateY(-1px); box-shadow: 0 .25rem .5rem rgba(0,74,147,.18); }

    /* Remarks inline input */
    .sf-remark {
        min-width: 200px; width: 100%; resize: none; font-size: .85rem;
        height: 38px; line-height: 1.4; overflow: hidden; white-space: nowrap;
    }

    /* Pagination */
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

    /* Time Period date-range trigger */
    .sf-daterange-wrap { display: inline-block; }
    .sf-daterange-input {
        width: auto; min-width: 190px; padding-left: 2.1rem; cursor: pointer; background: #fff;
    }
    .sf-daterange-input:focus { box-shadow: none; border-color: var(--sf-primary); }
    .sf-daterange-icon {
        position: absolute; left: .7rem; top: 50%; transform: translateY(-50%);
        color: #6c7689; pointer-events: none;
    }

    /* Search control */
    .sf-filterbar .sf-search-input { width: 220px; }

    /* Column Visibility modal */
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

    /* Flatpickr brand theming (dual-month range) */
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

    <!-- Main Content -->
    <div class="container-fluid session-feedback-main p-0 py-2 px-2">
         <!-- Toolbar: status pills (tabs) + bulk submit -->
            <div class="sf-toolbar">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-lg-auto me-lg-auto">
                        <ul class="nav nav-pills flex-wrap gap-2 feedback-nav-tabs" id="feedbackTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active d-inline-flex align-items-center" id="pending-tab"
                                    data-bs-toggle="tab" data-bs-target="#pending-tab-pane" type="button" role="tab"
                                    aria-controls="pending-tab-pane" aria-selected="true">Pending
                                    <span class="badge rounded-1 ms-2" id="pending-count">{{ $pendingData->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link d-inline-flex align-items-center" id="submitted-tab"
                                    data-bs-toggle="tab" data-bs-target="#submitted-tab-pane" type="button" role="tab"
                                    aria-controls="submitted-tab-pane" aria-selected="false">Submitted
                                    <span class="badge rounded-1 ms-2" id="submitted-count">{{ $submittedData->count() }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-auto d-flex gap-2">
                        <button type="submit" form="vertical-wizard"
                            class="btn btn-primary rounded-1">Bulk Feedback Submit
                        </button>
                        <button type="button" class="btn btn-outline-primary rounded-1 d-inline-flex align-items-center border-0 bg-white text-primary">
                            <i class="bi bi-download me-1"></i> Download
                        </button>
                    </div>
                </div>
            </div>
        <div class="card border-0 shadow-sm rounded-4 session-feedback-card">

            <!-- Filter bar -->
            <div class="card-header sf-filterbar">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold me-1" style="font-size: .85rem; color: #6c7689;">Filters</span>

                    {{-- Time Period → opens dual-month range calendar --}}
                    <div class="sf-daterange-wrap position-relative">
                        <i class="bi bi-calendar3 sf-daterange-icon"></i>
                        <input type="text" id="sf-daterange" class="form-control rounded-1 sf-daterange-input"
                            placeholder="Time Period" readonly>
                    </div>

                    {{-- Ratings filter --}}
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
                        <button type="button" class="btn btn-outline-secondary rounded-1 d-inline-flex align-items-center" style="border:1px solid #d6dee8; color:#6c757d;" id="sf-columns-btn"
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

                {{-- Hidden date-filter controls preserved for existing JS hooks --}}
                <input type="date" id="date-filter" class="d-none">
                <button type="button" id="clear-date-filter" class="d-none" style="display:none;"></button>
            </div>

            <!-- Tabs Content -->
            <div class="tab-content" id="feedbackTabsContent">
                <!-- Pending Feedback Tab -->
                <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel"
                    aria-labelledby="pending-tab" tabindex="0">
                    @if ($pendingData->count() > 0)
                        <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}">
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
                                                <th class="text-center">Content Ratings</th>
                                                <th class="text-center">Presentation Ratings</th>
                                                <th>Remarks</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pending-feedback-body">
                                            @php $pendingIndex = 0; @endphp
                                            @foreach ($pendingData as $feedback)
                                                @if ($feedback->feedback_checkbox == 1)
                                                    <tr data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}">
                                                        <td class="text-center fw-semibold text-muted">{{ ++$pendingIndex }}</td>
                                                        <td class="text-nowrap">
                                                            <span class="fw-medium">{{ \Carbon\Carbon::parse($feedback->from_date)->format('d/m/Y') }}</span>
                                                            <br>
                                                            <small class="text-muted">{{ $feedback->class_session }}</small>
                                                        </td>
                                                        <td class="fw-medium text-wrap" style="min-width: 180px;">{{ $feedback->subject_topic }}</td>
                                                        <td class="text-nowrap">{{ $feedback->faculty_name }}</td>

                                                        {{-- Content Rating (inline) --}}
                                                        <td class="text-center">
                                                            @if ($feedback->Ratting_checkbox == 1)
                                                                <div class="star-rating">
                                                                    @for ($i = 5; $i >= 1; $i--)
                                                                        <input type="radio"
                                                                            id="content-{{ $i }}-{{ $loop->index }}"
                                                                            name="content[{{ $loop->index }}]"
                                                                            value="{{ $i }}"
                                                                            {{ old('content.' . $loop->index) == $i ? 'checked' : '' }}>
                                                                        <label for="content-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                                    @endfor
                                                                </div>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>

                                                        {{-- Presentation Rating (inline) --}}
                                                        <td class="text-center">
                                                            @if ($feedback->Ratting_checkbox == 1)
                                                                <div class="star-rating">
                                                                    @for ($i = 5; $i >= 1; $i--)
                                                                        <input type="radio"
                                                                            id="presentation-{{ $i }}-{{ $loop->index }}"
                                                                            name="presentation[{{ $loop->index }}]"
                                                                            value="{{ $i }}"
                                                                            {{ old('presentation.' . $loop->index) == $i ? 'checked' : '' }}>
                                                                        <label for="presentation-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                                    @endfor
                                                                </div>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>

                                                        {{-- Remarks (inline) --}}
                                                        <td style="min-width: 220px;">
                                                            @if ($feedback->Remark_checkbox == 1)
                                                                <textarea class="form-control form-control-sm rounded-1 sf-remark" name="remarks[{{ $loop->index }}]" rows="1"
                                                                    placeholder="eg. Lorem Ipsum dolor sit">{{ old('remarks.' . $loop->index) }}</textarea>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>

                                                        {{-- Action --}}
                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-sm rounded-1 fw-semibold give-feedback-btn individual-feedback-submit-btn d-inline-flex align-items-center"
                                                                onclick="submitIndividual({{ $loop->index }})">
                                                                <i class="bi bi-send me-1"></i> Give Feedback
                                                            </button>

                                                            <!-- Hidden Inputs -->
                                                            <input type="hidden"
                                                                name="timetable_pk[{{ $loop->index }}]"
                                                                value="{{ $feedback->timetable_pk . '_' . $feedback->faculty_pk }}">
                                                            <input type="hidden" name="faculty_pk[{{ $loop->index }}]"
                                                                value="{{ $feedback->faculty_pk }}">
                                                            <input type="hidden"
                                                                name="original_timetable_pk[{{ $loop->index }}]"
                                                                value="{{ $feedback->timetable_pk }}">
                                                            <input type="hidden" name="topic_name[{{ $loop->index }}]"
                                                                value="{{ $feedback->subject_topic }}">
                                                            <input type="hidden"
                                                                name="Ratting_checkbox[{{ $loop->index }}]"
                                                                value="{{ $feedback->Ratting_checkbox }}">
                                                            <input type="hidden"
                                                                name="Remark_checkbox[{{ $loop->index }}]"
                                                                value="{{ $feedback->Remark_checkbox }}">
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div id="table-loader"
                                        style="
                                display: none;
                                position: absolute;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background: rgba(255, 255, 255, 0.7);
                                justify-content: center;
                                align-items: center;
                                z-index: 10;
                                ">
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

                            <!-- Footer: pagination + page size -->
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
                            <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3" style="width:72px;height:72px;">
                                <i class="bi bi-check-circle-fill" style="font-size: 2.25rem;"></i>
                            </span>
                            <h5 class="fw-semibold mb-1">No pending feedback</h5>
                            <p class="text-muted mb-0">All feedback has been submitted.</p>
                        </div>
                    @endif
                </div>

                <!-- Submitted Feedback Tab -->
                <div class="tab-pane fade" id="submitted-tab-pane" role="tabpanel" aria-labelledby="submitted-tab"
                    tabindex="0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle sf-table text-wrap">
                                <thead>
                                    <tr>
                                        <th class="text-center">S. No.</th>
                                        <th width="15%">Date &amp; time</th>
                                        <th>Topic Detail</th>
                                        <th>Faculty Name</th>
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
                                            <tr
                                                data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}"
                                                data-rating="{{ $feedback->content ?? 0 }}">
                                                <td class="text-center fw-semibold text-muted">{{ ++$submittedIndex }}</td>
                                                <td class="text-nowrap">
                                                    <span class="fw-medium">{{ \Carbon\Carbon::parse($feedback->from_date)->format('d/m/Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ $feedback->class_session }}</small>
                                                </td>
                                                <td class="fw-medium">{{ $feedback->subject_topic }}</td>
                                                <td>{{ $feedback->faculty_name }}</td>

                                                {{-- Content Rating --}}
                                                <td class="text-center">
                                                    @if ($feedback->Ratting_checkbox == 1 && $feedback->content)
                                                        <div class="star-rating-display">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $feedback->content)
                                                                    <i class="bi bi-star-fill text-warning"></i>
                                                                @else
                                                                    <i class="bi bi-star text-secondary"></i>
                                                                @endif
                                                            @endfor
                                                            <div>
                                                                <small class="text-muted">({{ $feedback->content }}/5)</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-light text-muted border">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Presentation Rating --}}
                                                <td class="text-center">
                                                    @if ($feedback->Ratting_checkbox == 1 && $feedback->presentation)
                                                        <div class="star-rating-display">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $feedback->presentation)
                                                                    <i class="bi bi-star-fill text-warning"></i>
                                                                @else
                                                                    <i class="bi bi-star text-secondary"></i>
                                                                @endif
                                                            @endfor
                                                            <div>
                                                                <small class="text-muted">({{ $feedback->presentation }}/5)</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-light text-muted border">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Remarks --}}
                                                <td style="min-width: 180px;">
                                                    @if ($feedback->Remark_checkbox == 1 && $feedback->remark)
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
                                                    <span class="fw-medium">{{ \Carbon\Carbon::parse($feedback->created_date)->format('d/m/Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($feedback->created_date)->format('h:i A') }}
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
                        <!-- Pagination (submitted) -->
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

    <!-- JavaScript for Tab Functionality -->
    <script>
    $(document).ready(function() {
        // Initialize Bootstrap tabs
        const feedbackTabs = document.getElementById('feedbackTabs');
        const pendingTabBtn = feedbackTabs ? feedbackTabs.querySelector('button[data-bs-target="#pending-tab-pane"]') : null;
        if (pendingTabBtn) {
            new bootstrap.Tab(pendingTabBtn);
        }

        // Add form validation - PLACE IT HERE
        $('#vertical-wizard').validate({
            rules: {
                'timetable_pk[]': {
                    required: true
                }
            },
            errorPlacement: function(error, element) {
                if (element.attr("name").includes("presentation") || element.attr("name").includes(
                        "content")) {
                    error.insertAfter(element.closest('td'));
                } else if (element.attr("name").includes("remarks")) {
                    error.insertAfter(element);
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // Show loader centered inside table
                $('#table-loader').show();

                // Disable all buttons while submitting
                $(form).find('button[type="submit"], .individual-feedback-submit-btn').prop('disabled', true);

                // Filter out rows that don't have any feedback
                let hasFeedback = false;
                $('tr[data-feedback-date]').each(function() {
                    const contentChecked = $(this).find('input[name^="content"]:checked').length;
                    const presentationChecked = $(this).find('input[name^="presentation"]:checked').length;
                    const remarks = $(this).find('textarea[name^="remarks"]').val().trim();

                    if (contentChecked > 0 || presentationChecked > 0 || remarks !== '') {
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

        // Handle tab click events
        $('#submitted-tab').on('click', function() {
            // Tab switching handled by Bootstrap
        });

        // Success/error flash alerts are rendered by the master layout (admin.layouts.timetable)

        // Prevent double form submission
        $('#vertical-wizard').on('submit', function(e) {
            const submitBtn = $(this).find('button[type="submit"]');
            if (submitBtn.prop('disabled')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-load submitted tab if URL has hash
        if (window.location.hash === '#submitted') {
            const submittedTab = new bootstrap.Tab(document.getElementById('submitted-tab'));
            submittedTab.show();
        }

        // Update URL hash when tabs change
        $('button[data-bs-toggle="tab"]').on('click', function() {
            const tabId = $(this).attr('id');
            if (tabId === 'submitted-tab') {
                window.location.hash = 'submitted';
            } else if (tabId === 'pending-tab') {
                window.location.hash = 'pending';
            }
        });

        /* ===== Filters: date range + rating + search + columns + pagination ===== */
        const sfFilters = { start: null, end: null, minRating: 0, search: '' };
        let sfPage = 1, sfTotalPages = 1;

        function sfPageSize() {
            const v = parseInt($('.tab-pane.active').find('.sf-pagesize').val(), 10);
            return v > 0 ? v : 8;
        }

        function sfPad(n) { return (n < 10 ? '0' : '') + n; }
        function sfFmt(d) { return d.getFullYear() + '-' + sfPad(d.getMonth() + 1) + '-' + sfPad(d.getDate()); }

        // Dual-month range calendar on the Time Period field
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

        // A row's rating: submitted rows expose data-rating; pending rows use the checked content star
        function sfRowRating($row) {
            const attr = $row.attr('data-rating');
            if (attr !== undefined && attr !== '') return parseInt(attr, 10) || 0;
            const checked = $row.find('input[name^="content"]:checked').val();
            return checked ? parseInt(checked, 10) : 0;
        }

        // resetPage defaults to true; pass false to stay on the current page (used by page-link clicks)
        function sfApplyFilters(resetPage) {
            if (resetPage !== false) sfPage = 1;

            const activeTab = $('.tab-pane.active');
            const tbody = activeTab.find('tbody');
            const dataRows = tbody.find('tr[data-feedback-date]');
            const emptyStateRow = tbody.find('tr:not([data-feedback-date])');

            // 1. Apply filters → collect matching rows
            const matched = [];
            dataRows.each(function() {
                const $row = $(this);
                let ok = true;

                const rowDate = $row.attr('data-feedback-date');
                if (sfFilters.start && rowDate < sfFilters.start) ok = false;
                if (ok && sfFilters.end && rowDate > sfFilters.end) ok = false;
                if (ok && sfFilters.minRating > 0 && sfRowRating($row) < sfFilters.minRating) ok = false;
                if (ok && sfFilters.search &&
                    $row.text().toLowerCase().indexOf(sfFilters.search) === -1) ok = false;

                if (ok) matched.push(this);
            });

            // 2. Paginate the matched rows
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

            // 3. Count badge reflects the filtered total
            if (activeTab.attr('id') === 'pending-tab-pane') {
                $('#pending-count').text(total);
            } else if (activeTab.attr('id') === 'submitted-tab-pane') {
                $('#submitted-count').text(total);
            }

            // 4. "Showing X–Y of N items" + pagination controls
            const from = total === 0 ? 0 : startIdx + 1;
            const to = Math.min(endIdx, total);
            activeTab.find('.sf-showing-info').text('Showing ' + from + '–' + to + ' of ' + total + ' items');
            sfRenderPagination(activeTab, sfTotalPages);
        }

        // Build a windowed list of page numbers with ellipses, e.g. [1, '…', 4, 5, 6, '…', 20]
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

        // Rating filter
        $('#sf-rating-filter').on('change', function() {
            sfFilters.minRating = parseInt($(this).val(), 10) || 0;
            sfApplyFilters();
        });

        // Search: reveal input on icon click, filter as you type
        $('#sf-search-btn').on('click', function() {
            const $input = $('#sf-search-input');
            $input.toggleClass('d-none');
            if (!$input.hasClass('d-none')) $input.trigger('focus');
        });
        $('#sf-search-input').on('input', function() {
            sfFilters.search = $(this).val().trim().toLowerCase();
            sfApplyFilters();
        });

        // Reset all filters
        $('#sf-reset-filters').on('click', function() {
            if (sfPicker) sfPicker.clear();
            sfFilters.start = sfFilters.end = null;
            sfFilters.minRating = 0;
            sfFilters.search = '';
            $('#sf-rating-filter').val('');
            $('#sf-search-input').val('').addClass('d-none');
            sfApplyFilters();
        });

        // Columns: build the "Column Visibility" modal from the active table's headers
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

        // Rows-per-page change → back to page 1
        $(document).on('change', '.sf-pagesize', function() {
            sfApplyFilters();
        });

        // Pagination clicks (prev / next / numbered)
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

        // Re-apply filters whenever the tab changes
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            sfApplyFilters();
        });

        // Initial render (paginate the first tab on load)
        sfApplyFilters();
    });

    // Individual row submission
    function submitIndividual(index) {
        // Show loader inside table
        $('#table-loader').show();

        // Disable all buttons
        $('.individual-feedback-submit-btn, .bulk-feedback-submit-btn').prop('disabled', true);

        // Create a temporary form for single row submission
        const form = $('<form>', {
            method: 'POST',
            action: '{{ route('feedback.submit.feedback') }}',
            id: 'individual-form-' + index
        }).append('@csrf');

        // Add a hidden input to indicate this is an individual submission
        form.append('<input type="hidden" name="submit_index" value="' + index + '">');

        // Append only the row inputs for this specific index
        $(`input[name="timetable_pk[${index}]"]`).clone().appendTo(form);
        $(`input[name="faculty_pk[${index}]"]`).clone().appendTo(form);
        $(`input[name="original_timetable_pk[${index}]"]`).clone().appendTo(form);
        $(`input[name="topic_name[${index}]"]`).clone().appendTo(form);
        $(`input[name="Ratting_checkbox[${index}]"]`).clone().appendTo(form);
        $(`input[name="Remark_checkbox[${index}]"]`).clone().appendTo(form);

        // Get checked radio buttons
        const contentChecked = $(`input[name="content[${index}]"]:checked`);
        if (contentChecked.length > 0) {
            contentChecked.clone().appendTo(form);
        }

        const presentationChecked = $(`input[name="presentation[${index}]"]:checked`);
        if (presentationChecked.length > 0) {
            presentationChecked.clone().appendTo(form);
        }

        // Get remarks textarea
        $(`textarea[name="remarks[${index}]"]`).clone().appendTo(form);

        $('body').append(form);

        // Submit the form
        setTimeout(function() {
            form.submit();
        }, 100);
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
