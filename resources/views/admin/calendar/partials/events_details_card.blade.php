@php
    $isModal = ($prefix ?? '') === 'modal';
    $canManageEvents = $canManageEvents ?? false;
@endphp
<div class="fc-event-card calendar-reference-card calendar-event-details-card"
    role="document"
    @if($isModal) aria-labelledby="eventDetailsTitle" @endif>

    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
        <div class="min-w-0 flex-grow-1">
            <div class="event-card-title" @if($isModal) id="eventDetailsTitle" @endif>
                <span @if($isModal) id="eventTitle" @endif data-detail="title">Event</span>
            </div>
            <div class="event-card-time">
                <span @if($isModal) id="eventDate" @endif data-detail="date"></span>
                <span @if($isModal) id="eventclasssession" @endif data-detail="session" class="visually-hidden" aria-hidden="true"></span>
            </div>
        </div>
        @if($canManageEvents)
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <button type="button"
                class="btn border-0 p-0 event-card-action event-card-action-danger"
                @if($isModal) id="deleteEventBtn" @endif
                data-event-detail-delete
                data-detail="delete-btn"
                title="Delete event"
                aria-label="Delete event">
                <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
            </button>
            <button type="button"
                class="btn border-0 p-0 event-card-action"
                @if($isModal) id="editEventBtn" @endif
                data-event-detail-edit
                data-detail="edit-btn"
                title="Edit event"
                aria-label="Edit event">
                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
            </button>
        </div>
        @endif
    </div>

    <hr class="event-details-divider my-2">

    <div class="event-card-topic mb-2" @if($isModal) id="eventTopic" @endif data-detail="topic"></div>

    <div class="event-card-info mb-2">
        <p class="mb-1">
            <strong>Faculty:</strong>
            <span @if($isModal) id="eventfaculty" @endif data-detail="faculty"></span>
        </p>
        <p class="mb-0">
            <strong>Group Name:</strong>
            <span @if($isModal) id="eventgroupname" @endif data-detail="group"></span>
        </p>
        <p class="mb-0 mt-1 event-details-internal-row">
            <strong>Internal Faculty:</strong>
            <span @if($isModal) id="internal_faculty_name_show" @endif data-detail="internal-faculty"></span>
        </p>
    </div>

    <div class="event-card-venue mb-0">
        <span><strong>Venue:</strong> <span @if($isModal) id="eventVanue" @endif data-detail="venue"></span></span>
        <i class="bi bi-geo-alt" aria-hidden="true"></i>
    </div>
</div>
