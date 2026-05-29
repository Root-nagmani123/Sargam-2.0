@extends('admin.layouts.master')

@section('title', 'Attendance Notice List')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/view-all-notice-list-admin.css') }}?v={{ @filemtime(public_path('css/view-all-notice-list-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $topicRaw = optional($courseGroup->timetable)->subject_topic ?? '';
    $topicPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $topicRaw)));
    $topicDisplay = $topicPlain !== '' ? $topicPlain : 'N/A';
    $topicDate = !empty(optional($courseGroup->timetable)->START_DATE)
        ? \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d/m/Y')
        : 'N/A';
    $sessionTime = optional($courseGroup->timetable)->class_session ?? 'N/A';
    $facultyName = optional($courseGroup->timetable)->faculty->full_name ?? 'N/A';
    $courseName = optional($courseGroup->course)->course_name ?? 'N/A';
    $studentPaginator = $students ?? null;
    $hasStudents = $studentPaginator && $studentPaginator->count() > 0;
@endphp

<form action="{{ route('notice.direct.save') }}" method="post" class="vanl-notice-form">
    @csrf

    <div class="container-fluid vanl-master-page py-3 px-3 px-lg-4">
        <x-breadcrum title="Attendance Notice List" :showBack="true" />

        <x-session_message />

        <input type="hidden" name="subject_master_id" id="subject_master_id" value="{{ optional($courseGroup->timetable)->subject_master_pk }}">
        <input type="hidden" name="course_master_pk" id="course_master_pk" value="{{ $course_pk }}">
        <input type="hidden" name="topic_id" id="topic_id" value="{{ optional($courseGroup->timetable)->pk }}">
        <input type="hidden" name="venue_id" id="venue_id" value="{{ optional($courseGroup->timetable)->venue_id }}">
        <input type="hidden" name="class_session_master_pk" id="class_session_master_pk" value="{{ optional($courseGroup->timetable)->class_session }}">
        <input type="hidden" name="faculty_master_pk" id="faculty_master_pk" value="{{ optional($courseGroup->timetable)->faculty_master }}">

        <div class="card vanl-summary-card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 g-md-4 vanl-meta-grid mt-2">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="vanl-meta-item">
                            <span class="vanl-meta-label">Major Subject</span>
                            <span class="vanl-meta-value">{{ $courseName }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="vanl-meta-item">
                            <span class="vanl-meta-label">Topic Name</span>
                            <span class="vanl-meta-value">{{ $topicDisplay }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="vanl-meta-item">
                            <span class="vanl-meta-label">Faculty Name</span>
                            <span class="vanl-meta-value">{{ $facultyName }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="vanl-meta-item">
                            <span class="vanl-meta-label">Topic Date</span>
                            <span class="vanl-meta-value">{{ $topicDate }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="vanl-meta-item">
                            <span class="vanl-meta-label">Session Time</span>
                            <span class="vanl-meta-value">{{ $sessionTime }}</span>
                        </div>
                    </div>
                </div>

                <div class="vanl-success-banner" role="status">
                    Attendance has been Marked for the Session
                </div>
            </div>
        </div>

        <div class="card vanl-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 vanl-dt-toolbar">
                    <h2 class="vanl-section-title mb-0">Attendance</h2>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto vanl-dt-toolbar-actions">
                        <div class="dropdown vanl-search-slot">
                            <button type="button"
                                class="btn vanl-search-trigger"
                                id="vanlSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search students">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 vanl-search-menu">
                                <label for="vanlTableSearch" class="form-label small text-secondary mb-2">Search</label>
                                <input type="search"
                                    class="form-control vanl-search-input shadow-none"
                                    id="vanlTableSearch"
                                    placeholder="Search OT name or code..."
                                    autocomplete="off"
                                    aria-label="Search students in table">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary vanl-btn-send send_notice" disabled>
                            Send Notice
                        </button>
                    </div>
                </div>

                <div class="programme-dt-panel vanl-dt-panel">
                    <div class="table-responsive vanl-dt-scroll">
                        <table class="table align-middle mb-0 w-100 programme-dt-table vanl-dt-table" id="simpleAttendanceTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="vanl-col-check text-center">
                                        <input type="checkbox" id="selectAllAttendance" class="form-check-input" aria-label="Select all">
                                    </th>
                                    <th scope="col" class="vanl-col-sno text-center">S. No.</th>
                                    <th scope="col">OT Name</th>
                                    <th scope="col" class="text-nowrap">OT Code</th>
                                    <th scope="col" class="text-center text-nowrap">Attendance</th>
                                </tr>
                            </thead>
                            <tbody id="vanlTableBody">
                                @if($hasStudents)
                                    @foreach($students as $row)
                                        @php $studentId = $row->Student_master_pk; @endphp
                                        <tr class="vanl-student-row">
                                            <td class="text-center">
                                                <input type="checkbox"
                                                    class="attendance-select form-check-input"
                                                    data-student-id="{{ $studentId }}"
                                                    aria-label="Select {{ $row->display_name }} for notice"
                                                    name="selected_student_list[]"
                                                    value="{{ $studentId }}">
                                                <input type="hidden" name="attendance_pk_{{ $studentId }}" value="{{ $row->pk }}">
                                            </td>
                                            <td class="text-center vanl-col-sno">
                                                {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}
                                            </td>
                                            <td class="vanl-col-name fw-medium">{{ $row->display_name }}</td>
                                            <td class="text-nowrap text-secondary">{{ $row->generated_OT_code }}</td>
                                            <td class="text-center">
                                                @if($row->status == 2)
                                                    <span class="vanl-status-badge vanl-status-badge--late">Late</span>
                                                @elseif($row->status == 3)
                                                    <span class="vanl-status-badge vanl-status-badge--absent">Absent</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="vanl-empty-row">
                                        <td colspan="5" class="text-center vanl-dt-empty">
                                            <div class="py-4">
                                                <i class="bi bi-people d-block mb-2 fs-3 text-secondary" aria-hidden="true"></i>
                                                <p class="mb-0 fw-medium">No students found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if($hasStudents && $students->hasPages())
                        <div class="vanl-dt-footer d-flex flex-wrap align-items-center justify-content-center justify-content-md-start">
                            {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>

                <button id="sendMemoAllBtn" type="button" class="btn btn-success mt-2" style="display:none;" aria-hidden="true">
                    Send Memo to All
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkboxes = document.querySelectorAll('.attendance-select');
    var sendNoticeBtn = document.querySelector('.send_notice');
    var selectAll = document.getElementById('selectAllAttendance');
    var searchInput = document.getElementById('vanlTableSearch');
    var tableBody = document.getElementById('vanlTableBody');

    function toggleSendNoticeBtn() {
        if (!sendNoticeBtn) return;
        var anyChecked = document.querySelectorAll('.attendance-select:checked').length > 0;
        sendNoticeBtn.disabled = !anyChecked;
    }

    function syncSelectAllState() {
        if (!selectAll) return;
        var visibleBoxes = Array.prototype.filter.call(checkboxes, function (cb) {
            var row = cb.closest('tr');
            return row && row.style.display !== 'none';
        });
        if (!visibleBoxes.length) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }
        var checkedCount = visibleBoxes.filter(function (cb) { return cb.checked; }).length;
        selectAll.checked = checkedCount === visibleBoxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < visibleBoxes.length;
    }

    if (sendNoticeBtn) {
        sendNoticeBtn.disabled = true;
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            toggleSendNoticeBtn();
            syncSelectAllState();
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) {
                var row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
            toggleSendNoticeBtn();
            syncSelectAllState();
        });
    }

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            var term = (searchInput.value || '').trim().toLowerCase();
            var rows = tableBody.querySelectorAll('tr.vanl-student-row');
            rows.forEach(function (row) {
                var text = (row.textContent || '').toLowerCase();
                row.style.display = !term || text.indexOf(term) !== -1 ? '' : 'none';
            });
            syncSelectAllState();
            toggleSendNoticeBtn();
        });
    }
});
</script>
@endpush
