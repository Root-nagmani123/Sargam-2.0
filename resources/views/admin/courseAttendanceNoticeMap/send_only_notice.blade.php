@extends('admin.layouts.master')

@section('title', 'Direct Notice - Sargam | LBSNAA')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/css/dual-listbox.css') }}">
<style>
    .dual-listbox .dual-listbox__button {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Direct Notice" />
    <x-session_message />

    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Direct Notice</h4>
            <hr>

            <form action="{{ route('send.notice.direct.save') }}" method="POST">
                @csrf
                <div class="row">

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="courseSelectNotice" class="form-label">Course</label>
                            <select name="course_master_pk" class="form-control" id="courseSelectNotice" required>
                                <option value="">Select Course</option>
                                @foreach($courseMasters as $course)
                                    <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                                @endforeach
                            </select>
                            @error('course_master_pk')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="date_of_notice" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date_of_notice" name="date_of_notice" required
                                value="{{ old('date_of_notice') }}">
                            @error('date_of_notice')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="select_notice_student" class="form-label">Select Students</label>
                        <select id="select_notice_student" class="form-control" name="selected_student_list[]" multiple>
                        </select>
                        @error('selected_student_list')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 mt-3">
                        <label for="notice_remark" class="form-label">Message (If Any)</label>
                        <textarea class="form-control" id="notice_remark" rows="3"
                            placeholder="Enter notice message..." name="remark">{{ old('remark') }}</textarea>
                        @error('remark')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <hr>

                <div class="row">
                    <div class="col-12">
                        <div class="text-end gap-3">
                            <button type="submit" class="btn btn-outline-danger">Send Notice</button>
                            <a href="{{ url()->previous() }}" class="btn btn-secondary ms-2">Back</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var STUDENT_BY_COURSE_URL = "{{ route('send.notice.students') }}";

    document.addEventListener('DOMContentLoaded', function () {
        var today = new Date().toISOString().split('T')[0];
        var dateInput = document.getElementById('date_of_notice');
        if (!dateInput.value) {
            dateInput.value = today;
        }
        dateInput.max = today;
    });

    var noticeDualListbox;

    $('#courseSelectNotice').on('change', function () {
        var courseId = $(this).val();

        if (!courseId) {
            $('#select_notice_student').empty();
            if (typeof noticeDualListbox !== 'undefined' && noticeDualListbox) {
                try { noticeDualListbox.destroy(); } catch (e) {}
            }
            $('.dual-listbox').remove();
            return;
        }

        $('#select_notice_student').empty().append('<option disabled>Loading...</option>');

        $.ajax({
            url: STUDENT_BY_COURSE_URL,
            type: 'GET',
            data: { course_id: courseId },
            success: function (response) {
                $('#select_notice_student').empty();

                if (response.status && response.students && response.students.length > 0) {
                    response.students.forEach(function (student) {
                        $('#select_notice_student').append(
                            $('<option>', {
                                value: student.pk,
                                text: student.display_name + ' (' + student.generated_OT_code + ')'
                            })
                        );
                    });
                }

                if (typeof noticeDualListbox !== 'undefined' && noticeDualListbox) {
                    try { noticeDualListbox.destroy(); } catch (e) {}
                }
                $('.dual-listbox').remove();

                noticeDualListbox = new DualListbox('#select_notice_student', {
                    addEvent: function (value) {},
                    removeEvent: function (value) {},
                    availableTitle: 'Available Students',
                    selectedTitle: 'Selected Students',
                    addButtonText: 'Move Right',
                    removeButtonText: 'Move Left',
                    addAllButtonText: 'Move All Right',
                    removeAllButtonText: 'Move All Left',
                    draggable: true
                });
            },
            error: function () {
                $('#select_notice_student').empty();
                alert('Error fetching student list. Please try again.');
            }
        });
    });
</script>
@endsection
