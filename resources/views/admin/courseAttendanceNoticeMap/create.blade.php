@extends('admin.layouts.master')

@section('title', 'Create Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
<div class="container-fluid">
    <x-breadcrum title="Create Memo/Notice Management" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Memo/Notice Management</h4>
            <hr>
            <form action="{{ route('memo.notice.management.store_memo_notice') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="course_master_pk" class="form-label">Course</label>
                            <select name="course_master_pk" class="form-control" id="courseSelect" required>
                                <option value="">Select Course</option>
                                @foreach ($activeCourses as $course)
                                    <option value="{{ $course->pk }}" >{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            @error('course_master_pk')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="date_memo_notice" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date_memo_notice" name="date_memo_notice" required>
                            @error('date_memo_notice')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Subject <span style="color:#af2910;">*</span></label>
                            <select name="subject_master_id" class="form-control" id="subject_master_id">
                                <option value="">Select Subject</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                            </div>
                            @error('subject_master_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Topic</label>
                            <select name="topic_id" class="form-control" id="topic_id">
                                <option value="">Select Topic</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                            @error('topic_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Venue <span style="color:#af2910;">*</span></label>
                            <input type="text" id="venue_name" class="form-control" readonly>
                        <input type="hidden" id="venue_id" name="venue_id">
                        @error('venue_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Session</label>
                          <input type="text" id="session_name" class="form-control" readonly>
                        <input type="hidden" id="class_session_master_pk" name="class_session_master_pk">
                        @error('session_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Faculty Name</label>
                           <input type="text" id="faculty_name" class="form-control" readonly>
                        <input type="hidden" id="faculty_master_pk" name="faculty_master_pk">
                        @error('faculty_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label for="selected_student_list" class="form-label">Select Students</label>
                        <select id="select_memo_student" class="select1 form-control" name="selected_student_list[]" multiple>

                        </select>
                        @error('selected_student_list')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label for="textarea" class="form-label">Message (If Any) </label>
                        <textarea class="form-control" id="textarea" rows="3" placeholder="Enter remarks..."
                            name="Remark"></textarea>
                        @error('Remark')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>


                </div>

                <hr>

                <div class="row">
                    <div class="col-10">
                        <div class="text-center gap-3">
                            <button type="submit" class="btn btn-danger" name="submission_type" value="1">Notice</button>
                          
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-end gap-3">
                            <a href="{{ route('memo.notice.management.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
    <div id="template-preview-wrapper" style="display:none;">
    <div class="bg-white p-4 rounded shadow-sm">
        <h5 class="text-center fw-bold mb-3" id="tpl_course_name"></h5>
        <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
        <hr>
        <p class="mb-1" id="tpl_type_label"></p>
        <p><strong>Date:</strong> <span id="tpl_date"></span></p>
        <div id="tpl_content" class="mb-3"></div>
        <p class="text-end">
            <strong id="tpl_director_name"></strong><br>
            <span id="tpl_director_designation"></span>
        </p>
    </div>
    </div>
    <div id="template-not-found" class="alert alert-warning mt-3" style="display:none;">
        No active template found for the selected course and type.
    </div>
    <!-- end Vertical Steps Example -->
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
        // Reset template preview when course changes
        $('#template-preview-wrapper, #template-not-found').hide();
        // Auto-load Notice template for selected course
        if (courseId) {
            loadTemplatePreview(courseId, 'Notice');
        }
    });

    function loadTemplatePreview(courseId, type) {
        if (!courseId || !type) return;
        $.ajax({
            url: "{{ route('memo.notice.management.getTemplateByCourse') }}",
            type: "GET",
            data: { course_id: courseId, type: type },
            success: function(tpl) {
                if (tpl) {
                    var courseName = $('#courseSelect option:selected').text();
                    var today = new Date().toLocaleDateString('en-GB');
                    $('#tpl_course_name').text(courseName);
                    $('#tpl_type_label').text('SHOW CAUSE ' + type.toUpperCase());
                    $('#tpl_date').text(today);
                    $('#tpl_content').html(tpl.content);
                    $('#tpl_director_name').text(tpl.director_name || '');
                    $('#tpl_director_designation').text(tpl.director_designation || '');
                    $('#template-not-found').hide();
                    $('#template-preview-wrapper').show();
                } else {
                    $('#template-preview-wrapper').hide();
                    $('#template-not-found').show();
                }
            },
            error: function() {
                $('#template-preview-wrapper').hide();
                $('#template-not-found').show();
            }
        });
    }

    // Show template preview when Notice button clicked (in case course was pre-selected)
    $('button[name="submission_type"]').on('click', function(e) {
        var courseId = $('#courseSelect').val();
        var type = ($(this).val() == '1') ? 'Notice' : 'Memo';
        if (!courseId) {
            e.preventDefault();
            alert('Please select a course first.');
            return;
        }
        loadTemplatePreview(courseId, type);
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
        // Fetch timetable details (venue, session, faculty)
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

        // Fetch defaulter students for this topic
        $.ajax({
            url: "{{ route('memo.notice.management.getStudentAttendanceBytopic') }}",
            type: "POST",
            data: {
                topic_id: topic_id,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                var select = $('#select_memo_student');
                select.empty();
                if (response.status && response.students && response.students.length > 0) {
                    $.each(response.students, function(i, student) {
                        select.append('<option value="' + student.pk + '">' + student.display_name + '</option>');
                    });
                    // Auto-select all students
                    select.find('option').prop('selected', true);
                } else {
                    select.append('<option value="" disabled>No defaulter students found</option>');
                }
            },
            error: function() {
                $('#select_memo_student').empty().append('<option value="" disabled>Error loading students</option>');
            }
        });
    } else {
        $('#venue_name, #session_name, #faculty_name').val('');
        $('#select_memo_student').empty();
    }
    });

</script>

@endsection
 