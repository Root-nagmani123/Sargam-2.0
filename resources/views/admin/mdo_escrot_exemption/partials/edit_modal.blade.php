<div class="modal fade mst-modal mee-modal mee-add-modal" id="meeEditModal" tabindex="-1"
    aria-labelledby="meeEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content mee-form-modal border-0 shadow">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.update') }}"
                id="meeEditForm"
                novalidate>
                @csrf
                <input type="hidden" name="pk" id="meeEditRecordPk" value="">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="meeEditModalLabel">Edit MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="meeEditFormAlert" class="alert d-none mb-3" role="alert"></div>

                    {{-- Read-only context: Student + Course (filled from edit-data/{id}) --}}
                    <div class="mst-field-card mb-3" role="status">
                        <div class="mst-facts">
                            <div>
                                <span class="mst-fact__label">Student</span>
                                <span class="mst-fact__value fw-semibold" id="meeEditStudentDisplay">—</span>
                            </div>
                            <div>
                                <span class="mst-fact__label">Course</span>
                                <span class="mst-fact__value fw-semibold" id="meeEditCourseDisplay">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="mst-field-card">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="meeEditDutyType" class="mst-form-label d-block">
                                    Duty Type <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="mdo_duty_type_master_pk" id="meeEditDutyType"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Duty Type" required aria-required="true"
                                    aria-describedby="meeEditErrorDutyType">
                                    <option value="">Select Duty Type</option>
                                    @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeEditErrorDutyType">Duty type is required.</small>
                            </div>

                            {{-- Multi-select; Select2 is initialised by the page script
                                 (initMeeEditModal) with this modal as dropdownParent. --}}
                            <div class="col-md-6 d-none" id="meeEditFacultyContainer">
                                <label for="meeEditFaculty" class="mst-form-label d-block">
                                    Faculty <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="faculty_master_pk[]" id="meeEditFaculty"
                                    class="form-select mst-control mee-faculty-select2" multiple
                                    aria-describedby="meeEditErrorFaculty">
                                    @foreach($facultyMaster ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeEditErrorFaculty">Faculty is required for Escort duty.</small>
                            </div>

                            <div class="col-12">
                                <label for="meeEditDate" class="mst-form-label d-block">
                                    Start Date <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="date" name="mdo_date" id="meeEditDate" class="form-control mst-control"
                                    required aria-required="true" aria-describedby="meeEditErrorDate">
                                <small class="text-danger mee-field-error d-none" id="meeEditErrorDate">Start date is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="meeEditTimeFrom" class="mst-form-label d-block">
                                    Start Time <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="time" name="Time_from" id="meeEditTimeFrom" class="form-control mst-control"
                                    required aria-required="true" aria-describedby="meeEditErrorTimeFrom">
                                <small class="text-danger mee-field-error d-none" id="meeEditErrorTimeFrom">Start time is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="meeEditTimeTo" class="mst-form-label d-block">
                                    End Time <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="time" name="Time_to" id="meeEditTimeTo" class="form-control mst-control"
                                    required aria-required="true" aria-describedby="meeEditErrorTimeTo">
                                <small class="text-danger mee-field-error d-none" id="meeEditErrorTimeTo">End time is required.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="meeEditSubmitBtn">
                        Update MDO/ Escort Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
