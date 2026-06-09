@extends('admin.layouts.timetable')
@section('title', 'Student Feedback - Sargam')
@section('content')
<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
    <!-- Main Content -->
    <div class="container-fluid session-feedback-main">
        <div class="card border-0 shadow-sm rounded-4 session-feedback-card">
            <div class="card-header text-center rounded-top-4 mb-2" style="background-color: #004a93;">
                <h4 class="mb-0 text-white fw-bold">Session Feedbacks</h4>
            </div>

            <!-- Date Filter -->
            <div class="card-header bg-light border-bottom-0 px-4 py-3">
                <div class="row align-items-end g-3">
                    <div class="col-md-6">
                        <label for="date-filter" class="form-label fw-semibold text-secondary mb-2">
                            <i class="bi bi-calendar-event me-2"></i>Filter by Date
                        </label>
                        <div class="input-group shadow-sm rounded-3" style="max-width: 240px;">
                            <span class="input-group-text bg-white border-end-0 text-primary">
                                <i class="bi bi-funnel"></i>
                            </span>
                            <input type="date" class="form-control border-start-0 ps-2" id="date-filter">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="clear-date-filter"
                            style="display: none;">
                            <i class="bi bi-x-circle me-1"></i>Clear Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="card-header bg-light border-bottom-0">
                <ul class="nav nav-tabs nav-fill border-0 feedback-nav-tabs" id="feedbackTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab"
                            data-bs-target="#pending-tab-pane" type="button" role="tab"
                            aria-controls="pending-tab-pane" aria-selected="true">Pending Feedback
                            <span class="badge rounded-pill ms-2" id="pending-count" style="background:#004a93;">{{ $pendingData->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="submitted-tab" data-bs-toggle="tab"
                            data-bs-target="#submitted-tab-pane" type="button" role="tab"
                            aria-controls="submitted-tab-pane" aria-selected="false">Submitted Feedback
                            <span class="badge rounded-pill ms-2"
                                id="submitted-count" style="background:#004a93;">{{ $submittedData->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tabs Content -->
            <div class="tab-content" id="feedbackTabsContent">
                <!-- Pending Feedback Tab -->
                <!-- Pending Feedback Tab -->
                <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel"
                    aria-labelledby="pending-tab" tabindex="0">
                    @if ($pendingData->count() > 0)
                        <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}">
                            @csrf
                            <div class="card-body mb-4 p-0">
                                <div class="table-responsive feedback-table-wrap">
                                    <div class="rating-legend d-flex flex-wrap gap-3 align-items-center mt-2 mb-3 bg-light border rounded-3 px-3 py-2 mx-2 mx-md-3">
                                        <span class="legend-item">
                                            <span class="stars">★★★★★</span>
                                            <span class="text">Excellent</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★★★★</span>
                                            <span class="text">Very Good</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★★★</span>
                                            <span class="text">Good</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★★</span>
                                            <span class="text">Average</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★</span>
                                            <span class="text">Below Average</span>
                                        </span>
                                    </div>
                                    <style>
                                        #pending-tab-pane .modal .star-rating { display: inline-flex; flex-direction: row-reverse; }
                                        #pending-tab-pane .modal .star-rating input { position: absolute; opacity: 0; width: 0; height: 0; }
                                        #pending-tab-pane .modal .star-rating label {
                                            font-size: 2rem; line-height: 1; cursor: pointer; padding: 0 .15rem;
                                            color: #dee2e6; transition: color .15s ease; margin: 0;
                                        }
                                        #pending-tab-pane .modal .star-rating label:hover,
                                        #pending-tab-pane .modal .star-rating label:hover ~ label,
                                        #pending-tab-pane .modal .star-rating input:checked ~ label { color: #f5b301; }
                                        #pending-tab-pane .give-feedback-btn { transition: all .15s ease; }
                                        #pending-tab-pane .give-feedback-btn:hover { transform: translateY(-1px); box-shadow: 0 .25rem .5rem rgba(0,74,147,.15); }
                                    </style>
                                    <table class="table text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>S.No.</th>
                                                <th>Date &amp; Time</th>
                                                <th>Topic Detail</th>
                                                <th>Faculty Name</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pending-feedback-body">
                                            @php $pendingIndex = 0; @endphp
                                            @foreach ($pendingData as $feedback)
                                                @if ($feedback->feedback_checkbox == 1)
                                                    <tr data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}">
                                                        <td class="text-center fw-semibold">{{ ++$pendingIndex }}</td>
                                                        <td class="text-center text-nowrap">
                                                            {{ \Carbon\Carbon::parse($feedback->from_date)->format('d-m-Y') }}
                                                            <br>
                                                            <small
                                                                class="text-muted">{{ $feedback->class_session }}</small>
                                                        </td>
                                                        <td class="fw-medium">{{ $feedback->subject_topic }}</td>
                                                        <td>{{ $feedback->faculty_name }}</td>

                                                        {{-- Action: opens per-row Add Feedback modal --}}
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-sm fw-semibold give-feedback-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#feedbackModal{{ $loop->index }}">
                                                                <i class="bi bi-chat-square-text me-1"></i> Give Feedback
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

                                                            {{-- Add Feedback Modal --}}
                                                            <div class="modal fade text-start" id="feedbackModal{{ $loop->index }}"
                                                                tabindex="-1"
                                                                aria-labelledby="feedbackModalLabel{{ $loop->index }}"
                                                                aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content border-0 rounded-4 shadow">
                                                                        <div class="modal-header border-bottom">
                                                                            <h5 class="modal-title fw-bold"
                                                                                id="feedbackModalLabel{{ $loop->index }}">
                                                                                Add Feedback
                                                                            </h5>
                                                                            <button type="button" class="btn-close"
                                                                                data-bs-dismiss="modal"
                                                                                aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body p-4">
                                                                            <p class="text-muted small mb-4">
                                                                                <i class="bi bi-mortarboard me-1"></i>{{ $feedback->subject_topic }}
                                                                                <span class="mx-1">&middot;</span>
                                                                                <i class="bi bi-person-video3 me-1"></i>{{ $feedback->faculty_name }}
                                                                            </p>

                                                                            @if ($feedback->Ratting_checkbox == 1)
                                                                                {{-- Content Rating --}}
                                                                                <div class="mb-4">
                                                                                    <label class="form-label fw-semibold mb-2">How did you like the content?</label>
                                                                                    <div class="star-rating d-inline-flex flex-row-reverse">
                                                                                        @for ($i = 5; $i >= 1; $i--)
                                                                                            <input type="radio"
                                                                                                id="content-{{ $i }}-{{ $loop->index }}"
                                                                                                name="content[{{ $loop->index }}]"
                                                                                                value="{{ $i }}"
                                                                                                {{ old('content.' . $loop->index) == $i ? 'checked' : '' }}>
                                                                                            <label for="content-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                                                        @endfor
                                                                                    </div>
                                                                                </div>

                                                                                {{-- Presentation Rating --}}
                                                                                <div class="mb-4">
                                                                                    <label class="form-label fw-semibold mb-2">How did you like the presentation?</label>
                                                                                    <div class="star-rating d-inline-flex flex-row-reverse">
                                                                                        @for ($i = 5; $i >= 1; $i--)
                                                                                            <input type="radio"
                                                                                                id="presentation-{{ $i }}-{{ $loop->index }}"
                                                                                                name="presentation[{{ $loop->index }}]"
                                                                                                value="{{ $i }}"
                                                                                                {{ old('presentation.' . $loop->index) == $i ? 'checked' : '' }}>
                                                                                            <label for="presentation-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                                                        @endfor
                                                                                    </div>
                                                                                </div>
                                                                            @endif

                                                                            @if ($feedback->Remark_checkbox == 1)
                                                                                {{-- Remarks --}}
                                                                                <div class="mb-1">
                                                                                    <label class="form-label fw-semibold mb-2">Remarks</label>
                                                                                    <textarea class="form-control" name="remarks[{{ $loop->index }}]" rows="3"
                                                                                        placeholder="eg. Lorem Ipsum dolor sit">{{ old('remarks.' . $loop->index) }}</textarea>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                        <div class="modal-footer border-top-0 pt-0">
                                                                            <button type="button"
                                                                                class="btn btn-outline-primary px-4"
                                                                                data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="button"
                                                                                class="btn btn-primary px-4 individual-feedback-submit-btn"
                                                                                onclick="submitIndividual({{ $loop->index }})">
                                                                                Add Feedback
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
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
                                            <p style="margin-top:10px; font-weight:500;">Submitting...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3 mb-4 me-4">
                                <button type="submit" class="btn btn-primary">
                                    Submit All Feedback
                                </button>
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
                    <div class="card-body mb-4 p-0">
                        <div class="table-responsive">
                            <table class="table text-wrap">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th width="15%">Date &amp; Time</th>
                                        <th>Topic Detail</th>
                                        <th>Faculty Name</th>
                                        <th>Content Rating</th>
                                        <th>Presentation Rating</th>
                                        <th>Remarks</th>
                                        <th width="15%">Submitted On</th>
                                    </tr>
                                </thead>
                                <tbody id="submitted-feedback-body">
                                    @if ($submittedData->count() > 0)
                                        @php $submittedIndex = 0; @endphp
                                        @foreach ($submittedData as $feedback)
                                            <tr
                                                data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}">
                                                <td>{{ ++$submittedIndex }}</td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($feedback->from_date)->format('d-m-Y') }}
                                                    <br>
                                                    <small class="text-muted">{{ $feedback->class_session }}</small>
                                                </td>
                                                <td>{{ $feedback->subject_topic }}</td>
                                                <td>{{ $feedback->faculty_name }}</td>

                                                {{-- Content Rating --}}
                                                <td>
                                                    @if ($feedback->Ratting_checkbox == 1 && $feedback->content)
                                                        <div class="star-rating-display">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $feedback->content)
                                                                    <span class="text-warning">★</span>
                                                                @else
                                                                    <span class="text-secondary">★</span>
                                                                @endif
                                                            @endfor
                                                            <br>
                                                            <small
                                                                class="text-muted">({{ $feedback->content }}/5)</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Presentation Rating --}}
                                                <td>
                                                    @if ($feedback->Ratting_checkbox == 1 && $feedback->presentation)
                                                        <div class="star-rating-display">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $feedback->presentation)
                                                                    <span class="text-warning">★</span>
                                                                @else
                                                                    <span class="text-secondary">★</span>
                                                                @endif
                                                            @endfor
                                                            <br>
                                                            <small
                                                                class="text-muted">({{ $feedback->presentation }}/5)</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Remarks --}}
                                                <td style="min-width: 180px;">
                                                    @if ($feedback->Remark_checkbox == 1 && $feedback->remark)
                                                        <div class="remarks-text"
                                                            style="max-height: 60px; overflow-y: auto;">
                                                            {{ $feedback->remark }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Submitted Date --}}
                                                <td>
                                                    {{ \Carbon\Carbon::parse($feedback->created_date)->format('d-m-Y') }}
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

        // Handle form submission success
        @if (session('success'))
            const successAlert = `
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`;
            $('.session-feedback-main').prepend(successAlert);

            setTimeout(function() {
                const submittedTab = new bootstrap.Tab(document.getElementById('submitted-tab'));
                submittedTab.show();

                setTimeout(function() {
                    window.location.reload();
                }, 500);
            }, 1500);
        @endif

        @if (session('error'))
            const errorAlert = `
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`;
            $('.session-feedback-main').prepend(errorAlert);
        @endif

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

        // Date Filter Functionality
        $('#date-filter').on('change', function() {
            const selectedDate = $(this).val();
            filterByDate(selectedDate);
            
            // Show/hide clear button
            if (selectedDate) {
                $('#clear-date-filter').show();
            } else {
                $('#clear-date-filter').hide();
            }
        });

        // Clear Date Filter
        $('#clear-date-filter').on('click', function() {
            $('#date-filter').val('');
            filterByDate('');
            $(this).hide();
        });

        // Function to filter rows by date
        function filterByDate(selectedDate) {
            // Get active tab
            const activeTab = $('.tab-pane.active');
            const tbody = activeTab.find('tbody');
            
            let visibleCount = 0;
            const dataRows = tbody.find('tr[data-feedback-date]');
            const emptyStateRow = tbody.find('tr:not([data-feedback-date])');
            
            if (selectedDate) {
                // Filter rows based on selected date
                dataRows.each(function() {
                    const rowDate = $(this).attr('data-feedback-date');
                    if (rowDate === selectedDate) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });
                
                // Hide empty state row if we have matching rows, show it if no matches
                if (visibleCount > 0) {
                    emptyStateRow.hide();
                } else {
                    emptyStateRow.show();
                }
            } else {
                // Show all data rows
                dataRows.each(function() {
                    $(this).show();
                    visibleCount++;
                });
                
                // Show empty state only if there are no data rows at all
                if (dataRows.length === 0) {
                    emptyStateRow.show();
                } else {
                    emptyStateRow.hide();
                }
            }
            
            // Update badge counts based on active tab
            if (activeTab.attr('id') === 'pending-tab-pane') {
                $('#pending-count').text(visibleCount);
            } else if (activeTab.attr('id') === 'submitted-tab-pane') {
                $('#submitted-count').text(visibleCount);
            }
        }

        // Re-apply filter when tab changes
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            const selectedDate = $('#date-filter').val();
            if (selectedDate) {
                filterByDate(selectedDate);
            }
        });
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
@endsection
