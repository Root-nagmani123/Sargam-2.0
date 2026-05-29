{{-- Per-row Mark Attendance modal (design reference) — syncs to table radios on apply, no separate submit. --}}
<div class="modal fade mark-att-row-modal"
    id="markAttendanceRowModal"
    tabindex="-1"
    aria-labelledby="markAttendanceRowModalLabel"
    aria-hidden="true"
    data-mark-att-locked="{{ !empty($allMarked) && $allMarked ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered mark-att-row-modal-dialog">
        <div class="modal-content mark-att-row-modal-content border-0 shadow">
            <div class="modal-header mark-att-row-modal-header px-4 py-3">
                <h5 class="modal-title fw-bold mb-0 text-dark" id="markAttendanceRowModalLabel">Mark Attendance</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body mark-att-row-modal-body px-4 pt-4 pb-2">
                <p class="visually-hidden" id="markAttModalStudentContext" aria-live="polite"></p>

                <div class="mark-att-modal-field mb-4">
                    <label class="form-label mark-att-modal-label fw-medium mb-3">
                        Attendance <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <div class="d-flex flex-wrap align-items-center gap-3 gap-md-4"
                        id="markAttModalAttendanceGroup"
                        role="radiogroup"
                        aria-label="Attendance status">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="mark_att_modal_status" id="markAttModalPresent" value="1" checked>
                            <label class="form-check-label" for="markAttModalPresent">Present</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="mark_att_modal_status" id="markAttModalLate" value="2">
                            <label class="form-check-label" for="markAttModalLate">Late</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="mark_att_modal_status" id="markAttModalAbsent" value="3">
                            <label class="form-check-label" for="markAttModalAbsent">Absent</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mark-att-modal-switch-row mb-4">
                    <span class="mark-att-modal-row-label">MDO Duty</span>
                    <div class="form-check form-switch mb-0 mark-att-modal-switch mark-att-modal-switch--mdo">
                        <input class="form-check-input" type="checkbox" role="switch" id="markAttModalMdo" aria-label="MDO Duty">
                    </div>
                </div>

                <div class="mark-att-modal-exemptions">
                    <p class="fw-semibold mb-0 mark-att-modal-section-title">Exemptions</p>
                    <hr class="mark-att-modal-divider my-3">

                    <div class="d-flex align-items-center justify-content-between mark-att-modal-switch-row mb-3">
                        <span class="mark-att-modal-row-label">Medical Exemptions</span>
                        <div class="form-check form-switch mb-0 mark-att-modal-switch mark-att-modal-switch--medical">
                            <input class="form-check-input" type="checkbox" role="switch" id="markAttModalMedical" aria-label="Medical Exemptions">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mark-att-modal-switch-row">
                        <span class="mark-att-modal-row-label">Other Exemptions</span>
                        <div class="form-check form-switch mb-0 mark-att-modal-switch mark-att-modal-switch--other">
                            <input class="form-check-input" type="checkbox" role="switch" id="markAttModalOther" aria-label="Other Exemptions">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer mark-att-row-modal-footer border-0 px-4 pb-4 pt-2 gap-2 justify-content-end">
                <button type="button"
                    class="btn mark-att-modal-btn-cancel px-4 py-2 rounded-2 fw-semibold"
                    data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button"
                    class="btn mark-att-modal-btn-apply px-4 py-2 rounded-2 fw-semibold"
                    id="markAttModalApplyBtn">
                    Mark Attendance
                </button>
            </div>
        </div>
    </div>
</div>
