@extends('admin.layouts.master')

@section('title', 'Faculty')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid faculty-index-page">
    <x-breadcrum title="Faculty">
        <a href="{{ route('faculty.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
           aria-label="Add New Faculty">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Faculty</span>
        </a>
    </x-breadcrum>

    <div id="status-msg"></div>

    {{-- Secondary actions (Print / Download) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('faculty.printBlank') }}" target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print Blank Form">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print Blank Form</span>
        </a>
        <a href="{{ route('faculty.excel.export') }}" class="btn programme-dt-btn-columns border-0 text-primary" title="Download">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="facultyBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#facultyColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="facultyDtSearch" class="programme-dt-search" data-dt-search-for="faculty-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="facultyDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="faculty-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="facultyColumnVisibilityModal" tabindex="-1" aria-labelledby="facultyColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="facultyColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="facultyColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}

<script>
$(function () {
    var TABLE_ID = '#faculty-table';
    var table;

    /* ---- Relocate search + build footer (pagination + count) ---- */
    function enhanceFacultyDtControls() {
        var $wrapper = $(TABLE_ID + '_wrapper');
        if (!$wrapper.length) { return; }

        var $searchSlot = $('#facultyDtSearch');
        var $footer = $('#facultyDtFooter');

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search faculty');
                $filter.find('label').contents().filter(function () {
                    return this.nodeType === 3;
                }).remove();
                $searchSlot.append($filter);
            }
        }

        if (!$footer.length) { return; }

        if ($footer.data('dtReady')) { updateFacultyDtCount(); return; }

        // If the global DataTable UI already populated this footer, don't duplicate it.
        if ($footer.find('.dataTables_info, .dataTables_paginate, .dataTables_length').length) {
            $footer.data('dtReady', true);
            updateFacultyDtCount();
            return;
        }

        var $paginate = $wrapper.find('.dataTables_paginate').first();
        var $length = $wrapper.find('.dataTables_length').first();
        var $info = $wrapper.find('.dataTables_info').first();

        if (!$paginate.length && !$length.length) { return; }

        var $pagCol = $('<div class="programme-dt-pagination"></div>');
        var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

        if ($paginate.length) {
            $paginate.find('.pagination').addClass('mb-0');
            $pagCol.append($paginate);
        }

        if ($length.length) {
            // Detach (not empty) the select so DataTables' change.DT handler survives.
            var $select = $length.find('select').addClass('form-select form-select-sm').detach();
            $length.find('label')
                .empty()
                .append(document.createTextNode('Showing '))
                .append($select)
                .append(document.createTextNode(' '));
            $countCol.append($length);
        }

        if ($info.length) {
            $info.addClass('mb-0');
            $countCol.append($info);
        }

        $footer.append($pagCol).append($countCol);
        $footer.data('dtReady', true);
        updateFacultyDtCount();
    }

    function updateFacultyDtCount() {
        if (!$.fn.DataTable.isDataTable(TABLE_ID)) { return; }
        var info = $(TABLE_ID).DataTable().page.info();
        var $info = $('#facultyDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    /* ---- Column show / hide (DataTables API) ---- */
    var facultyColStorageKey = 'facultyGrid:hiddenColumns:v1';

    function facultyGetHiddenCols() {
        try {
            var raw = localStorage.getItem(facultyColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function facultyPersistHiddenCols(arr) {
        try { localStorage.setItem(facultyColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupFacultyColumns(dt) {
        if (!dt) { return; }
        var hidden = facultyGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#facultyColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'facultycolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = facultyGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                facultyPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    // Yajra initialises the table itself; wait for it, then wire up the slots.
    var facultyInitTimer = setInterval(function () {
        if (!$.fn.DataTable.isDataTable(TABLE_ID)) { return; }
        clearInterval(facultyInitTimer);

        table = $(TABLE_ID).DataTable();

        enhanceFacultyDtControls();
        updateFacultyDtCount();
        setupFacultyColumns(table);

        var $wrapper = $(TABLE_ID + '_wrapper');
        $(TABLE_ID).on('draw.dt', function () {
            if ($wrapper.find('.dataTables_paginate').length && !$('#facultyDtFooter .dataTables_paginate').length) {
                $('#facultyDtFooter').empty().data('dtReady', false);
                enhanceFacultyDtControls();
            }
            updateFacultyDtCount();
        });

        setTimeout(function () { enhanceFacultyDtControls(); updateFacultyDtCount(); }, 300);
    }, 50);
});
</script>

<script>
// Delete Faculty with SweetAlert Confirmation
$(document).on('click', '.delete-faculty-btn', function(e) {
    e.preventDefault();

    if (this.disabled) { return; }

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
