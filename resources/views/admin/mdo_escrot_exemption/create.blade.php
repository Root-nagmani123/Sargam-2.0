@extends('admin.layouts.master')
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css">
    <style>
        .btn-group {
            height: 50px !important;
        }

        .btn-group .btn-default {
            background-color: #af2910 !important;
        }

        .btn-group .btn-default i {
            color: #fff !important;
        }

        .bootstrap-duallistbox-container .moveall i,
        .bootstrap-duallistbox-container .removeall i,
        .bootstrap-duallistbox-container .move i,
        .bootstrap-duallistbox-container .remove i {
            display: none !important;
            /* Hides default icons */
        }

        select#bootstrap-duallistbox-nonselected-list_selected_student_list\[\],
        select#bootstrap-duallistbox-selected-list_selected_student_list\[\] {
            height: 500px !important;
        }

        select#bootstrap-duallistbox-nonselected-list_selected_student_list\[\] option,
        select#bootstrap-duallistbox-selected-list_selected_student_list\[\] option {
            padding: 7px;
            border-bottom: 1px solid;
            border-radius: 2px;
        }
    </style>
@endsection
@section('title', 'MDO Escrot Exemption')

@section('content')

    <div class="container-fluid">
        <x-breadcrum title="MDO Escrot Exemption" />
        <x-session_message />
        <!-- start Vertical Steps Example -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
    <div class="card-body p-4">

        <h4 class="fw-bold mb-2">
            {{ !empty($mdoDutyType) ? 'Edit MDO Escort Exemption' : 'Create MDO Escort Exemption' }}
        </h4>
        <p class="text-muted mb-4 small">
            Please fill in the required fields. Fields marked with <span class="text-danger">*</span> are mandatory.
        </p>

        <form action="{{ route('mdo-escrot-exemption.store') }}" method="POST" id="mdoDutyTypeForm" novalidate>
            @csrf

            @if(!empty($mdoDutyType))
                <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
            @endif

            <div class="row g-4">

                <!-- Course Name -->
                <div class="col-md-6">
                    <x-select 
                        name="course_master_pk" 
                        label="Course Name" 
                        formLabelClass="form-label fw-semibold"
                        formSelectClass="form-select select2"
                        :options="$courseMaster"
                        labelRequired="true"
                        value="{{ old('course_master_pk', $mdoDutyType->course_master_pk ?? '') }}"
                    />
                    <small class="text-muted">Select the course for which exemption is being created.</small>
                </div>

                <!-- Duty Type -->
                <div class="col-md-6">
                    <x-select 
                        name="mdo_duty_type_master_pk" 
                        label="Duty Type" 
                        formLabelClass="form-label fw-semibold"
                        formSelectClass="form-select select2"
                        :options="$MDODutyTypeMaster"
                        labelRequired="true"
                        value="{{ old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '') }}"
                    />
                </div>

                <!-- Date -->
                <div class="col-md-6">
                    <x-input 
                        type="date" 
                        name="mdo_date" 
                        label="Select Date"
                        formLabelClass="form-label fw-semibold"
                        labelRequired="true"
                        value="{{ old('mdo_date', $mdoDutyType->mdo_date ?? '') }}"
                    />
                </div>

                <!-- Time From -->
                <div class="col-md-3">
                    <x-input 
                        type="time" 
                        name="Time_from" 
                        label="From Time"
                        formLabelClass="form-label fw-semibold"
                        labelRequired="true"
                        value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}"
                    />
                </div>

                <!-- Time To -->
                <div class="col-md-3">
                    <x-input 
                        type="time" 
                        name="Time_to" 
                        label="To Time"
                        formLabelClass="form-label fw-semibold"
                        labelRequired="true"
                        value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}"
                    />
                </div>

                <!-- Student List -->
                <div class="col-md-12">
                    <label for="select" class="form-label fw-semibold">
                        Select Students <span class="text-danger">*</span>
                    </label>

                    <select id="select" class="form-select select2" name="selected_student_list[]" multiple
                        data-placeholder="Choose one or more students">
                    </select>

                    @error('selected_student_list')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <small class="text-muted">Select multiple students by holding CTRL (Windows) or CMD (Mac).</small>
                </div>

                <!-- Remarks -->
                <div class="col-md-12">
                    <label for="textarea" class="form-label fw-semibold">Remarks (Optional)</label>
                    <textarea class="form-control" id="textarea" rows="3" 
                        placeholder="Enter any additional remarks..."
                        name="Remark">{{ old('Remark', $mdoDutyType->Remark ?? '') }}</textarea>

                    @error('Remark')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

            </div>

            <hr class="mt-4">

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('mdo-escrot-exemption.index') }}" 
                   class="btn btn-light border px-4">
                    Back
                </a>

                <button class="btn btn-primary px-4" type="submit">
                    Save Details
                </button>

            </div>

        </form>
    </div>
</div>

        <!-- end Vertical Steps Example -->
    </div>
@endsection


@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js"></script>
    <script>
        let dualListbox1;

        function initDualListbox() {
            if (dualListbox1) {
                dualListbox1.bootstrapDualListbox('refresh');
            } else {
                dualListbox1 = $('#select').bootstrapDualListbox({
                    nonSelectedListLabel: 'Available Students',
                    selectedListLabel: 'Selected Students',
                    moveOnSelect: false,
                    showFilterInputs: true,
                    filterPlaceHolder: 'Search...',
                    infoText: 'Showing all {0}',
                    infoTextFiltered: '<span class="badge badge-warning">Filtered</span> {0} from {1}',
                    // Custom buttons with Iconify icons
                    moveSelectedLabel: 'Move Selected',
                    moveAllLabel: 'Move All',
                    removeSelectedLabel: 'Remove Selected',
                    removeAllLabel: 'Remove All'
                });
            }
            setTimeout(() => {
                $('.moveall').html('Move All');
                $('.removeall').html('Remove All');
                $('.move').html('Move Selected');
                $('.remove').html('Remove Selected');
            }, 100); // delay to allow rendering
        }

        $(document).ready(function () {
            initDualListbox();

            $('#mdo_date').on('change', function(){
                const courses = $('.course-selected').val();
                const selectedDate = $('#mdo_date').val();

                if (!courses || courses.length === 0) {
                    alert('Please select a course first.');
                    return;
                }

                $.ajax({
                    url: "{{ route('mdo-escrot-exemption.get.student.list.according.to.course') }}",
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        selectedCourses: courses,
                        selectedDate: selectedDate
                    },
                    success: function (response) {
                        if (!response.status) {
                            alert(response.message);
                            return;
                        }
                        if (response.students.length === 0) {
                            alert('No students found for the selected courses.');
                            return;
                        }

                        const currentSelected = $('#select').val() || [];

                        // Rebuild options
                        $('#select').empty();
                        response.students.forEach(s => {
                            const isSel = currentSelected.includes(s.pk.toString());
                            $('#select').append(new Option(s.display_name, s.pk, false, isSel));
                        });

                        initDualListbox(); // refresh to re-render
                    },
                    error: function () {
                        alert('Error fetching student list');
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
                setTimeout(function () {
                    $('#mdo_date').trigger('change');
                }, 500);
            @endif

        });
    </script>
@endsection