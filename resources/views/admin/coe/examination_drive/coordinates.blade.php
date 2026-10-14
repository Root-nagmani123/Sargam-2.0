@extends('admin.layouts.master')

@section('title', 'Faculty Coordinates – ' . $drive['name'])

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.edc-info-bar        { background:var(--ds-surface-2);border:1px solid var(--ds-line);border-radius:8px;padding:14px 20px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:20px; }
.edc-info-item       { display:flex;flex-direction:column;gap:2px; }
.edc-info-label      { font-size:11px;font-weight:600;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.5px; }
.edc-info-value      { font-size:13px;font-weight:600;color:var(--ds-ink); }
/* Coordinates table */
.edc-coord-table     { width:100%;border-collapse:collapse; }
.edc-coord-table th  { padding:10px 14px;background:var(--ds-surface-2);font-size:12px;font-weight:700;color:var(--ds-ink-muted);border-bottom:2px solid var(--ds-line);white-space:nowrap; }
.edc-coord-table td  { padding:10px 14px;border-bottom:1px solid var(--ds-line);font-size:13px;vertical-align:middle; }
.edc-coord-table tr:last-child td { border-bottom:none; }
.edc-sub-link        { color:var(--ds-brand);font-weight:600;text-decoration:none;cursor:pointer; }
.edc-sub-link:hover  { text-decoration:underline; }
/* Assign Faculty modal tabs */
.edc-af-tabs         { display:flex;border-bottom:2px solid var(--ds-line);margin-bottom:18px; }
.edc-af-tab          { padding:8px 20px;font-size:13px;font-weight:600;color:var(--ds-ink-muted);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;user-select:none; }
.edc-af-tab.is-active{ color:var(--ds-brand);border-bottom-color:var(--ds-brand); }
/* Single Faculty search list */
.edc-fac-search      { position:relative;margin-bottom:10px; }
.edc-fac-search input{ width:100%;padding:7px 12px;border:1px solid var(--ds-line);border-radius:6px;font-size:13px; }
.edc-fac-list        { max-height:220px;overflow-y:auto;border:1px solid var(--ds-line);border-radius:6px; }
.edc-fac-item        { display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;font-size:13px; }
.edc-fac-item:hover  { background:var(--ds-surface-2); }
.edc-fac-item input[type=checkbox]{ width:16px;height:16px;accent-color:var(--ds-brand);flex-shrink:0; }
.edc-add-roll        { margin-top:10px;display:flex;gap:8px;align-items:center; }
.edc-add-roll input  { flex:1;padding:7px 12px;border:1px solid var(--ds-line);border-radius:6px;font-size:13px; }
/* Multi Faculty table */
.edc-multi-table     { width:100%;border-collapse:collapse; }
.edc-multi-table th  { padding:8px 10px;background:var(--ds-surface-2);font-size:12px;font-weight:600;color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line); }
.edc-multi-table td  { padding:7px 10px;border-bottom:1px solid var(--ds-line);font-size:13px;vertical-align:middle; }
.edc-multi-table .form-check-input{ width:16px;height:16px;accent-color:var(--ds-brand); }
/* Sub-faculty popover list */
.edc-sub-popover     { padding:10px 14px;min-width:180px; }
.edc-sub-popover li  { font-size:13px;padding:3px 0; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Faculty Coordinates &amp; Components" :showBack="true"
                 :backUrl="route('admin.coe.examination-drive.index')"
                 :items="['Setup', 'COE', 'Examination Management', 'Examination Drive', 'Faculty Coordinates']">
        <button type="button" class="btn coe-btn-header" id="edcAssignBtn">
            <i class="bi bi-person-plus" aria-hidden="true"></i><span>Assign Faculty</span>
        </button>
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
            <h6 class="fw-bold mb-3" style="font-size:15px">Faculty &amp; Sub-Coordinates per Component</h6>
            <div class="table-responsive">
                <table class="edc-coord-table">
                    <thead>
                        <tr>
                            <th style="width:5%">S.NO.</th>
                            <th style="width:22%">COMPONENT</th>
                            <th style="width:18%">FACULTY</th>
                            <th style="width:22%">SUB-COORDINATES (FACULTY)</th>
                            <th style="width:10%">EXAM DATE</th>
                            <th style="width:10%">EXAM TYPE</th>
                            <th style="width:13%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drive['component_rows'] as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $row['component'] }}</td>
                            <td>{{ $row['faculty'] }}</td>
                            <td>
                                @if(count($row['sub_faculty']) > 0)
                                    <a class="edc-sub-link"
                                       data-bs-toggle="popover"
                                       data-bs-html="true"
                                       data-bs-title="Sub-Coordinates"
                                       data-bs-content="<ul class='list-unstyled edc-sub-popover mb-0'>{{ implode('', array_map(fn($f) => '<li>'.$f.'</li>', $row['sub_faculty'])) }}</ul>"
                                       tabindex="0">
                                        {{ count($row['sub_faculty']) }} Sub-Coord{{ count($row['sub_faculty']) > 1 ? 's' : '' }}
                                    </a>
                                @else
                                    <span class="coe-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $row['exam_date'] }}</td>
                            <td>{{ $row['exam_type'] }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn coe-btn coe-btn--narrow coe-btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Open
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.coe.examination-drive.show', $drive['id']) }}">Map Faculty</a></li>
                                        <li><button type="button" class="dropdown-item edc-open-assign" data-component="{{ $row['component'] }}">Assign Faculty</button></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Assign Faculty modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="edcAssignModal" tabindex="-1" aria-labelledby="edcAssignModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header coe-modal-header--rule">
                <h5 class="modal-title" id="edcAssignModalLabel">Assign Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                {{-- Tabs --}}
                <div class="edc-af-tabs">
                    <div class="edc-af-tab is-active" data-tab="single">Single Faculty</div>
                    <div class="edc-af-tab" data-tab="multi">Multi Faculty</div>
                </div>

                {{-- Single Faculty pane --}}
                <div id="edcAfSingle">
                    <div class="edc-fac-search">
                        <input type="text" id="edcFacSearch" placeholder="Search faculty…" autocomplete="off">
                    </div>
                    <div class="edc-fac-list" id="edcFacList">
                        @foreach ($faculty as $f)
                        <label class="edc-fac-item">
                            <input type="checkbox" value="{{ $f }}">
                            <span>{{ $f }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="edc-add-roll mt-3">
                        <input type="text" id="edcRollInput" placeholder="Add Roll Number…" maxlength="20" autocomplete="off">
                        <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="edcAddRollBtn">Add</button>
                    </div>
                </div>

                {{-- Multi Faculty pane --}}
                <div id="edcAfMulti" class="d-none">
                    {{-- Code Share radio --}}
                    <div class="mb-3">
                        <span class="fw-semibold me-3" style="font-size:13px">Code Share:</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="edcCodeShare" id="edcCodeShareYes" value="yes">
                            <label class="form-check-label" for="edcCodeShareYes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="edcCodeShare" id="edcCodeShareNo" value="no" checked>
                            <label class="form-check-label" for="edcCodeShareNo">No</label>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="edc-multi-table">
                            <thead>
                                <tr>
                                    <th style="width:5%"></th>
                                    <th style="width:30%">FACULTY</th>
                                    <th>UNIQUE CODES OF OTs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($faculty as $f)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"></td>
                                    <td>{{ $f }}</td>
                                    <td><span class="coe-muted" style="font-size:12px">— (assign codes after selection)</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="edcAssignSave">Assign</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    /* Popovers */
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { trigger: 'click', sanitize: false });
    });

    /* Assign modal open */
    function openAssign(component) {
        if (component) {
            $('#edcAssignModalLabel').text('Assign Faculty – ' + component);
        }
        bootstrap.Modal.getOrCreateInstance($('#edcAssignModal')[0]).show();
    }
    $('#edcAssignBtn').on('click', function () { openAssign(''); });
    $(document).on('click', '.edc-open-assign', function () { openAssign($(this).data('component')); });

    /* Tab switching */
    $('.edc-af-tab').on('click', function () {
        $('.edc-af-tab').removeClass('is-active');
        $(this).addClass('is-active');
        var tab = $(this).data('tab');
        $('#edcAfSingle, #edcAfMulti').addClass('d-none');
        $('#edcAf' + tab.charAt(0).toUpperCase() + tab.slice(1)).removeClass('d-none');
    });

    /* Faculty search filter */
    $('#edcFacSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('#edcFacList .edc-fac-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) > -1);
        });
    });

    /* Add roll number */
    $('#edcAddRollBtn').on('click', function () {
        var roll = $.trim($('#edcRollInput').val());
        if (!roll) { return; }
        var $item = $('<label class="edc-fac-item"><input type="checkbox" value="' + roll + '" checked><span>' + $('<div>').text(roll).html() + '</span></label>');
        $('#edcFacList').prepend($item);
        $('#edcRollInput').val('');
    });

    /* Assign save */
    $('#edcAssignSave').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($('#edcAssignModal')[0]).hide();
        Swal.fire({ icon: 'success', title: 'Assigned', text: 'Faculty assigned to the component.' });
    });
});
</script>
@endpush
