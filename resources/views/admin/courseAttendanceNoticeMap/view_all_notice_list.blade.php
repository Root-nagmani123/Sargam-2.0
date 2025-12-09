@extends('admin.layouts.master')

@section('title', 'Attendance Notice List - Sargam | LBSNAA')
@section('setup_content')
    <form action="{{ route('notice.direct.save') }}" method="post">
        @csrf
        <div class="container-fluid">

            <x-breadcrum title="Attendance Notice List" />
            <x-session_message />

            <input type="hidden" name="subject_master_id" id="subject_master_id" value="{{ $courseGroup->timetable->subject_master_pk }}">
            <input type="hidden" name="course_master_pk" id="course_master_pk" value="{{ $course_pk }}">
            <input type="hidden" name="topic_id" id="topic_id" value="{{ optional($courseGroup->timetable)->pk }}">
            <input type="hidden" name="venue_id" id="venue_id" value="{{ optional($courseGroup->timetable)->venue_id }}">
            <input type="hidden" name="class_session_master_pk" id="class_session_master_pk" value="{{ optional($courseGroup->timetable)->class_session }}">
            <input type="hidden" name="faculty_master_pk" id="faculty_master_pk" value="{{ optional($courseGroup->timetable)->faculty_master }}">

        {{-- Session Summary --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="mb-3">Through this page you can manage Attendance of Officer Trainees</h5>
                <hr>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <strong>Major Subject:</strong>
                            <span class="text-primary">
                                {{ optional($courseGroup->course)->course_name }}
                            </span>
                        </div>

                        <div class="col-md-3">
                            <strong>Topic Name:</strong>
                            <span class="text-primary">
                                {{ optional($courseGroup->timetable)->subject_topic }}
                            </span>
                        </div>

                        <div class="col-md-3">
                            <strong>Faculty Name:</strong>
                            <span class="text-primary">{{ optional($courseGroup->timetable)->faculty->full_name ?? '' }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Topic Date:</strong>
                            <span class="text-primary">
                                {{ optional($courseGroup->timetable)->START_DATE ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Session Time:</strong>
                            <span class="text-primary">
                                {{ optional($courseGroup->timetable)->class_session ?? 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="alert customize-alert rounded-pill alert-success bg-success text-white mt-4 mb-0 border-0 fade show text-center fw-bold">
                        Attendance has been Marked for the Session
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Attendance</h4>
                        <div class="">
                            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Back</a>
                            
                        </div>
                        <div class="clearfix">
                            <button type="submit" class="btn btn-primary float-end send_notice">Send Notice</button>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="simpleAttendanceTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">
                                        <input type="checkbox" id="selectAllAttendance" class="form-check-input" aria-label="Select all">
                                    </th>
                                    <th class="text-center">#</th>
                                    <th class="text-center">OT Name</th>
                                    <th class="text-center">OT Code</th>
                                    <th class="text-center">Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @if(isset($students) && count($students))
                                    @foreach($students as $row)
                                        @php $studentId = $row->Student_master_pk; @endphp
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="attendance-select form-check-input" data-student-id="{{ $studentId }}" aria-label="Select student for memo" name="selected_student_list[]" value="{{ $studentId }}">
                                                <input type="hidden" name="attendance_pk_{{ $studentId }}" value="{{ $row->pk }}">
                                            </td>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center"><label class="text-dark">{{ $row->display_name }}</label></td>
                                            <td class="text-center"><label class="text-dark">{{ $row->generated_OT_code }}</label></td>
                                            <td class="text-center">
                                                <div class="d-inline-flex gap-3">
                                                    <div class="form-check form-check-inline">
                                                        @if($row->status == 2)
                                                        <span class="text-warning">Late</span>
                                                        @elseif($row->status == 3)
                                                        <span class="text-danger">Absent</span>
                                                        @endif
                                                    </div>
                                                
                                                </div>
                                            </td>
                                           
                                           
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No students found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <button id="sendMemoAllBtn" type="button" class="btn btn-success mt-2" style="display:none;">Send Memo to All</button>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function(){

    const checkboxes = document.querySelectorAll('.attendance-select');
    const sendNoticeBtn = document.querySelector('.send_notice');

    function toggleSendNoticeBtn() {
        const anyChecked = document.querySelectorAll('.attendance-select:checked').length > 0;

        if (anyChecked) {
            sendNoticeBtn.disabled = false;
        } else {
            sendNoticeBtn.disabled = true;
        }
    }

    // Page load par disabled rakho
    sendNoticeBtn.disabled = true;

    // Individual checkbox
    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleSendNoticeBtn);
    });

    // Select All checkbox
    const selectAll = document.getElementById('selectAllAttendance');
    if (selectAll) {
        selectAll.addEventListener('change', function(){
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleSendNoticeBtn();
        });
    }

});

</script>

@endsection