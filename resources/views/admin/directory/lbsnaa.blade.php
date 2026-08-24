@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory')

@push('styles')
{{-- Select2 for the two filters — 54 sections and 125 designations are more than
     a plain <select> can be scanned. Only the CSS is per-page; select2.full.min.js
     is already global (footer.blade.php). select2-theme.css MUST come second. --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">

{{-- Directory module chrome — the same file the OT Directory uses, so the two
     pages cannot drift apart. --}}
<link rel="stylesheet"
      href="{{ asset('css/directory-admin.css') }}?v={{ @filemtime(public_path('css/directory-admin.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid dir-page">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    {{-- Nothing to filter by status on this grid, so the row above the card keeps
         only Print + Download, right-aligned (§1). --}}
    {{-- ?section / ?designation / ?q / ?sort / ?dir / ?cols are stamped on every
         link by lbsUpdateExportLinks(), so a download or a printout carries the
         filters, search term, ordering and columns the grid is showing. Print is
         a server-rendered view, not window.print() on this page. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 dir-secondary-actions">
        <a href="{{ route('admin.directory.lbsnaa.export', ['format' => 'print']) }}"
           id="lbsPrintLink" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>

        <div class="dropdown">
            <button type="button" id="lbsDownloadToggle"
                    class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2"
                aria-labelledby="lbsDownloadToggle">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="lbsCsvLink"
                       href="{{ route('admin.directory.lbsnaa.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
                        <span>Download CSV</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="lbsExcelLink"
                       href="{{ route('admin.directory.lbsnaa.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel text-success" aria-hidden="true"></i>
                        <span>Download Excel</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="lbsPdfLink"
                       href="{{ route('admin.directory.lbsnaa.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                        <span>Download PDF</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-2"></li>
                <li>
                    {{-- Below the divider because it is a different KIND of export:
                         every column, whatever the Columns modal says. --}}
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="lbsFullLink"
                       href="{{ route('admin.directory.lbsnaa.export', ['format' => 'full']) }}">
                        <i class="bi bi-database text-secondary" aria-hidden="true"></i>
                        <span>Full Details (Excel)</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters left, columns + search right (§2). The search slot
                 is left empty on purpose — datatable-global-ui.js moves DataTables'
                 own filter into it, so the grid filters as you type. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar dir-toolbar--two-filters">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select id="lbsSection" class="form-select" aria-label="Section">
                            <option value="">Section</option>
                            @foreach($sections as $pk => $name)
                                <option value="{{ $pk }}" title="{{ $name }}" @selected((int) $selectedSection === (int) $pk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="lbsDesignation" class="form-select" aria-label="Designation">
                            <option value="">Designation</option>
                            @foreach($designations as $pk => $name)
                                <option value="{{ $pk }}" title="{{ $name }}" @selected((int) $selectedDesignation === (int) $pk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="lbsReset">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="lbsBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#lbsColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="lbsDtSearch" class="programme-dt-search" data-dt-search-for="lbsnaaDirectoryTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL. This page
                         used to render all 443 active employees into the markup and
                         let a client-side DataTable paginate them — see §9. --}}
                    <table id="lbsnaaDirectoryTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table dir-grid--wide">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                {{-- Photo + Name are one identity column. --}}
                                <th scope="col" class="dir-col-identity">Name</th>
                                <th scope="col" class="dir-col-text">Designation</th>
                                <th scope="col" class="dir-col-text">Section</th>
                                <th scope="col" class="dir-col-address">Address</th>
                                <th scope="col" class="dir-col-tight">Office Ext.</th>
                                <th scope="col" class="dir-col-tight">Mobile</th>
                                <th scope="col" class="dir-col-tight">Residence</th>
                                <th scope="col" class="dir-col-email">Email ID</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates, datatable-global-ui.js
                     fills this in with the pager and "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="lbsnaaDirectoryTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="lbsColumnVisibilityModal" tabindex="-1" aria-labelledby="lbsColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="lbsColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="lbsnaaColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    /* DataTable (server-side) -----------------------------------------------
       Search, sort, paging and the footer are DataTables', and every one is
       answered by the server: a draw fetches only the page being shown.
       `sargamServerOrder` keeps ordering on the server too, so a header click
       re-sorts the WHOLE directory rather than the visible page. */
    var dt = $('#lbsnaaDirectoryTable').DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        // dataTables.responsive is loaded globally (footer.blade.php) and, with
        // nine columns, it stamps dtr-hidden + display:none on the tail rather
        // than scrolling — Residence and Email ID vanished with no details
        // renderer to reach them. Off, so .table-responsive scrolls instead
        // (that is also why .dir-grid--wide gives the table a width floor).
        responsive: false,
        searching: true,
        // 400ms after the last keystroke — search as you type, one query per pause.
        searchDelay: 400,
        order: [[1, 'asc']],
        ajax: {
            url: '{{ route('admin.directory.lbsnaa.data') }}',
            data: function (d) {
                // The two filters aren't DataTables state, so they ride along on
                // every draw.
                d.section = $('#lbsSection').val() || '';
                d.designation = $('#lbsDesignation').val() || '';
            }
        },
        /* `name` is the sort key the controller whitelists
           (LBSNAA_SORTABLE_COLUMNS); an empty one means "not sortable in SQL".
           Address and Residence are free-text with no index worth ordering on. */
        columns: [
            { data: 'sno', name: '', orderable: false, searchable: false },
            { data: 'identity', name: 'name' },
            { data: 'designation', name: 'designation', className: 'dir-col-wrap dir-col-text' },
            { data: 'section', name: 'section', className: 'dir-col-wrap dir-col-text' },
            { data: 'address', name: '', orderable: false, className: 'dir-col-wrap dir-col-address' },
            { data: 'office_ext', name: '', orderable: false, searchable: false },
            { data: 'mobile', name: 'mobile' },
            { data: 'residence', name: '', orderable: false },
            { data: 'email', name: 'email', className: 'dir-col-wrap dir-col-email' }
        ],
        language: {
            emptyTable: '<div class="dir-empty">' +
                '<i class="bi bi-people d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Staff Found</h6>' +
                '<p class="mb-0 small">No active employee matches these filters.</p>' +
                '</div>',
            zeroRecords: '<div class="dir-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Staff Found</h6>' +
                '<p class="mb-0 small">No employee matches your search.</p>' +
                '</div>'
        }
    });

    /* An image already in the browser cache can finish before its inline onload
       is live, which would leave a good photo sitting at opacity 0 over the
       initials. Settle those after every draw — the rows are replaced each time. */
    dt.on('draw.dt', function () {
        $('#lbsnaaDirectoryTable').find('.dir-avatar__img').each(function () {
            if (!this.complete) { return; }
            if (this.naturalWidth > 0) { this.classList.add('is-loaded'); } else { $(this).remove(); }
        });
    });

    /* Filters ----------------------------------------------------------------
       Select2 signals a pick with jQuery's .trigger('change'), which only reaches
       jQuery-bound handlers — so these listeners must stay jQuery-bound, not
       addEventListener. */
    if ($.fn.select2) {
        $('#lbsSection, #lbsDesignation').select2({
            width: '100%',
            minimumResultsForSearch: 10,
            // The slots are 150px so the toolbar stays on one row; the dropdown
            // panel is free to be wider than its control (see .dir-dropdown-wide).
            dropdownCssClass: 'dir-dropdown-wide',
        });
    }

    $('#lbsSection, #lbsDesignation').on('change', function () {
        dt.ajax.reload(null, true);   // back to page 1
        lbsUpdateExportLinks();
    });

    $('#lbsReset').on('click', function () {
        var $filters = $('#lbsSection, #lbsDesignation').val('');
        if ($.fn.select2) { $filters.trigger('change.select2'); }
        dt.search('').order([[1, 'asc']]).page.len(10).ajax.reload(null, true);
        lbsUpdateExportLinks();
    });

    /* Column visibility (DataTables column API) ------------------------------
       Stored by LABEL, not index — an index points at a different column the
       moment one is added, silently hiding the wrong one. */
    var COLVIS_KEY = 'sargam.lbsnaaDirectory.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    /* Header index -> the export column keys that header covers, for ?cols=.
       The identity column shows Photo + Name; Photo is never exported, so it
       contributes no key of its own.
       Positional: adding a table column means adding an entry here too. */
    var EXPORT_COLUMN_KEYS = [
        ['sno'],
        ['name'],
        ['designation'],
        ['section'],
        ['address'],
        ['office_ext'],
        ['mobile'],
        ['residence'],
        ['email']
    ];
    var EXPORT_COL_COUNT = EXPORT_COLUMN_KEYS.reduce(function (n, keys) { return n + keys.length; }, 0);

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COLVIS_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];   // private mode / storage disabled / corrupt value
        }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COLVIS_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    /* Keep every export link carrying exactly what the grid is showing: both
       filters, the search term (DataTables holds it, the export reads ?q), the
       ordering, and the columns still visible. */
    function lbsUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var cols = EXPORT_COLUMN_KEYS[this.index()] || [];
            if (this.visible()) { cols.forEach(function (key) { keys.push(key); }); }
        });

        var order = dt.order()[0] || [1, 'asc'];
        var column = dt.settings()[0].aoColumns[order[0]];
        var sortKey = (column && column.sName) || '';
        var term = dt.search() || '';
        var section = $('#lbsSection').val() || '';
        var designation = $('#lbsDesignation').val() || '';

        // Full Details is deliberately NOT column-filtered — it is the everything
        // dump, so it gets the same scope but never a ?cols=.
        ['lbsCsvLink', 'lbsExcelLink', 'lbsPdfLink', 'lbsPrintLink', 'lbsFullLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var params = new URLSearchParams();
            if (section) { params.set('section', section); }
            if (designation) { params.set('designation', designation); }
            if (term !== '') { params.set('q', term); }
            if (sortKey !== '') { params.set('sort', sortKey); params.set('dir', order[1]); }
            // Omit ?cols= while nothing is hidden — the server reads that as "all".
            if (id !== 'lbsFullLink' && keys.length !== EXPORT_COL_COUNT) {
                params.set('cols', keys.join(','));
            }
            var qs = params.toString();
            link.href = link.href.split('?')[0] + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type and re-ordering have to re-stamp the links, not just
    // redraw the grid.
    dt.on('search.dt order.dt', lbsUpdateExportLinks);

    function buildColumnToggles() {
        var $grid = $('#lbsnaaColumnToggleGrid');
        var hidden = getHiddenCols();

        dt.columns().every(function () {
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (title) { this.visible(hidden.indexOf(title) === -1, false); }
        });
        dt.columns.adjust();

        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var index = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'lbsColvis' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(title);
                }
                persistHiddenCols(cols);
                dt.column(index).visible(this.checked, false);
                dt.columns.adjust();
                lbsUpdateExportLinks();
            });

            // Built as jQuery objects, not concatenated HTML — .text() escapes.
            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr({ 'for': inputId, title: title })
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();
    // Stamp the restored column state onto the export links on first paint too.
    lbsUpdateExportLinks();
});
</script>
@endpush
