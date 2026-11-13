@extends('admin.layouts.master')

@php
    $num = fn ($v) => \App\Services\COE\ResultMarksService::number($v);
    $two = fn ($v) => $v === null ? '' : number_format((float) $v, 2, '.', '');
    $isCompleted = $tab === 'completed';
    $shown = array_values(array_filter($entries, fn ($e) => (bool) $e['mark']?->isApproved() === $isCompleted));
    $filters = array_filter(['course' => $coursePk, 'subject' => $subjectPk, 'component' => $componentKey]);
    $editable = ! $isCompleted && $shown !== [];
    // OT Unique Code, Course, Subject / Component, then Attendance, Marks (and Remarks) are fields.
    $fieldCols = $withRemarks ? [3, 4, 5] : [3, 4];
    $emptyState = $isCompleted
        ? ['title' => 'No Approved Marks', 'zeroTitle' => 'No Records Found', 'text' => 'Marks you approve show here.']
        : ['title' => 'No Marks to Enter', 'zeroTitle' => 'No Records Found', 'text' => 'Marks entry opens once you are mapped on an examination drive, its Course Coordinator approves it and the exam is over.'];
@endphp

@section('title', 'Evaluation & Marks Entry')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.coe-page .rre-table th:last-child,
.coe-page .rre-table td:last-child { text-align: left; }
.rre-att { width: 150px; }
.rre-att.is-absent { background-color: #fef7d6; border-color: #f5e3a3; }
.rre-mark { width: 150px; }
.rre-mark:disabled { background-color: #eef0f3; }
.rre-mark:disabled::placeholder { color: #344054; opacity: 1; }
.rre-remark { min-width: 220px; }
.rre-locked .bi { font-size: 12px; opacity: .6; }
.rre-reset { font-weight: 600; text-decoration: underline; }
.coe-page .coe-btn-header--outline { background: #fff; color: var(--coe-brand); }
.coe-page .coe-btn-header--outline:hover { background: #eef3fb; color: var(--coe-brand); }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Evaluation & Marks Entry" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Evaluation & Marks Entry']">
        @if ($editable)
            <button type="button" id="rreDraftBtn" class="btn coe-btn-header coe-btn-header--outline">
                <i class="bi bi-folder-symlink" aria-hidden="true"></i><span>Save as Draft</span>
            </button>
            <button type="button" class="btn coe-btn-header" data-bs-toggle="modal" data-bs-target="#rreImportModal">
                <i class="bi bi-upload" aria-hidden="true"></i><span>Import</span>
            </button>
        @endif
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Marks status">
            <a href="{{ route('admin.coe.faculty-result-entry.index', $filters) }}"
               class="coe-tab {{ $isCompleted ? '' : 'is-active' }}" @unless ($isCompleted) aria-current="page" @endunless>Pending: {{ $pendingCount }}</a>
            <a href="{{ route('admin.coe.faculty-result-entry.index', $filters + ['tab' => 'completed']) }}"
               class="coe-tab {{ $isCompleted ? 'is-active' : '' }}" @if ($isCompleted) aria-current="page" @endif>Completed: {{ $completedCount }}</a>
        </nav>
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
                {{-- Course / Subject / Component change what is on the sheet, so they reload the page. --}}
                <form method="GET" action="{{ route('admin.coe.faculty-result-entry.index') }}" id="rreFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    @if ($isCompleted)
                        <input type="hidden" name="tab" value="completed">
                    @endif
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
                    <div class="programme-dt-filter-select">
                        <select name="component" class="form-select rre-filter" aria-label="Component">
                            <option value="">All Components</option>
                            @foreach ($components as $key => $label)
                                <option value="{{ $key }}" @selected($key === $componentKey)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
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

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rreTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table coe-table--no-sno rre-table">
                        <thead>
                            <tr>
                                <th scope="col">OT Unique Code</th>
                                <th scope="col">Course</th>
                                <th scope="col">Subject / Component</th>
                                <th scope="col">Attendance</th>
                                <th scope="col">{{ $marksHead }}</th>
                                @if ($withRemarks)
                                    <th scope="col">Remarks</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shown as $e)
                                @php
                                    $mark = $e['mark'];
                                    $absent = (bool) $mark?->is_absent;
                                    $marks = $absent ? '' : $two($mark?->marks);
                                    $remark = (string) ($mark?->remarks ?? '');
                                    $label = $e['subject'].' — '.$e['component'];
                                @endphp
                                <tr>
                                    <td>{{ $e['code'] }}</td>
                                    <td>{{ $e['course'] }}</td>
                                    <td>{{ $label }}</td>
                                    @if ($isCompleted)
                                        <td>{{ $absent ? 'Absent' : 'Present' }}</td>
                                        <td class="rre-locked" title="Approved">
                                            {{ $absent ? 'Not applicable' : $marks.' / '.$num($e['max']) }}
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                        </td>
                                        @if ($withRemarks)
                                            <td>{{ $remark !== '' ? $remark : '-' }}</td>
                                        @endif
                                    @else
                                        <td>
                                            <select class="form-select rre-att {{ $absent ? 'is-absent' : '' }}"
                                                    data-initial="{{ $absent ? 'Absent' : 'Present' }}"
                                                    aria-label="Attendance of {{ $e['code'] }}, {{ $label }}">
                                                <option value="Present" @selected(! $absent)>Present</option>
                                                <option value="Absent" @selected($absent)>Absent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" inputmode="decimal" min="0" max="{{ $e['max'] }}" step="0.01"
                                                   class="form-control rre-mark" value="{{ $marks }}"
                                                   data-key="{{ $e['key'] }}" data-student="{{ $e['student'] }}" data-max="{{ $e['max'] }}"
                                                   data-initial="{{ $marks }}"
                                                   placeholder="{{ $absent ? 'Not applicable' : '0 - '.$num($e['max']) }}"
                                                   @disabled($absent)
                                                   aria-label="Marks of {{ $e['code'] }}, {{ $label }}, out of {{ $num($e['max']) }}">
                                        </td>
                                        @if ($withRemarks)
                                            <td>
                                                <input type="text" class="form-control rre-remark" value="{{ $remark }}"
                                                       maxlength="{{ \App\Services\COE\ResultMarksService::REMARKS_MAX }}"
                                                       data-initial="{{ $remark }}" placeholder="Optional Notes" autocomplete="off"
                                                       aria-label="Remarks for {{ $e['code'] }}, {{ $label }}">
                                            </td>
                                        @endif
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rreTable"></div>
            </div>

            @if ($editable)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                    <button type="button" class="btn btn-link p-0 rre-reset" id="rreResetBtn">Reset the data</button>
                    <button type="button" class="btn coe-btn coe-btn-primary" id="rreApproveBtn">Approved</button>
                </div>
            @endif
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'rreColumnVisibilityModal', 'grid' => 'rreColumnToggleGrid'])

@if ($editable)
{{-- ── Import marks from the template ─────────────────────────────────── --}}
<div class="modal fade" id="rreImportModal" tabindex="-1" aria-labelledby="rreImportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header">
                <h5 class="modal-title" id="rreImportModalLabel">Import Marks</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                @include('admin.coe.partials.dropzone', [
                    'id' => 'rreImportFile',
                    'field' => 'marks_file',
                    'accept' => collect($importExtensions)->map(fn ($e) => '.'.$e)->implode(','),
                    'hint' => 'File type: '.collect($importExtensions)->map(fn ($e) => '.'.$e)->implode(' ').' | Max size: '.$importMaxMb.' MB',
                    'icon' => 'bi-file-earmark-spreadsheet',
                ])
                <div class="coe-instructions">
                    <h6 class="coe-instructions__title">Instructions &amp; Excel Template</h6>
                    <ul class="coe-instructions__list">
                        <li><a href="{{ route('admin.coe.faculty-result-entry.template', $filters) }}">Download the template file</a> to ensure your Excel file has the correct format for importing marks. It lists every pending row of this table ({{ $pendingCount }}); fill in Attendance and Marks{{ $withRemarks ? ', and Remarks if any' : '' }}.</li>
                        <li><span class="coe-tip">Tip:</span> Make sure your Excel file matches the template format exactly to avoid import errors. Imported marks are saved as a draft — press Approved once you have checked them.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary" id="rreImportSubmit" disabled>Import Marks Entry</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var WITH_REMARKS = @json($withRemarks);
    var CSRF = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };

    var dt = CoeGrid.init({
        table: '#rreTable',
        title: @json('Evaluation & Marks Entry' . ($coursePk ? ' - ' . $courses[$coursePk] : '')),
        snoCol: false,
        // Rows arrive in OT-code order (A2 before A10), which a text sort would break.
        order: [],
        noSort: @json($isCompleted ? [] : $fieldCols),
        searchToggle: '#rreSearchToggle',
        searchSlot: '#rreDtSearch',
        download: '#rreDownloadExcel',
        pdf: '#rreDownloadPdf',
        print: '#rrePrintBtn',
        colvisGrid: '#rreColumnToggleGrid',
        colvisKey: 'coeFacultyResultEntry:hiddenColumns:v2',
        empty: @json($emptyState)
    });

    if (!$('#rreApproveBtn').length) { return; }

    function validMark(value, max) {
        value = $.trim(value);
        return value === '' || (/^\d+(\.\d{1,2})?$/.test(value) && parseFloat(value) <= max);
    }

    // Every page of the grid, not only the one on screen.
    function rows() { return dt.rows().nodes().to$(); }
    function parts($tr) {
        return { att: $tr.find('.rre-att'), mark: $tr.find('.rre-mark'), remark: $tr.find('.rre-remark') };
    }

    function setAbsent($tr, absent) {
        var p = parts($tr);
        p.att.toggleClass('is-absent', absent);
        p.mark.prop('disabled', absent).removeClass('is-invalid').attr('title', null)
            .attr('placeholder', absent ? 'Not applicable' : '0 - ' + p.mark.data('max'));
        if (absent) { p.mark.val(''); }
    }

    $(document).on('change', '.rre-att', function () {
        setAbsent($(this).closest('tr'), $(this).val() === 'Absent');
    });

    $(document).on('input', '.rre-mark', function () {
        var $in = $(this);
        var bad = !validMark($in.val(), parseFloat($in.data('max')));
        $in.toggleClass('is-invalid', bad).attr('title', bad ? 'Enter marks from 0 to ' + $in.data('max') + ' (up to 2 decimals)' : null);
    });

    function submit(approve) {
        var marks = {}, remarks = {}, bad = 0;
        rows().each(function () {
            var p = parts($(this));
            var absent = p.att.val() === 'Absent';
            var value = absent ? 'AB' : $.trim(p.mark.val());
            if (!absent && !validMark(value, parseFloat(p.mark.data('max')))) { bad++; return; }

            var initial = p.att.attr('data-initial') === 'Absent' ? 'AB' : p.mark.attr('data-initial');
            var note = WITH_REMARKS ? $.trim(p.remark.val()) : '';
            var noteChanged = WITH_REMARKS && note !== p.remark.attr('data-initial');
            if (value === initial && !noteChanged) { return; }

            var key = p.mark.attr('data-key'), student = p.mark.attr('data-student');
            (marks[key] = marks[key] || {})[student] = value;
            if (WITH_REMARKS) { (remarks[key] = remarks[key] || {})[student] = note; }
        });
        if (bad) {
            Swal.fire({ icon: 'error', title: 'Check the marks', text: bad + ' mark(s) are not valid. Fix the highlighted cells.' });
            return;
        }

        var $btns = $('#rreDraftBtn, #rreApproveBtn').prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.faculty-result-entry.save')),
            method: 'POST',
            contentType: 'application/json',
            // JSON, not form fields: a full sheet can pass PHP's max_input_vars.
            data: JSON.stringify({ course: @json($coursePk), subject: @json($subjectPk), component: @json($componentKey), marks: marks, remarks: remarks, approve: approve }),
            headers: CSRF
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Success', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            $.each(res.errors || {}, function (cell, message) {
                var bits = cell.split('|');
                rows().find('.rre-mark').filter(function () {
                    return $(this).attr('data-key') === bits[0] && $(this).attr('data-student') === bits[1];
                }).addClass('is-invalid').attr('title', message);
            });
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not save the marks.' });
        }).always(function () { $btns.prop('disabled', false); });
    }

    $('#rreDraftBtn').on('click', function () { submit(false); });
    $('#rreApproveBtn').on('click', function () {
        Swal.fire({
            icon: 'question', title: 'Approve marks?',
            text: 'Every mark on this sheet ({{ $pendingCount }} pending) will be saved and locked, and sent to Result Compilation. Approved marks cannot be changed.',
            showCancelButton: true, confirmButtonText: 'Approve', cancelButtonText: 'Cancel'
        }).then(function (r) { if (r.isConfirmed) { submit(true); } });
    });

    // Back to what is saved; nothing is sent.
    $('#rreResetBtn').on('click', function () {
        Swal.fire({
            icon: 'warning', title: 'Reset the data?',
            text: 'Changes you have not saved on this sheet will be discarded.',
            showCancelButton: true, confirmButtonText: 'Reset', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            rows().each(function () {
                var $tr = $(this), p = parts($tr);
                p.att.val(p.att.attr('data-initial'));
                setAbsent($tr, p.att.val() === 'Absent');
                p.mark.val(p.mark.attr('data-initial')).removeClass('is-invalid').attr('title', null);
                p.remark.val(p.remark.attr('data-initial'));
            });
        });
    });

    // A component belongs to one course / subject: changing either starts from All Components.
    $('.rre-filter').on('change', function () {
        if (this.name !== 'component') { $('#rreFilterForm select[name="component"]').val(''); }
        $('#rreFilterForm').submit();
    });

    /* ── Import ──────────────────────────────────────────────────────────── */
    var IMPORT_EXT = @json($importExtensions);
    var $importBtn = $('#rreImportSubmit');
    var importFile = null;

    var dz = CoeGrid.dropzone({
        root: '#rreImportFile',
        accept: new RegExp('\\.(' + IMPORT_EXT.join('|') + ')$', 'i'),
        acceptText: 'Only ' + IMPORT_EXT.map(function (e) { return '.' + e; }).join(', ') + ' files are allowed.',
        maxBytes: @json($importMaxMb) * 1024 * 1024,
        onChange: function (file) { importFile = file; $importBtn.prop('disabled', !file); }
    });

    $importBtn.on('click', function () {
        if (!importFile) { return; }
        var form = new FormData();
        form.append('file', importFile);
        // Onto this sheet: the same Course / Subject the template came from.
        $.each(@json($filters), function (k, v) { form.append(k, v); });
        $importBtn.prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.faculty-result-entry.import')),
            method: 'POST', data: form, processData: false, contentType: false, headers: CSRF
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rreImportModal')).hide();
            Swal.fire({ icon: 'success', title: 'Imported', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            var $html = $('<div class="text-start"></div>').append($('<p class="mb-2"></p>').text(res.message || 'Could not import the file.'));
            if ((res.failures || []).length) {
                var $list = $('<ul class="small mb-0 ps-3" style="max-height:220px;overflow:auto"></ul>');
                res.failures.forEach(function (f) { $list.append($('<li></li>').text(f)); });
                $html.append($list);
            }
            Swal.fire({ icon: 'error', title: 'Nothing imported', html: $html[0] });
        }).always(function () { $importBtn.prop('disabled', !importFile); });
    });

    $('#rreImportModal').on('hidden.bs.modal', function () { dz.reset(); importFile = null; $importBtn.prop('disabled', true); });
});
</script>
@endpush
