@extends('admin.layouts.master')

@section('title', 'Map Faculty – ' . $drive['name'])

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Same layout as faculty Complete Setup (faculty_setup.blade.php). */
.edc-toolbar        { display:flex;justify-content:flex-end;gap:12px;margin-bottom:18px; }
.edc-tool-btn       { display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:0;border-radius:8px;
                      background:#fff;color:var(--ds-brand);font-size:15px;font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.06); }
.edc-tool-btn:hover { background:var(--ds-surface-2); }
/* Info card */
.edc-info-bar       { background:#fff;border-radius:12px;padding:24px 30px;margin-bottom:22px;
                      display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:20px; }
.edc-info-item      { display:flex;flex-direction:column;gap:6px;min-width:0; }
.edc-info-label     { font-size:13px;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.3px; }
.edc-info-value     { font-size:17px;font-weight:600;color:var(--ds-ink);overflow-wrap:anywhere; }
@media (max-width: 1199.98px) { .edc-info-bar { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media (max-width: 767.98px)  { .edc-info-bar { grid-template-columns:repeat(2,minmax(0,1fr)); } }
/* Component table */
.edc-comp-table     { width:100%;border-collapse:separate;border-spacing:0;min-width:1080px;table-layout:fixed; }
.edc-comp-table thead th { padding:10px 8px;background:var(--ds-surface-2);font-size:14px;font-weight:500;line-height:1.2;
                      color:var(--ds-ink);white-space:normal;vertical-align:middle; }
.edc-comp-table thead th:first-child { padding-left:14px;border-radius:10px 0 0 0; }
.edc-comp-table thead th:last-child  { padding-right:12px;border-radius:0 10px 0 0; }
.edc-comp-table thead th .bi { margin-left:4px;font-size:12px;color:var(--ds-ink-muted); }
.edc-comp-table tbody td { padding:12px 5px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.edc-comp-table tbody td:first-child { padding-left:12px; }
.edc-comp-table tbody td:last-child  { padding-right:12px; }
.edc-comp-table .form-control,
.edc-comp-table .form-select { height:40px;font-size:14px;border-color:var(--ds-line);border-radius:6px;color:var(--ds-ink); }
.edc-comp-table .form-select  { padding-left:12px;padding-right:30px;background-position:right 10px center;text-overflow:ellipsis; }
.edc-comp-table input[type=number] { padding:6px 8px 6px 12px; }
.edc-comp-table input[type=date]   { padding:6px 6px 6px 10px; }
.edc-comp-table .form-select:disabled { background-color:var(--ds-surface-2);color:var(--ds-ink); }
/* Row buttons: red minus on every row, blue plus only on the last */
.edc-row-del, .edc-row-add { width:36px;height:36px;border:none;border-radius:6px;color:#fff;font-size:18px;
                      display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0; }
.edc-row-del        { background:#e5141a; }
.edc-row-add        { background:#004a93; }
.edc-row-del:hover  { background:#b80f14; }
.edc-row-add:hover  { background:#003670; }
.edc-comp-row:not(:last-child) .edc-row-add { visibility:hidden; }
.edc-actions        { display:flex;justify-content:flex-end;gap:10px; }
/* Total weightage bar */
.edc-total-row td   { background:#e5ecfb;padding:14px 8px;font-size:15px;color:var(--ds-ink); }
.edc-total-row td:first-child { border-radius:0 0 0 10px; }
.edc-total-row td:last-child  { border-radius:0 0 10px 0; }
.edc-footer         { display:flex;justify-content:flex-end;gap:16px;margin-top:28px; }
.edc-footer .btn    { min-width:116px;height:48px;font-size:17px; }
@media print {
    .edc-toolbar, .edc-footer, .edc-actions { display:none !important; }
}
</style>
@endpush

@section('setup_content')
@php
    // Faculty Setup page uses the same rule: system-sourced rows have no faculty.
    $systemSources = ['Sargam', 'Sargam 2.0'];
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Edit Map Faculty to Examination Drive" :showBack="true"
                 :backUrl="route('admin.coe.examination-drive.index')"
                 :items="['Setup', 'COE', 'Examination Management', 'Examination Drive', 'Map Faculty']" />

    <div class="edc-toolbar">
        <button type="button" class="edc-tool-btn" id="edcDownload">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" class="edc-tool-btn" id="edcPrint">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    {{-- Drive info card --}}
    <div class="edc-info-bar">
        <div class="edc-info-item">
            <span class="edc-info-label">Drive Name</span>
            <span class="edc-info-value">{{ $drive['name'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Term Name</span>
            <span class="edc-info-value">{{ $drive['term'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Course Name</span>
            <span class="edc-info-value">{{ $drive['course'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Academic Session</span>
            <span class="edc-info-value">{{ $drive['session'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Subject</span>
            <span class="edc-info-value">{{ $subjectTabs[$subjectPk] ?? '—' }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Status</span>
            <span class="edc-info-value">{{ $drive['status'] }}</span>
        </div>
    </div>

    {{-- One subject per page; switch subjects here. --}}
    @if (count($subjectTabs) > 1)
        <div class="d-flex flex-wrap gap-2 mb-3" role="tablist" aria-label="Subjects">
            @foreach ($subjectTabs as $pk => $name)
                <a href="{{ route('admin.coe.examination-drive.show', ['id' => $drive['id'], 'subject' => $pk]) }}"
                   class="btn {{ $pk === $subjectPk ? 'btn-primary' : 'btn-outline-primary' }}" role="tab"
                   aria-selected="{{ $pk === $subjectPk ? 'true' : 'false' }}">{{ $name }}</a>
            @endforeach
        </div>
    @endif

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="edc-comp-table" id="edcCompTable">
                    <thead>
                        <tr>
                            <th style="width:15%">Components<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:8%">Max Marks<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:8%">Passing Marks<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:11%">Source</th>
                            <th style="width:17%">Faculty</th>
                            <th style="width:8%">Weightage %<i class="bi bi-chevron-expand"></i></th>
                            <th style="width:13%">Exam Date</th>
                            <th style="width:11%">Exam Mode</th>
                            <th style="width:9%" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="edcCompBody">
                        @foreach ($drive['component_rows'] as $row)
                        @php($systemSource = in_array($row['source'], $systemSources, true))
                        <tr class="edc-comp-row" data-component="{{ $row['component'] }}">
                            <td>
                                <select class="form-select edc-sel-component">
                                    <option value="">Component</option>
                                    @foreach ($components as $c)
                                    <option value="{{ $c }}" @if($c === $row['component']) selected @endif>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" class="form-control edc-max" min="0" max="1000" step="0.01" value="{{ $row['max_marks'] }}"></td>
                            <td><input type="number" class="form-control edc-pass" min="0" max="1000" step="0.01" value="{{ $row['passing_marks'] }}"></td>
                            <td>
                                <select class="form-select edc-sel-source">
                                    @foreach ($sources as $s)
                                    <option value="{{ $s }}" @if($s === $row['source']) selected @endif>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select edc-sel-faculty" @disabled($systemSource)>
                                    <option value="">Faculty</option>
                                    @foreach ($faculty as $f)
                                    <option value="{{ $f }}" @if(! $systemSource && $f === $row['faculty']) selected @endif>{{ $f }}</option>
                                    @endforeach
                                    <option value="Sargam 2.0" @if($systemSource) selected @endif>Sargam 2.0</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control edc-weightage" min="0" max="100" step="0.01" value="{{ $row['weightage'] }}"></td>
                            <td><input type="date" class="form-control edc-exam-date" value="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $row['exam_date'])->format('Y-m-d') }}"></td>
                            <td>
                                <select class="form-select edc-sel-mode">
                                    @foreach ($examModes as $m)
                                    <option value="{{ $m }}" @if($m === $row['exam_mode']) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="edc-actions">
                                    <button type="button" class="edc-row-del" aria-label="Remove row"><i class="bi bi-dash" aria-hidden="true"></i></button>
                                    <button type="button" class="edc-row-add" aria-label="Add row"><i class="bi bi-plus" aria-hidden="true"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="edc-total-row">
                            <td colspan="5" class="text-end pe-4">Total Weightage:</td>
                            <td id="edcTotalWeightage" class="ps-3">{{ array_sum(array_column($drive['component_rows'], 'weightage')) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="edc-footer">
                <a href="{{ route('admin.coe.examination-drive.index') }}" class="btn coe-btn coe-btn-cancel">Cancel</a>
                <button type="button" class="btn coe-btn coe-btn-primary" id="edcSaveMap">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var COMPONENTS = @json($components);
    var SOURCES    = @json($sources);
    var FACULTY    = @json($faculty);
    var EXAM_MODES = @json($examModes);
    var SYSTEM_SOURCES = @json($systemSources);
    var SUBJECT_PK = @json($subjectPk);

    var $body = $('#edcCompBody');

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function options(list, sel) {
        return list.map(function (v) {
            return '<option value="' + esc(v) + '"' + (v === sel ? ' selected' : '') + '>' + esc(v) + '</option>';
        }).join('');
    }
    function facultyOptions(sel) {
        return '<option value="">Faculty</option>' + options(FACULTY, sel) +
            '<option value="Sargam 2.0"' + (sel === 'Sargam 2.0' ? ' selected' : '') + '>Sargam 2.0</option>';
    }

    function buildRow() {
        return '<tr class="edc-comp-row">' +
            '<td><select class="form-select edc-sel-component"><option value="">Component</option>' + options(COMPONENTS, '') + '</select></td>' +
            '<td><input type="number" class="form-control edc-max" min="0" max="1000" step="0.01" value="100"></td>' +
            '<td><input type="number" class="form-control edc-pass" min="0" max="1000" step="0.01" value="40"></td>' +
            '<td><select class="form-select edc-sel-source">' + options(SOURCES, SOURCES[0]) + '</select></td>' +
            '<td><select class="form-select edc-sel-faculty">' + facultyOptions('') + '</select></td>' +
            '<td><input type="number" class="form-control edc-weightage" min="0" max="100" step="0.01" value="0"></td>' +
            '<td><input type="date" class="form-control edc-exam-date" value=""></td>' +
            '<td><select class="form-select edc-sel-mode">' + options(EXAM_MODES, EXAM_MODES[0]) + '</select></td>' +
            '<td><div class="edc-actions">' +
            '<button type="button" class="edc-row-del" aria-label="Remove row"><i class="bi bi-dash" aria-hidden="true"></i></button>' +
            '<button type="button" class="edc-row-add" aria-label="Add row"><i class="bi bi-plus" aria-hidden="true"></i></button>' +
            '</div></td></tr>';
    }

    /* Total Weightage, live */
    function recalcTotal() {
        var total = 0;
        $body.find('.edc-weightage').each(function () { total += parseFloat($(this).val()) || 0; });
        $('#edcTotalWeightage').text(total % 1 === 0 ? total : total.toFixed(2));
    }
    $body.on('input', '.edc-weightage', recalcTotal);

    $body.on('click', '.edc-row-del', function () {
        if ($body.find('.edc-comp-row').length > 1) {
            $(this).closest('.edc-comp-row').remove();
            recalcTotal();
        }
    });
    $body.on('click', '.edc-row-add', function () {
        $(this).closest('.edc-comp-row').after(buildRow());
    });

    /* A system-sourced component (Sargam / Sargam 2.0) has no faculty. */
    $body.on('change', '.edc-sel-source', function () {
        var system = SYSTEM_SOURCES.indexOf($(this).val()) !== -1;
        var $fac = $(this).closest('.edc-comp-row').find('.edc-sel-faculty');
        if (system) {
            $fac.val('Sargam 2.0').prop('disabled', true);
        } else if ($fac.prop('disabled')) {
            $fac.prop('disabled', false).val('');
        }
    });

    if ($body.find('.edc-comp-row').length === 0) {
        $body.append(buildRow());
        recalcTotal();
    }

    function collectRows() {
        var rows = [];
        $body.find('.edc-comp-row').each(function () {
            var $r = $(this);
            rows.push({
                component: $r.find('.edc-sel-component').val(),
                max_marks: $r.find('.edc-max').val(),
                passing_marks: $r.find('.edc-pass').val(),
                source: $r.find('.edc-sel-source').val(),
                faculty: $r.find('.edc-sel-faculty').val(),
                weightage: $r.find('.edc-weightage').val(),
                exam_date: $r.find('.edc-exam-date').val(),
                exam_mode: $r.find('.edc-sel-mode').val()
            });
        });
        return rows;
    }

    /* Save. Sub-coordinators and OT codes are not sent from this page, so the
       server keeps the ones faculty set on Complete Setup. */
    $('#edcSaveMap').on('click', function () {
        $.ajax({
            url: '{{ url("admin/coe/examination-drive") }}/{{ (int) $drive["id"] }}/components',
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { subject_master_pk: SUBJECT_PK, rows: collectRows() }
        }).done(function () {
            Swal.fire({ icon: 'success', title: 'Saved', text: 'Component mapping saved.' });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save component mapping.' });
        });
    });

    $('#edcPrint').on('click', function () { window.print(); });

    /* Download: CSV of the table as currently shown. */
    $('#edcDownload').on('click', function () {
        var cell = function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; };
        var lines = [['Component', 'Max Marks', 'Passing Marks', 'Source', 'Faculty', 'Weightage %', 'Exam Date', 'Exam Mode'].map(cell).join(',')];
        collectRows().forEach(function (r) {
            lines.push([r.component, r.max_marks, r.passing_marks, r.source, r.faculty, r.weightage, r.exam_date, r.exam_mode].map(cell).join(','));
        });
        var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = @json('map-faculty-drive-' . $drive['id'] . '.csv');
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
    });
});
</script>
@endpush
