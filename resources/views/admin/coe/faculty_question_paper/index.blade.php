@extends('admin.layouts.master')

@section('title', 'My Question Paper')

@push('styles')
{{-- Select2 JS is global; its CSS is per page (the filter selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE question-paper layer (coe-*), shared with the other COE grids. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="My Question Paper" :showBack="false"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Question Paper']" />

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" id="qpfDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="qpfPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="qpfFilterDrive" class="form-select" data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $drive)
                                <option value="{{ $drive }}">{{ $drive }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpfFilterBatch" class="form-select" data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpfFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            @foreach ($statusLabels as $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="qpfResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#qpfColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="qpfDtSearch" class="programme-dt-search d-none" data-dt-search-for="qpfTable"></div>
                    <button type="button" id="qpfSearchToggle" class="coe-search-toggle"
                            aria-controls="qpfDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="qpfTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    <input type="checkbox" class="form-check-input m-0" id="qpfCheckAll"
                                           aria-label="Select all rows">
                                </th>
                                <th scope="col">S. No.</th>
                                <th scope="col">Question paper Code</th>
                                <th scope="col">Question Paper Name</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Max Marks</th>
                                <th scope="col">Exam Date</th>
                                <th scope="col">Original Exam Paper</th>
                                <th scope="col">Translated Exam Paper</th>
                                <th scope="col">Translated By (Rajbhasha)</th>
                                <th scope="col">Comments</th>
                                <th scope="col">Version</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state.
                             The Action cell is painted by the script from data-coe-state, so
                             the buttons for each state are defined in exactly one place. --}}
                        <tbody>
                            @foreach ($papers as $paper)
                                @php
                                    // Exam day reached and the paper is still with the faculty.
                                    $isOverdue = $paper['status'] === 'pending-faculty'
                                        && $paper['exam_date']->isPast();
                                @endphp
                                <tr data-coe-id="{{ $paper['id'] }}"
                                    data-coe-code="{{ $paper['code'] }}"
                                    data-coe-name="{{ $paper['name'] }}"
                                    data-coe-state="{{ $paper['status'] }}"
                                    data-coe-version="{{ $paper['version'] }}"
                                    data-coe-translated="{{ $paper['translated_file'] ? '1' : '0' }}"
                                    data-coe-create-url="{{ $paper['editable'] ? route('admin.coe.faculty-question-paper.create', $paper['id']) : '' }}"
                                    data-coe-original-url="{{ $paper['original_file'] ? route('admin.coe.faculty-question-paper.original', $paper['id']) : '' }}"
                                    data-coe-translated-url="{{ $paper['translated_file'] ? route('admin.coe.faculty-question-paper.translated', $paper['id']) : '' }}">
                                    <td class="coe-col-check">
                                        <input type="checkbox" class="form-check-input m-0 qpf-row-check"
                                               aria-label="Select {{ $paper['name'] }}">
                                    </td>
                                    <td></td>
                                    <td>{{ $paper['code'] }}</td>
                                    <td>{{ $paper['name'] }}</td>
                                    <td class="coe-col-wrap">{{ $paper['drive'] }}</td>
                                    <td>{{ $paper['batch'] }}</td>
                                    <td>{{ $paper['max_marks'] }}</td>
                                    <td data-order="{{ $paper['exam_date']->timestamp }}">
                                        <span class="coe-deadline {{ $isOverdue ? 'is-overdue' : '' }}"
                                              @if ($isOverdue) title="Exam date reached — paper not yet submitted" @endif>
                                            {{ $paper['exam_date']->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="coe-cell-original">
                                        @if ($paper['original_file'])
                                            <a href="{{ route('admin.coe.faculty-question-paper.original', $paper['id']) }}" class="coe-attach" title="{{ $paper['original_file'] }}">See Attachment</a>
                                        @else
                                            <span class="coe-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($paper['translated_file'])
                                            <a href="{{ route('admin.coe.faculty-question-paper.translated', $paper['id']) }}" class="coe-attach" title="{{ $paper['translated_file'] }}">See Attachment</a>
                                        @else
                                            <span class="coe-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{!! $paper['translated_by'] ? e($paper['translated_by']) : '<span class="coe-muted">-</span>' !!}</td>
                                    <td class="coe-col-comment">{!! $paper['comments'] ? e($paper['comments']) : '<span class="coe-muted">-</span>' !!}</td>
                                    <td class="coe-cell-version">V{{ $paper['version'] }}</td>
                                    <td class="coe-col-center coe-cell-status">
                                        <span class="coe-state coe-state--{{ $paper['status'] }}">{{ $statusLabels[$paper['status']] ?? ucfirst($paper['status']) }}</span>
                                    </td>
                                    <td class="coe-cell-actions"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills it (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="qpfTable"></div>
            </div>

        </div>
    </div>
</div>

@include('admin.coe.partials.freeze_modal', ['phoneMasked' => $otpPhoneMasked])

{{-- Column Visibility --}}
<div class="modal fade" id="qpfColumnVisibilityModal" tabindex="-1" aria-labelledby="qpfColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="qpfColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="qpfColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { check: 0, sno: 1, drive: 4, batch: 5, examDate: 7, status: 13, action: 14 };
    var $table = $('#qpfTable');

    /* ── Row actions per state — the ONE place they are defined ─────────── */
    // href: null → a button; '#' → an inert link; 'create' / 'original' /
    // 'translated' → the row's own URL (data-coe-*-url); otherwise a real link.
    function act(cls, icon, label, href) {
        var $el = href ? $('<a></a>').attr('href', href) : $('<button type="button"></button>');
        return $el.addClass('coe-act ' + cls).append(
            '<span class="coe-act__icon"><i class="bi ' + icon + '" aria-hidden="true"></i></span>',
            $('<span class="coe-act__label"></span>').text(label)
        ).prop('outerHTML');
    }

    var ACTIONS = {
        // Opens the Add Question Paper page (written from a template).
        'pending-faculty': [
            ['coe-act--create coe-act--wide', 'bi-plus-lg', 'Create Original Paper', 'create']
        ],
        // Added: straight to Rajbhasha, no freeze. Editable (next version) until
        // Rajbhasha uploads the translation - then the row has no create URL.
        'pending-rajbhasha': [
            ['coe-act--create coe-act--wide', 'bi-pencil-square', 'Edit Original Paper', 'create']
        ],
        // Pending Verification: Rajbhasha sent the translation back; Freeze (OTP) publishes it.
        'translated': [
            ['coe-act--download', 'bi-download', 'Download Original Paper', 'original'],
            ['coe-act--download', 'bi-translate', 'Download Translated Paper', 'translated'],
            ['coe-act--freeze', 'bi-snow', 'Freeze']
        ],
        'published': [
            ['coe-act--download', 'bi-download', 'Download Original Paper', 'original'],
            ['coe-act--download', 'bi-translate', 'Download Translated Paper', 'translated']
        ]
    };

    function paintActions($row) {
        var list = (ACTIONS[$row.attr('data-coe-state')] || []).filter(function (a) {
            return a[3] !== 'create' || $row.attr('data-coe-create-url');   // locked: no Add / Edit
        });
        $row.find('.coe-cell-actions').html(
            '<div class="coe-act-group" role="group" aria-label="Row actions">' +
            list.map(function (a) {
                var href = ['create', 'original', 'translated'].indexOf(a[3]) !== -1
                    ? ($row.attr('data-coe-' + a[3] + '-url') || '#')
                    : a[3];
                return act(a[0], a[1], a[2], href);
            }).join('') + '</div>'
        );
    }

    // Paint before DataTables reads the DOM.
    $table.find('tbody tr').each(function () { paintActions($(this)); });

    var dt = CoeGrid.init({
        table: '#qpfTable',
        title: 'My Question Paper',
        snoCol: COL.sno,
        order: [[COL.examDate, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        rowCheck: '.qpf-row-check',
        filters: { '#qpfFilterDrive': COL.drive, '#qpfFilterBatch': COL.batch, '#qpfFilterStatus': COL.status },
        reset: '#qpfResetFilters',
        download: '#qpfDownloadBtn',
        print: '#qpfPrintBtn',
        searchToggle: '#qpfSearchToggle',
        searchSlot: '#qpfDtSearch',
        colvisGrid: '#qpfColumnToggleGrid',
        colvisKey: 'coeFacultyQuestionPaper:hiddenColumns:v1',
        empty: { title: 'No Question Papers', zeroTitle: 'No Question Papers Found', text: 'No question paper has been assigned to you yet.' }
    });

    /* ── Row selection ───────────────────────────────────────────────────────
       "Select all" ticks every row the current filters/search let through, not
       just the visible page. Download / Print take only the ticked rows, if any. */
    function syncSelection() {
        var $all = dt.rows({ search: 'applied' }).nodes().to$();
        var n = $all.find('.qpf-row-check:checked').length;
        $('#qpfCheckAll')
            .prop('checked', n > 0 && n === $all.length)
            .prop('indeterminate', n > 0 && n < $all.length);
    }

    $('#qpfCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.qpf-row-check').prop('checked', this.checked);
        syncSelection();
    });
    $table.on('change', '.qpf-row-check', syncSelection);
    dt.on('search.dt', function () { setTimeout(syncSelection, 0); });

    // Links with no file behind them yet (the translated paper) stay inert.
    $table.on('click', 'a.coe-act--download[href="#"], a.coe-attach[href="#"]', function (e) { e.preventDefault(); });

    /* ── Row actions → server ────────────────────────────────────────────────
       Every change is sent first; on success the page reloads, so a row is
       always repainted from the database, never from the client alone. */
    var URLS = {
        freezeOtp: @json(route('admin.coe.faculty-question-paper.freeze-otp', ['paper' => '__PAPER__'])),
        freeze: @json(route('admin.coe.faculty-question-paper.freeze', ['paper' => '__PAPER__']))
    };

    function url(name, $row) { return URLS[name].replace('__PAPER__', $row.attr('data-coe-id')); }

    function errorText(xhr) {
        var r = (xhr && xhr.responseJSON) || {};
        var keys = r.errors ? Object.keys(r.errors) : [];
        return keys.length ? r.errors[keys[0]][0] : (r.message || 'Something went wrong. Please try again.');
    }

    // Resolves with the JSON response; rejects with the server's message.
    function send(method, href, data) {
        var isForm = data instanceof FormData;
        var d = $.Deferred();
        $.ajax({
            url: href,
            method: method,
            data: data || {},
            processData: !isForm,
            contentType: isForm ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function (res) { d.resolve(res || {}); })
          .fail(function (xhr) { d.reject(errorText(xhr)); });
        return d.promise();
    }

    function successThenReload(res) {
        Swal.fire({ icon: 'success', title: 'Success', text: res.message || 'Done.' })
            .then(function () { window.location.reload(); });
    }

    // Freeze (Pending Verification only) publishes the translation. The OTP is
    // sent when the modal opens and on Resend; errors show under the boxes.
    CoeGrid.freeze({
        table: '#qpfTable',
        trigger: '.coe-act--freeze',
        onOpen: function ($row) { return send('POST', url('freezeOtp', $row)); },
        onResend: function ($row) { return send('POST', url('freezeOtp', $row)); },
        onConfirm: function ($row, otp) {
            var request = send('POST', url('freeze', $row), { otp: otp });
            request.then(successThenReload);
            return request;
        }
    });
});
</script>
@endpush
