@extends('admin.layouts.master')

@php
    $num = fn ($v) => \App\Services\COE\ResultMarksService::number($v);
    $markCount = count($columns);
    // S.No, OT Code, Tutor Name, then one column per component.
    $markCols = $markCount ? range(3, 2 + $markCount) : [];
    $assigned = [];
    foreach ($columns as $col) {
        $assigned[$col['key']] = array_flip($col['students']);
    }
    $editable = 0;
@endphp

@section('title', 'Result Entry')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.rre-marks th { white-space: normal; min-width: 84px; vertical-align: bottom; }
.rre-marks thead tr:first-child th[colspan] { text-align: center; border-bottom: 1px solid var(--ds-line, #e8e8e8); }
.rre-mark { width: 76px; text-align: right; }
.rre-locked { white-space: nowrap; }
.rre-locked .bi { font-size: 12px; opacity: .6; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Result Entry" :showBack="false"
                 :items="['Setup', 'COE', 'Result Management', 'Result Entry']">
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            @include('admin.coe.partials.download_menu', ['prefix' => 'rre'])
            <button type="button" id="rrePrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                {{-- Course / Subject change the columns, so they reload the page. --}}
                <form method="GET" action="{{ route('admin.coe.faculty-result-entry.index') }}" id="rreFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" class="form-select rre-filter" aria-label="Course">
                            @forelse ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected($pk === $coursePk)>{{ $name }}</option>
                            @empty
                                <option value="">Course</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="subject" class="form-select rre-filter" aria-label="Subject">
                            <option value="">All Subjects</option>
                            @foreach ($allSubjects as $pk => $s)
                                <option value="{{ $pk }}" @selected($pk === $subjectPk)>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.coe.faculty-result-entry.index', ['course' => $coursePk]) }}"
                       class="btn programme-dt-btn-reset d-inline-flex align-items-center">Remove Filter</a>
                </form>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rreColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="rreDtSearch" class="programme-dt-search d-none" data-dt-search-for="rreTable"></div>
                    <button type="button" id="rreSearchToggle" class="coe-search-toggle"
                            aria-controls="rreDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <p class="coe-muted small mb-3">
                Enter marks, or <strong>AB</strong> for an absent participant. <strong>Save</strong> keeps a draft you can change;
                <strong>Save &amp; Approve</strong> locks every mark on this sheet and sends it to Result Compilation.
            </p>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rreTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table rre-marks">
                        <thead>
                            <tr>
                                <th scope="col" rowspan="2">S.No</th>
                                <th scope="col" rowspan="2">OT Code</th>
                                <th scope="col" rowspan="2">Tutor Name</th>
                                @foreach ($subjects as $s)
                                    <th scope="colgroup" colspan="{{ count($s['columns']) }}">{{ $s['name'] }} ({{ $num($s['max']) }})</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($subjects as $s)
                                    @foreach ($s['columns'] as $col)
                                        {{-- The subject prefix is for exports, which only read this row. --}}
                                        <th scope="col"><span class="visually-hidden">{{ $s['name'] }} - </span>{{ $col['label'] }} ({{ $num($col['max']) }})</th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $pk => $row)
                                <tr>
                                    <td></td>
                                    <td>{{ $row['code'] }}</td>
                                    <td class="coe-nowrap">{{ implode(', ', $row['tutors']) ?: '-' }}</td>
                                    @foreach ($subjects as $s)
                                        @foreach ($s['columns'] as $key => $col)
                                            @php
                                                $mark = $marks[$key][$pk] ?? null;
                                                $value = $mark === null ? '' : ($mark->is_absent ? 'AB' : $num($mark->marks));
                                            @endphp
                                            @if (! isset($assigned[$key][$pk]))
                                                <td class="coe-muted" title="Not assigned to you">-</td>
                                            @elseif ($mark?->isApproved())
                                                <td class="rre-locked" title="Approved">{{ $value }} <i class="bi bi-lock-fill" aria-hidden="true"></i></td>
                                            @else
                                                @php $editable++; @endphp
                                                <td>
                                                    <input type="text" inputmode="decimal" maxlength="6" autocomplete="off"
                                                           class="form-control form-control-sm rre-mark" value="{{ $value }}"
                                                           data-key="{{ $key }}" data-student="{{ $pk }}" data-max="{{ $col['max'] }}"
                                                           data-initial="{{ $value }}"
                                                           aria-label="{{ $col['component'] }} marks for {{ $row['code'] }}">
                                                </td>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rreTable"></div>
            </div>

            @if ($editable > 0)
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" id="rreSaveBtn">Save</button>
                    <button type="button" class="btn coe-btn coe-btn-primary" id="rreApproveBtn">Save &amp; Approve</button>
                </div>
            @elseif ($rows !== [])
                <p class="text-end coe-muted small mt-3 mb-0"><i class="bi bi-lock-fill" aria-hidden="true"></i> Every mark on this sheet is approved.</p>
            @endif
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'rreColumnVisibilityModal', 'grid' => 'rreColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var dt = CoeGrid.init({
        table: '#rreTable',
        title: @json('Result Entry' . ($coursePk ? ' - ' . $courses[$coursePk] : '')),
        snoCol: 0,
        order: [[1, 'asc']],
        noSort: [0].concat(@json($markCols)),
        searchToggle: '#rreSearchToggle',
        searchSlot: '#rreDtSearch',
        download: '#rreDownloadExcel',
        pdf: '#rreDownloadPdf',
        print: '#rrePrintBtn',
        colvisGrid: '#rreColumnToggleGrid',
        colvisKey: 'coeFacultyResultEntry:hiddenColumns:v1',
        empty: { title: 'No Marks to Enter', zeroTitle: 'No Participants Found',
                 text: 'Marks entry opens once you are mapped on an examination drive and its Course Coordinator approves it.' }
    });

    function validMark(value, max) {
        value = $.trim(value);
        if (value === '' || /^ab$/i.test(value)) { return true; }
        return /^\d+(\.\d{1,2})?$/.test(value) && parseFloat(value) <= max;
    }

    // Every page of the grid, not only the one on screen.
    function inputs() { return dt.$('input.rre-mark'); }

    $(document).on('input', 'input.rre-mark', function () {
        var $in = $(this);
        $in.toggleClass('is-invalid', !validMark($in.val(), parseFloat($in.data('max'))))
            .attr('title', $in.hasClass('is-invalid') ? 'Enter marks from 0 to ' + $in.data('max') + ', or AB' : null);
    });

    function submit(approve) {
        var marks = {};
        var bad = 0;
        inputs().each(function () {
            var $in = $(this);
            if (!validMark($in.val(), parseFloat($in.data('max')))) { bad++; return; }
            var key = $in.attr('data-key');
            if ($.trim($in.val()) === $in.attr('data-initial')) { return; }
            (marks[key] = marks[key] || {})[$in.attr('data-student')] = $.trim($in.val());
        });
        if (bad) {
            Swal.fire({ icon: 'error', title: 'Check the marks', text: bad + ' mark(s) are not valid. Fix the highlighted cells.' });
            return;
        }

        var $btns = $('#rreSaveBtn, #rreApproveBtn').prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.faculty-result-entry.save')),
            method: 'POST',
            contentType: 'application/json',
            // JSON, not form fields: a full sheet can pass PHP's max_input_vars.
            data: JSON.stringify({ course: @json($coursePk), subject: @json($subjectPk), marks: marks, approve: approve }),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Success', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            $.each(res.errors || {}, function (cell, message) {
                var parts = cell.split('|');
                inputs().filter(function () {
                    return $(this).attr('data-key') === parts[0] && $(this).attr('data-student') === parts[1];
                }).addClass('is-invalid').attr('title', message);
            });
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save the marks.' });
        }).always(function () { $btns.prop('disabled', false); });
    }

    $('#rreSaveBtn').on('click', function () { submit(false); });
    $('#rreApproveBtn').on('click', function () {
        Swal.fire({
            icon: 'question', title: 'Approve marks?',
            text: 'Every mark on this sheet will be saved and locked. Approved marks cannot be changed.',
            showCancelButton: true, confirmButtonText: 'Approve', cancelButtonText: 'Cancel'
        }).then(function (r) { if (r.isConfirmed) { submit(true); } });
    });

    $('.rre-filter').on('change', function () { $('#rreFilterForm').submit(); });
});
</script>
@endpush
