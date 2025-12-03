@extends('admin.layouts.master')
@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<style>
.dual-list-container {
    display: flex;
    gap: 20px;
    align-items: stretch;
    margin-top: 15px;
}

.student-panel {
    flex: 1;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    display: flex;
    flex-direction: column;
}

.panel-header {
    background: #f8f9fa;
    padding: 12px 15px;
    border-bottom: 2px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    color: #495057;
}

.search-box {
    padding: 10px 15px;
    border-bottom: 1px solid #e9ecef;
}

.search-box input {
    width: 100%;
    padding: 6px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.select-all-box {
    padding: 8px 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 8px;
}

.table-header {
    display: grid;
    grid-template-columns: 40px 1fr 120px 50px;
    padding: 10px 15px;
    background: #e9ecef;
    font-weight: 600;
    font-size: 13px;
    color: #495057;
    border-bottom: 1px solid #dee2e6;
}

.student-list {
    flex: 1;
    overflow-y: auto;
    max-height: 400px;
    min-height: 300px;
}

.student-row {
    display: grid;
    grid-template-columns: 40px 1fr 120px 50px;
    padding: 10px 15px;
    border-bottom: 1px solid #f1f3f5;
    align-items: center;
    transition: background 0.2s;
}

.student-row:hover {
    background: #f8f9fa;
}

.student-row input[type="checkbox"] {
    cursor: pointer;
}

.arrow-btn {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.arrow-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.arrow-btn.add:hover {
    background: #d1e7dd;
    color: #0f5132;
}

.arrow-btn.remove:hover {
    background: #f8d7da;
    color: #842029;
}

.transfer-btns {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    padding: 0 10px;
}

.transfer-btns button {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 18px;
}

.transfer-btns button:hover:not(:disabled) {
    background: #e9ecef;
    border-color: #adb5bd;
}

.transfer-btns button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.no-students {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
    font-style: italic;
}

@media (max-width: 768px) {
    .dual-list-container {
        flex-direction: column;
    }

    .transfer-btns {
        flex-direction: row;
        justify-content: center;
        padding: 15px 0;
    }
}
/* Main card section */
.table-section {
    border-left: 4px solid #004a93 !important;
}

/* Header */
.enhanced-header span {
    font-size: 0.85rem;
}

/* Student list styling */
.modern-student-list {
    max-height: 300px;
    overflow-y: auto;
    border-radius: 6px;
}

/* Student row */
.modern-student-list .student-row {
    display: grid;
    grid-template-columns: 40px 1fr 1fr 40px;
    align-items: center;
    padding: 10px 12px;
    border-bottom: 1px solid #e6e6e6;
    transition: background 0.2s ease;
}

.modern-student-list .student-row:hover {
    background: #f2f7ff;
}

.student-row input[type="checkbox"] {
    transform: scale(1.1);
    cursor: pointer;
}

/* No data */
.no-students {
    font-size: 0.9rem;
    color: #6c757d;
}

/* Scrollbar (GIGW accessible visible scrollbar) */
.modern-student-list::-webkit-scrollbar {
    width: 8px;
}

.modern-student-list::-webkit-scrollbar-thumb {
    background-color: #b5c7e3;
    border-radius: 10px;
}

.modern-student-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

</style>
@endsection
@section('title', 'MDO/Escort Exemption')

@section('content')

<div class="container-fluid">
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($mdoDutyType) ? 'Edit MDO/Escort Exemption' : 'Create MDO/Escort Exemption' }}
            </h4>
            <hr>
            <form action="{{ route('mdo-escrot-exemption.store') }}" method="POST" id="mdoDutyTypeForm">
                @csrf
                @if(!empty($mdoDutyType))
                <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
                @endif
                <div class="row">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-select name="course_master_pk" label="Course Name :" formLabelClass="form-label"
                                formSelectClass="select2 course-selected" :options="$courseMaster" labelRequired="true"
                                value="{{ old('course_master_pk', $mdoDutyType->course_master_pk ?? '') }}" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">

                            <x-select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk" label="Duty Type :" formLabelClass="form-label"
                                formSelectClass="select2 "
                                value="{{ old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '') }}"
                                :options="$MDODutyTypeMaster" labelRequired="true" />
                        </div>

                    </div>
                    <div class="col-md-6" id="faculty_field_container" style="display: none;">
                        <div class="mb-3">
                            <x-select name="faculty_master_pk" id="faculty_master_pk" label="Faculty :" formLabelClass="form-label"
                                formSelectClass="select2"
                                value="{{ old('faculty_master_pk', '') }}"
                                :options="$facultyMaster" labelRequired="true" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input type="date" name="mdo_date" label="Select Date & Time :"
                                placeholder="Select Date & Time : " formLabelClass="form-label"
                                value="{{ old('mdo_date', $mdoDutyType->mdo_date ?? '') }}" labelRequired="true" />
                        </div>
                    </div>

                    <div class="col-md-3">

                        <x-input type="time" name="Time_from" label="From Time :" placeholder="From Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}" />

                    </div>
                    <div class="col-md-3">

                        <x-input type="time" name="Time_to" label="To Time :" placeholder="To Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}" />

                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Select Students <span class="text-danger">*</span></label>

                        <div class="dual-list-container">
                            <!-- Available Students Panel -->
                            <div class="student-panel">
                                <div class="panel-header">Available Students</div>
                                <div class="search-box">
                                    <input type="text" id="searchAvailable"
                                        placeholder="🔍 Search by name or OT code...">
                                </div>

                                <div class="table-section border rounded-3 p-3 bg-white shadow-sm">

                                    <div class="table-header enhanced-header d-flex align-items-center px-2 py-2 mb-2"
                                        style="background:#af2910; border-bottom:2px solid #af2910; color:#ffffff;">

                                        <div class="select-all-box me-3">
                                            <input type="checkbox" id="selectAllAvailable" class="form-check-input"
                                                aria-label="Select all students">
                                        </div>

                                        <span class="flex-fill fw-semibold text-white small">Username</span>
                                        <span class="flex-fill fw-semibold text-white small">OT Code</span>
                                        <span class="small fw-semibold text-white"></span>
                                    </div>

                                    <div class="student-list modern-student-list" id="availableList">
                                        <div class="no-students text-center py-4 text-muted fst-italic small">
                                            Please select a course and date
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <!-- Transfer Buttons -->
                            <div class="transfer-btns">
                                <button type="button" id="moveRight" title="Add selected students"><i
                                        class="material-icons material-symbols-rounded">keyboard_double_arrow_right</i></button>
                                <button type="button" id="moveLeft" title="Remove selected students"><i
                                        class="material-icons material-symbols-rounded">keyboard_double_arrow_left</i></button>
                            </div>

                            <!-- Selected Students Panel -->
                            <div class="student-panel">
                                <div class="panel-header">Selected Students</div>
                                <div class="search-box">
                                    <input type="text" id="searchSelected"
                                        placeholder="🔍 Search by name or OT code...">
                                </div>
                                <div class="select-all-box">
                                    <input type="checkbox" id="selectAllSelected" class="form-check-input">
                                    <label for="selectAllSelected" class="mb-0">Select All</label>
                                </div>
                                <div class="table-header">
                                    <span></span>
                                    <span>Username</span>
                                    <span>OT Code</span>
                                    <span></span>
                                </div>
                                <div class="student-list" id="selectedList">
                                    <div class="no-students">No students selected</div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden select for form submission -->
                        <select name="selected_student_list[]" id="hiddenStudentSelect" multiple
                            style="display:none;"></select>

                        @error('selected_student_list')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-12 mt-4">
                        <label for="textarea" class="form-label">Remarks (If Any) </label>
                        <textarea class="form-control" id="textarea" rows="3" placeholder="Enter remarks..."
                            name="Remark"></textarea>
                        @error('Remark')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <hr>
                {{-- <div class="my-3 gap-2 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit" id="saveFacultyForm">
                            Save
                        </button>
                        <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn btn-secondary ">
                Back
                </a>
        </div> --}}

        <div class="mb-3">
            <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                <i class="material-icons menu-icon">save</i>
                Save
            </button>
            <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                <i class="material-icons menu-icon">arrow_back</i>
                Back
            </a>
        </div>

        </form>
    </div>
</div>
<!-- end Vertical Steps Example -->
</div>
@endsection


@section('scripts')
<script>
const availableList = document.getElementById('availableList');
const selectedList = document.getElementById('selectedList');
const hiddenSelect = document.getElementById('hiddenStudentSelect');
const moveRightBtn = document.getElementById('moveRight');
const moveLeftBtn = document.getElementById('moveLeft');

// Move selected rows from available to selected
moveRightBtn.addEventListener('click', () => {
    const checkedBoxes = availableList.querySelectorAll('input[type="checkbox"]:checked');
    checkedBoxes.forEach(cb => {
        const row = cb.closest('.student-row');
        if (row) {
            cb.checked = false;
            selectedList.appendChild(row);
            // Update arrow button
            const arrowBtn = row.querySelector('.arrow-btn');
            arrowBtn.innerHTML =
                '<i class="material-icons material-symbols-rounded">keyboard_double_arrow_down</i>';
            arrowBtn.classList.remove('add');
            arrowBtn.classList.add('remove');
            arrowBtn.title = 'Remove from selection';
        }
    });
    updateHiddenSelect();
    updateNoStudentsMessage();
});

// Move selected rows from selected to available
moveLeftBtn.addEventListener('click', () => {
    const checkedBoxes = selectedList.querySelectorAll('input[type="checkbox"]:checked');
    checkedBoxes.forEach(cb => {
        const row = cb.closest('.student-row');
        if (row) {
            cb.checked = false;
            availableList.appendChild(row);
            // Update arrow button
            const arrowBtn = row.querySelector('.arrow-btn');
            arrowBtn.innerHTML =
                ' <i class="material-icons material-symbols-rounded"> chevron_right</i>';
            arrowBtn.classList.remove('remove');
            arrowBtn.classList.add('add');
            arrowBtn.title = 'Add to selection';
        }
    });
    updateHiddenSelect();
    updateNoStudentsMessage();
});

// Per-row arrow click handler
document.addEventListener('click', (e) => {
    const arrowBtn = e.target.closest('.arrow-btn');
    if (!arrowBtn) return;

    const row = arrowBtn.closest('.student-row');
    if (!row) return;

    if (arrowBtn.classList.contains('add')) {
        // Move to selected
        selectedList.appendChild(row);
        arrowBtn.innerHTML =
            '<i class="material-icons material-symbols-rounded">keyboard_double_arrow_down</i>';
        arrowBtn.classList.remove('add');
        arrowBtn.classList.add('remove');
        arrowBtn.title = 'Remove from selection';
    } else if (arrowBtn.classList.contains('remove')) {
        // Move to available
        availableList.appendChild(row);
        arrowBtn.innerHTML = ' <i class="material-icons material-symbols-rounded"> chevron_right</i>';
        arrowBtn.classList.remove('remove');
        arrowBtn.classList.add('add');
        arrowBtn.title = 'Add to selection';
    }
    updateHiddenSelect();
    updateNoStudentsMessage();
});

// Select all functionality
document.getElementById('selectAllAvailable').addEventListener('change', function() {
    availableList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.checked = this.checked;
    });
});

document.getElementById('selectAllSelected').addEventListener('change', function() {
    selectedList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Search functionality
document.getElementById('searchAvailable').addEventListener('input', function() {
    filterStudents(availableList, this.value);
});

document.getElementById('searchSelected').addEventListener('input', function() {
    filterStudents(selectedList, this.value);
});

function filterStudents(list, searchTerm) {
    const term = searchTerm.toLowerCase();
    list.querySelectorAll('.student-row').forEach(row => {
        const name = row.dataset.name?.toLowerCase() || '';
        const otCode = row.dataset.ot?.toLowerCase() || '';
        row.style.display = (name.includes(term) || otCode.includes(term)) ? 'grid' : 'none';
    });
}

function updateHiddenSelect() {
    hiddenSelect.innerHTML = '';
    selectedList.querySelectorAll('.student-row').forEach(row => {
        const option = document.createElement('option');
        option.value = row.dataset.id;
        option.selected = true;
        hiddenSelect.appendChild(option);
    });
}

function updateNoStudentsMessage() {
    // Update available list
    const availableRows = availableList.querySelectorAll('.student-row');
    const availableMsg = availableList.querySelector('.no-students');
    if (availableRows.length === 0) {
        if (!availableMsg) {
            availableList.innerHTML = '<div class="no-students">All students selected</div>';
        }
    } else if (availableMsg) {
        availableMsg.remove();
    }

    // Update selected list
    const selectedRows = selectedList.querySelectorAll('.student-row');
    const selectedMsg = selectedList.querySelector('.no-students');
    if (selectedRows.length === 0) {
        if (!selectedMsg) {
            selectedList.innerHTML = '<div class="no-students">No students selected</div>';
        }
    } else if (selectedMsg) {
        selectedMsg.remove();
    }
}

function createStudentRow(student) {
    return `
                <div class="student-row" data-id="${student.pk}" data-name="${student.display_name || ''}" data-ot="${student.ot_code || ''}">
                    <input type="checkbox" class="form-check-input">
                    <span>${student.display_name || 'N/A'}</span>
                    <span>${student.ot_code || 'N/A'}</span>
                    <button type="button" class="arrow-btn add" title="Add to selection">
                        <i class="material-icons material-symbols-rounded"> chevron_right</i>
                    </button>
                </div>
            `;
}

$(document).ready(function() {
    // Function to toggle faculty field based on duty type
    function toggleFacultyField() {
        const dutyTypeSelect = $('#mdo_duty_type_master_pk');
        const facultyContainer = $('#faculty_field_container');
        const selectedDutyType = dutyTypeSelect.val();
        
        // Get all duty type options to find Escort
        let escortDutyTypeId = null;
        dutyTypeSelect.find('option').each(function() {
            const optionText = $(this).text().toLowerCase().trim();
            if (optionText === 'escort') {
                escortDutyTypeId = $(this).val();
            }
        });
        
        // Show faculty field if Escort is selected
        if (selectedDutyType && selectedDutyType == escortDutyTypeId) {
            facultyContainer.show();
            $('#faculty_master_pk').attr('required', true);
        } else {
            facultyContainer.hide();
            $('#faculty_master_pk').val('').trigger('change');
            $('#faculty_master_pk').removeAttr('required');
        }
    }
    
    // Initialize after select2 is ready
    setTimeout(function() {
        toggleFacultyField();
    }, 100);
    
    // Toggle when duty type changes
    $('#mdo_duty_type_master_pk').on('change', function() {
        toggleFacultyField();
    });
    
    $('#mdo_date').on('change', function() {
        const courses = $('.course-selected').val();
        const selectedDate = $('#mdo_date').val();

        console.log('Course selected:', courses);
        console.log('Date selected:', selectedDate);

        if (!courses || courses.length === 0) {
            alert('Please select a course first.');
            return;
        }

        if (!selectedDate) {
            alert('Please select a date.');
            return;
        }

        // Show loading message
        availableList.innerHTML = '<div class="no-students">Loading students...</div>';

        $.ajax({
            url: "{{ route('mdo-escrot-exemption.get.student.list.according.to.course') }}",
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                selectedCourses: courses,
                selectedDate: selectedDate
            },
            success: function(response) {
                console.log('AJAX Response:', response);

                if (!response.status) {
                    availableList.innerHTML = '<div class="no-students">Error: ' + response
                        .message + '</div>';
                    return;
                }

                if (!response.students || response.students.length === 0) {
                    availableList.innerHTML =
                        '<div class="no-students">No students found for the selected course and date</div>';
                    return;
                }

                // Clear and populate available list
                availableList.innerHTML = '';
                response.students.forEach(s => {
                    if (s) { // Check if student object is not null
                        availableList.innerHTML += createStudentRow(s);
                    }
                });
                updateNoStudentsMessage();
                console.log('Students loaded:', response.students.length);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                availableList.innerHTML =
                    '<div class="no-students">Error loading students. Please try again.</div>';
            }
        });
    });


    // $('.course-selected').on('change', function () {
    //     const courses = $(this).val();
    //     console.log(courses);
    //     if (!courses || courses.length === 0) return;

    //     $.ajax({
    //         url: "{{ route('mdo-escrot-exemption.get.student.list.according.to.course') }}",
    //         type: 'POST',
    //         data: {
    //             _token: $('meta[name="csrf-token"]').attr('content'),
    //             selectedCourses: courses
    //         },
    //         success: function (response) {
    //             if (!response.status) {
    //                 alert(response.message);
    //                 return;
    //             }
    //             if (response.students.length === 0) {
    //                 alert('No students found for the selected courses.');
    //                 return;
    //             }

    //             const currentSelected = $('#select').val() || [];

    //             // Rebuild options
    //             $('#select').empty();
    //             response.students.forEach(s => {
    //                 const isSel = currentSelected.includes(s.pk.toString());
    //                 $('#select').append(new Option(s.display_name, s.pk, false, isSel));
    //             });

    //             initDualListbox(); // refresh to re-render
    //         },
    //         error: function () {
    //             alert('Error fetching student list');
    //         }
    //     });
    // });

    @if(old('course_master_pk') && old('mdo_date'))
    setTimeout(function() {
        $('#mdo_date').trigger('change');
    }, 500);
    @endif

});
</script>
@endsection