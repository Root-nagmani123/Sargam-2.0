{{--
    Shared Guest / In-House faculty listing table.

    Required data:
      $faculties    - collection of faculty rows
      $tableId      - unique DOM id for the table (e.g. "guess_faculty")
      $cssFile      - page-specific stylesheet path (asset())
      $cardClass    - card wrapper modifier class
      $pageTitle    - breadcrumb / heading title
      $exportTitle  - title used for export + print output
      $badgeClass   - faculty-type badge modifier class
      $badgeLabel   - faculty-type badge text
      $emptyMessage - message shown when there are no rows
--}}
<link rel="stylesheet" href="{{ asset($cssFile) }}">

@push('styles')
<style>
    /* ---- Faculty table toolbar (shared Guest / In-House) ---- */
    .dt-external-toolbar {
        background: #fff;
        border: 1px solid #eceef1;
        border-radius: .75rem;
        padding: .625rem .75rem;
        row-gap: .5rem;
    }

    /* Native length + search, relocated into the toolbar */
    .dt-external-toolbar .dataTables_length,
    .dt-external-toolbar .dataTables_filter {
        margin: 0;
    }
    .dt-external-toolbar .dataTables_length label,
    .dt-external-toolbar .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        margin: 0;
        font-size: .8125rem;
        font-weight: 500;
        color: #6c757d;
    }
    .dt-external-toolbar .dataTables_length select.form-select {
        width: auto;
        min-width: 4.75rem;
        border-radius: .5rem;
    }
    .dt-external-toolbar .dataTables_filter input.form-control {
        min-width: 230px;
        border-radius: .5rem;
        padding-left: 2rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23adb5bd'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: .6rem center;
        background-size: .85rem;
    }
    .dt-external-toolbar .dataTables_filter input.form-control:focus,
    .dt-external-toolbar .dataTables_length select.form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
    }

    /* Unified toolbar buttons: custom Columns dropdown + DataTables export/print */
    .dt-external-toolbar .dt-toolbar-right .btn,
    .dt-external-toolbar .dt-buttons .dt-button {
        --bs-btn-padding-y: .375rem;
        --bs-btn-padding-x: .75rem;
        --bs-btn-font-size: .8125rem;
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        margin: 0;
        border-radius: .5rem;
        font-weight: 500;
        background-color: #fff;
        border: 1px solid #dee2e6;
        color: #495057;
        box-shadow: none;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    .dt-external-toolbar .dt-toolbar-right .btn:hover,
    .dt-external-toolbar .dt-buttons .dt-button:hover,
    .dt-external-toolbar .dt-toolbar-right .btn.show,
    .dt-external-toolbar .dt-buttons .dt-button:focus {
        background-color: #eef4ff;
        border-color: #0d6efd;
        color: #0d6efd;
    }
    .dt-external-toolbar .dt-buttons {
        margin: 0;
    }
    .dt-external-toolbar .dt-toolbar-right .btn i,
    .dt-external-toolbar .dt-buttons .dt-button span {
        line-height: 1;
    }

    /* Columns dropdown menu + export collection menu */
    .dt-external-toolbar .dropdown-menu,
    .dt-button-collection.dropdown-menu {
        border: 1px solid #eceef1;
        border-radius: .625rem;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .1);
        padding: .375rem;
    }
    .dt-external-toolbar .dropdown-menu .dropdown-header {
        color: #adb5bd;
        letter-spacing: .04em;
        font-weight: 600;
    }
    .dt-external-toolbar .dropdown-menu .dropdown-item,
    .dt-button-collection .dt-button {
        border-radius: .375rem;
        padding: .4rem .6rem;
        font-size: .8125rem;
    }
    .dt-external-toolbar .dropdown-menu .dropdown-item:hover,
    .dt-button-collection .dt-button:hover {
        background-color: #f1f5f9;
        color: #0d6efd;
    }
    .dt-external-toolbar .dropdown-menu .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    @media (max-width: 575.98px) {
        .dt-external-toolbar .dataTables_filter input.form-control { min-width: 0; width: 100%; }
        .dt-external-toolbar .dataTables_filter { flex: 1 1 100%; }
    }
</style>
@endpush

<div class="container-fluid px-2 px-md-3">
    <x-breadcrum title="{{ $pageTitle }}"></x-breadcrum>
    <div class="card {{ $cardClass }} border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <div class="datatables">
                {{-- External toolbar (kept OUTSIDE .table-responsive so dropdown menus are not clipped).
                     The native DataTables length + search controls are relocated into the left side
                     in initComplete so everything sits on a single line. --}}
                <div class="dt-external-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"
                     id="{{ $tableId }}_toolbar">
                    <div class="dt-toolbar-left d-flex flex-wrap align-items-center gap-2"></div>
                    <div class="dt-toolbar-right d-flex flex-wrap align-items-center gap-2"></div>
                </div>
                <div class="table-responsive">
                    {{-- Opt out of the global SargamDataTableUI auto-enhancer so it does not
                         fight this page's own toolbar layout (search/length relocation, etc.). --}}
                    <table class="table table-hover align-middle text-nowrap mb-0" id="{{ $tableId }}"
                        data-sargam-dt-ui="false">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Faculty Type</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile Number</th>
                                <th scope="col">Current Sector</th>
                                <th scope="col">Session Count</th>
                                <th scope="col">Feedback Average</th>
                                @if(hasRole('Admin'))
                                <th scope="col">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($faculties as $index => $faculty)
                            <tr>
                                <td class="text-body-secondary fw-medium">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge rounded-1 {{ $badgeClass }} bg-success-subtle text-success border border-success-subtle">{{ $badgeLabel }}</span>
                                </td>
                                <td>
                                    <span class="faculty-name">{{ $faculty->full_name }}</span>
                                </td>
                                <td>
                                    @if($faculty->email_id ?? null)
                                        <a href="mailto:{{ $faculty->email_id }}" class="email-link">
                                            <span class="material-symbols-rounded align-text-bottom me-1" style="font-size: 1rem;">mail</span>
                                            {{ $faculty->email_id }}
                                        </a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $faculty->mobile_no ?? 'N/A' }}</td>
                                <td>
                                    @if($faculty->faculty_sector == 1)
                                        <span class="badge rounded-1 badge-sector-gov border border-primary-subtle">Government</span>
                                    @elseif($faculty->faculty_sector == 2)
                                        <span class="badge rounded-1 badge-sector-private border border-warning-subtle">Private</span>
                                    @else
                                        <span class="badge rounded-1 badge-sector-other border border-secondary-subtle">Other</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="session-count-badge d-inline-flex align-items-center gap-1">
                                        <span class="material-symbols-rounded align-text-bottom" style="font-size: 1rem;">event</span>
                                        {{ $faculty->session_count ?? 0 }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $avgContent = data_get($faculty, 'feedback_summary.avg_content', 0);
                                        $avgPresentation = data_get($faculty, 'feedback_summary.avg_presentation', 0);
                                        $totalFeedback = (int) data_get($faculty, 'feedback_summary.total_feedback', 0);
                                        $getScoreClass = function($score) {
                                            if ($score >= 80) return 'excellent';
                                            if ($score >= 60) return 'good';
                                            if ($score >= 40) return 'average';
                                            return 'poor';
                                        };
                                    @endphp
                                    @if($totalFeedback > 0)
                                        <div class="feedback-average">
                                            <div class="feedback-score">
                                                <span class="feedback-label">Content:</span>
                                                <span class="feedback-value {{ $getScoreClass($avgContent) }}">
                                                    {{ number_format($avgContent, 1) }}%
                                                </span>
                                            </div>
                                            <div class="feedback-score">
                                                <span class="feedback-label">Presentation:</span>
                                                <span class="feedback-value {{ $getScoreClass($avgPresentation) }}">
                                                    {{ number_format($avgPresentation, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">No feedback yet</span>
                                    @endif
                                </td>
                                @if(hasRole('Admin'))
                                <td>
                                    <a href="{{ route('feedback.average', ['faculty_name' => $faculty->full_name]) }}"
                                       class="btn btn-view-feedback btn-sm">
                                        <span class="material-symbols-rounded">visibility</span>
                                        View Feedback
                                    </a>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="no-data text-center py-5 text-body-secondary fst-italic">
                                    <span class="material-symbols-rounded fs-1 d-block mb-2 opacity-50">person_off</span>
                                    {{ $emptyMessage }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var tableId = '{{ $tableId }}';
    var exportTitle = @json($exportTitle);
    var $toolbar = $('#' + tableId + '_toolbar');

    var table = $('#' + tableId).DataTable({
        order: [[0, 'asc']], // Sort by S. No. by default
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        // Persist column visibility (and search/sort/page) across navigation & refresh.
        stateSave: true,
        stateDuration: 60 * 60 * 24 * 30, // 30 days (localStorage)
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: false,
        autoWidth: false,
        // The length (l) + search (f) live in a hidden top row and are relocated
        // into the external toolbar in initComplete so the whole control bar is
        // one line. info (i) + pagination (p) stay below the table.
        dom: '<"dt-top-row d-none"lf>rt<"row g-2 align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
        initComplete: function() {
            var api = this.api();
            var wrapper = $('#' + tableId + '_wrapper');

            var $length = wrapper.find('.dataTables_length');
            var $filter = wrapper.find('.dataTables_filter');

            $length.find('select').addClass('form-select form-select-sm');
            $filter.find('input').addClass('form-control').attr('placeholder', 'Search faculty...');
            $length.addClass('mb-0');
            $filter.addClass('mb-0');
            wrapper.find('.dataTables_info').addClass('small text-muted');
            wrapper.find('.dataTables_paginate').addClass('small');

            // Relocate native length + search into the single-line toolbar (left side).
            $toolbar.find('.dt-toolbar-left').append($length).append($filter);

            buildColumnToggle(api);
            buildExportPrint(api);
        }
    });

    // ---- Column Show / Hide (Bootstrap dropdown, DataTables core only) ----
    function buildColumnToggle(api) {
        var $dropdown = $(
            '<div class="dropdown">' +
                '<button class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1" ' +
                    'type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">' +
                    '<i class="bi bi-layout-three-columns"></i><span class="d-none d-sm-inline">Columns</span>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end p-2 shadow-sm" style="min-width: 220px;">' +
                    '<li class="dropdown-header px-2 pt-0 pb-1 text-uppercase small">Show / Hide</li>' +
                '</ul>' +
            '</div>'
        );
        var $menu = $dropdown.find('.dropdown-menu');

        api.columns().every(function(index) {
            var column = this;
            var title = $(column.header()).text().trim() || ('Column ' + (index + 1));
            var $item = $(
                '<li>' +
                    '<label class="dropdown-item d-flex align-items-center gap-2 mb-0">' +
                        '<input type="checkbox" class="form-check-input m-0"' + (column.visible() ? ' checked' : '') + '>' +
                        '<span></span>' +
                    '</label>' +
                '</li>'
            );
            $item.find('span').text(title);
            $item.find('input').on('change', function() {
                // DataTables persists this via stateSave automatically.
                column.visible($(this).is(':checked'));
            });
            $menu.append($item);
        });

        $toolbar.find('.dt-toolbar-right').append($dropdown);
    }

    // ---- Export (Excel/CSV/PDF) + Print — only if Buttons extension exists ----
    function buildExportPrint(api) {
        if (!($.fn.dataTable && $.fn.dataTable.Buttons)) {
            return; // Buttons not loaded — skip gracefully, table stays functional.
        }
        try {
            new $.fn.dataTable.Buttons(api, {
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download me-1"></i>Export',
                        className: 'btn btn-sm btn-outline-secondary',
                        buttons: [
                            { extend: 'excelHtml5', text: 'Excel (.xlsx)', title: exportTitle, exportOptions: { columns: ':visible' } },
                            { extend: 'csvHtml5',   text: 'CSV (.csv)',   title: exportTitle, exportOptions: { columns: ':visible' } },
                            { extend: 'pdfHtml5',   text: 'PDF (.pdf)',   title: exportTitle, orientation: 'landscape', pageSize: 'A4', exportOptions: { columns: ':visible' } }
                        ]
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer me-1"></i>Print',
                        className: 'btn btn-sm btn-outline-secondary',
                        title: exportTitle,
                        exportOptions: { columns: ':visible' } // only visible columns
                    }
                ]
            });
            api.buttons().container().appendTo($toolbar.find('.dt-toolbar-right'));
        } catch (e) {
            console.warn('DataTables export/print buttons unavailable:', e);
        }
    }
});
</script>
@endpush
