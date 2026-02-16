@extends('admin.layouts.master')

@section('title', 'Discipline mark record - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
<style>
    .dual-listbox .dual-listbox__button {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
</style>
<div class="container-fluid">
    <x-breadcrum title="Discipline mark record" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Discipline mark record</h4>
            <hr>
            <form action="{{ route('memo.discipline.discipline_generate_memo_store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="course_master_pk" class="form-label">Course</label>
                            <select name="course_master_pk" class="form-control" id="courseSelectTogetStudent" required>
                                <option value="">Select Course</option>
                                @foreach ($activeCourses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}</option>
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
                            <input type="date" class="form-control" id="date_of_memo" name="date_of_memo" required
                                value="{{ old('date_of_memo') }}">
                            @error('date_of_memo')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Discipline</label>
                            <select name="discipline_master_pk" class="form-control" id="discipline_pk" required>
                                <option value="">Select Discipline</option>
                               
                            </select>
                            @error('discipline_master_pk')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Discipline marks</label>
                            <input type="number" class="form-control" name="discipline_marks" id="discipline_marks"
                                required>
                            @error('discipline_marks')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>




                    <div class="col-12">
                        <label for="selected_student_list" class="form-label">Select Students</label>
                        <select id="select_memo_student" class="select1 form-control" name="selected_student_list[]"
                            multiple>

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


                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-end gap-3">
                            <button type="submit" class="btn btn-outline-danger" name="submission_type" value="1">Save</button>
                            <a href="{{ route('memo.discipline.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- end Vertical Steps Example -->
</div>

@include('components.jquery-3-6')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('date_memo_notice');
    dateInput.value = today; // Set today's date
    dateInput.max = today; // Prevent future dates
});

$('#courseSelectTogetStudent').on('change', function() {
    var courseId = $(this).val();

    if (courseId) {
        $('#select_memo_student').empty().append('<option>Select...</option>');

         $('#discipline_pk').empty().append('<option>Select...</option>');
        $.ajax({
            url: "{{ route('memo.discipline.getStudentByCourse') }}",

            type: "GET",
            data: {
                course_id: courseId
            },
            success: function(response) {
                if (response.status) {
                    const currentSelected = $('#select_memo_student').val() || [];
                    $('#select_memo_student').empty();

                    // Append new options (even if empty array)
                    if (response.students && response.students.length > 0) {
                        response.students.forEach(student => {
                            const isSelected = currentSelected.includes(student.pk
                            .toString());
                            $('#select_memo_student').append(
                                $('<option>', {
                                    value: student.pk,
                                    text: student.display_name + ' (' + student
                                        .generated_OT_code + ')',
                                    selected: isSelected
                                })
                            );
                        });
                    } else {
                        // Show message if no defaulters found, but don't prevent UI update
                        console.log('No defaulter students found for this topic.');
                    }
                     if (response.discipline_master_data && response.discipline_master_data.length > 0) {
                        response.discipline_master_data.forEach(discipline => {
                            const isSelected = currentSelected.includes(discipline.pk
                            .toString());
                            $('#discipline_pk').append(
                                $('<option>', {
                                    value: discipline.pk,
                                    text: discipline.discipline_name,
                                    selected: isSelected
                                })
                            );
                        });
                    } else {
                        // Show message if no defaulters found, but don't prevent UI update
                        console.log('No defaulter students found for this topic.');
                    }

                    // Destroy the old dual listbox wrapper
                    if (typeof dualListbox !== 'undefined' && dualListbox) {
                        try {
                            dualListbox.destroy();
                        } catch (e) {
                            console.log('Error destroying dual listbox:', e);
                        }
                    }
                    $('.dual-listbox').remove();

                    // Reinitialize the DualListbox
                    dualListbox = new DualListbox("#select_memo_student", {
                        addEvent: function(value) {},
                        removeEvent: function(value) {},
                        availableTitle: "Defaulter Students",
                        selectedTitle: "Selected Students",
                        addButtonText: "Move Right",
                        removeButtonText: "Move Left",
                        addAllButtonText: "Move All Right",
                        removeAllButtonText: "Move All Left",
                        draggable: true
                    });

                } else {
                    alert(response.message || 'Error fetching student list.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error fetching defaulter students. Please check the console for details.');
            }
        });
    } else {
        $('#subject_master_id').html('<option value="">Select Subject</option>');
    }
});
$('#discipline_pk').on('change', function() {
    var discipline_pk = $(this).val();

    if (discipline_pk) {
        $.ajax({
            url: "{{ route('memo.discipline.getMarkDeduction') }}",
            type: "GET",
            data: { discipline_master_pk: discipline_pk },
            dataType: 'json',
            success: function(response) {
                if (response && response.success && response.mark_deduction != null) {
                    $('#discipline_marks').val(response.mark_deduction);
                } else {
                    $('#discipline_marks').val('');
                }
            },
            error: function() {
                $('#discipline_marks').val('');
            }
        });
    } else {
        $('#discipline_marks').val('');
    }
});
</script>

@endsection