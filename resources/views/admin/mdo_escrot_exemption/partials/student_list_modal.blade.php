<div class="modal fade mee-student-list-modal" id="meeStudentListModal" tabindex="-1"
    aria-labelledby="meeStudentListModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="meeStudentListModalLabel">Student List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <hr class="mt-0 mb-3 opacity-50">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 mee-student-selected-bar">
                    <span class="text-secondary fw-medium small text-nowrap" id="meeStudentSelectedCount">0 Selected</span>
                    <span class="mee-student-selected-divider" aria-hidden="true"></span>
                    <div class="d-flex flex-wrap gap-2 flex-grow-1 mee-student-tags" id="meeStudentTags"></div>
                </div>

                <div class="position-relative mb-3">
                    <label for="meeStudentListSearch" class="visually-hidden">Search students</label>
                    <i class="bi bi-search mee-student-search-icon" aria-hidden="true"></i>
                    <input type="search"
                        id="meeStudentListSearch"
                        class="form-control rounded-pill mee-student-search-input shadow-none"
                        placeholder="Search"
                        autocomplete="off">
                </div>

                <div class="mee-student-list-wrap border rounded-3" id="meeStudentListWrap">
                    <div class="text-center text-muted small py-5" id="meeStudentListEmpty">
                        Select course and start date to load students.
                    </div>
                    <ul class="list-group list-group-flush mb-0 d-none" id="meeStudentList"></ul>
                </div>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                <button type="button" class="btn btn-outline-danger rounded-3 px-4 fw-semibold" id="meeStudentClearAll">
                    Clear All
                </button>
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 fw-semibold" id="meeStudentSelectAll">
                    Select All
                </button>
                <button type="button" class="btn btn-primary rounded-3 px-4 fw-semibold" id="meeStudentSave">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
