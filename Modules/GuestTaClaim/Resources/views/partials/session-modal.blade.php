<div class="modal fade" id="gtcSessionModal" tabindex="-1" aria-labelledby="gtcSessionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="gtcSessionModalTitle">Edit session</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="alert alert-danger py-2 gtc-modal-error d-none"></div>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label req" for="m_topic">Topic</label><input class="form-control" id="m_topic" name="m_topic" maxlength="255"></div>
                    <div class="col-sm-6"><label class="form-label req" for="m_session_date">Date</label><input type="date" class="form-control" id="m_session_date" name="m_session_date"></div>
                    <div class="col-sm-3 col-6"><label class="form-label req" for="m_start_time">Start</label><input type="time" class="form-control" id="m_start_time" name="m_start_time"></div>
                    <div class="col-sm-3 col-6"><label class="form-label req" for="m_end_time">End</label><input type="time" class="form-control" id="m_end_time" name="m_end_time"></div>
                    <div class="col-sm-6"><label class="form-label" for="m_venue">Venue</label><input class="form-control" id="m_venue" name="m_venue" maxlength="120"></div>
                    <div class="col-sm-6"><label class="form-label" for="m_status">Status</label>
                        <select class="form-select" id="m_status" name="m_status"><option value="taken">Taken</option><option value="cancelled">Cancelled</option></select></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" data-gtc-save-session>Save session</button></div>
        </div>
    </div>
</div>
