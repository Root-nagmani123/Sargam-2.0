<!-- Event Details Modal -->
<div class="modal fade" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <div class="d-flex flex-column w-100"> 
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="modal-title h5 mb-0" id="eventDetailsTitle">
                            <span id="eventTitle">Event</span>
                        </h3>
                        <div class="d-flex gap-2 align-items-center">
                            @if(hasRole('Training Induction Admin') || hasRole('Super Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST'))
                            <button type="button" class="btn btn-sm btn-primary" id="editEventBtn">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" id="deleteEventBtn">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                            </button>
                            @endif
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="mb-0 small fw-medium text-white">
                            <i class="material-icons me-1" aria-hidden="true">date_range</i><i class="bi bi-calendar me-1" aria-hidden="true"></i>
                            <span id="eventDate"></span>
                        </p>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0 align-items-start">
                        <a id="viewEventCardBtn" href="#" target="_blank" rel="noopener" class="btn cal-event-action-btn" title="View / Download Event Card PDF" aria-label="View event card">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        </a>
                        @if(hasRole('Training-Induction') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
                        <button type="button" class="btn cal-event-action-btn cal-event-action-btn--danger" id="deleteEventBtn" title="Delete event" aria-label="Delete event">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn cal-event-action-btn cal-event-action-btn--edit" id="editEventBtn" title="Edit event" aria-label="Edit event">
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>