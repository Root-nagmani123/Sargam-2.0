{{-- Floating event detail card (shown on hover / tap) — matches reference popover design --}}
<div id="calEventHoverCard" class="cal-event-hover-card d-none" role="tooltip" aria-live="polite" aria-hidden="true">
    <div class="cal-event-hover-card__panel shadow-lg">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="flex-grow-1 min-w-0">
                <h3 class="cal-event-hover-card__title h6 fw-bold mb-1" id="calHoverEventTitle">Event</h3>
                <p class="cal-event-hover-card__datetime small text-secondary mb-0" id="calHoverEventDate"></p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0 align-items-start">
                @if(hasRole('Training') || hasRole('Super Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST'))
                <button type="button" class="btn cal-event-action-btn cal-event-action-btn--danger" id="calHoverDeleteBtn" title="Delete event" aria-label="Delete event">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn cal-event-action-btn cal-event-action-btn--edit" id="calHoverEditBtn" title="Edit event" aria-label="Edit event">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                </button>
                @endif
            </div>
        </div>

        <hr class="cal-event-details-divider my-3">

        <p class="fw-bold mb-3 small" id="calHoverEventTopic"></p>

        <div class="cal-event-info-block mb-2">
            <p class="mb-1 small"><strong>Faculty:</strong> <span id="calHoverFaculty"></span></p>
            <p class="mb-0 small"><strong>Group Name:</strong> <span id="calHoverGroup"></span></p>
        </div>

        <div class="cal-event-info-block cal-event-info-block--venue d-flex align-items-center justify-content-between gap-2">
            <p class="mb-0 small"><strong>Venue:</strong> <span id="calHoverVenue"></span></p>
            <i class="bi bi-geo-alt-fill text-primary fs-5 flex-shrink-0" aria-hidden="true"></i>
        </div>
    </div>
    <span class="cal-event-hover-card__arrow" aria-hidden="true"></span>
</div>
