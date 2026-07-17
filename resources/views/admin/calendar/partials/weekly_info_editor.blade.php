{{--
    Weekly info-sheet editor.

    Holds everything the printed weekly timetable needs that no master table
    models: the footer under the grid, and the P.T.O. page (counsellor labels and
    rooms, session moderators, language venues, the outdoor block, the signatory).

    Counsellor and guest rows are rendered from the API response rather than
    hardcoded — the form follows whoever is actually on the course and teaching
    that week.
--}}
<div class="modal fade" id="weeklyInfoModal" tabindex="-1" aria-labelledby="weeklyInfoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
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

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#wiTabCourse" type="button" role="tab">Course</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wiTabFooter" type="button" role="tab">Timetable Footer</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wiTabSheet" type="button" role="tab">Info Sheet (P.T.O.)</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- ---------- Course-level ---------- --}}
                        <div class="tab-pane fade show active" id="wiTabCourse" role="tabpanel">
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
                                    <textarea class="form-control" id="wi_mention_of_week" name="mention_of_week" rows="3" placeholder="Editorial note for this specific week…"></textarea>
                                    <div class="form-text">Specific to the week currently in view.</div>
                                </div>
                            </div>
                        </div>

                        {{-- ---------- Printed under the grid ---------- --}}
                        <div class="tab-pane fade" id="wiTabFooter" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="wi_venue_line" class="form-label small fw-semibold">Venues line</label>
                                    <input type="text" class="form-control" id="wi_venue_line" maxlength="1000" placeholder="e.g. Full Group: VH, Group-A: VH, Group B: H">
                                    <div class="form-text">Prints as <strong>VENUES:</strong> directly under the grid.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                                        <span>Notes</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="wiAddNote">
                                            <i class="bi bi-plus-lg"></i> Add note
                                        </button>
                                    </label>
                                    <div id="wiNotesList" class="vstack gap-2"></div>
                                    <div class="form-text">Printed numbered under the venues line. Blank notes are dropped.</div>
                                </div>
                            </div>
                        </div>

                        {{-- ---------- The P.T.O. page ---------- --}}
                        <div class="tab-pane fade" id="wiTabSheet" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="wi_outdoor" class="form-label small fw-semibold">Outdoor and Other Activities</label>
                                    <textarea class="form-control" id="wi_outdoor" rows="3" maxlength="2000" placeholder="Time: Outdoors- Morning 06:30 - 07:30 (Monday to Friday)&#10;Venue: Happy Valley Sports Complex, at T-5"></textarea>
                                    <div class="form-text">Line breaks are preserved.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">Cadre Counsellors</label>
                                    <div class="form-text mt-0 mb-2">Cadres come from the Counsellor Groups. Set the printed label and room here.</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 28%;">Counsellor</th>
                                                    <th style="width: 28%;">Cadre</th>
                                                    <th style="width: 20%;">Label</th>
                                                    <th style="width: 24%;">Venue</th>
                                                </tr>
                                            </thead>
                                            <tbody id="wiCounsellorRows"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">Session Moderators</label>
                                    <div class="form-text mt-0 mb-2">Guest speakers teaching this week.</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 45%;">Guest Speaker</th>
                                                    <th>Session Moderator</th>
                                                </tr>
                                            </thead>
                                            <tbody id="wiGuestRows"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                                        <span>Venue for Language Classes</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="wiAddLanguage">
                                            <i class="bi bi-plus-lg"></i> Add language
                                        </button>
                                    </label>
                                    <div id="wiLanguageList" class="vstack gap-2"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="wi_signatory_name" class="form-label small fw-semibold">Signatory</label>
                                    <input type="text" class="form-control" id="wi_signatory_name" maxlength="255" placeholder="e.g. Kranthi Kumar Pati">
                                </div>
                                <div class="col-md-5">
                                    <label for="wi_signatory_designation" class="form-label small fw-semibold">Designation</label>
                                    <input type="text" class="form-control" id="wi_signatory_designation" maxlength="255" placeholder="e.g. Deputy Director &amp; Course Coordinator">
                                </div>
                                <div class="col-md-3">
                                    <label for="wi_signatory_date" class="form-label small fw-semibold">Date</label>
                                    <input type="date" class="form-control" id="wi_signatory_date">
                                </div>
                            </div>
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
