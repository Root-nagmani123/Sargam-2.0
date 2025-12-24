<!-- Event Details Modal -->
<div class="modal fade" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsTitle" aria-hidden="true" 
     role="dialog" aria-modal="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <!-- Enhanced Header with Gradient Background -->
            <div class="modal-header position-relative p-0 overflow-hidden" 
                 style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                <!-- Decorative Elements -->
                <div class="position-absolute top-0 end-0 w-100 h-100 opacity-10">
                    <div class="position-absolute top-0 end-0 translate-middle" style="width: 300px; height: 300px;">
                        <div class="rounded-circle bg-white" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>
                
                <div class="w-100 p-4 position-relative z-1">
                    <div class="d-flex flex-column gap-2">
                        <!-- Title and Close Button -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white bg-opacity-20 p-2 rounded-circle">
                                    <i class="material-icons text-white fs-5" aria-hidden="true">event</i>
                                </div>
                                <div>
                                    <h2 class="modal-title h4 mb-0 text-white fw-bold" id="eventDetailsTitle">
                                        <span id="eventTitle" class="text-truncate d-inline-block" style="max-width: 300px;">Event Details</span>
                                    </h2>
                                    <p class="text-white-80 mb-0 small text-white">
                                        <i class="material-icons me-1" aria-hidden="true" style="font-size: 14px;">info</i>
                                        Complete event information
                                    </p>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light btn-rounded p-2" 
                                    data-bs-dismiss="modal" aria-label="Close modal">
                                <i class="material-icons" aria-hidden="true">close</i>
                                <span class="visually-hidden">Close</span>
                            </button>
                        </div>
                        
                        <!-- Date Badge -->
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="badge bg-white bg-opacity-25 text-white border-0 d-inline-flex align-items-center gap-1 px-3 py-1">
                                <i class="material-icons" aria-hidden="true" style="font-size: 16px;">calendar_today</i>
                                <span id="eventDate" class="fw-medium">Loading date...</span>
                            </span>
                            <span class="badge bg-warning bg-opacity-25 text-warning border-0 d-inline-flex align-items-center gap-1 px-3 py-1">
                                <i class="material-icons" aria-hidden="true" style="font-size: 16px;">schedule</i>
                                <span id="eventTime" class="fw-medium">Time pending</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Topic Section -->
                <div class="mb-4">
                    <h3 class="h6 fw-semibold text-primary mb-2 d-flex align-items-center gap-2">
                        <i class="material-icons" aria-hidden="true">topic</i>
                        Topic
                    </h3>
                    <div class="bg-light bg-opacity-25 p-3 rounded border-start border-4 border-primary">
                        <p id="eventTopic" class="mb-0 fw-medium text-body" style="line-height: 1.6;">Loading topic...</p>
                    </div>
                </div>

                <!-- Event Details Grid -->
                <div class="row g-3 mb-4">
                    <!-- Faculty -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 bg-light bg-opacity-10 h-100 transition-all hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                                        <i class="material-icons text-primary" aria-hidden="true" style="font-size: 18px;">school</i>
                                    </div>
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                                        Faculty
                                    </span>
                                </div>
                                <p id="eventfaculty" class="mb-0 fw-medium text-body" aria-label="Faculty name">
                                    <span class="placeholder-glow">
                                        <span class="placeholder col-8"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Session -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 bg-light bg-opacity-10 h-100 transition-all hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-info bg-opacity-10 p-2 rounded-circle">
                                        <i class="material-icons text-info" aria-hidden="true" style="font-size: 18px;">groups</i>
                                    </div>
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                                        Session
                                    </span>
                                </div>
                                <p id="eventclasssession" class="mb-0 fw-medium text-body" aria-label="Session details">
                                    <span class="placeholder-glow">
                                        <span class="placeholder col-6"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Group Name -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 bg-light bg-opacity-10 h-100 transition-all hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                                        <i class="material-icons text-success" aria-hidden="true" style="font-size: 18px;">diversity_3</i>
                                    </div>
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                                        Group
                                    </span>
                                </div>
                                <p id="eventgroupname" class="mb-0 fw-medium text-body" aria-label="Group name">
                                    <span class="placeholder-glow">
                                        <span class="placeholder col-7"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Venue -->
                    <div class="col-md-6 col-lg-8">
                        <div class="card border-0 bg-light bg-opacity-10 h-100 transition-all hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-warning bg-opacity-10 p-2 rounded-circle">
                                        <i class="material-icons text-warning" aria-hidden="true" style="font-size: 18px;">location_on</i>
                                    </div>
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                                        Venue
                                    </span>
                                </div>
                                <p id="eventVanue" class="mb-0 fw-medium text-body" aria-label="Event venue">
                                    <span class="placeholder-glow">
                                        <span class="placeholder col-10"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 bg-light bg-opacity-10 h-100 transition-all hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                        <i class="material-icons text-danger" aria-hidden="true" style="font-size: 18px;">flag</i>
                                    </div>
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                                        Status
                                    </span>
                                </div>
                                <div>
                                    <span id="eventStatus" class="badge bg-success fw-medium px-3 py-1" aria-label="Event status">
                                        <span class="placeholder-glow">
                                            <span class="placeholder col-4"></span>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Details Section (Collapsible) -->
                <div class="accordion mb-4" id="additionalDetailsAccordion">
                    <div class="accordion-item border-0 shadow-sm">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed bg-light border-0 fw-semibold px-4 py-3" 
                                    type="button" data-bs-toggle="collapse" data-bs-target="#additionalDetailsContent" 
                                    aria-expanded="false" aria-controls="additionalDetailsContent"
                                    aria-label="Toggle additional event details">
                                <i class="material-icons me-2 text-primary" aria-hidden="true">expand_more</i>
                                Additional Details
                            </button>
                        </h3>
                        <div id="additionalDetailsContent" class="accordion-collapse collapse" 
                             data-bs-parent="#additionalDetailsAccordion">
                            <div class="accordion-body p-4">
                                <div class="row g-3" id="additionalDetailsGrid">
                                    <!-- Dynamic additional details will be loaded here -->
                                    <div class="col-12 text-center text-muted py-3">
                                        <i class="material-icons mb-2" aria-hidden="true" style="font-size: 48px; opacity: 0.3;">info</i>
                                        <p class="mb-0">No additional details available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                @if(hasRole('Training') || hasRole('Admin'))
                <div class="border-top pt-4 mt-4">
                    <div class="d-flex flex-column flex-sm-row gap-3 align-items-center justify-content-between">
                        <div class="text-muted small">
                            <i class="material-icons me-1" aria-hidden="true" style="font-size: 16px;">security</i>
                            Administrative actions require appropriate permissions
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2" 
                                    id="editEventBtn" aria-label="Edit this event">
                                <i class="material-icons" aria-hidden="true">edit</i>
                                <span>Edit Event</span>
                            </button>
                            
                            <!-- Delete Button -->
                            <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-2 px-4 py-2" 
                                    id="deleteEventBtn" aria-label="Delete this event">
                                <i class="material-icons" aria-hidden="true">delete</i>
                                <span>Delete</span>
                            </button>
                            
                            <!-- Share Button -->
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 px-4 py-2" 
                                    id="shareEventBtn" aria-label="Share event details">
                                <i class="material-icons" aria-hidden="true">share</i>
                                <span>Share</span>
                            </button>
                            
                            <!-- Export Button -->
                            <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2 px-4 py-2" 
                                    id="exportEventBtn" aria-label="Export event details">
                                <i class="material-icons" aria-hidden="true">download</i>
                                <span>Export</span>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top-0 pt-3 px-4 pb-4">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="text-muted small">
                        <i class="material-icons me-1" aria-hidden="true" style="font-size: 14px;">update</i>
                        Last updated: <span id="lastUpdated" class="fw-medium">Just now</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2" 
                                data-bs-dismiss="modal" aria-label="Close modal without saving">
                            <i class="material-icons" aria-hidden="true">close</i>
                            <span>Close</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2" 
                                id="printEventBtn" aria-label="Print event details">
                            <i class="material-icons" aria-hidden="true">print</i>
                            <span>Print</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Optional: Add CSS for animations and transitions -->
<style>
    .modal-dialog {
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .hover-lift:hover {
        transform: translateY(-4px);
        transition: transform 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }
    
    .transition-all {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-rounded {
        border-radius: 50px !important;
    }
    
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }
    
    .bg-opacity-20 {
        --bs-bg-opacity: 0.2;
    }
    
    .bg-opacity-25 {
        --bs-bg-opacity: 0.25;
    }
    
    /* Accessibility focus styles */
    .modal-content *:focus {
        outline: 3px solid rgba(0, 74, 147, 0.5);
        outline-offset: 2px;
    }
    
    /* Loading placeholder animation */
    @keyframes placeholder-glow {
        50% {
            opacity: 0.2;
        }
    }
    
    .placeholder-glow .placeholder {
        animation: placeholder-glow 2s ease-in-out infinite;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .modal-header .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 1rem !important;
        }
        
        .modal-header .btn {
            margin-top: 0.5rem;
        }
        
        .modal-footer .d-flex {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
    }
    
    @media (max-width: 576px) {
        .modal-body .row.g-3 > [class*="col-"] {
            margin-bottom: 0.75rem;
        }
        
        .modal-body .card {
            margin-bottom: 0.5rem;
        }
    }
    
    /* Print styles */
    @media print {
        .modal-header {
            background: #004a93 !important;
        }
        
        .modal-footer,
        .accordion-button,
        [id$="EventBtn"]:not(#printEventBtn) {
            display: none !important;
        }
        
        .modal-dialog {
            max-width: 100% !important;
            margin: 0 !important;
        }
        
        .modal-content {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focus management for accessibility
    const eventModal = document.getElementById('eventDetails');
    eventModal.addEventListener('shown.bs.modal', function() {
        const firstFocusable = eventModal.querySelector('button[data-bs-dismiss="modal"]');
        firstFocusable?.focus();
    });
    
    eventModal.addEventListener('hidden.bs.modal', function() {
        // Return focus to the trigger button
        const triggerBtn = document.querySelector('[data-bs-target="#eventDetails"]');
        triggerBtn?.focus();
    });
    
    // Keyboard navigation
    eventModal.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const closeBtn = eventModal.querySelector('[data-bs-dismiss="modal"]');
            closeBtn?.click();
        }
        
        // Trap focus within modal
        if (e.key === 'Tab') {
            const focusableElements = eventModal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    });
    
    // Button functionality
    document.getElementById('editEventBtn')?.addEventListener('click', function() {
        console.log('Edit event clicked');
        // Implement edit functionality
    });
    
    document.getElementById('deleteEventBtn')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
            console.log('Delete event confirmed');
            // Implement delete functionality
        }
    });
    
    document.getElementById('printEventBtn')?.addEventListener('click', function() {
        window.print();
    });
    
    document.getElementById('shareEventBtn')?.addEventListener('click', function() {
        if (navigator.share) {
            navigator.share({
                title: document.getElementById('eventTitle').textContent,
                text: document.getElementById('eventTopic').textContent,
                url: window.location.href
            });
        } else {
            // Fallback copy to clipboard
            const textToCopy = `${document.getElementById('eventTitle').textContent}\n${document.getElementById('eventTopic').textContent}`;
            navigator.clipboard.writeText(textToCopy).then(() => {
                alert('Event details copied to clipboard!');
            });
        }
    });
    
    document.getElementById('exportEventBtn')?.addEventListener('click', function() {
        // Implement export functionality
        console.log('Export event clicked');
    });
    
    // Load event data function (to be called from parent page)
    window.loadEventDetails = function(eventData) {
        // Update all elements with event data
        document.getElementById('eventTitle').textContent = eventData.title || 'Untitled Event';
        document.getElementById('eventDate').textContent = eventData.date || 'Date not specified';
        document.getElementById('eventTime').textContent = eventData.time || 'Time not specified';
        document.getElementById('eventTopic').textContent = eventData.topic || 'No topic specified';
        document.getElementById('eventfaculty').textContent = eventData.faculty || 'Not assigned';
        document.getElementById('eventclasssession').textContent = eventData.session || 'No session';
        document.getElementById('eventgroupname').textContent = eventData.group || 'No group';
        document.getElementById('eventVanue').textContent = eventData.venue || 'Venue not specified';
        document.getElementById('eventStatus').textContent = eventData.status || 'Pending';
        document.getElementById('eventStatus').className = `badge fw-medium px-3 py-1 ${getStatusClass(eventData.status)}`;
        document.getElementById('lastUpdated').textContent = eventData.lastUpdated || 'Just now';
        
        // Remove placeholders
        document.querySelectorAll('.placeholder, .placeholder-glow').forEach(el => {
            el.classList.remove('placeholder', 'placeholder-glow');
        });
    };
    
    function getStatusClass(status) {
        const statusMap = {
            'Completed': 'bg-success',
            'Ongoing': 'bg-primary',
            'Upcoming': 'bg-info',
            'Cancelled': 'bg-danger',
            'Pending': 'bg-warning'
        };
        return statusMap[status] || 'bg-secondary';
    }
});
</script>