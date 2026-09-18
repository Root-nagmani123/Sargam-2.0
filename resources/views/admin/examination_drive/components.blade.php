@extends('admin.layouts.master')

@section('title', 'Subject & Component Mapping')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid ed-components-page">
    <x-breadcrum title="Map Subjects to Examination Drive">
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
            <div id="edcFormAlert" class="alert d-none mb-3" role="alert"></div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="edcTable">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Subject</th>
                            <th style="min-width:200px;">Component</th>
                            <th style="width:110px;">Max Marks</th>
                            <th style="width:130px;">Passing Marks</th>
                            <th style="min-width:150px;">Source</th>
                            <th style="width:120px;">Weightage (%)</th>
                            <th style="width:60px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="edcTableBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-semibold">Total Weightage</td>
                            <td id="edcTotalWeightage" class="fw-semibold">0.00%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary rounded-1 mt-3" id="edcAddRowBtn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Component
            </button>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('master.examination.drive.index') }}" class="btn btn-outline-secondary rounded-1 px-4">Cancel</a>
                <button type="button" class="btn btn-primary rounded-1 px-4" id="edcSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<template id="edcRowTemplate">
    <tr class="edc-row">
        <td>
            <select class="form-select edc-subject" required>
                <option value="">Select Subject</option>
                @foreach ($subjects as $pk => $name)
                    <option value="{{ $pk }}">{{ $name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-select edc-component" required>
                <option value="">Select Component</option>
                @foreach ($components as $pk => $name)
                    <option value="{{ $pk }}">{{ $name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" class="form-control edc-max-marks" required></td>
        <td><input type="number" step="0.01" min="0" class="form-control edc-passing-marks" required></td>
        <td>
            <select class="form-select edc-source" required>
                @foreach ($sources as $source)
                    <option value="{{ $source }}">{{ $source }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" max="100" class="form-control edc-weightage" required></td>
        <td class="text-center">
            <button type="button" class="programme-action-btn programme-action-btn--danger edc-remove-row" aria-label="Remove row">
                <i class="bi bi-trash3" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        var existingRows = @json($rows);

        var $tbody = $('#edcTableBody');
        var $template = document.getElementById('edcRowTemplate');

        function edcAddRow(data) {
            var $row = $($template.content.cloneNode(true));
            if (data) {
                $row.find('.edc-subject').val(data.subject_master_pk);
                $row.find('.edc-component').val(data.component_master_pk);
                $row.find('.edc-max-marks').val(data.max_marks);
                $row.find('.edc-passing-marks').val(data.passing_marks);
                $row.find('.edc-source').val(data.source);
                $row.find('.edc-weightage').val(data.weightage);
            }
            $tbody.append($row);
            edcRecalcTotal();
        }

        function edcRecalcTotal() {
            var total = 0;
            $tbody.find('.edc-weightage').each(function () {
                total += parseFloat($(this).val()) || 0;
            });
            $('#edcTotalWeightage').text(total.toFixed(2) + '%');
        }

        if (existingRows.length) {
            existingRows.forEach(function (row) { edcAddRow(row); });
        } else {
            edcAddRow();
        }

        $('#edcAddRowBtn').on('click', function () {
            edcAddRow();
        });

        $(document).on('click', '.edc-remove-row', function () {
            if ($tbody.find('.edc-row').length <= 1) {
                return;
            }
            $(this).closest('tr').remove();
            edcRecalcTotal();
        });

        $(document).on('input change', '.edc-weightage', function () {
            edcRecalcTotal();
        });

        function edcShowAlert(type, message) {
            $('#edcFormAlert').removeClass('d-none alert-danger alert-success')
                .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
                .text(message);
        }

        $('#edcSaveBtn').on('click', function () {
            var rows = [];
            var valid = true;

            $tbody.find('.edc-row').each(function () {
                var $row = $(this);
                var row = {
                    subject_master_pk: $row.find('.edc-subject').val(),
                    component_master_pk: $row.find('.edc-component').val(),
                    max_marks: $row.find('.edc-max-marks').val(),
                    passing_marks: $row.find('.edc-passing-marks').val(),
                    source: $row.find('.edc-source').val(),
                    weightage: $row.find('.edc-weightage').val(),
                };

                if (!row.subject_master_pk || !row.component_master_pk || row.max_marks === '' || row.passing_marks === '' || row.weightage === '') {
                    valid = false;
                }

                rows.push(row);
            });

            if (!valid) {
                edcShowAlert('error', 'Please fill in all fields for every row.');
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: @json(route('master.examination.drive.components.store', ['driveId' => request()->route('driveId')])),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    rows: rows
                },
                success: function (response) {
                    edcShowAlert('success', response.message || 'Saved successfully.');
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'An error occurred while saving. Please try again.';
                    edcShowAlert('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush
