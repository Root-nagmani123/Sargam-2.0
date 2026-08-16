@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-lookup-admin.css') }}?v={{ @filemtime(public_path('css/master-lookup-admin.css')) ?: time() }}">
@endpush


@section('setup_content')
<div class="container-fluid mst-page employee-type-page">
    <x-breadcrum title="Employee Type Master" :showBack="false">
        {{-- Add and Edit both open #etmFormModal — no page navigation
             (docs/new-design-index-page.md §3c). --}}
        <button type="button" id="etmAddBtn"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#etmFormModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Type</span>
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
                    id="etmDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2"
                    aria-labelledby="etmDownloadBtn">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 etm-export-link"
                            data-export-format="csv" href="{{ route('master.employee.type.export', 'csv') }}">
                            <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
                            <span>Download CSV</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 etm-export-link"
                            data-export-format="excel" href="{{ route('master.employee.type.export', 'excel') }}">
                            <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i>
                            <span>Download Excel</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 etm-export-link"
                            data-export-format="pdf" href="{{ route('master.employee.type.export', 'pdf') }}">
                            <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                            <span>Download PDF</span>
                        </a>
                    </li>
                </ul>
            </div>

            <a class="btn programme-dt-btn-columns border-0 text-primary etm-export-link"
                id="etmPrintBtn" data-export-format="print"
                href="{{ route('master.employee.type.export', 'print') }}"
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
                    <button type="button" class="btn programme-dt-btn-columns" id="etmBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#etmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="etmDtSearch" class="programme-dt-search"
                        data-dt-search-for="employeetypemaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="etmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeetypemaster-table"></div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Employee Type Modal — one form for both, so they cannot drift apart (§3c) -->
    <div class="modal fade mst-modal" id="etmFormModal" tabindex="-1"
        aria-labelledby="etmFormModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="etmForm" action="{{ route('master.employee.type.store') }}" method="POST" novalidate>
                    @csrf
                    {{-- Encrypted pk: present = update, empty = create. The controller
                         decrypts it and scopes the unique rule to that row. --}}
                    <input type="hidden" name="pk" id="etmPk" value="">

                    <div class="mst-modal-header">
                        <h5 class="mst-modal-title" id="etmFormModalLabel">Add Employee Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="mst-modal-body">
                        <div id="etmFormAlert" class="alert d-none mb-3" role="alert"></div>

                        <div class="mst-field-card">
                            <label for="etmName" class="mst-form-label">
                                Category Type Name <span class="mst-req">*</span>
                            </label>
                            <input type="text" class="mst-control" id="etmName" name="employee_type_name"
                                placeholder="eg. Contractual" maxlength="255" required>
                            <div class="invalid-feedback" data-field="employee_type_name"></div>
                        </div>
                    </div>

                    <div class="mst-modal-footer">
                        <button type="button" class="btn mst-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn mst-btn-submit" id="etmSubmitBtn">Add Employee Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Column Visibility Modal -->
    <div class="modal fade" id="etmColumnVisibilityModal" tabindex="-1"
        aria-labelledby="etmColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="etmColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="etmColumnToggleGrid"></div>
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
     #etmDtSearch / #etmDtFooter by the global enhancer
     (public/js/datatable-global-ui.js) via the data-dt-search-for /
     data-dt-footer-for hooks above. Do NOT add a page-local copy of that logic. --}}

<script>
$(function () {
    var ETM_TABLE = '#employeetypemaster-table';

    /* ---- Column show / hide (DataTables API) ------------------------------- */
    // Labels are stored, never indices: adding a column later would silently shift
    // every saved index and hide the wrong column (docs/column-visibility.md §3).
    var etmColStorageKey = 'sargam.employeeType.hiddenColumns.v1.{{ auth()->id() ?? 'guest' }}';

    function etmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(etmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function etmPersistHiddenCols(arr) {
        try { localStorage.setItem(etmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function etmColTitle(col) {
        return $(col.header()).text().replace(/\s+/g, ' ').trim();
    }

    /* ---- Export links follow the screen ------------------------------------ */
    // Header label -> the export column key the controller knows. "Action" has
    // no export column, so it is absent by design.
    var ETM_EXPORT_KEYS = {
        'S.No.': 'sno',
        'Category Type Name': 'category_type_name',
        'Status': 'status'
    };
    var ETM_EXPORT_KEY_COUNT = Object.keys(ETM_EXPORT_KEYS).length;

    window.etmSyncExportLinks = function () {
        var hidden = etmGetHiddenCols();
        var keys = [];
        Object.keys(ETM_EXPORT_KEYS).forEach(function (label) {
            if (hidden.indexOf(label) === -1) {
                keys.push(ETM_EXPORT_KEYS[label]);
            }
        });

        var search = $.trim($('#etmDtSearch input[type="search"]').val() || '');

        $('.etm-export-link').each(function () {
            var url = new URL(this.href, window.location.origin);
            url.search = '';
            if (search) {
                url.searchParams.set('q', search);
            }
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length && keys.length !== ETM_EXPORT_KEY_COUNT) {
                url.searchParams.set('cols', keys.join(','));
            }
            this.href = url.toString();
        });
    };

    // The search box is moved into #etmDtSearch by the global enhancer, so it is
    // bound through the document rather than directly.
    $(document).on('input search', '#etmDtSearch input[type="search"]', function () {
        window.etmSyncExportLinks();
    });

    function setupEtmColumns(dt) {
        if (!dt) {
            return;
        }

        var hidden = etmGetHiddenCols();

        dt.columns().every(function () {
            var title = etmColTitle(this);
            this.visible(!title || hidden.indexOf(title) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#etmColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = etmColTitle(this);
            if (!title) {
                return;
            }

            var inputId = 'etmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $cb.on('change', function () {
                var h = etmGetHiddenCols();
                var pos = h.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(title);
                }
                etmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                window.etmSyncExportLinks();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });

        // Stamp ?cols= now that the saved visibility has been applied.
        window.etmSyncExportLinks();
    }

    // Yajra initialises the table itself. Handle both orders: if it is already up
    // we build now, otherwise init.dt fires for us. Both paths are idempotent.
    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable && settings.nTable.id === 'employeetypemaster-table') {
            setupEtmColumns(new $.fn.dataTable.Api(settings));
        }
    });

    if ($.fn.DataTable.isDataTable(ETM_TABLE)) {
        setupEtmColumns($(ETM_TABLE).DataTable());
    }

    /* ---- Add / Edit modal -------------------------------------------------- */
    var $form = $('#etmForm');
    var $alert = $('#etmFormAlert');
    var etmModal = document.getElementById('etmFormModal');

    function etmClearErrors() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
    }

    function etmResetForm() {
        $form[0].reset();
        $('#etmPk').val('');
        etmClearErrors();
    }

    function etmSetMode(isEdit) {
        $('#etmFormModalLabel').text(isEdit ? 'Edit Employee Type' : 'Add Employee Type');
        $('#etmSubmitBtn').text(isEdit ? 'Update' : 'Add Employee Type');
    }

    $('#etmAddBtn').on('click', function () {
        etmResetForm();
        etmSetMode(false);
    });

    // Delegated: the button is redrawn with every DataTables page.
    $(document).on('click', '#employeetypemaster-table .etm-edit-btn', function () {
        var $btn = $(this);
        etmResetForm();
        etmSetMode(true);
        $('#etmPk').val($btn.data('id'));
        $('#etmName').val($btn.data('name'));
        bootstrap.Modal.getOrCreateInstance(etmModal).show();
    });

    // Create and update share the store route.
    $form.on('submit', function (e) {
        e.preventDefault();
        etmClearErrors();

        var $submit = $('#etmSubmitBtn');
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

                if ($.fn.DataTable.isDataTable(ETM_TABLE)) {
                    $(ETM_TABLE).DataTable().ajax.reload(null, false);
                }

                setTimeout(function () {
                    bootstrap.Modal.getInstance(etmModal)?.hide();
                    etmResetForm();
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
    etmModal.addEventListener('hidden.bs.modal', function () {
        etmResetForm();
        etmSetMode(false);
    });
});
</script>
@endpush
