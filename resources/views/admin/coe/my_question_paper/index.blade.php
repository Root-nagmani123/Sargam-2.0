@extends('admin.layouts.master')

@section('title', 'My Question Paper')

@push('styles')
{{-- COE question-paper layer (coe-*). Chrome is the shared programme-dt-* set;
     see docs/new-design-index-page.md and docs/design.md. --}}
{{-- Select2 JS is global; its CSS is per page (the filter selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $QP = \App\Http\Controllers\Admin\COE\MyQuestionPaperController::class;
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="My Question Paper" :showBack="false"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Question Paper']" />

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" id="qpDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="qpPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: Filter label + selects + Remove Filter · Columns + search (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="qpFilterDrive" class="form-select coe-filter"
                                data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $drive)
                                <option value="{{ $drive }}">{{ $drive }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpFilterBatch" class="form-select coe-filter"
                                data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="qpFilterStatus" class="form-select coe-filter"
                                data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            @foreach ($statusLabels as $label)
                                <option value="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="qpResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#qpColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here;
                         the icon button reveals it. --}}
                    <div id="qpDtSearch" class="programme-dt-search d-none" data-dt-search-for="qpTable"></div>
                    <button type="button" id="qpSearchToggle" class="coe-search-toggle"
                            aria-controls="qpDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="qpTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Question paper Code</th>
                                <th scope="col">Question Paper Name</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Faculty</th>
                                <th scope="col">Original Exam Paper</th>
                                <th scope="col">Translated Exam Paper</th>
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
                                    $status = $paper['status'];
                                    $isOverdue = $status === $QP::STATUS_PENDING && $paper['deadline']->isPast();
                                @endphp
                                <tr data-coe-id="{{ $paper['id'] }}"
                                    data-coe-code="{{ $paper['code'] }}"
                                    data-coe-name="{{ $paper['name'] }}"
                                    data-coe-state="{{ $status }}"
                                    data-coe-translated="{{ $paper['translated_file'] ? '1' : '0' }}"
                                    data-coe-translate-url="{{ in_array($status, [$QP::STATUS_PENDING, $QP::STATUS_TRANSLATED], true) ? route('admin.coe.my-question-paper.translate', $paper['id']) : '' }}">
                                    <td></td>
                                    <td>{{ $paper['code'] }}</td>
                                    <td>{{ $paper['name'] }}</td>
                                    <td class="coe-col-wrap">{{ $paper['drive'] }}</td>
                                    <td>{{ $paper['batch'] }}</td>
                                    <td data-order="{{ $paper['deadline']->timestamp }}">
                                        <span class="coe-deadline {{ $isOverdue ? 'is-overdue' : '' }}"
                                              @if ($isOverdue) title="Deadline has passed" @endif>
                                            {{ $paper['deadline']->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>{{ $paper['faculty'] }}</td>
                                    <td>
                                        <a href="#" role="button" class="coe-attach" data-coe-view="original">See Attachment</a>
                                    </td>
                                    <td class="coe-cell-translated">
                                        @if ($paper['translated_file'])
                                            <a href="#" role="button" class="coe-attach" data-coe-view="translated">See Attachment</a>
                                        @else
                                            <span class="coe-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="coe-col-center coe-cell-status">
                                        <span class="coe-state coe-state--{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                                    </td>
                                    <td class="coe-cell-actions"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills in the pager and
                     "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="qpTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Send for Approval (shared) --}}
@include('admin.coe.partials.confirm_approval_modal')

{{-- View Original / Translated Paper — read-only, no download (shared) --}}
@include('admin.coe.partials.paper_viewer_modal')

{{-- Column Visibility --}}
<div class="modal fade" id="qpColumnVisibilityModal" tabindex="-1" aria-labelledby="qpColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="qpColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="qpColumnToggleGrid"></div>
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
<script src="{{ asset('js/coe-paper-viewer.js') }}?v={{ @filemtime(public_path('js/coe-paper-viewer.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, drive: 3, batch: 4, status: 9, action: 10 };

    var $table = $('#qpTable');

    /* ── Row actions per state — the ONE place they are defined ─────────── */
    // [class, icon, label, href, view]: href 'translate' → the row's Translate
    // Question Paper page (data-coe-translate-url), none → a button. view:
    // 'original' / 'translated' → that paper in the read-only viewer, no download.
    var VIEW_ORIGINAL = ['coe-act--download', 'bi-eye', 'View Original Paper', null, 'original'];
    var VIEW_TRANSLATED = ['coe-act--download', 'bi-eye', 'View Translated Paper', null, 'translated'];
    var ACTIONS = {
        'pending': [VIEW_ORIGINAL, ['coe-act--create', 'bi-plus-lg', 'Translate Paper', 'translate']],
        'translated': [
            VIEW_ORIGINAL,
            ['coe-act--create', 'bi-pencil', 'Edit Translated Paper', 'translate'],
            ['coe-act--approve', 'bi-send-check', 'Send for Approval']
        ],
        'sent': [VIEW_ORIGINAL, VIEW_TRANSLATED],
        'published': [VIEW_ORIGINAL, VIEW_TRANSLATED]
    };

    function act(a, $row) {
        var href = a[3] === 'translate' ? $row.attr('data-coe-translate-url') : a[3];
        var $el = href ? $('<a></a>').attr('href', href) : $('<button type="button"></button>');
        if (a[4]) { $el.attr('data-coe-view', a[4]); }
        return $el.addClass('coe-act ' + a[0]).append(
            '<span class="coe-act__icon"><i class="bi ' + a[1] + '" aria-hidden="true"></i></span>',
            $('<span class="coe-act__label"></span>').text(a[2])
        ).prop('outerHTML');
    }

    function paintActions($row) {
        var list = (ACTIONS[$row.attr('data-coe-state')] || []).filter(function (a) {
            if (a[3] === 'translate') { return !!$row.attr('data-coe-translate-url'); }
            return a[4] !== 'translated' || $row.attr('data-coe-translated') === '1';
        });
        $row.find('.coe-cell-actions').html(
            '<div class="coe-act-group" role="group" aria-label="Row actions">' +
            list.map(function (a) { return act(a, $row); }).join('') + '</div>'
        );
    }

    // Paint before DataTables reads the DOM.
    $table.find('tbody tr').each(function () { paintActions($(this)); });

    /* DataTable, S. No., exports, filters, search toggle and column picker —
       shared with the other COE grids (public/js/coe-grid.js). */
    var dt = CoeGrid.init({
        table: '#qpTable',
        title: 'My Question Paper',
        order: [[5, 'asc']],                                // nearest deadline first
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#qpFilterDrive': COL.drive, '#qpFilterBatch': COL.batch, '#qpFilterStatus': COL.status },
        reset: '#qpResetFilters',
        download: '#qpDownloadBtn',
        print: '#qpPrintBtn',
        searchToggle: '#qpSearchToggle',
        searchSlot: '#qpDtSearch',
        colvisGrid: '#qpColumnToggleGrid',
        colvisKey: 'coeMyQuestionPaper:hiddenColumns:v1',
        empty: { title: 'No Question Papers', zeroTitle: 'No Question Papers Found', text: 'No question paper has been assigned to you yet.' }
    });

    function rowLabel($row) {
        return $row.data('coe-code') + ' — ' + $row.data('coe-name');
    }

    /* ── Row actions → server ────────────────────────────────────────────────
       Every change is sent first; on success the page reloads, so a row is
       always repainted from the database, never from the client alone. */
    var URLS = {
        original: @json(route('admin.coe.my-question-paper.original', ['paper' => '__PAPER__'])),
        translated: @json(route('admin.coe.my-question-paper.translated', ['paper' => '__PAPER__'])),
        send: @json(route('admin.coe.my-question-paper.send-for-approval', ['paper' => '__PAPER__']))
    };

    function url(name, $row) { return URLS[name].replace('__PAPER__', $row.attr('data-coe-id')); }

    // See Attachment / View … Paper: read-only viewer, no download.
    $table.on('click', '[data-coe-view]', function (e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        var which = $(this).attr('data-coe-view');
        CoePaperViewer.open({
            url: url(which, $row),
            title: rowLabel($row),
            subtitle: which === 'original' ? 'Original Paper' : 'Translated Paper (Rajbhasha)'
        });
    });

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

    function errorAlert(message) { Swal.fire({ icon: 'error', title: 'Error', text: message }); }

    function run(request) {
        request.then(successThenReload, errorAlert);
        return request;
    }

    /* ── Send for Approval — shared modal behaviour (coe-grid.js) ── */
    CoeGrid.confirm({
        table: '#qpTable',
        trigger: '.coe-act--approve',
        modal: '#qpApprovalModal',
        confirm: '#qpApprovalConfirm',
        onConfirm: function ($row) { return run(send('POST', url('send', $row))); }
    });
});
</script>
@endpush
