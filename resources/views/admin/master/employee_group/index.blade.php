@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-lookup-admin.css') }}?v={{ @filemtime(public_path('css/master-lookup-admin.css')) ?: time() }}">
@endpush


@section('setup_content')
<div class="container-fluid mst-page employee-group-page">
    <x-breadcrum title="Employee Group Master" :showBack="false">
        {{-- Add and Edit both open #egmFormModal — no page navigation
             (docs/new-design-index-page.md §3c). --}}
        <button type="button" id="egmAddBtn"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#egmFormModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Group</span>
        </button>
    </x-breadcrum>

    <x-session_message />
    {{-- The shared .status-toggle handler (admin_assets/js/custom.js) writes its
         confirmation here, so the div must exist on any page carrying a switch. --}}
    <div id="status-msg"></div>

    {{-- Nothing to filter by status on this grid, so the export row keeps its
         place above the card with the buttons alone on the right. Print sits
         beside Download because it opens a page rather than saving a file
         (docs/new-design-index-page.md §1). --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="dropdown">
                <button type="button"
                    class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary"
                    id="egmDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2"
                    aria-labelledby="egmDownloadBtn">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 egm-export-link"
                            data-export-format="csv" href="{{ route('master.employee.group.export', 'csv') }}">
                            <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
                            <span>Download CSV</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 egm-export-link"
                            data-export-format="excel" href="{{ route('master.employee.group.export', 'excel') }}">
                            <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i>
                            <span>Download Excel</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 egm-export-link"
                            data-export-format="pdf" href="{{ route('master.employee.group.export', 'pdf') }}">
                            <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                            <span>Download PDF</span>
                        </a>
                    </li>
                </ul>
            </div>

            <a class="btn programme-dt-btn-columns border-0 text-primary egm-export-link"
                id="egmPrintBtn" data-export-format="print"
                href="{{ route('master.employee.group.export', 'print') }}"
                target="_blank" rel="noopener" title="Print the filtered list">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-1 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="egmBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#egmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="egmDtSearch" class="programme-dt-search"
                        data-dt-search-for="employeegroupmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="egmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeegroupmaster-table"></div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Employee Group Modal — one form for both, so they cannot drift apart (§3c) -->
    <div class="modal fade mst-modal" id="egmFormModal" tabindex="-1"
        aria-labelledby="egmFormModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="egmForm" action="{{ route('master.employee.group.store') }}" method="POST" novalidate>
                    @csrf
                    {{-- Encrypted pk: present = update, empty = create. The controller
                         decrypts it and scopes the unique rule to that row. --}}
                    <input type="hidden" name="pk" id="egmPk" value="">

                    <div class="mst-modal-header">
                        <h5 class="mst-modal-title" id="egmFormModalLabel">Add Employee Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="mst-modal-body">
                        <div id="egmFormAlert" class="alert d-none mb-3" role="alert"></div>

                        <div class="mst-field-card">
                            <label for="egmName" class="mst-form-label">
                                Employee Group Name <span class="mst-req">*</span>
                            </label>
                            <input type="text" class="mst-control" id="egmName" name="emp_group_name"
                                placeholder="eg. Group A" maxlength="30" required>
                            <div class="invalid-feedback" data-field="emp_group_name"></div>
                        </div>
                    </div>

                    <div class="mst-modal-footer">
                        <button type="button" class="btn mst-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn mst-btn-submit" id="egmSubmitBtn">Add Employee Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Column Visibility Modal -->
    <div class="modal fade" id="egmColumnVisibilityModal" tabindex="-1"
        aria-labelledby="egmColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="egmColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="egmColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}

{{-- Search box, pagination and the "Showing N of M items" count are relocated into
     #egmDtSearch / #egmDtFooter by the global enhancer
     (public/js/datatable-global-ui.js) via the data-dt-search-for /
     data-dt-footer-for hooks above. Do NOT add a page-local copy of that logic. --}}

<script>
$(function () {
    var EGM_TABLE = '#employeegroupmaster-table';

    /* ---- Column show / hide (DataTables API) ------------------------------- */
    // Labels are stored, never indices: adding a column later would silently shift
    // every saved index and hide the wrong column (docs/column-visibility.md §3).
    var egmColStorageKey = 'sargam.employeeGroup.hiddenColumns.v1.{{ auth()->id() ?? 'guest' }}';

    function egmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(egmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function egmPersistHiddenCols(arr) {
        try { localStorage.setItem(egmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function egmColTitle(col) {
        return $(col.header()).text().replace(/\s+/g, ' ').trim();
    }

    /* ---- Export links follow the screen ------------------------------------ */
    // Header label -> the export column key the controller knows. "Action" has
    // no export column, so it is absent by design.
    var EGM_EXPORT_KEYS = {
        'S.No.': 'sno',
        'Employee Group Name': 'emp_group_name',
        'Status': 'status'
    };
    var EGM_EXPORT_KEY_COUNT = Object.keys(EGM_EXPORT_KEYS).length;

    window.egmSyncExportLinks = function () {
        var hidden = egmGetHiddenCols();
        var keys = [];
        Object.keys(EGM_EXPORT_KEYS).forEach(function (label) {
            if (hidden.indexOf(label) === -1) {
                keys.push(EGM_EXPORT_KEYS[label]);
            }
        });

        var search = $.trim($('#egmDtSearch input[type="search"]').val() || '');

        $('.egm-export-link').each(function () {
            var url = new URL(this.href, window.location.origin);
            url.search = '';
            if (search) {
                url.searchParams.set('q', search);
            }
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length && keys.length !== EGM_EXPORT_KEY_COUNT) {
                url.searchParams.set('cols', keys.join(','));
            }
            this.href = url.toString();
        });
    };

    // The search box is moved into #egmDtSearch by the global enhancer, so it is
    // bound through the document rather than directly.
    $(document).on('input search', '#egmDtSearch input[type="search"]', function () {
        window.egmSyncExportLinks();
    });

    function setupEgmColumns(dt) {
        if (!dt) {
            return;
        }

        var hidden = egmGetHiddenCols();

        dt.columns().every(function () {
            var title = egmColTitle(this);
            this.visible(!title || hidden.indexOf(title) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#egmColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = egmColTitle(this);
            if (!title) {
                return;
            }

            var inputId = 'egmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $cb.on('change', function () {
                var h = egmGetHiddenCols();
                var pos = h.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(title);
                }
                egmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                window.egmSyncExportLinks();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });

        // Stamp ?cols= now that the saved visibility has been applied.
        window.egmSyncExportLinks();
    }

    // Yajra initialises the table itself. Handle both orders: if it is already up
    // we build now, otherwise init.dt fires for us. Both paths are idempotent.
    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable && settings.nTable.id === 'employeegroupmaster-table') {
            setupEgmColumns(new $.fn.dataTable.Api(settings));
        }
    });

    if ($.fn.DataTable.isDataTable(EGM_TABLE)) {
        setupEgmColumns($(EGM_TABLE).DataTable());
    }

    /* ---- Add / Edit modal -------------------------------------------------- */
    var $form = $('#egmForm');
    var $alert = $('#egmFormAlert');
    var egmModal = document.getElementById('egmFormModal');

    function egmClearErrors() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
    }

    function egmResetForm() {
        $form[0].reset();
        $('#egmPk').val('');
        egmClearErrors();
    }

    function egmSetMode(isEdit) {
        $('#egmFormModalLabel').text(isEdit ? 'Edit Employee Group' : 'Add Employee Group');
        $('#egmSubmitBtn').text(isEdit ? 'Update' : 'Add Employee Group');
    }

    $('#egmAddBtn').on('click', function () {
        egmResetForm();
        egmSetMode(false);
    });

    // Delegated: the button is redrawn with every DataTables page.
    $(document).on('click', '#employeegroupmaster-table .egm-edit-btn', function () {
        var $btn = $(this);
        egmResetForm();
        egmSetMode(true);
        $('#egmPk').val($btn.data('id'));
        $('#egmName').val($btn.data('name'));
        bootstrap.Modal.getOrCreateInstance(egmModal).show();
    });

    // Create and update share the store route.
    $form.on('submit', function (e) {
        e.preventDefault();
        egmClearErrors();

        var $submit = $('#egmSubmitBtn');
        var originalText = $submit.text();
        $submit.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                $alert.removeClass('d-none alert-danger').addClass('alert-success')
                    .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));

                if ($.fn.DataTable.isDataTable(EGM_TABLE)) {
                    $(EGM_TABLE).DataTable().ajax.reload(null, false);
                }

                setTimeout(function () {
                    bootstrap.Modal.getInstance(egmModal)?.hide();
                    egmResetForm();
                }, 1000);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function (field) {
                        $form.find('[name="' + field + '"]').addClass('is-invalid');
                        $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                    });
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'An error occurred while saving. Please try again.';
                    $alert.removeClass('d-none alert-success').addClass('alert-danger')
                        .html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                }
            },
            complete: function () {
                $submit.prop('disabled', false).text(originalText);
            }
        });
    });

    // Reset on close so a stale edit can't leak into the next Add.
    egmModal.addEventListener('hidden.bs.modal', function () {
        egmResetForm();
        egmSetMode(false);
    });
});
</script>
@endpush
