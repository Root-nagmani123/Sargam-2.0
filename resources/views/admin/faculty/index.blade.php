@extends('admin.layouts.master')

@section('title', 'Faculty')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Faculty" :showBack="false">
        <a href="{{ route('faculty.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
           aria-label="Add New Faculty">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Faculty</span>
        </a>
    </x-breadcrum>

    {{-- The status toggle (public/admin_assets/js/custom.js) writes its result here. --}}
    <div id="status-msg"></div>

    {{-- Secondary actions (Download / Print / Blank Form) — above the card,
         right-aligned, in the same slot, chrome and wording as every other
         master listing (§1 of docs/new-design-index-page.md).

         ?q= and ?cols= are stamped on by facultyUpdateExportLinks(), so each
         format carries the same search term and columns the grid is showing.
         Print is the branded server-rendered list; Blank Form is the separate
         fill-in sheet and keeps its own button rather than being buried in the
         Download menu. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <div class="dropdown">
            <button type="button" id="facultyDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="facultyDownloadToggle">
                <li>
                    <a class="dropdown-item" id="facultyCsvLink"
                       href="{{ route('faculty.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="facultyExcelLink"
                       href="{{ route('faculty.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="facultyPdfLink"
                       href="{{ route('faculty.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    {{-- The 34-column dump (qualifications, experience, bank).
                         Not a grid export, so it carries no ?q= / ?cols=. --}}
                    <a class="dropdown-item" href="{{ route('faculty.excel.export') }}">
                        <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i> Full Details (.xlsx)
                    </a>
                </li>
            </ul>
        </div>

        <a href="{{ route('faculty.export', ['format' => 'print']) }}"
           id="facultyPrintLink" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>

        <a href="{{ route('faculty.printBlank') }}"
           class="btn programme-dt-btn-columns border-0 text-primary"
           title="Blank Form" aria-label="Blank Form">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Blank Form</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="facultyBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#facultyColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="facultyDtSearch" class="programme-dt-search" data-dt-search-for="faculty-table"></div>
                </div>
            </div>

            {{-- Search box, pagination and the "Showing N of M items" count are
                 relocated into #facultyDtSearch / #facultyDtFooter by the global
                 enhancer (public/js/datatable-global-ui.js). Don't rebuild them. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- mst-table-pin-action: nine columns don't fit, so the panel
                         scrolls — the Action column stays pinned to the right. --}}
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table mst-table-pin-action']) !!}
                </div>
                <div id="facultyDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="faculty-table"></div>
            </div>

        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="facultyColumnVisibilityModal" tabindex="-1"
        aria-labelledby="facultyColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="facultyColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="facultyColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

<script>
/* ---- Column show / hide (DataTables API), matching the other listing pages ----
   Stores LABELS, not indices: an index shifts the moment a column is added and
   would then hide the wrong one. A label that no longer matches any header is
   simply ignored, so a renamed column comes back visible. Keyed by user id so
   two people sharing a machine don't inherit each other's hidden columns. */
$(function () {
    var TABLE_ID = '#faculty-table';
    var FACULTY_COLVIS_KEY = 'sargam.faculty.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    /* ---------- Export links follow the grid ----------
     * Positional map: header index -> the export key the server understands
     * (FacultyController::exportColumnDefs()). '' marks a column that is not in
     * the export at all — here, Action.
     * ⚠️ Adding a column to the table means adding an entry here too. */
    var FACULTY_EXPORT_COLUMN_KEYS = [
        'sno', 'faculty_code', 'faculty_name', 'faculty_email',
        'mobile_number', 'modified_date', 'modified_by', 'status', ''
    ];
    var FACULTY_EXPORT_COL_COUNT = FACULTY_EXPORT_COLUMN_KEYS.filter(Boolean).length;
    var FACULTY_EXPORT_LINK_IDS = ['facultyCsvLink', 'facultyExcelLink', 'facultyPdfLink', 'facultyPrintLink'];

    function facultyUpdateExportLinks() {
        var dt = $.fn.DataTable.isDataTable(TABLE_ID) ? $(TABLE_ID).DataTable() : null;
        var keys = [];
        var term = '';

        if (dt) {
            dt.columns().every(function () {
                var key = FACULTY_EXPORT_COLUMN_KEYS[this.index()];
                if (key && this.visible()) { keys.push(key); }
            });
            // The term lives in DataTables, which sends it to the grid feed as
            // search[value]; the export reads ?q=. Without carrying it the
            // download returns every row and its header can't name the filter.
            term = dt.search() || '';
        }

        FACULTY_EXPORT_LINK_IDS.forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }

            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('cols');
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (dt && keys.length !== FACULTY_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    function facultyGetHiddenCols() {
        try {
            var raw = window.localStorage.getItem(FACULTY_COLVIS_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function facultyPersistHiddenCols(arr) {
        try {
            window.localStorage.setItem(FACULTY_COLVIS_KEY, JSON.stringify(arr));
        } catch (e) { /* private mode — the preference just won't persist */ }
    }

    function setupFacultyColumns(dt) {
        if (!dt) {
            return;
        }

        var hidden = facultyGetHiddenCols();
        var $grid = $('#facultyColumnToggleGrid');
        if ($grid.length) {
            $grid.empty();
        }

        dt.columns().every(function () {
            var idx = this.index();
            var label = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!label) {
                return;
            }

            // Apply the saved choice — DataTables keeps it across redraws / reloads.
            this.visible(hidden.indexOf(label) === -1, false);

            if (!$grid.length) {
                return;
            }

            var inputId = 'facultycolvis_' + idx;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr({ id: inputId, 'data-col': idx, 'data-label': label })
                .prop('checked', hidden.indexOf(label) === -1);

            $checkbox.on('change', function () {
                var current = facultyGetHiddenCols();
                var pos = current.indexOf(label);

                if (this.checked) {
                    if (pos !== -1) { current.splice(pos, 1); }
                } else if (pos === -1) {
                    current.push(label);
                }

                facultyPersistHiddenCols(current);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                facultyUpdateExportLinks();
            });

            $grid.append(
                $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                        .attr({ 'for': inputId, title: label })
                        .append($checkbox)
                        .append($('<span></span>').text(label))
                )
            );
        });

        dt.columns.adjust();

        // Stamp the restored column state onto the export links on first paint —
        // a preference read back from localStorage would otherwise not reach the
        // server until the user opened the modal and toggled something. Search-
        // as-you-type has to re-stamp them too, not just redraw the grid.
        dt.off('search.dt.facultyExport').on('search.dt.facultyExport', facultyUpdateExportLinks);
        facultyUpdateExportLinks();
    }

    // Yajra initialises the table itself. Handle both orders: if it is already up
    // we build now, otherwise init.dt fires for us. Both paths are idempotent.
    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable && settings.nTable.id === 'faculty-table') {
            setupFacultyColumns(new $.fn.dataTable.Api(settings));
        }
    });

    if ($.fn.DataTable.isDataTable(TABLE_ID)) {
        setupFacultyColumns($(TABLE_ID).DataTable());
    }
});
</script>

<script>
// Delete Faculty with SweetAlert Confirmation
$(document).on('click', '.delete-faculty-btn', function(e) {
    e.preventDefault();

    var deleteUrl = $(this).data('url');
    var facultyName = $(this).data('name');
    var csrfToken = $(this).data('token');

    Swal.fire({
        title: 'Are you sure?',
        html: 'You are about to delete faculty: <strong>' + facultyName + '</strong><br><br>This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;">delete</i> Yes, delete it!',
        cancelButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;">close</i> Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the faculty record.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX delete request
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: csrfToken
                },
                success: function(response) {
                    if (response.status) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message || 'Faculty has been deleted successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the DataTable
                            $('#faculty-table').DataTable().ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to delete faculty.',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Something went wrong. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        title: 'Error!',
                        text: errorMessage,
                        icon: 'error'
                    });
                }
            });
        }
    });
});
</script>

<script>
$(document).ready(function () {
    var toastMsg = sessionStorage.getItem('facultyToast');
    if (toastMsg) {
        sessionStorage.removeItem('facultyToast');
        toastr.options = {
            "timeOut": "4000",
            "extendedTimeOut": "1000",
            "positionClass": "toast-top-right",
            "closeButton": true,
            "progressBar": true
        };
        toastr.success(toastMsg);
        $('#toast-container').css('top', '80px');
    }
});
</script>
@endpush
