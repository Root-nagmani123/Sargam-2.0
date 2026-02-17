@extends('admin.layouts.master')

@section('title', 'Discipline mark record - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
<div class="container-fluid py-3">
    <x-breadcrum title="Discipline mark record" />
    <x-session_message />

    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <h4 class="card-title mb-0 fw-bold text-body d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                    <i class="material-icons material-symbols-rounded fs-6 align-middle">edit_note</i>
                </span>
                Discipline mark record
            </h4>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('memo.discipline.discipline_generate_memo_store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="course_master_pk" class="form-label fw-semibold small text-body-secondary">
                            <i class="material-icons material-symbols-rounded fs-6 align-middle me-1">school</i>
                            Course
                        </label>
                        <select name="course_master_pk" class="form-select" id="courseSelectTogetStudent" required aria-label="Select course">
                            <option value="">Select Course</option>
                            @foreach ($activeCourses as $course)
                            <option value="{{ $course->pk }}" {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="date_of_memo" class="form-label fw-semibold small text-body-secondary">
                            <i class="material-icons material-symbols-rounded fs-6 align-middle me-1">event</i>
                            Date
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="material-icons material-symbols-rounded text-muted fs-6">calendar_today</i>
                            </span>
                            <input type="date" class="form-control border-start-0" id="date_of_memo" name="date_of_memo" required
                                value="{{ old('date_of_memo') }}" aria-label="Memo date">
                        </div>
                        @error('date_of_memo')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="discipline_pk" class="form-label fw-semibold small text-body-secondary">
                            <i class="material-icons material-symbols-rounded fs-6 align-middle me-1">category</i>
                            Discipline
                        </label>
                        <select name="discipline_master_pk" class="form-select" id="discipline_pk" required aria-label="Select discipline">
                            <option value="">Select Discipline</option>
                        </select>
                        @error('discipline_master_pk')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="discipline_marks" class="form-label fw-semibold small text-body-secondary">
                            <i class="material-icons material-symbols-rounded fs-6 align-middle me-1">score</i>
                            Discipline marks
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discipline_marks" id="discipline_marks" required
                                placeholder="0" min="0" aria-label="Discipline marks">
                            <span class="input-group-text bg-light">Marks</span>
                        </div>
                        @error('discipline_marks')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="select_memo_student" class="form-label fw-semibold small text-body-secondary">
                            <i class="material-icons material-symbols-rounded fs-6 align-middle me-1">group</i>
                            Select Students
                        </label>
                        <select id="select_memo_student" class="form-control" name="selected_student_list[]" multiple
                            aria-label="Select defaulter students">
                        </select>
                        <div class="form-text">Choose course first to load defaulter students. Use arrows to move between lists.</div>
                        @error('selected_student_list')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="textarea" class="form-label fw-semibold small text-body-secondary">
                            <i class="material-icons material-symbols-rounded fs-6 align-middle me-1">comment</i>
                            Message (If Any)
                        </label>
                        <textarea class="form-control" id="textarea" rows="4" placeholder="Enter remarks or additional notes..."
                            name="Remark" aria-label="Additional remarks"></textarea>
                        @error('Remark')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
                    <a href="{{ route('memo.discipline.index') }}" class="btn btn-outline-secondary">
                        <i class="material-icons material-symbols-rounded align-middle me-1 fs-6">arrow_back</i>
                        Back
                    </a>
                    <button type="submit" class="btn btn-danger" name="submission_type" value="1">
                        <i class="material-icons material-symbols-rounded align-middle me-1 fs-6">save</i>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('date_of_memo');
    if (dateInput && !dateInput.value) {
        dateInput.value = today;
        dateInput.max = today;
    }
});

$('#courseSelectTogetStudent').on('change', function() {
    var courseId = $(this).val();

    if (courseId) {
        $('#select_memo_student').empty().append('<option>Select...</option>');

         $('#discipline_pk').empty().append('<option value="">Select Discipline</option>');
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
                            $('#discipline_pk').append(
                                $('<option>', {
                                    value: discipline.pk,
                                    text: discipline.discipline_name
                                })
                            );
                        });
                    } else {
                        console.log('No discipline options found for this course.');
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
                        addButtonText: "Add →",
                        removeButtonText: "← Remove",
                        addAllButtonText: "Add All ⇒",
                        removeAllButtonText: "⇐ Remove All",
                        searchPlaceholder: "Search students...",
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
        $('#discipline_pk').empty().append('<option value="">Select Discipline</option>');
        $('#select_memo_student').empty();
        $('#discipline_marks').val('');
        if (typeof dualListbox !== 'undefined' && dualListbox) {
            try { dualListbox.destroy(); } catch (e) { console.log('Error destroying dual listbox:', e); }
        }
        $('.dual-listbox').remove();
        dualListbox = new DualListbox("#select_memo_student", {
            addEvent: function(value) {},
            removeEvent: function(value) {},
            availableTitle: "Defaulter Students",
            selectedTitle: "Selected Students",
            addButtonText: "Add →",
            removeButtonText: "← Remove",
            addAllButtonText: "Add All ⇒",
            removeAllButtonText: "⇐ Remove All",
            searchPlaceholder: "Search students...",
            draggable: true
        });
    }
});
$('#discipline_pk').on('change', function() {
    var discipline_pk = $(this).val();
    var courseId = $('#courseSelectTogetStudent').val(); // Fix: use selector, not variable

    if (discipline_pk && courseId) {
        $.ajax({
            url: "{{ route('memo.discipline.getMarkDeduction') }}",
            type: "GET",
            data: {
                discipline_master_pk: discipline_pk,
                course_id: courseId
            },
            success: function(response) {
                $('#discipline_marks').val(response);
            },
            error: function() {
                $('#discipline_marks').val('Error loading topics');
            }
        });
    } else {
        $('#discipline_marks').val('');

    }
});
</script>

@endsection