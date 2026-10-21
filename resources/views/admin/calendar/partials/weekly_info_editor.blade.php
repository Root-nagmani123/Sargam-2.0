{{-- Weekly Info-Sheet editor: Director / Joint Director / Participants Profile / Mention of the Week --}}
<div class="modal fade" id="weeklyInfoModal" tabindex="-1" aria-labelledby="weeklyInfoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="weeklyInfoModalTitle">
                    <i class="bi bi-people me-2"></i>Info-Sheet Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="weeklyInfoForm">
                <div class="modal-body">
                    <p class="small text-secondary mb-3" id="weeklyInfoContext"></p>

                    <div id="weeklyInfoAlert" class="alert d-none" role="alert"></div>

                    <input type="hidden" id="wi_course_id" name="course_id">
                    <input type="hidden" id="wi_week_start" name="week_start">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="wi_director" class="form-label small fw-semibold">Director</label>
                            <input type="text" class="form-control" id="wi_director" name="director_name" maxlength="255" placeholder="Director name (course-level)">
                        </div>
                        <div class="col-md-6">
                            <label for="wi_joint_director" class="form-label small fw-semibold">Joint Director</label>
                            <input type="text" class="form-control" id="wi_joint_director" name="joint_director_name" maxlength="255" placeholder="Joint Director name (course-level)">
                        </div>
                        <div class="col-12">
                            <label for="wi_participants_profile" class="form-label small fw-semibold">Participants Profile</label>
                            <textarea class="form-control" id="wi_participants_profile" name="participants_profile" rows="2" placeholder="e.g. All India Services — IAS Officers (Batch 2012–2014)"></textarea>
                            <div class="form-text">Course-level — shown on every week's info sheet.</div>
                        </div>
                        <div class="col-12">
                            <label for="wi_mention_of_week" class="form-label small fw-semibold">Mention of the Week</label>
                            <textarea class="form-control" id="wi_mention_of_week" name="mention_of_week" rows="4" placeholder="Editorial note for this specific week…"></textarea>
                            <div class="form-text">Specific to the week currently in view.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="wiSaveBtn">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
