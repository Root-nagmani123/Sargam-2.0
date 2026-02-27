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
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="mt-2">
                        <p class="mb-0 small fw-medium text-white">
                            <i class="material-icons me-1" aria-hidden="true">date_range</i><i class="bi bi-calendar me-1" aria-hidden="true"></i>
                            <span id="eventDate"></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="event-details">
                    <h4 class="h6 mb-3" id="eventTopic"></h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-person-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Faculty:</strong>
                                <span id="eventfaculty" class="ms-1"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d`etail-item">
                                <i class="bi bi-person-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Session:</strong>
                                <span id="eventclasssession" class="ms-1"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d`etail-item">
                                <i class="bi bi-person-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Group name:</strong>
                                <span id="eventgroupname" class="ms-1"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <i class="bi bi-geo-alt-fill text-primary me-2" aria-hidden="true"></i>
                                <strong>Venue:</strong>
                                <span id="eventVanue" class="ms-1"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            @if(hasRole('Training') || hasRole('Admin'))
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editEventBtn">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="deleteEventBtn">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>