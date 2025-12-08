@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('setup_content')
    <form action="{{ route('attendance.save') }}" method="post">
        @csrf
        <div class="container-fluid">

            <x-breadcrum title="Mark Attendance Of Officer Trainees" />
            <x-session_message />

            <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
            <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
            <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $courseGroup->timetable_pk }}">

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
                            @if($currentPath === 'mark')
                            <button type="submit" class="btn btn-primary ">save</button>
                            
                            @endif
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
                                    <th class="text-center">MDO Duty</th>
                                    <th class="text-center">Escort Duty</th>
                                    <th class="text-center">Medical Exemption</th>
                                    <th class="text-center">Other Exemption</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @if(isset($students) && count($students))
                                    @foreach($students as $row)
                                        @php $studentId = $row->studentsMaster->pk; @endphp
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="attendance-select form-check-input" data-student-id="{{ $studentId }}" aria-label="Select student for memo">
                                            </td>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center"><label class="text-dark">{{ $row->studentsMaster->display_name }}</label></td>
                                            <td class="text-center"><label class="text-dark">{{ $row->studentsMaster->generated_OT_code }}</label></td>
                                            <td class="text-center">
                                                <div class="d-inline-flex gap-3">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="1" id="student_{{ $studentId }}_1">
                                                        <label class="form-check-label text-success" for="student_{{ $studentId }}_1">Present</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="2" id="student_{{ $studentId }}_2">
                                                        <label class="form-check-label text-warning" for="student_{{ $studentId }}_2">Late</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="3" id="student_{{ $studentId }}_3">
                                                        <label class="form-check-label text-danger" for="student_{{ $studentId }}_3">Absent</label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="4" id="student_{{ $studentId }}_4">
                                                    <label class="form-check-label text-dark" for="student_{{ $studentId }}_4">MDO</label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="5" id="student_{{ $studentId }}_5">
                                                    <label class="form-check-label text-dark" for="student_{{ $studentId }}_5">Escort</label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="6" id="student_{{ $studentId }}_6">
                                                    <label class="form-check-label text-dark" for="student_{{ $studentId }}_6">Medical Exempted</label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="student[{{ $studentId }}]" value="7" id="student_{{ $studentId }}_7">
                                                    <label class="form-check-label text-dark" for="student_{{ $studentId }}_7">Other Exempted</label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-primary send-memo-btn" data-student-id="{{ $studentId }}">Send Memo</button>
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
    const selectAll = document.getElementById('selectAllAttendance');
    const memoAllBtn = document.getElementById('sendMemoAllBtn');
    const table = document.getElementById('simpleAttendanceTable');

    function updateMemoAllVisibility(){
        const anyChecked = table.querySelectorAll('.attendance-select:checked').length > 0;
        memoAllBtn.style.display = anyChecked ? 'inline-block' : 'none';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function(){
            const checked = this.checked;
            table.querySelectorAll('.attendance-select').forEach(cb => { cb.checked = checked; });
            updateMemoAllVisibility();
        });
    }

    table.addEventListener('change', function(e){
        if (e.target.classList.contains('attendance-select')){
            updateMemoAllVisibility();
        }
    });

    // Row Send Memo
    table.addEventListener('click', function(e){
        if (e.target.classList.contains('send-memo-btn')){
            const id = e.target.getAttribute('data-student-id');
            document.dispatchEvent(new CustomEvent('sendMemoSingle', { detail: { studentId: id } }));
        }
    });

    // Global Send Memo to All
    memoAllBtn.addEventListener('click', function(){
        const ids = Array.from(table.querySelectorAll('.attendance-select:checked')).map(cb => cb.getAttribute('data-student-id'));
        document.dispatchEvent(new CustomEvent('sendMemoBulk', { detail: { studentIds: ids } }));
    });
});
</script>
@endsection