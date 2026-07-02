{{-- Notice List — loaded via AJAX into #noticeListModalBody on the Send Direct Notice page. --}}
@php
    $count = isset($students) ? count($students) : 0;
    $subjectMasterId = optional($courseGroup->timetable)->subject_master_pk;
    $topicId         = optional($courseGroup->timetable)->pk;
    $venueId         = optional($courseGroup->timetable)->venue_id;
    $classSession    = optional($courseGroup->timetable)->class_session;

    $resolvedFacultyIds     = get_timetable_faculty_ids(optional($courseGroup)->timetable);
    $resolvedFacultyPayload = optional($courseGroup->timetable)->faculty_master;
    if (empty($resolvedFacultyPayload) && !empty($resolvedFacultyIds)) {
        $resolvedFacultyPayload = json_encode($resolvedFacultyIds);
    }
@endphp

<form action="{{ route('notice.direct.save') }}" method="POST" id="noticeListForm">
    @csrf
    <input type="hidden" name="subject_master_id" value="{{ $subjectMasterId }}">
    <input type="hidden" name="course_master_pk" value="{{ $course_pk }}">
    <input type="hidden" name="topic_id" value="{{ $topicId }}">
    <input type="hidden" name="venue_id" value="{{ $venueId }}">
    <input type="hidden" name="class_session_master_pk" value="{{ $classSession }}">
    <input type="hidden" name="faculty_master_pk" value="{{ $resolvedFacultyPayload }}">

    @if(isset($noticeTemplates) && $noticeTemplates->count())
        <div class="px-3 pt-3">
            <label for="noticeTemplateSelect" class="form-label mb-1 fw-semibold">Notice Template</label>
            <select name="memo_notice_template_pk" id="noticeTemplateSelect" class="form-select form-select-sm">
                @foreach($noticeTemplates as $tpl)
                    <option value="{{ $tpl->pk }}">{{ $tpl->title }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="nlm-toolbar">
        <div class="nlm-search">
            <i class="bi bi-search"></i>
            <input type="text" id="noticeSearch" placeholder="Search" autocomplete="off">
        </div>
        <button type="submit" class="nlm-send-btn" id="noticeSendAllBtn" disabled>Send Notice to All</button>
    </div>

    <div class="table-responsive nlm-table-wrap">
        <table class="table align-middle mb-0 nlm-table">
            <thead>
                <tr>
                    <th class="nlm-check-col"><input type="checkbox" id="noticeSelectAll" class="form-check-input" aria-label="Select all"></th>
                    <th>S. No.</th>
                    <th>OT Name</th>
                    <th>OT Code</th>
                    <th>Attendance</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $row)
                    @php $studentId = $row->Student_master_pk; @endphp
                    <tr class="notice-row" data-search="{{ strtolower(trim(($row->display_name ?? '') . ' ' . ($row->generated_OT_code ?? ''))) }}">
                        <td class="nlm-check-col">
                            <input type="checkbox" class="form-check-input notice-row-check" name="selected_student_list[]" value="{{ $studentId }}" aria-label="Select OT">
                            <input type="hidden" name="attendance_pk_{{ $studentId }}" value="{{ $row->pk }}">
                        </td>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-medium">{{ $row->display_name ?? 'N/A' }}</td>
                        <td>{{ $row->generated_OT_code ?? 'N/A' }}</td>
                        <td>
                            @if($row->status == 1)
                                <span class="nlm-badge nlm-badge--present">Present</span>
                            @elseif($row->status == 2)
                                <span class="nlm-badge nlm-badge--late">Late</span>
                            @elseif($row->status == 3)
                                <span class="nlm-badge nlm-badge--absent">Absent</span>
                            @else
                                <span class="text-muted">Not Marked</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="#" class="nlm-row-notice js-row-notice" data-student="{{ $studentId }}" data-attendance="{{ $row->pk }}" title="Send Notice">
                                <i class="material-icons material-icons-rounded">send</i><span>Notice</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="material-icons material-icons-rounded">inbox</i>
                            No students found for this session.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="nlm-footer">
        <span class="text-muted small">Showing {{ $count }} item{{ $count == 1 ? '' : 's' }}</span>
    </div>
</form>
