@extends('admin.layouts.master')

@section('title', 'Result Records and Marks Moderation')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.mm-table th { white-space: normal; vertical-align: bottom; }
.mm-out { font-size: 11px; color: var(--ds-ink-muted, #7c7c7c); }
.coe-page .coe-state--not-moderated { background: #fff4e5; border-color: #fedf89; color: #c4320a; }
.coe-page .coe-state--absent { background: #e2e8f0; border-color: #cbd5e1; color: #475569; }
/* Moderated, but not saved (no moderation columns yet). */
.mm-unsaved { color: var(--coe-brand); font-weight: 600; }
</style>
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $out = fn ($v) => \App\Services\COE\ResultMarksService::number($v);
    $labels = ['pending' => 'Pending', 'absent' => 'Absent', 'not-moderated' => 'Not Moderated', 'moderated' => 'Moderated'];
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Result Records and Marks Moderation" :showBack="false"
                 :items="['Setup', 'COE', 'Result Management', 'Result Records and Marks Moderation']">
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.marks-moderation.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}" @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.marks-moderation.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}" @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            @unless ($isArchived)
                {{-- Ticked rows, else every Not Moderated row the filters show. --}}
                <button type="button" id="mmModerateBtn" class="btn coe-btn coe-btn-primary"
                        title="Moderate the ticked marks, or every Not Moderated mark shown">
                    <i class="bi bi-sliders" aria-hidden="true"></i><span>Moderate Marks</span>
                </button>
            @endunless
            <button type="button" id="mmDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="mmPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    @foreach (['Course' => $courses, 'Subject' => $subjects, 'Component' => $components] as $label => $options)
                        <div class="programme-dt-filter-select">
                            <select id="mmFilter{{ $label }}" class="form-select" data-placeholder="{{ $label }}" aria-label="{{ $label }}">
                                <option value="">{{ $label }}</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <button type="button" id="mmResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#mmColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="mmDtSearch" class="programme-dt-search d-none" data-dt-search-for="mmTable"></div>
                    <button type="button" id="mmSearchToggle" class="coe-search-toggle"
                            aria-controls="mmDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <p class="coe-muted small mb-3">
                Moderated per SOP COE/11: M<sub>i</sub> = (UM<sub>i</sub> − X<sub>j</sub>) × σ<sub>G</sub> / σ<sub>j</sub> + μ<sub>G</sub>,
                evaluator-wise over all OTs of the component.
                A component can be moderated once every OT's marks in it are approved.
                @unless ($moderationEnabled)
                    <strong>Moderated marks are shown but cannot be saved yet: the moderation columns are not on the marks table.</strong>
                @endunless
            </p>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="mmTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table coe-table--no-sno mm-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    <input type="checkbox" class="form-check-input m-0" id="mmCheckAll" aria-label="Select all rows">
                                </th>
                                <th scope="col">OT / Participant Name</th>
                                <th scope="col">OT Code</th>
                                <th scope="col">Course</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Component</th>
                                <th scope="col">Marks</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Marks Moderation</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $selectable = ! $isArchived && $row['status'] === 'not-moderated' && $row['preview'] !== null;
                                @endphp
                                <tr data-key="{{ $row['key'] }}" data-status="{{ $row['status'] }}"
                                    data-code="{{ $row['code'] }}" data-name="{{ $row['name'] }}" data-max="{{ $out($row['max']) }}">
                                    <td class="coe-col-check">
                                        @if ($selectable)
                                            <input type="checkbox" class="form-check-input m-0 mm-row-check" aria-label="Select {{ $row['code'] }}">
                                        @endif
                                    </td>
                                    <td class="coe-nowrap">{{ $row['name'] ?: '-' }}</td>
                                    <td>{{ $row['code'] ?: '-' }}</td>
                                    <td>{{ $row['course'] }}</td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td>{{ $row['component'] }}</td>
                                    <td class="coe-nowrap" data-order="{{ is_numeric($row['marks']) ? $row['marks'] : -1 }}">
                                        {{ is_numeric($row['marks']) ? $out($row['marks']) : ($row['marks'] ?? '-') }}<span class="mm-out"> / {{ $out($row['max']) }}</span>
                                    </td>
                                    <td>{{ $row['evaluator'] }}</td>
                                    <td class="coe-nowrap mm-moderated">
                                        @if ($row['moderated'] !== null)
                                            {{ $out($row['moderated']) }}<span class="mm-out"> / {{ $out($row['max']) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="coe-col-center">
                                        <span class="coe-state coe-state--{{ $row['status'] }}">{{ $labels[$row['status']] }}</span>
                                    </td>
                                    <td>
                                        @if ($selectable)
                                            <button type="button" class="coe-act coe-act--wide mm-moderate">
                                                <span class="coe-act__icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Moderate Marks</span>
                                            </button>
                                        @elseif (! $isArchived && in_array($row['status'], ['pending', 'not-moderated'], true))
                                            <span class="coe-act coe-act--wide is-disabled"
                                                  title="Every mark of this component must be approved by faculty first">
                                                <span class="coe-act__icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Moderate Marks</span>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="mmTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'mmColumnVisibilityModal', 'grid' => 'mmColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var COL = { check: 0, course: 3, subject: 4, component: 5, action: 10 };
    var $table = $('#mmTable');

    var dt = CoeGrid.init({
        table: '#mmTable',
        title: 'Result Records and Marks Moderation',
        snoCol: false,
        order: [[COL.course, 'asc'], [COL.subject, 'asc'], [COL.component, 'asc'], [2, 'asc']],
        noSort: [COL.check, COL.action],
        exclude: [COL.check, COL.action],
        rowCheck: '.mm-row-check',
        filters: { '#mmFilterCourse': COL.course, '#mmFilterSubject': COL.subject, '#mmFilterComponent': COL.component },
        reset: '#mmResetFilters',
        download: '#mmDownloadBtn',
        print: '#mmPrintBtn',
        searchToggle: '#mmSearchToggle',
        searchSlot: '#mmDtSearch',
        colvisGrid: '#mmColumnToggleGrid',
        colvisKey: 'coeMarksModeration:hiddenColumns:v1',
        empty: { title: 'No Marks', zeroTitle: 'No Marks Found',
                 text: 'Marks show here once faculty enter them for a component whose exam is over.' }
    });

    /* ── Selection: picks the rows the Moderate Marks button acts on ── */
    function filtered() { return dt.rows({ search: 'applied' }).nodes().to$(); }

    function syncSelection() {
        var $boxes = filtered().find('.mm-row-check');
        var n = $boxes.filter(':checked').length;
        $('#mmCheckAll')
            .prop('checked', n > 0 && n === $boxes.length)
            .prop('indeterminate', n > 0 && n < $boxes.length);
    }

    $('#mmCheckAll').on('change', function () {
        filtered().find('.mm-row-check').prop('checked', this.checked);
        syncSelection();
    });
    $table.on('change', '.mm-row-check', syncSelection);
    dt.on('search.dt', function () { setTimeout(syncSelection, 0); });

    /* ── Save ── */
    function save(keys) {
        return $.ajax({
            url: @json(route('admin.coe.marks-moderation.moderate')),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ keys: keys }),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function (res) {
            if (res.saved) {
                Swal.fire({ icon: 'success', title: 'Marks Moderated', text: res.message })
                    .then(function () { window.location.reload(); });
                return;
            }
            // Not saved (no moderation columns yet): show what moderation gives.
            $.each(res.values || {}, function (key, value) {
                var $tr = $table.find('tr').filter(function () { return $(this).data('key') === key; });
                $tr.find('.mm-moderated').html('<span class="mm-unsaved" title="Not saved">' +
                    $('<span>').text(value).html() + '</span><span class="mm-out"> / ' + $tr.data('max') + '</span>');
            });
            Swal.fire({ icon: 'warning', title: 'Marks Moderated', text: res.message });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Moderate Marks',
                text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save the moderated marks.' });
        });
    }

    // Global: the ticked rows, else every Not Moderated row the filters show.
    $('#mmModerateBtn').on('click', function () {
        var $rows = filtered().has('.mm-row-check:checked');
        var ticked = $rows.length > 0;
        if (!ticked) { $rows = filtered().has('.mm-row-check'); }
        if (!$rows.length) {
            Swal.fire({ icon: 'info', title: 'Moderate Marks', text: 'Nothing here is ready to moderate.' });
            return;
        }
        var keys = $rows.map(function () { return $(this).data('key'); }).get();
        Swal.fire({
            icon: 'question',
            title: 'Moderate Marks',
            text: (ticked ? keys.length + ' ticked' : 'All ' + keys.length + ' Not Moderated') +
                  ' mark(s) will be saved as moderated per the SOP formula. This cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Moderate'
        }).then(function (r) { if (r.isConfirmed) { save(keys); } });
    });

    // Per row: confirm, then moderate.
    $table.on('click', '.mm-moderate', function () {
        var $tr = $(this).closest('tr');
        Swal.fire({
            icon: 'question',
            title: 'Moderate Marks',
            text: 'Moderate the marks of ' + $tr.data('name') + ' (' + $tr.data('code') + ') per the SOP formula? This cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'OK'
        }).then(function (r) { if (r.isConfirmed) { save([$tr.data('key')]); } });
    });
});
</script>
@endpush
