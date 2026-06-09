<!-- Event Details Modal -->
<div class="modal fade cal-event-details-modal" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg cal-event-details-card">
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-0">
                    <div class="flex-grow-1 min-w-0 pe-2">
                        <h3 class="h5 fw-bold mb-1" id="eventDetailsTitle">
                            <span id="eventTitle">Event</span>
                        </h3>
                        <p class="mb-0 small text-secondary">
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

                <hr class="cal-event-details-divider my-3">

                <h4 class="h6 fw-bold mb-3" id="eventTopic"></h4>

                <div class="cal-event-info-block mb-2">
                    <p class="mb-1 small"><strong>Faculty:</strong> <span id="eventfaculty"></span></p>
                    <p class="mb-0 small"><strong>Group Name:</strong> <span id="eventgroupname"></span></p>
                    <span id="eventclasssession" class="visually-hidden"></span>
                    <span id="internal_faculty_name_show" class="visually-hidden"></span>
                </div>

                <div class="cal-event-info-block cal-event-info-block--venue d-flex align-items-center justify-content-between gap-2">
                    <p class="mb-0 small"><strong>Venue:</strong> <span id="eventVanue"></span></p>
                    <i class="bi bi-geo-alt-fill text-primary fs-5 flex-shrink-0" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
</div>
