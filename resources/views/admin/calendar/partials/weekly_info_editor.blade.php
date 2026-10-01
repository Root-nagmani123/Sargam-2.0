{{--
    Weekly info-sheet editor.

    Holds everything the printed weekly timetable needs that no master table
    models: the footer under the grid, and the P.T.O. page (counsellor labels and
    rooms, session moderators, language venues, the outdoor block, the signatory).

    Counsellor and speaker rows are rendered from the API response rather than
    hardcoded - the form follows whoever is actually on the course and teaching
    that week. Built on the design-system layer (docs/design.md): .ds-form-section
    groups and --ds-* tokens only.
--}}
@push('styles')
<style>
    #weeklyInfoModal .modal-content {
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius-card);
        box-shadow: var(--ds-shadow-lg);
        overflow: hidden;
    }
    #weeklyInfoModal .modal-header {
        padding: var(--ds-space-3);
        background: var(--ds-surface-2);
        border-bottom: 1px solid var(--ds-line);
        align-items: flex-start;
    }
    #weeklyInfoModal .wi-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--ds-ink);
        margin: 0;
    }
    #weeklyInfoModal .wi-context {
        font-size: 0.875rem;
        color: var(--ds-ink-muted);
        margin: var(--ds-space-1) 0 0;
    }
    #weeklyInfoModal .modal-body {
        padding: var(--ds-space-3);
        background: var(--ds-canvas);
    }
    #weeklyInfoModal .nav-tabs {
        gap: var(--ds-space-1);
        border-bottom: 1px solid var(--ds-line);
        margin-bottom: var(--ds-space-3);
    }
    #weeklyInfoModal .nav-tabs .nav-link {
        border-radius: var(--ds-radius) var(--ds-radius) 0 0;
        color: var(--ds-ink-muted);
        font-weight: 500;
    }
    #weeklyInfoModal .nav-tabs .nav-link.active {
        color: var(--ds-primary);
        background: var(--ds-surface);
        border-color: var(--ds-line) var(--ds-line) var(--ds-surface);
    }
    #weeklyInfoModal .ds-form-section {
        padding: var(--ds-space-3);
        margin-bottom: var(--ds-space-3);
    }
    #weeklyInfoModal .ds-form-section:last-child { margin-bottom: 0; }
    #weeklyInfoModal .ds-form-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--ds-space-2);
    }
    #weeklyInfoModal .wi-section-hint {
        font-size: 0.8125rem;
        color: var(--ds-ink-muted);
        margin: calc(-1 * var(--ds-space-2)) 0 var(--ds-space-3);
    }
    #weeklyInfoModal .wi-table {
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius);
        overflow: hidden;
        margin: 0;
    }
    #weeklyInfoModal .wi-table thead th {
        background: var(--ds-surface-2);
        color: var(--ds-ink-muted);
        font-size: 0.8125rem;
        font-weight: 600;
        border-bottom: 1px solid var(--ds-line);
    }
    #weeklyInfoModal .wi-table td { vertical-align: middle; }
    #weeklyInfoModal .vstack .input-group .form-control,
    #weeklyInfoModal .vstack .input-group .input-group-text,
    #weeklyInfoModal .vstack .input-group .btn { border-radius: var(--ds-radius); }
    #weeklyInfoModal .modal-footer {
        padding: var(--ds-space-2) var(--ds-space-3);
        background: var(--ds-surface);
        border-top: 1px solid var(--ds-line);
        gap: var(--ds-space-2);
    }
    #weeklyInfoModal .btn { border-radius: var(--ds-radius); }
</style>
@endpush

<div class="modal fade" id="weeklyInfoModal" tabindex="-1" aria-labelledby="weeklyInfoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="wi-title" id="weeklyInfoModalTitle">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>Info-Sheet Details
                    </h5>
                    <p class="wi-context" id="weeklyInfoContext"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="weeklyInfoForm">
                <div class="modal-body">
                    <div id="weeklyInfoAlert" class="alert d-none" role="alert"></div>

                    <input type="hidden" id="wi_course_id" name="course_id">
                    <input type="hidden" id="wi_week_start" name="week_start">

                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#wiTabCourse" type="button" role="tab">
                                <i class="bi bi-mortarboard me-1"></i>Course
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wiTabFooter" type="button" role="tab">
                                <i class="bi bi-layout-text-window-reverse me-1"></i>Timetable Footer
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wiTabSheet" type="button" role="tab">
                                <i class="bi bi-file-earmark-richtext me-1"></i>Info Sheet (P.T.O.)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- ---------- Course-level ---------- --}}
                        <div class="tab-pane fade show active" id="wiTabCourse" role="tabpanel">
                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">Course details</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="wi_sheet_title" class="form-label small fw-semibold">Title on printed timetable</label>
                                        <input type="text" class="form-control" id="wi_sheet_title" maxlength="255" placeholder="e.g. IAS Professional Course, Phase - II (2024 Batch)">
                                        <div class="form-text">Printed in the timetable header in place of the course name; leave blank to print the course name.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="wi_director" class="form-label small fw-semibold">Director</label>
                                        <input type="text" class="form-control" id="wi_director" name="director_name" maxlength="255" placeholder="Director name">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="wi_joint_director" class="form-label small fw-semibold">Joint Director</label>
                                        <input type="text" class="form-control" id="wi_joint_director" name="joint_director_name" maxlength="255" placeholder="Joint Director name">
                                    </div>
                                    <div class="col-12">
                                        <label for="wi_participants_profile" class="form-label small fw-semibold">Participants Profile</label>
                                        <textarea class="form-control" id="wi_participants_profile" name="participants_profile" rows="2" placeholder="e.g. All India Services — IAS Officers (Batch 2012–2014)"></textarea>
                                        <div class="form-text">Course-level — shown for every week.</div>
                                    </div>
                                </div>
                            </section>
                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">This week</h6>
                                <label for="wi_mention_of_week" class="form-label small fw-semibold">Mention of the Week</label>
                                <textarea class="form-control" id="wi_mention_of_week" name="mention_of_week" rows="3" placeholder="Editorial note for this specific week…"></textarea>
                            </section>
                        </div>

                        {{-- ---------- Printed under the grid ---------- --}}
                        <div class="tab-pane fade" id="wiTabFooter" role="tabpanel">
                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">Venues line</h6>
                                <input type="text" class="form-control" id="wi_venue_line" maxlength="1000" placeholder="e.g. Full Group: VH, Group-A: VH, Group B: TH">
                                <div class="form-text">Prints as <strong>VENUES:</strong> directly under the grid. Leave blank to derive it from the sessions.</div>
                            </section>
                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">
                                    <span>Notes</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="wiAddNote">
                                        <i class="bi bi-plus-lg"></i> Add note
                                    </button>
                                </h6>
                                <div id="wiNotesList" class="vstack gap-2"></div>
                                <div class="form-text">Printed numbered under the venues line. Blank notes are dropped.</div>
                            </section>
                        </div>

                        {{-- ---------- The P.T.O. page ---------- --}}
                        <div class="tab-pane fade" id="wiTabSheet" role="tabpanel">
                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">Outdoor and Other Activities</h6>
                                <textarea class="form-control" id="wi_outdoor" rows="3" maxlength="2000" placeholder="Time: Outdoors- Morning 06:30 - 07:30 (Monday to Friday)&#10;Venue: Happy Valley Sports Complex, at T-5"></textarea>
                                <div class="form-text">Line breaks are preserved. When filled in, the morning Physical Activity rows are left off the printed grid.</div>
                            </section>

                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">Cadre Counsellors</h6>
                                <p class="wi-section-hint">Counsellors and their cadres come from the Counsellor Groups. Set the printed order, label and room, and reword the cadres if the sheet prints them differently.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm wi-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 7%;" title="Printed order (seniority)">#</th>
                                                <th style="width: 18%;">Counsellor</th>
                                                <th style="width: 34%;">Cadres (as printed)</th>
                                                <th style="width: 18%;">Label</th>
                                                <th style="width: 28%;">Venue</th>
                                            </tr>
                                        </thead>
                                        <tbody id="wiCounsellorRows"></tbody>
                                    </table>
                                </div>
                            </section>

                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">Session Moderators</h6>
                                <p class="wi-section-hint">Speakers teaching this week, guests first. Once any moderator is entered, the Guest Speakers box lists only the speakers with one.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm wi-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 45%;">Speaker</th>
                                                <th>Session Moderator</th>
                                            </tr>
                                        </thead>
                                        <tbody id="wiGuestRows"></tbody>
                                    </table>
                                </div>
                            </section>

                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <section class="ds-form-section h-100">
                                        <h6 class="ds-form-section-title">
                                            <span>Venue for Language Classes</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="wiAddLanguage">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </h6>
                                        <div id="wiLanguageList" class="vstack gap-2"></div>
                                    </section>
                                </div>
                                <div class="col-lg-6">
                                    <section class="ds-form-section h-100">
                                        <h6 class="ds-form-section-title">
                                            <span>Venues Abbreviation</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="wiAddVenue">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </h6>
                                        <div id="wiVenueList" class="vstack gap-2"></div>
                                        <div class="form-text">Leave empty to list only the venues this week's sessions use.</div>
                                    </section>
                                </div>
                            </div>

                            <section class="ds-form-section mt-3">
                                <h6 class="ds-form-section-title">Faculty Abbreviation order</h6>
                                <input type="text" class="form-control" id="wi_faculty_legend_order" maxlength="500">
                                <div class="form-text">Codes in the order the legend prints them, comma-separated (e.g. BR, SW, VL). Codes left out follow alphabetically.</div>
                            </section>

                            <section class="ds-form-section">
                                <h6 class="ds-form-section-title">Signatory</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="wi_signatory_name" class="form-label small fw-semibold">Name</label>
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
                            </section>
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
