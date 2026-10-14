@extends('admin.layouts.master')

@section('title', 'Map Faculty – ' . $drive['name'])

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.edc-info-bar        { background:var(--ds-surface-2);border:1px solid var(--ds-line);border-radius:8px;padding:14px 20px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:20px; }
.edc-info-item       { display:flex;flex-direction:column;gap:2px; }
.edc-info-label      { font-size:11px;font-weight:600;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.5px; }
.edc-info-value      { font-size:13px;font-weight:600;color:var(--ds-ink); }
/* Component table */
.edc-comp-table      { width:100%;border-collapse:collapse; }
.edc-comp-table th   { padding:10px 12px;background:var(--ds-surface-2);font-size:12px;font-weight:700;color:var(--ds-ink-muted);border-bottom:2px solid var(--ds-line);white-space:nowrap; }
.edc-comp-table td   { padding:8px 8px;border-bottom:1px solid var(--ds-line);vertical-align:middle;font-size:13px; }
.edc-comp-table tr:last-child td { border-bottom:none; }
.edc-spin            { width:80px; }
.edc-row-btn         { background:transparent;border:1.5px solid currentColor;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;line-height:1;padding:0; }
.edc-row-del         { color:#dc3545; }
.edc-row-del:hover   { background:#dc3545;color:#fff; }
.edc-row-add         { color:#004384; }
.edc-row-add:hover   { background:#004384;color:#fff; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Edit Map Faculty to Examination Drive" :showBack="true"
                 :backUrl="route('admin.coe.examination-drive.index')"
                 :items="['Setup', 'COE', 'Examination Management', 'Examination Drive', 'Map Faculty']">
    </x-breadcrum>

    {{-- Drive info bar --}}
    <div class="edc-info-bar">
        <div class="edc-info-item">
            <span class="edc-info-label">Drive Name</span>
            <span class="edc-info-value">{{ $drive['name'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Course</span>
            <span class="edc-info-value">{{ $drive['course'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Term</span>
            <span class="edc-info-value">{{ $drive['term'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Session</span>
            <span class="edc-info-value">{{ $drive['session'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Status</span>
            <span class="edc-info-value">{{ $drive['status'] }}</span>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0" style="font-size:15px">Component Mapping</h6>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="edcSaveMap">
                    <i class="bi bi-floppy" aria-hidden="true"></i> Save
                </button>
            </div>
            <div class="table-responsive">
                <table class="edc-comp-table" id="edcCompTable">
                    <thead>
                        <tr>
                            <th style="width:14%">COMPONENTS</th>
                            <th style="width:9%">MAX MARKS</th>
                            <th style="width:9%">PASSING MARKS</th>
                            <th style="width:11%">SOURCE</th>
                            <th style="width:16%">FACULTY</th>
                            <th style="width:9%">WEIGHTAGE (%)</th>
                            <th style="width:11%">EXAM DATE</th>
                            <th style="width:11%">EXAM TYPE</th>
                            <th style="width:10%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="edcCompBody">
                        @foreach ($drive['component_rows'] as $row)
                        <tr class="edc-comp-row" data-component="{{ $row['component'] }}">
                            <td>
                                <select class="form-select form-select-sm edc-sel-component">
                                    <option value="">Component</option>
                                    @foreach ($components as $c)
                                    <option value="{{ $c }}" @if($c === $row['component']) selected @endif>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-sm edc-spin" min="0" max="1000" value="{{ $row['max_marks'] }}"></td>
                            <td><input type="number" class="form-control form-control-sm edc-spin" min="0" max="1000" step="0.01" value="{{ $row['passing_marks'] }}"></td>
                            <td>
                                <select class="form-select form-select-sm edc-sel-source">
                                    @foreach ($sources as $s)
                                    <option value="{{ $s }}" @if($s === $row['source']) selected @endif>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm edc-sel-faculty">
                                    <option value="">Faculty</option>
                                    @foreach ($faculty as $f)
                                    <option value="{{ $f }}" @if($f === $row['faculty']) selected @endif>{{ $f }}</option>
                                    @endforeach
                                    <option value="Sargam 2.0" @if($row['faculty'] === 'Sargam 2.0') selected @endif>Sargam 2.0</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-sm edc-spin" min="0" max="100" step="0.01" value="{{ $row['weightage'] }}"></td>
                            <td><input type="date" class="form-control form-control-sm" value="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $row['exam_date'])->format('Y-m-d') }}"></td>
                            <td>
                                <select class="form-select form-select-sm">
                                    @foreach ($examModes as $m)
                                    <option value="{{ $m }}" @if($m === $row['exam_type']) selected @endif>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <button type="button" class="edc-row-btn edc-row-del me-1" aria-label="Remove row"><i class="bi bi-dash" aria-hidden="true"></i></button>
                                <button type="button" class="edc-row-btn edc-row-add" aria-label="Add row"><i class="bi bi-plus" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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

    var $body = $('#edcCompBody');

    function componentOptions(sel) {
        return '<option value="">Component</option>' + COMPONENTS.map(function (c) {
            return '<option value="' + c + '"' + (c === sel ? ' selected' : '') + '>' + c + '</option>';
        }).join('');
    }
    function sourceOptions(sel) {
        return SOURCES.map(function (s) {
            return '<option value="' + s + '"' + (s === sel ? ' selected' : '') + '>' + s + '</option>';
        }).join('');
    }
    function facultyOptions(sel) {
        var opts = '<option value="">Faculty</option>' + FACULTY.map(function (f) {
            return '<option value="' + f + '"' + (f === sel ? ' selected' : '') + '>' + f + '</option>';
        }).join('');
        opts += '<option value="Sargam 2.0"' + (sel === 'Sargam 2.0' ? ' selected' : '') + '>Sargam 2.0</option>';
        return opts;
    }
    function modeOptions(sel) {
        return EXAM_MODES.map(function (m) {
            return '<option value="' + m + '"' + (m === sel ? ' selected' : '') + '>' + m + '</option>';
        }).join('');
    }

    function buildRow(data) {
        data = data || {};
        return '<tr class="edc-comp-row">' +
            '<td><select class="form-select form-select-sm edc-sel-component">' + componentOptions(data.component || '') + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm edc-spin" min="0" max="1000" value="' + (data.max || 100) + '"></td>' +
            '<td><input type="number" class="form-control form-control-sm edc-spin" min="0" max="1000" step="0.01" value="' + (data.pass || 40) + '"></td>' +
            '<td><select class="form-select form-select-sm edc-sel-source">' + sourceOptions(data.source || SOURCES[0]) + '</select></td>' +
            '<td><select class="form-select form-select-sm edc-sel-faculty">' + facultyOptions(data.faculty || '') + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm edc-spin" min="0" max="100" step="0.01" value="' + (data.weightage || 0) + '"></td>' +
            '<td><input type="date" class="form-control form-control-sm" value=""></td>' +
            '<td><select class="form-select form-select-sm">' + modeOptions(data.mode || EXAM_MODES[0]) + '</select></td>' +
            '<td><button type="button" class="edc-row-btn edc-row-del me-1" aria-label="Remove row"><i class="bi bi-dash" aria-hidden="true"></i></button>' +
            '<button type="button" class="edc-row-btn edc-row-add" aria-label="Add row"><i class="bi bi-plus" aria-hidden="true"></i></button></td>' +
            '</tr>';
    }

    $body.on('click', '.edc-row-del', function () {
        var $rows = $body.find('.edc-comp-row');
        if ($rows.length > 1) { $(this).closest('.edc-comp-row').remove(); }
    });
    $body.on('click', '.edc-row-add', function () {
        $(this).closest('.edc-comp-row').after(buildRow());
    });

    $('#edcSaveMap').on('click', function () {
        Swal.fire({ icon: 'success', title: 'Saved', text: 'Component mapping saved.' });
    });
});
</script>
@endpush
