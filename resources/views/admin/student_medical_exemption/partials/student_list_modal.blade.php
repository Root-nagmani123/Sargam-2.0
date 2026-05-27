<div class="modal fade sme-student-list-modal" id="smeStudentListModal" tabindex="-1"
    aria-labelledby="smeStudentListModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="smeStudentListModalLabel">Student List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <hr class="mt-0 mb-3 opacity-50">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 sme-student-selected-bar">
                    <span class="text-secondary fw-medium small text-nowrap" id="smeStudentSelectedCount">0 Selected</span>
                    <span class="sme-student-selected-divider" aria-hidden="true"></span>
                    <div class="d-flex flex-wrap gap-2 flex-grow-1 sme-student-tags" id="smeStudentTags"></div>
                </div>

                <div class="position-relative mb-3">
                    <label for="smeStudentListSearch" class="visually-hidden">Search students</label>
                    <i class="bi bi-search sme-student-search-icon" aria-hidden="true"></i>
                    <input type="search"
                        id="smeStudentListSearch"
                        class="form-control rounded-pill sme-student-search-input shadow-none"
                        placeholder="Search"
                        autocomplete="off">
                </div>

                <div class="sme-student-list-wrap border rounded-3" id="smeStudentListWrap">
                    <div class="text-center text-muted small py-5" id="smeStudentListEmpty">
                        Select a course first to load students.
                    </div>
                    <ul class="list-group list-group-flush mb-0 d-none" id="smeStudentList"></ul>
                </div>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                <button type="button" class="btn btn-outline-danger rounded-3 px-4 fw-semibold" id="smeStudentClearAll">
                    Clear All
                </button>
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 fw-semibold" id="smeStudentSelectAll">
                    Select All
                </button>
                <button type="button" class="btn btn-primary rounded-3 px-4 fw-semibold" id="smeStudentSave">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
