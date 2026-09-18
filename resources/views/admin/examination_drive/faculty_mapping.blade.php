@extends('admin.layouts.master')

@section('title', 'Faculty Mapping')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid ed-faculty-page">
    <x-breadcrum title="Faculty Mapping">
        <a href="{{ route('master.examination.drive.index') }}"
           class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Back to Examination Drive</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3 mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small d-block">Examination Type</span>
                    <span class="fw-semibold">{{ $drive->examinationType->exam_type_name ?? '-' }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Term</span>
                    <span class="fw-semibold">{{ $drive->term->term_name ?? '-' }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Course</span>
                    <span class="fw-semibold">{{ $drive->course->couse_short_name ?? $drive->course->course_name ?? '-' }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Academic Session</span>
                    <span class="fw-semibold">{{ $drive->academic_session }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div id="fmFormAlert" class="alert d-none mb-3" role="alert"></div>

            @if ($subjects->isEmpty())
                <div class="alert alert-warning mb-0">
                    No subjects are mapped to this drive yet. Please complete
                    <a href="{{ route('master.examination.drive.components.show', ['driveId' => request()->route('driveId')]) }}">Subject & Component Mapping</a>
                    first.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th style="min-width:250px;">Subject</th>
                                <th style="min-width:250px;">Faculty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subjects as $subjectPk => $subject)
                                <tr>
                                    <td>{{ $subject->subject_name ?? '-' }}</td>
                                    <td>
                                        <select class="form-select fm-faculty" data-subject-pk="{{ $subjectPk }}">
                                            <option value="">Select Faculty</option>
                                            @foreach ($facultyOptions as $facultyPk => $facultyName)
                                                <option value="{{ $facultyPk }}" @selected(($existing[$subjectPk] ?? null) == $facultyPk)>{{ $facultyName }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('master.examination.drive.index') }}" class="btn btn-outline-secondary rounded-1 px-4">Cancel</a>
                    <button type="button" class="btn btn-primary rounded-1 px-4" id="fmSaveBtn">Save</button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        function fmShowAlert(type, message) {
            $('#fmFormAlert').removeClass('d-none alert-danger alert-success')
                .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
                .text(message);
        }

        $('#fmSaveBtn').on('click', function () {
            var rows = [];
            var valid = true;

            $('.fm-faculty').each(function () {
                var facultyPk = $(this).val();
                if (!facultyPk) {
                    valid = false;
                }
                rows.push({
                    subject_master_pk: $(this).data('subject-pk'),
                    faculty_master_pk: facultyPk,
                });
            });

            if (!valid) {
                fmShowAlert('error', 'Please assign a faculty for every subject.');
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: @json(route('master.examination.drive.faculty.store', ['driveId' => request()->route('driveId')])),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    rows: rows
                },
                success: function (response) {
                    fmShowAlert('success', response.message || 'Saved successfully.');
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'An error occurred while saving. Please try again.';
                    fmShowAlert('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush
