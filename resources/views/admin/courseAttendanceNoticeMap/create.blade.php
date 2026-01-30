@extends('admin.layouts.master')

@section('title', 'Create Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
<div class="container-fluid py-3">
    <x-breadcrum title="Create Memo/Notice Management" />
    <x-session_message />

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-0 border-bottom">
            <h4 class="card-title mb-0 fw-semibold text-body">Create Memo/Notice Management</h4>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('memo.notice.management.store_memo_notice') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="course_master_pk" class="form-label fw-medium">Course</label>
                            <select name="course_master_pk" class="form-select" id="courseSelect" required>
                                <option value="">Select Course</option>
                                @foreach ($activeCourses as $course)
                                    <option value="{{ $course->pk }}" >{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            @error('course_master_pk')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="date_memo_notice" class="form-label fw-medium">Date</label>
                            <input type="date" class="form-control" id="date_memo_notice" name="date_memo_notice" required>
                            @error('date_memo_notice')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="subject_master_id" class="form-label fw-medium">Subject <span class="text-danger">*</span></label>
                            <select name="subject_master_id" class="form-select" id="subject_master_id">
                                <option value="">Select Subject</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                            @error('subject_master_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="topic_id" class="form-label fw-medium">Topic</label>
                            <select name="topic_id" class="form-select" id="topic_id">
                                <option value="">Select Topic</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                            @error('topic_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="venue_name" class="form-label fw-medium">Venue <span class="text-danger">*</span></label>
                            <input type="text" id="venue_name" class="form-control bg-light" readonly placeholder="—">
                            <input type="hidden" id="venue_id" name="venue_id">
                            @error('venue_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="session_name" class="form-label fw-medium">Session</label>
                            <input type="text" id="session_name" class="form-control bg-light" readonly placeholder="—">
                            <input type="hidden" id="class_session_master_pk" name="class_session_master_pk">
                            @error('session_name')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-0">
                            <label for="faculty_name" class="form-label fw-medium">Faculty Name</label>
                            <input type="text" id="faculty_name" class="form-control bg-light" readonly placeholder="—">
                            <input type="hidden" id="faculty_master_pk" name="faculty_master_pk">
                            @error('faculty_name')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="select_memo_student" class="form-label fw-medium">Select Students</label>
                        <select id="select_memo_student" class="select1 form-control" name="selected_student_list[]" multiple>

                        </select>
                        @error('selected_student_list')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label for="textarea" class="form-label fw-medium">Message (If Any)</label>
                        <textarea class="form-control" id="textarea" rows="3" placeholder="Enter remarks..." name="Remark"></textarea>
                        @error('Remark')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <div class="row align-items-center g-2">
                    <div class="col-12 col-md">
                        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                            <button type="submit" class="btn btn-danger px-4" name="submission_type" value="1">Submit Notice</button>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <div class="d-flex justify-content-center justify-content-md-end">
                            <a href="{{ route('memo.notice.management.index') }}" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mt-4">
        <div class="card-body p-4 p-md-5">
            <h5 class="text-center fw-semibold mb-2">88th Foundation Course</h5>
            <p class="text-center text-body-secondary mb-0 small">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
            <hr class="my-4">

            <p class="mb-1 fw-semibold text-uppercase small">Show Cause Notice</p>
            <p class="mb-3"><strong>Date:</strong> 22/11/2013</p>

            <p class="text-body-secondary mb-4">It has been brought to the notice of the undersigned that you were absent without prior authorization from following session(s)...</p>

            <div class="table-responsive rounded-2 border">
                <table class="table table-hover table-bordered mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Date</th>
                            <th class="fw-semibold">No. of Session(s)</th>
                            <th class="fw-semibold">Topics</th>
                            <th class="fw-semibold">Venue</th>
                            <th class="fw-semibold">Session(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>22-11-2013</td>
                            <td>1</td>
                            <td>Lorem ipsum dolor sit amet.</td>
                            <td>Lorem, ipsum.</td>
                            <td>06:00-07:00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <p class="fw-semibold mb-2">You are advised to do the following:</p>
                <ul class="list-unstyled mb-3">
                    <li class="mb-1">• Reply to this Memo online through this <a href="#" class="text-decoration-none">conversation</a></li>
                    <li class="mb-1">• Appear <a href="#" class="text-decoration-none">in person before the undersigned at 1800 hrs on next working day</a></li>
                </ul>
                <p class="text-body-secondary small">In absence of online explanation and your personal appearance, unilateral decision may be taken.</p>
            </div>

            <p class="mb-0 mt-4"><strong>ALBY VARGHESE, A42</strong><br>
                <span class="text-body-secondary small">Remarks: Show Cause Notice for 22.11.13</span></p>

            <p class="text-end mb-0 mt-3"><strong>Rajesh Arya</strong><br><span class="text-body-secondary small">Deputy Director Sr. & I/C Discipline 88th F.C.</span></p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('date_memo_notice');
    dateInput.value = today;         // Set today's date
    dateInput.max = today;           // Prevent future dates
});

    $('#courseSelect').on('change', function() {
        var courseId = $(this).val();

        if (courseId) {
            $.ajax({
             url: "{{ route('memo.notice.management.getSubjectByCourse') }}",

                type: "GET",
                data: { course_id: courseId },
                success: function (response) {
                $('#subject_master_id').html(response);
            },
            error: function () {
                $('#subject_master_id').html('<option>Error loading subjects</option>');
            }
            });
        } else {
             $('#subject_master_id').html('<option value="">Select Subject</option>');
        }
    });
   $('#subject_master_id').on('change', function() {
    var subject_master_id = $(this).val();
    var courseId = $('#courseSelect').val(); // Fix: use selector, not variable

    if (subject_master_id && courseId) {
        $.ajax({
            url: "{{ route('memo.notice.management.getTopicBysubject') }}",
            type: "GET",
            data: {
                subject_master_id: subject_master_id,
                course_id: courseId
            },
            success: function (response) {
                $('#topic_id').html(response);
            },
            error: function () {
                $('#topic_id').html('<option>Error loading topics</option>');
            }
        });
    } else {
        $('#topic_id').html('<option value="">Select Topic</option>');
    }
    });
     $('#topic_id').on('change', function() {
    var topic_id = $(this).val();
   
    if (topic_id) {
        $.ajax({
            url: "{{ route('memo.notice.management.gettimetableDetailsBytopic') }}",
            type: "GET",
            data: {
                topic_id: topic_id,
            },
            success: function (response) {
                console.log(response);
                if (response) {
                    $('#venue_name').val(response.venue_name);
                    $('#session_name').val(response.shift_name);
                    $('#faculty_name').val(response.faculty_name);
                     $('#faculty_master_pk').val(response.faculty_master);
                        $('#venue_id').val(response.venue_id);
                        $('#class_session_master_pk').val(response.shift_name);
                } else {
                    $('#venue_name, #session_name, #faculty_name ,#faculty_master_pk,#venue_id,#class_session_master_pk').val('');
                }
            },
            error: function () {
                alert('Error fetching timetable details.');
            }
        });
    } else {
        $('#venue_name, #session_name, #faculty_name').val('');
    }
    });

</script>

@endsection
 