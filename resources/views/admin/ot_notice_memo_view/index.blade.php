@extends('admin.layouts.master')

@section('title', 'OT Notice / Memo View - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    /* GIGW skip link for keyboard users */
    .skip-link {
        position: absolute;
        top: -40px;
        left: 0;
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        color: #fff;
        padding: .75rem 1.5rem;
        z-index: 1050;
        font-weight: 500;
        text-decoration: none;
        border-radius: 0 0 8px 0;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .skip-link:focus {
        top: 0;
        outline: 3px solid #ffc107;
        outline-offset: 2px;
    }
    
    /* Modern card styles */
    .card-hover:hover {
        transform: translateY(-2px);
        transition: transform 0.2s ease;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }
    
    /* Status badges */
    .status-badge {
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    /* Conversation bubbles */
    .conversation-bubble {
        border-radius: 18px;
        padding: 1rem 1.25rem;
        max-width: 85%;
        position: relative;
        border: 1px solid rgba(0,0,0,0.08);
    }
    
    .conversation-bubble.faculty {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }
    
    .conversation-bubble.student {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }
    
    /* Print styles */
    @media print {
        .no-print {
            display: none !important;
        }
        .card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }
    }
    
    /* Focus styles for accessibility */
    *:focus {
        outline: 3px solid rgba(13, 110, 253, 0.5);
        outline-offset: 2px;
    }
    
    /* Smooth transitions */
    .transition-all {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" class="container-fluid py-4" role="main" aria-label="OT Notice and Memo Details">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb navigation">
        <x-breadcrum title="OT Notice / Memo View"></x-breadcrum>
    </nav>
    
    <!-- Status Messages -->
    <div aria-live="polite" aria-atomic="true" role="status" class="position-relative">
        <x-session_message />
    </div>
    
    <!-- Main Card -->
    <div class="card border-0 shadow-lg overflow-hidden">
        <!-- Card Header -->
        <div class="card-header bg-white border-bottom-0 pb-3 pt-4 px-4">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                            <i class="material-icons text-primary fs-4" aria-hidden="true">description</i>
                        </div>
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-gray-800">OT Notice / Memo View</h1>
                            <p class="text-muted mb-0">View and manage all notices and memos for this student</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end gap-2">
                        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2 no-print" onclick="window.print()" aria-label="Print this page">
                            <i class="material-icons fs-6" aria-hidden="true">print</i>
                            <span>Print</span>
                        </button>
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 no-print" onclick="history.back()" aria-label="Go back to previous page">
                            <i class="material-icons fs-6" aria-hidden="true">arrow_back</i>
                            <span>Back</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card Body -->
        <div class="card-body p-4">
            <!-- Student Information Card -->
            <div class="card border-primary border-start border-4 bg-light bg-opacity-10 mb-5 transition-all card-hover">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4 text-primary fw-bold d-flex align-items-center gap-2">
                        <i class="material-icons" aria-hidden="true">person</i>
                        Student Information
                    </h2>
                    
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="d-flex flex-column">
                                <span class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px;">
                                    <i class="material-icons me-1 fs-6" aria-hidden="true">badge</i>
                                    Student Name
                                </span>
                                <span class="fs-5 fw-semibold text-gray-800" aria-label="Student name: {{ $studentData['student_name'] }}">
                                    {{ $studentData['student_name'] }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="d-flex flex-column">
                                <span class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px;">
                                    <i class="material-icons me-1 fs-6" aria-hidden="true">code</i>
                                    OT Code
                                </span>
                                <span class="fs-5 fw-semibold text-gray-800" aria-label="OT code: {{ $studentData['ot_code'] }}">
                                    {{ $studentData['ot_code'] }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="d-flex flex-column">
                                <span class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px;">
                                    <i class="material-icons me-1 fs-6" aria-hidden="true">email</i>
                                    Email Address
                                </span>
                                <span class="fs-5 fw-semibold text-gray-800" aria-label="Email: {{ $studentData['email'] }}">
                                    {{ $studentData['email'] }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="d-flex flex-column">
                                <span class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px;">
                                    <i class="material-icons me-1 fs-6" aria-hidden="true">summarize</i>
                                    Total Records
                                </span>
                                <span class="fs-5 fw-bold text-primary" aria-label="Total notices and memos: {{ $studentData['total_count'] }}">
                                    {{ $studentData['total_count'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Records Section -->
            <section aria-label="Notice and Memo Records">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2">
                        <i class="material-icons text-primary" aria-hidden="true">list_alt</i>
                        Records
                        <span class="badge bg-primary rounded-pill ms-2">{{ $studentData['total_count'] ?? 0 }}</span>
                    </h2>
                    
                    @if(isset($studentData['has_records']) && $studentData['has_records'] && count($studentData['records']) > 0)
                    <div class="dropdown no-print">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter records">
                            <i class="material-icons fs-6" aria-hidden="true">filter_list</i>
                            Filter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><a class="dropdown-item" href="#" onclick="filterRecords('all')">All Records</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterRecords('notice')">Notices Only</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterRecords('memo')">Memos Only</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterRecords('open')">Open Status</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filterRecords('closed')">Closed Status</a></li>
                        </ul>
                    </div>
                    @endif
                </div>
                
                <!-- Records List -->
                @if(isset($studentData['has_records']) && $studentData['has_records'])
                    @if(count($studentData['records']) > 0)
                        <div class="row g-4" role="list" aria-label="List of notice and memo records">
                            @foreach($studentData['records'] as $index => $record)
                                <div class="col-12" role="listitem" aria-label="Record {{ $index + 1 }} of {{ count($studentData['records']) }}">
                                    <article class="card border-0 shadow-sm transition-all card-hover">
                                        <div class="card-body p-4">
                                            <!-- Record Header -->
                                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-{{ strtolower($record->type) === 'notice' ? 'info' : 'warning' }} bg-opacity-10 p-2 rounded">
                                                        <i class="material-icons text-{{ strtolower($record->type) === 'notice' ? 'info' : 'warning' }} fs-5" aria-hidden="true">
                                                            {{ strtolower($record->type) === 'notice' ? 'campaign' : 'gavel' }}
                                                        </i>
                                                    </div>
                                                    <div>
                                                        <h3 class="h5 mb-1 fw-bold text-gray-800">
                                                            {{ $record->type }}: {{ $record->course_name ?? 'N/A' }}
                                                        </h3>
                                                        <p class="text-muted small mb-0">
                                                            <i class="material-icons me-1 fs-6" aria-hidden="true">schedule</i>
                                                            Session: {{ $record->session_date ? \Carbon\Carbon::parse($record->session_date)->format('d M, Y') : 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge status-badge {{ strtolower($record->type) === 'notice' ? 'bg-info text-dark' : 'bg-warning text-dark' }}" 
                                                          aria-label="Record type: {{ $record->type }}">
                                                        {{ $record->type }}
                                                    </span>
                                                    <span class="badge status-badge {{ $record->status == 1 ? 'bg-success' : 'bg-danger' }}" 
                                                          aria-label="Status: {{ $record->status == 1 ? 'Open' : 'Closed' }}">
                                                        <i class="material-icons me-1 fs-6" aria-hidden="true">
                                                            {{ $record->status == 1 ? 'check_circle' : 'cancel' }}
                                                        </i>
                                                        {{ $record->status == 1 ? 'Open' : 'Closed' }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Record Details Grid -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-6 col-md-4 col-lg-3">
                                                    <div class="border-start border-3 border-primary ps-3 py-1">
                                                        <small class="text-muted fw-semibold d-block">Course Name</small>
                                                        <span class="fw-medium">{{ $record->course_name ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-md-4 col-lg-3">
                                                    <div class="border-start border-3 border-primary ps-3 py-1">
                                                        <small class="text-muted fw-semibold d-block">Participant</small>
                                                        <span class="fw-medium">{{ $record->participant_name ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-md-4 col-lg-3">
                                                    <div class="border-start border-3 border-primary ps-3 py-1">
                                                        <small class="text-muted fw-semibold d-block">Topic</small>
                                                        <span class="fw-medium">{{ $record->topic ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-md-4 col-lg-3">
                                                    <div class="border-start border-3 border-primary ps-3 py-1">
                                                        <small class="text-muted fw-semibold d-block">Session Date</small>
                                                        <span class="fw-medium">{{ $record->session_date ? \Carbon\Carbon::parse($record->session_date)->format('d/m/Y') : 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                
                                                @if($record->type == 'Memo')
                                                    @if(isset($record->response))
                                                    <div class="col-sm-6 col-md-4 col-lg-3">
                                                        <div class="border-start border-3 border-warning ps-3 py-1">
                                                            <small class="text-muted fw-semibold d-block">Response</small>
                                                            <span class="fw-medium">{{ $record->response }}</span>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @if(isset($record->conclusion_type))
                                                    <div class="col-sm-6 col-md-4 col-lg-3">
                                                        <div class="border-start border-3 border-warning ps-3 py-1">
                                                            <small class="text-muted fw-semibold d-block">Conclusion Type</small>
                                                            <span class="fw-medium">{{ $record->conclusion_type }}</span>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @if(isset($record->conclusion_remark))
                                                    <div class="col-sm-6 col-md-4 col-lg-3">
                                                        <div class="border-start border-3 border-warning ps-3 py-1">
                                                            <small class="text-muted fw-semibold d-block">Conclusion Remark</small>
                                                            <span class="fw-medium">{{ $record->conclusion_remark }}</span>
                                                        </div>
                                                    </div>
                                                    @endif
                                                @endif
                                            </div>
                                            
                                            <!-- Conversation Section -->
                                            @if(isset($record->conversations) && $record->conversations->count() > 0)
                                                <section class="border-top pt-4 mt-4" aria-label="Conversation history">
                                                    <div class="d-flex align-items-center gap-2 mb-4">
                                                        <div class="bg-primary bg-opacity-10 p-2 rounded">
                                                            <i class="material-icons text-primary fs-5" aria-hidden="true">chat</i>
                                                        </div>
                                                        <h4 class="h6 mb-0 fw-bold text-gray-800">Conversation History</h4>
                                                        <span class="badge bg-primary rounded-pill ms-2">{{ $record->conversations->count() }}</span>
                                                    </div>
                                                    
                                                    <div class="d-flex flex-column gap-3">
                                                        @foreach($record->conversations as $convIndex => $conversation)
                                                            <div class="conversation-bubble {{ isset($conversation->role_type) && $conversation->role_type == 'f' ? 'faculty' : 'student' }}"
                                                                 role="group"
                                                                 aria-label="Message from {{ isset($conversation->role_type) && $conversation->role_type == 'f' ? 'Faculty/Admin' : 'Student' }}">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <span class="badge bg-{{ isset($conversation->role_type) && $conversation->role_type == 'f' ? 'primary' : 'secondary' }} rounded-pill">
                                                                        @if(isset($conversation->role_type))
                                                                            @if($conversation->role_type == 'f')
                                                                                <i class="material-icons me-1 fs-6" aria-hidden="true">school</i> Faculty/Admin
                                                                            @elseif($conversation->role_type == 's')
                                                                                <i class="material-icons me-1 fs-6" aria-hidden="true">person</i> Student
                                                                            @else
                                                                                Unknown
                                                                            @endif
                                                                        @endif
                                                                    </span>
                                                                    <span class="text-muted small">
                                                                        <i class="material-icons me-1 fs-6" aria-hidden="true">access_time</i>
                                                                        {{ $conversation->created_date ? \Carbon\Carbon::parse($conversation->created_date)->format('d M Y, h:i A') : 'N/A' }}
                                                                    </span>
                                                                </div>
                                                                <p class="mb-2">{{ $conversation->student_decip_incharge_msg ?? 'N/A' }}</p>
                                                                
                                                                @if(isset($conversation->doc_upload) && $conversation->doc_upload)
                                                                    <div class="mt-3">
                                                                        <a href="{{ asset('storage/' . $conversation->doc_upload) }}" 
                                                                           target="_blank" 
                                                                           rel="noopener noreferrer" 
                                                                           class="btn btn-sm btn-outline-{{ isset($conversation->role_type) && $conversation->role_type == 'f' ? 'primary' : 'secondary' }} d-inline-flex align-items-center gap-2"
                                                                           aria-label="Download attached document">
                                                                            <i class="material-icons fs-6" aria-hidden="true">download</i>
                                                                            View Document
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </section>
                                            @endif
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5" role="status" aria-live="polite">
                            <div class="mb-4">
                                <i class="material-icons text-muted opacity-50" style="font-size: 4rem;" aria-hidden="true">inbox</i>
                            </div>
                            <h3 class="h5 text-muted mb-2">No Records Found</h3>
                            <p class="text-muted mb-0">There are no notice or memo records for this student.</p>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5" role="status" aria-live="polite">
                        <div class="mb-4">
                            <i class="material-icons text-muted opacity-50" style="font-size: 4rem;" aria-hidden="true">search_off</i>
                        </div>
                        <h3 class="h5 text-muted mb-2">No Records Available</h3>
                        <p class="text-muted mb-0">No notice or memo records found for this student.</p>
                    </div>
                @endif
            </section>
        </div>
        
        <!-- Card Footer -->
        <div class="card-footer bg-white border-top-0 pt-3 px-4 no-print">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">
                        <i class="material-icons me-1 fs-6" aria-hidden="true">info</i>
                        Showing all notice and memo records for this student
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top of page">
                            <i class="material-icons fs-6" aria-hidden="true">arrow_upward</i>
                            <span>Back to Top</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Filter functionality (basic example)
function filterRecords(type) {
    alert('Filter functionality would show ' + type + ' records here. This requires backend implementation.');
}

// Print functionality enhancement
window.addEventListener('beforeprint', function() {
    document.querySelectorAll('.no-print').forEach(el => {
        el.classList.add('d-none');
    });
});

window.addEventListener('afterprint', function() {
    document.querySelectorAll('.no-print').forEach(el => {
        el.classList.remove('d-none');
    });
});

// Add keyboard navigation for cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card[role="listitem"]');
    cards.forEach((card, index) => {
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
            if (e.key === 'ArrowDown' && cards[index + 1]) {
                cards[index + 1].focus();
            }
            if (e.key === 'ArrowUp' && cards[index - 1]) {
                cards[index - 1].focus();
            }
        });
    });
});
</script>
@endsection