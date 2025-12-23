<!DOCTYPE html>
<html lang="en" style="height: 100%;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback Form - Sargam | Lal Bahadur Shastri National Academy of Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://bootstrapdemos.adminmart.com/matdash/dist/assets/css/styles.css">
    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/ico" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <!-- jQuery Steps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{asset('admin_assets/css/accesibility-style_v1.css')}}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.ux4g.gov.in/UX4G@2.0.8/css/ux4g-min.css" rel="stylesheet">

    <style>
    .star-rating {
        display: inline-flex;
        justify-content: flex-start;
    }

    .star-rating input[type="radio"] {
        display: none;
    }

    .star-rating label {
        font-size: 1.5rem;
        color: #ccc;
        cursor: pointer;
    }

    .star-rating input[type="radio"]:checked~label {
        color: #af2910;
    }

    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #af2910;
    }

    /* Star Rating Style */
    .star-rating {
        position: relative;
        display: inline-flex;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        font-size: 1.25rem;
        color: #ffce54;
        cursor: pointer;
        transition: color 0.2s ease-in-out;
        padding: 0 1px;
    }

    .star-rating input:not(:checked)~label {
        color: #ffe8a1;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }
    
    /* Tab Styles */
    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 500;
        border: 1px solid transparent;
        border-radius: 0.375rem 0.375rem 0 0;
        padding: 0.75rem 1.5rem;
    }
    
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
    }
    
    .tab-content {
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        padding: 1.5rem;
    }
    
    .star-rating-display {
        font-size: 1.25rem;
    }
    
    .remarks-text {
        font-size: 0.875rem;
        line-height: 1.4;
    }
    
    .tab-content {
        min-height: 400px;
    }
    </style>
</head>
<x-session_message />

<body style="min-height: 100vh; display: flex; flex-direction: column;">
    <!-- Top Blue Bar (Govt of India) -->
    <div class="top-header d-flex justify-content-between align-items-center d-none d-md-block py-2"
        style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-3 d-flex align-items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                        alt="GoI Logo" height="30">
                    <span class="ms-2 text-white" style="font-size: 14px;">Government of India</span>
                </div>
                <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                    <ul class="nav justify-content-end align-items-center">
                        <li class="nav-item"><a href="#content" class="text-white text-decoration-none"
                                style=" font-size: 12px;">Skip to Main Content</a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a class="text-decoration-none" id="uw-widget-custom-trigger"
                                contenteditable="false" style="cursor: pointer;"><img
                                    src="{{ asset('images/accessible.png') }}" alt="" width="20">
                                <span class="text-white ms-1" style=" font-size: 12px;">
                                    More
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sticky Header -->
    <div class="header sticky-top bg-white shadow-sm">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">
                    <a class="navbar-brand me-2" href="#">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="Logo 1"
                            height="80">
                    </a>
                    <span class="vr mx-2"></span>
                    <a class="navbar-brand" href="#">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" height="80">
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa"
                                    target="_blank">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="https://www.lbsnaa.gov.in/footer_menu/contact-us"
                                    target="_blank">Contact</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    
    <!-- Accessibility Panel -->
    <div class="uwaw uw-light-theme gradient-head uwaw-initial paid_widget" id="uw-main">
        <div class="relative second-panel">
            <h3>Accessibility options by LBSNAA</h3>
            <div class="uwaw-close" onclick="closeMain()"></div>
        </div>
        <div class="uwaw-body">
            <div class="lang">
                <div class="lang_head">
                    <i></i>
                    <span>Language</span>
                </div>
                <div class="language_drop" id="google_translate_element">
                    <!-- google translate list coming inside here -->
                </div>
            </div>
            <div class="h-scroll">
                <div class="uwaw-features">
                    <!-- Accessibility features remain the same -->
                    <div class="uwaw-features__item reset-feature" id="featureItem_sp">
                        <button id="speak" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-speaker"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Screen Reader</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon_sp"
                                style="display: none">
                            </span>
                        </button>
                    </div>
                    <!-- Other accessibility features... -->
                </div>
            </div>
        </div>
        <div class="reset-panel">
            <div class="copyrights-accessibility">
                <button class="btn-reset-all" id="reset-all" onclick="resetAll()">
                    <span class="reset-icon"> </span>
                    <span class="reset-btn-text">Reset All Settings</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header text-center rounded-top-4 mb-2" style="background-color: #591512;">
                <h4 class="mb-0 text-white" style="font-family:Inter;font-weight:700;">Faculty Feedbacks</h4>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="card-header bg-light border-bottom-0">
                <ul class="nav nav-tabs nav-fill border-0" id="feedbackTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" 
                                data-bs-target="#pending-tab-pane" type="button" role="tab" 
                                aria-controls="pending-tab-pane" aria-selected="true">
                            <i class="bi bi-clock me-2"></i>Pending Feedback
                            <span class="badge bg-danger ms-2" id="pending-count">{{ $pendingData->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="submitted-tab" data-bs-toggle="tab" 
                                data-bs-target="#submitted-tab-pane" type="button" role="tab" 
                                aria-controls="submitted-tab-pane" aria-selected="false">
                            <i class="bi bi-check-circle me-2"></i>Submitted Feedback
                            <span class="badge bg-success ms-2" id="submitted-count">{{ $submittedData->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ $otUrl }}" target="_blank">
                            OT URL
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Tabs Content -->
            <div class="tab-content" id="feedbackTabsContent">
                <!-- Pending Feedback Tab -->
                <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel" 
                     aria-labelledby="pending-tab" tabindex="0">
                    @if($pendingData->count() > 0)
                    <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}">
                        @csrf
                        <div class="card-body mb-4 p-0">
                            <div class="table-responsive">
                                <table class="table rounded-3 overflow-hidden align-middle mb-0 table-bordered">
                                    <thead class="bg-danger text-white">
                                        <tr>
                                            <th class="text-center text-white">S.No.</th>
                                            <th class="text-center text-white">Date &amp; Time</th>
                                            <th class="text-center text-white">Topic Detail</th>
                                            <th class="text-center text-white">Faculty Name</th>
                                            <th class="text-center text-white">Q. How did you like the Content?</th>
                                            <th class="text-center text-white">Q. How did you like the Presentation?</th>
                                            <th class="text-center text-white">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pending-feedback-body">
                                        @php $pendingIndex = 0; @endphp
                                        @foreach ($pendingData as $feedback)
                                        @if ($feedback->feedback_checkbox == 1)
                                        <tr class="text-center">
                                            <td class="text-center">{{ ++$pendingIndex }}</td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($feedback->from_date)->format('d-m-Y') }}
                                                <br>
                                                <small class="text-muted">{{ $feedback->class_session }}</small>
                                            </td>
                                            <td>{{ $feedback->subject_topic }}</td>
                                            <td>{{ $feedback->faculty_name }}</td>

                                            {{-- Content Rating --}}
                                            <td>
                                                @if ($feedback->Ratting_checkbox == 1)
                                                <div class="star-rating d-inline-flex flex-row-reverse">
                                                    @for ($i = 5; $i >= 1; $i--)
                                                    <input type="radio" id="content-{{ $i }}-{{ $loop->index }}"
                                                           name="content[{{ $loop->index }}]" value="{{ $i }}"
                                                           {{ old('content.' . $loop->index) == $i ? 'checked' : '' }}>
                                                    <label for="content-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                    @endfor
                                                </div>
                                                @endif
                                            </td>

                                            {{-- Presentation Rating --}}
                                            <td>
                                                @if ($feedback->Ratting_checkbox == 1)
                                                <div class="star-rating d-inline-flex flex-row-reverse">
                                                    @for ($i = 5; $i >= 1; $i--)
                                                    <input type="radio" id="presentation-{{ $i }}-{{ $loop->index }}"
                                                           name="presentation[{{ $loop->index }}]" value="{{ $i }}"
                                                           {{ old('presentation.' . $loop->index) == $i ? 'checked' : '' }}>
                                                    <label for="presentation-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                    @endfor
                                                </div>
                                                @endif
                                            </td>

                                            {{-- Remarks --}}
                                            <td style="min-width: 180px;">
                                                @if ($feedback->Remark_checkbox == 1)
                                                <textarea class="form-control form-control-sm" name="remarks[{{ $loop->index }}]"
                                                          rows="2" placeholder="Enter remarks..."
                                                          >{{ old('remarks.' . $loop->index) }}</textarea>
                                                @endif
                                            </td>
                                            <input type="hidden" name="timetable_pk[{{ $loop->index }}]" value="{{ $feedback->timetable_pk }}">
                                            <input type="hidden" name="topic_name[{{ $loop->index }}]" value="{{ $feedback->subject_topic }}">
                                            <input type="hidden" name="faculty_pk[{{ $loop->index }}]" value="{{ $feedback->faculty_pk ?? '' }}">
                                            <input type="hidden" name="Ratting_checkbox[{{ $loop->index }}]" value="{{ $feedback->Ratting_checkbox }}">
                                            <input type="hidden" name="Remark_checkbox[{{ $loop->index }}]" value="{{ $feedback->Remark_checkbox }}">
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="text-end mt-3 mb-4 me-4">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill"
                                    style="background-color: #004a93;border-color: #004a93;">Submit Feedback</button>
                        </div>
                    </form>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">No pending feedback</h5>
                        <p class="text-muted">All feedback has been submitted.</p>
                    </div>
                    @endif
                </div>

                <!-- Submitted Feedback Tab -->
                <div class="tab-pane fade" id="submitted-tab-pane" role="tabpanel" 
                     aria-labelledby="submitted-tab" tabindex="0">
                    <div class="card-body mb-4 p-0">
                        <div class="table-responsive">
                            <table class="table rounded-3 overflow-hidden align-middle mb-0 table-bordered">
                                <thead class="bg-success text-white">
                                    <tr>
                                        <th class="text-center text-white">S.No.</th>
                                        <th class="text-center text-white">Date &amp; Time</th>
                                        <th class="text-center text-white">Topic Detail</th>
                                        <th class="text-center text-white">Faculty Name</th>
                                        <th class="text-center text-white">Content Rating</th>
                                        <th class="text-center text-white">Presentation Rating</th>
                                        <th class="text-center text-white">Remarks</th>
                                        <th class="text-center text-white">Submitted On</th>
                                    </tr>
                                </thead>
                                <tbody id="submitted-feedback-body">
                                    @if($submittedData->count() > 0)
                                        @php $submittedIndex = 0; @endphp
                                        @foreach ($submittedData as $feedback)
                                        <tr class="text-center">
                                            <td class="text-center">{{ ++$submittedIndex }}</td>
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
                                                    <small class="text-muted">({{ $feedback->content }}/5)</small>
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
                                                    <small class="text-muted">({{ $feedback->presentation }}/5)</small>
                                                </div>
                                                @else
                                                <span class="text-muted">N/A</span>
                                                @endif
                                            </td>

                                            {{-- Remarks --}}
                                            <td style="min-width: 180px;">
                                                @if ($feedback->Remark_checkbox == 1 && $feedback->remark)
                                                <div class="remarks-text" style="max-height: 60px; overflow-y: auto;">
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

    <!-- Footer -->
    <footer class="mt-auto text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; {{date('Y')}} Lal Bahadur Shastri National Academy
                        of Administration, Mussoorie, Uttarakhand</p>
                </div>
                <div class="col-md-4 text-end">
                    <ul class="list-unstyled d-flex justify-content-end mb-0">
                        <li class="me-3">
                            <a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: Inter;">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: Inter;">Need Help</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Accessibility Widget -->
    <script src="https://cdn.ux4g.gov.in/tools/accessibility-widget.js" async></script>

    <!-- JavaScript for Tab Functionality -->
    <script>
    $(document).ready(function() {
        // Initialize Bootstrap tabs
        const feedbackTabs = document.getElementById('feedbackTabs');
        const tab = new bootstrap.Tab(feedbackTabs.querySelector('button[data-bs-target="#pending-tab-pane"]'));
        
        // Handle tab click events
        $('#submitted-tab').on('click', function() {
            // No need for AJAX since data is already loaded initially
            // Tab switching is handled by Bootstrap
        });
        
        // Handle form submission success
        @if(session('success'))
            // Show success message
            const successAlert = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert alert at the top of the content
            $('.container.my-5').prepend(successAlert);
            
            // Auto-switch to submitted tab after form submission
            setTimeout(function() {
                const submittedTab = new bootstrap.Tab(document.getElementById('submitted-tab'));
                submittedTab.show();
                
                // Refresh the page to update counts (simplest approach)
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            }, 1500);
        @endif
        
        @if(session('error'))
            // Show error message
            const errorAlert = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('.container.my-5').prepend(errorAlert);
        @endif
        
        // Add form validation
        $('#vertical-wizard').validate({
            rules: {
                'timetable_pk[]': {
                    required: false
                }
            },
            errorPlacement: function(error, element) {
                if (element.attr("name").includes("presentation") || 
                    element.attr("name").includes("content")) {
                    error.insertAfter(element.closest('td'));
                } else if (element.attr("name").includes("remarks")) {
                    error.insertAfter(element);
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // Show loading state on submit button
                const submitBtn = $(form).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true);
                submitBtn.html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Submitting...
                `);
                
                // Submit the form
                form.submit();
            }
        });
        
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
    });
    </script>
</body>
</html>