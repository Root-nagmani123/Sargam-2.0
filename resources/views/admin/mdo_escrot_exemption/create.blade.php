@extends('admin.layouts.master')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
/* Dual-list student picker — the one piece of this form the shared mst-*
 * layer has no component for. Scoped to the page root, --ds-* tokens only.
 * The script relies on .student-row being display:grid (filterStudents()
 * writes style.display = 'grid'), on .arrow-btn.add / .remove and on the
 * .no-students placeholder. */
.mst-page.mee-form-page .dual-list-container {
    display: flex;
    gap: var(--ds-space-3);
    align-items: stretch;
}
.mst-page.mee-form-page .student-panel {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: var(--ds-surface);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-card);
    overflow: hidden;
}
.mst-page.mee-form-page .panel-header {
    padding: var(--ds-space-2) var(--ds-space-3);
    background: var(--ds-surface-2);
    border-bottom: 1px solid var(--ds-line);
    font-weight: 600;
    color: var(--ds-ink);
}
.mst-page.mee-form-page .search-box {
    padding: var(--ds-space-2) var(--ds-space-3);
    border-bottom: 1px solid var(--ds-line);
}
.mst-page.mee-form-page .select-all-box {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    padding: var(--ds-space-2) var(--ds-space-3);
    border-bottom: 1px solid var(--ds-line);
    font-size: 0.875rem;
}
.mst-page.mee-form-page .table-header,
.mst-page.mee-form-page .student-row {
    display: grid;
    grid-template-columns: 2.5rem minmax(0, 1fr) 7.5rem 2.75rem;
    align-items: center;
    gap: var(--ds-space-2);
    padding: var(--ds-space-2) var(--ds-space-3);
}
.mst-page.mee-form-page .table-header {
    background: var(--ds-surface-2);
    border-bottom: 1px solid var(--ds-line);
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ds-ink-muted);
}
.mst-page.mee-form-page .student-list {
    flex: 1 1 auto;
    min-height: 18rem;
    max-height: 25rem;
    overflow-y: auto;
}
.mst-page.mee-form-page .student-row {
    border-bottom: 1px solid var(--ds-line);
    font-size: 0.875rem;
}
.mst-page.mee-form-page .student-row:hover { background: var(--ds-surface-2); }
.mst-page.mee-form-page .student-row .form-check-input { margin: 0; cursor: pointer; }
.mst-page.mee-form-page .arrow-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--ds-space-1);
    border: 0;
    border-radius: var(--ds-radius);
    background: transparent;
    color: var(--ds-ink-muted);
    cursor: pointer;
}
.mst-page.mee-form-page .arrow-btn:hover { background: var(--ds-surface-2); color: var(--ds-primary); }
.mst-page.mee-form-page .arrow-btn.remove:hover { color: var(--bs-danger); }
.mst-page.mee-form-page .arrow-btn:focus-visible,
.mst-page.mee-form-page .transfer-btns .btn:focus-visible { outline: 0; box-shadow: var(--ds-focus-ring); }
.mst-page.mee-form-page .transfer-btns {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: var(--ds-space-2);
}
.mst-page.mee-form-page .no-students {
    padding: var(--ds-space-5) var(--ds-space-3);
    text-align: center;
    color: var(--ds-ink-muted);
    font-size: 0.875rem;
}
@media (max-width: 767.98px) {
    .mst-page.mee-form-page .dual-list-container { flex-direction: column; }
    .mst-page.mee-form-page .transfer-btns { flex-direction: row; justify-content: center; }
}
</style>
@endpush

@section('title', 'MDO/Escort Exemption')

@section('setup_content')
@php
    // $mdoDutyType is never passed by MDOEscrotExemptionController::create(); the
    // `?? ''` fallbacks are kept from the old x-select / x-input markup.
    $meeCourse = (string) old('course_master_pk', $mdoDutyType->course_master_pk ?? '');
    $meeDuty = (string) old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '');
    $meeFaculty = old('faculty_master_pk', '');
    $meeFaculty = is_array($meeFaculty) ? (string) ($meeFaculty[0] ?? '') : (string) $meeFaculty;
@endphp
<div class="container-fluid mst-page mee-form-page">
    <x-breadcrum title="{{ !empty($mdoDutyType) ? 'Edit MDO/Escort Exemption' : 'Create MDO/Escort Exemption' }}" />
    <x-session_message />

    <form action="{{ route('mdo-escrot-exemption.store') }}" method="POST" id="mdoDutyTypeForm">
        @csrf
        @if(!empty($mdoDutyType))
        <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Duty Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="course_master_pk" class="mst-form-label d-block">
                            Course Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="course_master_pk" id="course_master_pk"
                            class="form-select mst-control mst-searchable course-selected @error('course_master_pk') is-invalid @enderror"
                            data-placeholder="Select Course" required aria-required="true"
                            @error('course_master_pk') aria-describedby="meeCourseError" @enderror>
                            <option value="">Select</option>
                            @foreach ($courseMaster as $value => $label)
                            <option value="{{ $value }}" @selected($meeCourse === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                            <span class="mst-field-error" id="meeCourseError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="mdo_duty_type_master_pk" class="mst-form-label d-block">
                            Duty Type <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk"
                            class="form-select mst-control mst-searchable @error('mdo_duty_type_master_pk') is-invalid @enderror"
                            data-placeholder="Select Duty Type" required aria-required="true"
                            @error('mdo_duty_type_master_pk') aria-describedby="meeDutyError" @enderror>
                            <option value="">Select</option>
                            @foreach ($MDODutyTypeMaster as $value => $label)
                            <option value="{{ $value }}" @selected($meeDuty === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('mdo_duty_type_master_pk')
                            <span class="mst-field-error" id="meeDutyError">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Shown by toggleFacultyField() when the duty type is Escort
                         (jQuery .show()/.hide(), so no display utility class here). --}}
                    <div class="col-md-6" id="faculty_field_container" style="display: none;">
                        <label for="faculty_master_pk" class="mst-form-label d-block">
                            Faculty <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="faculty_master_pk" id="faculty_master_pk"
                            class="form-select mst-control mst-searchable @error('faculty_master_pk') is-invalid @enderror"
                            data-placeholder="Select Faculty"
                            @error('faculty_master_pk') aria-describedby="meeFacultyError" @enderror>
                            <option value="">Select</option>
                            @foreach ($facultyMaster as $value => $label)
                            <option value="{{ $value }}" @selected($meeFaculty === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('faculty_master_pk')
                            <span class="mst-field-error" id="meeFacultyError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="mdo_date" class="mst-form-label d-block">
                            Date <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="date" name="mdo_date" id="mdo_date"
                            class="form-control mst-control @error('mdo_date') is-invalid @enderror"
                            value="{{ old('mdo_date', $mdoDutyType->mdo_date ?? '') }}" required aria-required="true"
                            @error('mdo_date') aria-describedby="meeDateError" @enderror>
                        @error('mdo_date')
                            <span class="mst-field-error" id="meeDateError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="Time_from" class="mst-form-label d-block">
                            From Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" name="Time_from" id="Time_from"
                            class="form-control mst-control @error('Time_from') is-invalid @enderror"
                            value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}" required aria-required="true"
                            @error('Time_from') aria-describedby="meeTimeFromError" @enderror>
                        @error('Time_from')
                            <span class="mst-field-error" id="meeTimeFromError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="Time_to" class="mst-form-label d-block">
                            To Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" name="Time_to" id="Time_to"
                            class="form-control mst-control @error('Time_to') is-invalid @enderror"
                            value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}" required aria-required="true"
                            @error('Time_to') aria-describedby="meeTimeToError" @enderror>
                        @error('Time_to')
                            <span class="mst-field-error" id="meeTimeToError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">
                    Select Students <span class="mst-req" aria-hidden="true">*</span>
                </h2>
                <p class="small text-muted mb-3">Pick the course and date above to load the students who are free in that slot.</p>

                <div class="dual-list-container">
                    {{-- Available --}}
                    <div class="student-panel">
                        <div class="panel-header" id="meeAvailableHeading">Available Students</div>
                        <div class="search-box">
                            <label for="searchAvailable" class="visually-hidden">Search available students</label>
                            <input type="text" id="searchAvailable" class="form-control mst-control"
                                placeholder="Search by name or OT code" autocomplete="off">
                        </div>
                        <div class="table-header">
                            <span class="select-all-cell">
                                <input type="checkbox" id="selectAllAvailable" class="form-check-input m-0"
                                    aria-label="Select all available students">
                            </span>
                            <span>Username</span>
                            <span>OT Code</span>
                            <span><span class="visually-hidden">Move</span></span>
                        </div>
                        <div class="student-list modern-student-list" id="availableList" aria-labelledby="meeAvailableHeading">
                            <div class="no-students">
                                Please select a course and date
                            </div>
                        </div>
                    </div>

                    {{-- Transfer --}}
                    <div class="transfer-btns">
                        <button type="button" id="moveRight" class="btn btn-outline-primary rounded-1"
                            title="Add selected students" aria-label="Add selected students">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">keyboard_double_arrow_right</i>
                        </button>
                        <button type="button" id="moveLeft" class="btn btn-outline-primary rounded-1"
                            title="Remove selected students" aria-label="Remove selected students">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">keyboard_double_arrow_left</i>
                        </button>
                    </div>

                    {{-- Selected --}}
                    <div class="student-panel">
                        <div class="panel-header" id="meeSelectedHeading">Selected Students</div>
                        <div class="search-box">
                            <label for="searchSelected" class="visually-hidden">Search selected students</label>
                            <input type="text" id="searchSelected" class="form-control mst-control"
                                placeholder="Search by name or OT code" autocomplete="off">
                        </div>
                        <div class="select-all-box">
                            <input type="checkbox" id="selectAllSelected" class="form-check-input m-0">
                            <label for="selectAllSelected" class="mb-0">Select All</label>
                        </div>
                        <div class="table-header">
                            <span></span>
                            <span>Username</span>
                            <span>OT Code</span>
                            <span><span class="visually-hidden">Move</span></span>
                        </div>
                        <div class="student-list" id="selectedList" aria-labelledby="meeSelectedHeading">
                            <div class="no-students">No students selected</div>
                        </div>
                    </div>
                </div>

                {{-- Hidden select for form submission (rebuilt by updateHiddenSelect()) --}}
                <select name="selected_student_list[]" id="hiddenStudentSelect" multiple
                    style="display:none;" aria-hidden="true" tabindex="-1"></select>

                @error('selected_student_list')
                    <span class="mst-field-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Remarks</h2>
                <label for="textarea" class="mst-form-label d-block">Remarks (If Any)</label>
                <textarea class="form-control mst-control @error('Remark') is-invalid @enderror" id="textarea" rows="3"
                    placeholder="Enter remarks..." name="Remark">{{ old('Remark') }}</textarea>
                @error('Remark')
                    <span class="mst-field-error">{{ $message }}</span>
                @enderror

                <div class="mst-form-footer">
                    <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection


@section('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
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
                selectedDate: selectedDate,
                selectedTimeFrom: $('#Time_from').val(),
                selectedTimeTo: $('#Time_to').val()
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