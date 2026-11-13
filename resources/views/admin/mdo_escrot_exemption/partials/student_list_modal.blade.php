<div class="modal fade mst-modal mee-modal mee-student-list-modal" id="meeStudentListModal" tabindex="-1"
    aria-labelledby="meeStudentListModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="meeStudentListModalLabel">Student List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 mee-student-selected-bar">
                    <span class="text-secondary fw-medium small text-nowrap" id="meeStudentSelectedCount" aria-live="polite">0 Selected</span>
                    <span class="mee-student-selected-divider" aria-hidden="true"></span>
                    <div class="d-flex flex-wrap gap-2 flex-grow-1 mee-student-tags" id="meeStudentTags"></div>
                </div>

                <div class="mee-student-search mb-3">
                    <label for="meeStudentListSearch" class="visually-hidden">Search students</label>
                    <i class="bi bi-search mee-student-search-icon" aria-hidden="true"></i>
                    <input type="search"
                        id="meeStudentListSearch"
                        class="form-control mst-control mee-student-search-input"
                        placeholder="Search by name or OT code"
                        autocomplete="off">
                </div>

                <div class="mee-student-list-wrap" id="meeStudentListWrap">
                    <div class="text-center text-muted small py-5" id="meeStudentListEmpty">
                        Select course and start date to load students.
                    </div>
                    <ul class="list-group list-group-flush mb-0 d-none" id="meeStudentList"></ul>
                </div>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-danger rounded-1 px-4 fw-semibold" id="meeStudentClearAll">
                    Clear All
                </button>
                <button type="button" class="btn btn-outline-primary rounded-1 px-4 fw-semibold" id="meeStudentSelectAll">
                    Select All
                </button>
                <button type="button" class="btn mst-btn-submit px-4" id="meeStudentSave">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
