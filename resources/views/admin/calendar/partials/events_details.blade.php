<!-- Event Details Modal -->
<style>
    /* Event Details Modal - Responsive Styles */
    #eventDetails .modal-dialog {
        margin: 0;
        max-width: 100vw;
        width: 100vw;
        max-height: 100vh;
        height: 100vh;
        display: flex;
        align-items: stretch;
    }
    
    @media (min-width: 576px) {
        #eventDetails .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
            width: calc(100vw - 1rem);
            max-height: calc(100vh - 1rem);
            height: auto;
        }
    }
    
    #eventDetails .modal-content {
        border-radius: 0;
        max-height: 100vh;
        height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
        overflow: hidden;
    }
    
    @media (min-width: 576px) {
        #eventDetails .modal-content {
            border-radius: 0.75rem;
            max-height: calc(100vh - 1rem);
            height: auto;
            margin: 0;
        }
    }
    
    #eventDetails .modal-header {
        padding: 1rem;
        border-radius: 0.75rem 0.75rem 0 0;
    }
    
    #eventDetails .modal-body {
        padding: 1rem;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
    }
    
    @media (max-width: 575.98px) {
        #eventDetails .modal-body {
            padding: 0.75rem 0.5rem;
        }
    }
    
    #eventDetails .detail-item {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        border-left: 3px solid var(--bs-primary);
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        word-wrap: break-word;
    }
    
    #eventDetails .detail-item i {
        flex-shrink: 0;
        margin-top: 0.125rem;
    }
    
    #eventDetails .detail-item strong {
        flex-shrink: 0;
        min-width: fit-content;
    }
    
    #eventDetails .detail-item span {
        word-break: break-word;
        flex: 1;
    }
    
    /* Very Small Devices (< 400px) */
    @media (max-width: 399.98px) {
        #eventDetails .modal-dialog {
            margin: 0 !important;
            max-width: 100vw !important;
            width: 100vw !important;
            max-height: 100vh !important;
            height: 100vh !important;
        }
        
        #eventDetails .modal-content {
            border-radius: 0 !important;
            max-height: 100vh !important;
            height: 100vh !important;
        }
        
        #eventDetails .modal-header {
            padding: 0.75rem 0.5rem !important;
        }
        
        #eventDetails .modal-title {
            font-size: 0.95rem !important;
            line-height: 1.3;
            word-break: break-word;
        }
        
        #eventDetails .modal-header .d-flex {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        #eventDetails .modal-header .d-flex > div:first-child {
            width: 100%;
        }
        
        #eventDetails .modal-header .d-flex > div:last-child {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        
        #eventDetails .modal-header .btn {
            font-size: 0.75rem !important;
            padding: 0.4rem 0.6rem !important;
            min-height: 36px;
        }
        
        #eventDetails .modal-header .btn i {
            margin-right: 0.25rem !important;
        }
        
        #eventDetails .modal-header .btn span {
            display: none;
        }
        
        #eventDetails .modal-header .btn-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            margin: 0;
            padding: 0.5rem;
        }
        
        #eventDetails .modal-header p {
            font-size: 0.75rem !important;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        #eventDetails .modal-body {
            padding: 0.75rem 0.5rem !important;
            max-height: calc(100vh - 140px);
        }
        
        #eventDetails .event-details h4 {
            font-size: 0.9rem !important;
            margin-bottom: 0.75rem !important;
            word-break: break-word;
        }
        
        #eventDetails .detail-item {
            padding: 0.625rem !important;
            margin-bottom: 0.5rem;
            flex-direction: column;
            gap: 0.35rem;
        }
        
        #eventDetails .detail-item i {
            font-size: 1rem;
        }
        
        #eventDetails .detail-item strong {
            font-size: 0.8rem;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        #eventDetails .detail-item span {
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        #eventDetails .row.g-3 {
            --bs-gutter-y: 0.5rem;
            --bs-gutter-x: 0.5rem;
        }
        
        #eventDetails .row > [class*="col-"] {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
    }
    
    /* Small Mobile Devices (400px - 575px) */
    @media (min-width: 400px) and (max-width: 575.98px) {
        #eventDetails .modal-dialog {
            margin: 0 !important;
            max-width: 100vw !important;
            width: 100vw !important;
            max-height: 100vh !important;
            height: 100vh !important;
        }
        
        #eventDetails .modal-content {
            border-radius: 0 !important;
            max-height: 100vh !important;
            height: 100vh !important;
        }
        
        #eventDetails .modal-header {
            padding: 0.875rem !important;
        }
        
        #eventDetails .modal-title {
            font-size: 1rem !important;
        }
        
        #eventDetails .modal-header .btn {
            font-size: 0.8rem !important;
            padding: 0.45rem 0.7rem !important;
            min-height: 38px;
        }
        
        #eventDetails .modal-header .btn span {
            display: inline;
        }
        
        #eventDetails .modal-body {
            padding: 0.875rem !important;
            max-height: calc(100vh - 160px);
        }
        
        #eventDetails .event-details h4 {
            font-size: 0.95rem !important;
        }
        
        #eventDetails .detail-item {
            padding: 0.75rem !important;
            flex-direction: row;
            align-items: flex-start;
        }
        
        #eventDetails .detail-item strong {
            font-size: 0.85rem;
            min-width: 100px;
        }
        
        #eventDetails .detail-item span {
            font-size: 0.875rem;
        }
    }
    
    /* Tablet Portrait (576px - 767px) */
    @media (min-width: 576px) and (max-width: 767.98px) {
        #eventDetails .modal-dialog {
            max-width: 540px;
            margin: 1rem auto;
        }
        
        #eventDetails .modal-content {
            max-height: calc(100vh - 2rem);
        }
        
        #eventDetails .modal-header {
            padding: 1rem;
        }
        
        #eventDetails .modal-body {
            padding: 1rem;
            max-height: calc(100vh - 180px);
        }
        
        #eventDetails .detail-item {
            padding: 0.875rem;
        }
        
        #eventDetails .detail-item strong {
            min-width: 120px;
        }
    }
    
    /* Tablet Landscape / Small Desktop (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991.98px) {
        #eventDetails .modal-dialog {
            max-width: 720px;
            margin: 1.75rem auto;
        }
        
        #eventDetails .modal-body {
            max-height: calc(100vh - 200px);
        }
        
        #eventDetails .detail-item {
            padding: 1rem;
        }
    }
    
    /* Desktop (992px+) */
    @media (min-width: 992px) {
        #eventDetails .modal-dialog {
            max-width: 900px;
            margin: 1.75rem auto;
        }
        
        #eventDetails .modal-header {
            padding: 1.25rem 1.5rem;
        }
        
        #eventDetails .modal-body {
            padding: 1.5rem;
        }
        
        #eventDetails .detail-item {
            padding: 1rem 1.25rem;
        }
        
        #eventDetails .detail-item strong {
            min-width: 140px;
        }
    }
    
    /* Landscape orientation on mobile */
    @media (max-width: 767.98px) and (orientation: landscape) {
        #eventDetails .modal-dialog {
            margin: 0.25rem !important;
            max-width: calc(100vw - 0.5rem) !important;
            width: calc(100vw - 0.5rem) !important;
            max-height: calc(100vh - 0.5rem) !important;
            height: calc(100vh - 0.5rem) !important;
        }
        
        #eventDetails .modal-content {
            max-height: calc(100vh - 0.5rem) !important;
            height: calc(100vh - 0.5rem) !important;
            border-radius: 0.5rem !important;
        }
        
        #eventDetails .modal-body {
            max-height: calc(100vh - 140px) !important;
            padding: 0.75rem !important;
        }
        
        #eventDetails .modal-header {
            padding: 0.75rem !important;
        }
        
        #eventDetails .modal-header .d-flex {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        #eventDetails .modal-header .d-flex > div:last-child {
            width: auto;
        }
    }
    
    /* Better button layout on mobile */
    @media (max-width: 575.98px) {
        #eventDetails .modal-header .d-flex.gap-2 {
            flex-wrap: wrap;
            gap: 0.5rem !important;
        }
        
        #eventDetails .modal-header .btn {
            flex: 1;
            min-width: 0;
            max-width: calc(50% - 0.25rem);
        }
        
        #eventDetails .modal-header .btn-close {
            flex: 0 0 auto;
            max-width: none;
        }
    }
    
    /* Ensure proper text wrapping */
    #eventDetails .modal-title,
    #eventDetails .detail-item span,
    #eventDetails #eventTopic {
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }
    
    /* Better icon alignment */
    #eventDetails .detail-item {
        align-items: flex-start;
    }
    
    #eventDetails .detail-item i {
        margin-top: 0.2rem;
    }
    
    /* Prevent horizontal overflow */
    #eventDetails,
    #eventDetails .modal-content,
    #eventDetails .modal-body {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    /* Better spacing for detail items */
    @media (max-width: 767.98px) {
        #eventDetails .row.g-3 {
            --bs-gutter-y: 0.75rem;
        }
        
        #eventDetails .row > [class*="col-"] {
            margin-bottom: 0.5rem;
        }
        
        #eventDetails .row > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
    }
    
    /* Improved visual hierarchy */
    #eventDetails .event-details h4 {
        color: var(--bs-primary);
        font-weight: 600;
        border-bottom: 2px solid var(--bs-primary);
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 575.98px) {
        #eventDetails .event-details h4 {
            font-size: 0.95rem;
            padding-bottom: 0.375rem;
            margin-bottom: 0.75rem;
        }
    }
    
    /* Responsive button styles */
    #eventDetails .btn-responsive {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }
    
    @media (max-width: 399.98px) {
        #eventDetails .btn-responsive {
            min-height: 36px;
            font-size: 0.75rem;
            padding: 0.4rem 0.6rem;
        }
    }
    
    @media (min-width: 400px) and (max-width: 575.98px) {
        #eventDetails .btn-responsive {
            min-height: 38px;
            font-size: 0.8rem;
            padding: 0.45rem 0.7rem;
        }
    }
    
    /* Better empty state handling */
    #eventDetails .detail-item span:empty::before {
        content: "—";
        color: #6c757d;
        font-style: italic;
    }
    
    /* Improved hover effects */
    @media (hover: hover) {
        #eventDetails .detail-item:hover {
            background-color: #e9ecef;
            transform: translateX(2px);
            transition: all 0.2s ease;
        }
    }
    
    /* Better icon colors for different detail types */
    #eventDetails .detail-item i.bi-person-fill,
    #eventDetails .detail-item i.bi-person-check-fill {
        color: #0d6efd;
    }
    
    #eventDetails .detail-item i.bi-clock-history {
        color: #198754;
    }
    
    #eventDetails .detail-item i.bi-geo-alt-fill {
        color: #dc3545;
    }
    
    #eventDetails .detail-item i.bi-people-fill {
        color: #6f42c1;
    }
    
    /* Ensure modal doesn't overflow viewport */
    @media (max-width: 767.98px) {
        #eventDetails.modal {
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        
        #eventDetails .modal-dialog {
            margin: 0 !important;
            max-width: 100vw !important;
            width: 100vw !important;
            max-height: 100vh !important;
            height: 100vh !important;
        }
        
        #eventDetails .modal-content {
            border-radius: 0 !important;
            max-height: 100vh !important;
            height: 100vh !important;
        }
        
        #eventDetails .modal-body {
            max-height: none !important;
            overflow-y: auto !important;
        }
    }
    
    /* Better date display */
    #eventDetails #eventDate {
        word-break: break-word;
        line-height: 1.5;
    }
    
    /* Improved header layout on very small screens */
    @media (max-width: 399.98px) {
        #eventDetails .modal-header .d-flex > div:first-child {
            padding-right: 2rem;
        }
    }
    
    /* Better spacing for action buttons */
    @media (max-width: 575.98px) {
        #eventDetails .modal-header .d-flex.gap-2 {
            width: 100%;
            justify-content: space-between;
        }
        
        #eventDetails .modal-header .btn-close {
            order: 3;
        }
    }
</style>

<div class="modal fade" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary bg-gradient text-white position-relative">
                <div class="d-flex flex-column w-100"> 
                    <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-wrap gap-2">
                        <h3 class="modal-title h5 mb-0 flex-grow-1" id="eventDetailsTitle">
                            <span id="eventTitle">Event</span>
                        </h3>
                        <div class="d-flex gap-2 align-items-center flex-wrap flex-shrink-0">
                            @if(hasRole('Training-Induction') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
                            <button type="button" class="btn btn-sm btn-light btn-responsive" id="editEventBtn">
                                <i class="bi bi-pencil" aria-hidden="true"></i> 
                                <span class="d-none d-sm-inline">Edit</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-responsive" id="deleteEventBtn">
                                <i class="bi bi-trash" aria-hidden="true"></i> 
                                <span class="d-none d-sm-inline">Delete</span>
                            </button>
                            @endif
                            <button type="button" class="btn-close btn-close-white flex-shrink-0" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="mt-2 mt-sm-2">
                        <p class="mb-0 small fw-medium text-white d-flex align-items-center gap-2">
                            <i class="bi bi-calendar flex-shrink-0" aria-hidden="true"></i>
                            <span id="eventDate" class="text-break"></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-body bg-light">
                <div class="event-details">
                    <h4 class="h6 mb-3 fw-bold" id="eventTopic"></h4>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-person-fill text-primary" aria-hidden="true"></i>
                                <strong>Faculty:</strong>
                                <span id="eventfaculty"></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-clock-history text-primary" aria-hidden="true"></i>
                                <strong>Session:</strong>
                                <span id="eventclasssession"></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-people-fill text-primary" aria-hidden="true"></i>
                                <strong>Group name:</strong>
                                <span id="eventgroupname"></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-geo-alt-fill text-primary" aria-hidden="true"></i>
                                <strong>Venue:</strong>
                                <span id="eventVanue"></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-person-check-fill text-primary" aria-hidden="true"></i>
                                <strong>Internal Faculty:</strong>
                                <span id="internal_faculty_name_show"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>