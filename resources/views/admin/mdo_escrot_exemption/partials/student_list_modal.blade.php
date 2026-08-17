<div class="modal fade mee-modal mee-student-list-modal" id="meeStudentListModal" tabindex="-1"
    aria-labelledby="meeStudentListModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg overflow-hidden">
            <div class="mee-modal-header">
                <h5 class="mee-modal-title" id="meeStudentListModalLabel">Student List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="mee-modal-body">

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
                        class="mee-control mee-student-search-input"
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
            {{-- Three actions, so the equal/right footer grid runs three columns
                 wide; Save keeps the submit weight. --}}
            <div class="mee-modal-footer">
                <button type="button" class="btn mee-btn-cancel" id="meeStudentClearAll">
                    Clear All
                </button>
                <button type="button" class="btn mee-btn-ghost" id="meeStudentSelectAll">
                    Select All
                </button>
                <button type="button" class="btn mee-btn-submit" id="meeStudentSave">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
