@extends('admin.layouts.master')

@section('title', 'Member')

@push('styles')
<style>
    /* Page-scoped only — tokens come from public/css/sargam-app.css (docs/design.md).
       Everything else on this screen is the shared programme-dt chrome in custom.css. */

    /* Status column — soft badge. The theme ships the *-subtle backgrounds but not
       the matching text-*-emphasis colours, so the label would render black. */
    .member-index-page .status-pill {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35rem 0.7rem;
        letter-spacing: 0.01em;
    }

    .member-index-page .status-pill.bg-success-subtle {
        color: #146c43;
    }

    .member-index-page .status-pill.bg-danger-subtle {
        color: #b02a37;
    }

    /* Action column — Edit · View · switch · Delete as equal-width icon-over-label
       stacks. Equal min-width keeps the glyph row on one baseline regardless of
       caption length ("Edit" vs "Deactivate"). */
    .member-index-page .mbr-act-group {
        display: inline-flex;
        align-items: stretch;
        justify-content: center;
        gap: 2px;
    }

    /* The Actions cell is the widest thing on the row; trimming its own padding
       keeps all four stacks inside the panel instead of clipping Delete. */
    .member-index-page .programme-dt-table thead th:last-child,
    .member-index-page .programme-dt-table tbody td:last-child {
        padding-left: var(--ds-space-1, 0.25rem);
        padding-right: var(--ds-space-1, 0.25rem);
    }

    .member-index-page .mbr-act {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        /* wide enough for the longest caption ("Deactivate") so no stack is
           sized by its own text — every column must measure the same. */
        min-width: 58px;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        border-radius: var(--ds-radius, 4px);
        font-size: 0.68rem;
        font-weight: 500;
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
    }

    .member-index-page .mbr-act:focus-visible {
        outline: 0;
        box-shadow: var(--ds-focus-ring, 0 0 0 0.2rem rgba(0, 67, 132, 0.2));
    }

    .member-index-page .mbr-act__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 22px;
    }

    .member-index-page .mbr-act__icon > i {
        font-size: 1.1rem;
        line-height: 1;
    }

    .member-index-page .mbr-act__icon .form-check-input {
        margin: 0;
        float: none;
    }

    .member-index-page .mbr-act__label {
        white-space: nowrap;
    }

    .member-index-page .mbr-act--edit {
        color: #2563eb;
    }

    .member-index-page .mbr-act--view {
        color: var(--ds-primary, #004384);
    }

    .member-index-page .mbr-act--del {
        color: var(--bs-danger, #b12923);
    }

    .member-index-page .mbr-act--toggle {
        color: #475467;
    }

    .member-index-page .mbr-act--del.is-disabled {
        color: #98a2b3;
        opacity: 0.65;
        cursor: not-allowed;
    }

    .member-index-page .mbr-act:hover:not(.is-disabled) .mbr-act__label {
        text-decoration: underline;
    }

    .member-index-page .mbr-download-btn {
        text-decoration: none;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid member-index-page">
    <x-breadcrum title="Member" :showBack="false">
        <a href="{{ route('member.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Member</span>
        </a>
    </x-breadcrum>

    <div id="status-msg"></div>

    {{-- No status tabs on this grid, so the export row keeps its place above the
         card and the buttons sit alone on the right (docs/new-design-index-page.md §1).

         Print sits beside Download, not inside it — it opens a page rather than
         saving a file. Both are server-side exports off the same query, and the
         JS below keeps every href carrying the search box and the Columns modal,
         so what you get always matches what is on screen. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="dropdown">
            <button type="button"
                class="btn programme-dt-btn-columns mbr-download-btn dropdown-toggle border-0 text-primary"
                id="memberDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2"
                aria-labelledby="memberDownloadBtn">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 member-export-link"
                        data-export-format="csv" href="{{ route('member.export', 'csv') }}">
                        <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
                        <span>Download CSV</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 member-export-link"
                        data-export-format="excel" href="{{ route('member.export', 'excel') }}">
                        <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i>
                        <span>Download Excel</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2 member-export-link"
                        data-export-format="pdf" href="{{ route('member.export', 'pdf') }}">
                        <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                        <span>Download PDF</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    {{-- The pre-existing full-profile dump: every employee_master
                         column, unfiltered. A different report, not a format of
                         the ones above, so it is listed apart from them. --}}
                    <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2"
                        href="{{ route('member.excel.export') }}">
                        <i class="bi bi-database-down text-secondary" aria-hidden="true"></i>
                        <span>Full Details (Excel)</span>
                    </a>
                </li>
            </ul>
        </div>

        <a class="btn programme-dt-btn-columns mbr-download-btn border-0 text-primary member-export-link"
            id="memberPrintBtn" data-export-format="print" href="{{ route('member.export', 'print') }}"
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
                    <button type="button" class="btn programme-dt-btn-columns" id="memberBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#memberColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="memberDtSearch" class="programme-dt-search" data-dt-search-for="member-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="memberDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="member-table"></div>
            </div>
        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="memberColumnVisibilityModal" tabindex="-1"
        aria-labelledby="memberColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="memberColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="memberColumnToggleGrid"></div>
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
     #memberDtSearch / #memberDtFooter by the global enhancer
     (public/js/datatable-global-ui.js) via the data-dt-search-for /
     data-dt-footer-for hooks above. Do NOT add a page-local copy of that logic. --}}

<script>
$(function () {
    var MEMBER_TABLE = '#member-table';

    /* ---- Column show / hide (DataTables API) ------------------------------- */
    // Labels are stored, never indices: adding a column later would silently shift
    // every saved index and hide the wrong column (docs/column-visibility.md §3).
    var memberColStorageKey = 'sargam.member.hiddenColumns.v1.{{ auth()->id() ?? 'guest' }}';

    function memberGetHiddenCols() {
        try {
            var raw = localStorage.getItem(memberColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function memberPersistHiddenCols(arr) {
        try { localStorage.setItem(memberColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function memberColTitle(col) {
        return $(col.header()).text().replace(/\s+/g, ' ').trim();
    }

    /* ---- Export links follow the screen ------------------------------------ */
    // Header label -> the export column key MemberController::exportColumnDefs()
    // knows. "Actions" has no export column, so it is absent by design.
    var MEMBER_EXPORT_KEYS = {
        'S.No.': 'sno',
        'Employee Name': 'employee_name',
        'Employee ID': 'employee_id',
        'Mobile No': 'mobile_no',
        'Email': 'email',
        'Status': 'status'
    };
    var MEMBER_EXPORT_KEY_COUNT = Object.keys(MEMBER_EXPORT_KEYS).length;

    window.memberSyncExportLinks = function () {
        var hidden = memberGetHiddenCols();
        var keys = [];
        Object.keys(MEMBER_EXPORT_KEYS).forEach(function (label) {
            if (hidden.indexOf(label) === -1) {
                keys.push(MEMBER_EXPORT_KEYS[label]);
            }
        });

        var search = $.trim($('#memberDtSearch input[type="search"]').val() || '');

        $('.member-export-link').each(function () {
            var url = new URL(this.href, window.location.origin);
            url.search = '';
            if (search) {
                url.searchParams.set('q', search);
            }
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length && keys.length !== MEMBER_EXPORT_KEY_COUNT) {
                url.searchParams.set('cols', keys.join(','));
            }
            this.href = url.toString();
        });
    };

    // The search box is moved into #memberDtSearch by the global enhancer, so it
    // is bound through the document rather than directly.
    $(document).on('input search', '#memberDtSearch input[type="search"]', function () {
        window.memberSyncExportLinks();
    });

    function setupMemberColumns(dt) {
        if (!dt) {
            return;
        }

        var hidden = memberGetHiddenCols();

        dt.columns().every(function () {
            var title = memberColTitle(this);
            this.visible(!title || hidden.indexOf(title) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#memberColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = memberColTitle(this);
            if (!title) {
                return;
            }

            var inputId = 'membercolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $cb.on('change', function () {
                var h = memberGetHiddenCols();
                var pos = h.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(title);
                }
                memberPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                window.memberSyncExportLinks();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });

        // Stamp ?cols= now that the saved visibility has been applied.
        window.memberSyncExportLinks();
    }

    // Yajra initialises the table itself. Handle both orders: if it is already up
    // we build now, otherwise init.dt fires for us. Both paths are idempotent.
    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable && settings.nTable.id === 'member-table') {
            setupMemberColumns(new $.fn.dataTable.Api(settings));
        }
    });

    if ($.fn.DataTable.isDataTable(MEMBER_TABLE)) {
        setupMemberColumns($(MEMBER_TABLE).DataTable());
    }
});
</script>

<script>
$(function () {
    function memberReloadTable() {
        if ($.fn.DataTable.isDataTable('#member-table')) {
            $('#member-table').DataTable().ajax.reload(null, false);
        }
    }

    // Status switch — it lives in the Actions stack while the badge one column over
    // is the display, so the grid is reloaded rather than the two hand-mirrored.
    $(document).on('change', '.member-status-toggle', function () {
        const $checkbox = $(this);
        const memberId = $checkbox.data('id');
        const isChecked = $checkbox.is(':checked');
        const newStatus = isChecked ? 1 : 2; // checked=Active(1), unchecked=Inactive(2)
        const actionText = isChecked ? 'activate' : 'deactivate';

        Swal.fire({
            title: 'Confirm Action',
            text: `Are you sure you want to ${actionText} this member?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${actionText}!`
        }).then((result) => {
            if (!result.isConfirmed) {
                // User cancelled, revert checkbox
                $checkbox.prop('checked', !isChecked);
                return;
            }

            $.ajax({
                url: `/member/${memberId}/toggle-status`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({ status: newStatus }),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Status updated.',
                            timer: 1500
                        });
                        memberReloadTable();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Error updating status'
                        });
                        $checkbox.prop('checked', !isChecked);
                    }
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error toggling status'
                    });
                    $checkbox.prop('checked', !isChecked);
                }
            });
        });
    });

    // Delete — only rendered as a real button when the server would allow it
    // (MemberController@destroy refuses active members).
    $(document).on('click', '.member-delete-btn', function (e) {
        e.preventDefault();

        const deleteUrl = $(this).data('delete-url');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This member will be deleted. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'Member deleted successfully',
                        timer: 1500
                    });
                    memberReloadTable();
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error deleting member'
                    });
                }
            });
        });
    });
});
</script>
@endpush
