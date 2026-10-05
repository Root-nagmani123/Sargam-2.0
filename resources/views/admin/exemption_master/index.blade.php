@extends('admin.layouts.master')

@section('title', 'PT Exemption Master')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
    /* Time Period: a read-only date-range input with a calendar glyph. Same
       rules on PT Exemption Master and Stationed Leave Master (.lm-page). */
    .mst-page.lm-page .lm-daterange {
        position: relative;
        width: 13.5rem;
        max-width: 100%;
    }

    .mst-page.lm-page .lm-daterange .mst-control {
        padding-left: 2.25rem;
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mst-page.lm-page .lm-daterange__icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ds-ink-muted);
        pointer-events: none;
    }

    @media (max-width: 767.98px) {
        .mst-page.lm-page .lm-daterange,
        .mst-page.lm-page .programme-dt-filter-select {
            width: 100%;
        }
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid mst-page lm-page ptx-page">
    <x-breadcrum title="PT Exemption Master" :showBack="false">
        <a href="{{ route('admin.pt-exemption-master.create') }}" data-mst-modal-form
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Configure PT Exemption</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    {{-- Hidden state the grid's AJAX payload has always carried (d.pk /
         d.active_inactive); kept so the request is unchanged. --}}
    <input type="hidden" id="pk" value="">
    <input type="hidden" id="active_inactive" value="">

    {{-- Status pills (course lifecycle) left · Download right, above the card. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter by course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="filterActive" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="filterArchive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap justify-content-end gap-2 mst-secondary-actions">
            <div class="dropdown">
                <button type="button" id="exemptionDownload"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm py-2" aria-labelledby="exemptionDownload">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="exemptionExportPdf">
                            <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i>
                            <span>Download PDF</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="exemptionExportExcel">
                            <i class="bi bi-file-earmark-excel text-success" aria-hidden="true"></i>
                            <span>Download Excel</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- No overflow-hidden on this card: the searchable course filter opens its
         dropdown inside .card-body, and a short grid would clip it. --}}
    <div class="card rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- The two groups wrap as wholes: on a narrow card Columns + Search drop
                 to their own right-aligned line instead of splitting apart. --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <label for="courseFilter" class="visually-hidden">Course</label>
                        <select id="courseFilter" class="form-select mst-control mst-searchable"
                                data-placeholder="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($coursesActive ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lm-daterange">
                        <label for="timePeriodFilter" class="visually-hidden">Time Period (effective from)</label>
                        <i class="bi bi-calendar3 lm-daterange__icon" aria-hidden="true"></i>
                        <input type="text" id="timePeriodFilter" class="form-control mst-control"
                               placeholder="Time Period" autocomplete="off" readonly>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                        Reset Filters
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnExemptionColumns"
                            data-bs-toggle="modal" data-bs-target="#exemptionColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="exemptionDtSearch" class="programme-dt-search"
                         data-dt-search-for="exemption-master-table"></div>
                </div>
            </div>

            <p class="small text-secondary d-lg-none mb-2" role="note">
                Scroll inside the table area to see all rows and columns.
            </p>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                           id="exemption-master-table">
                        <caption class="visually-hidden">PT exemption configurations</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Course</th>
                                <th scope="col" class="text-nowrap">Effective From</th>
                                <th scope="col" class="text-nowrap">PT Timing</th>
                                <th scope="col">Gender</th>
                                <th scope="col">PT Exemption Count (Days)</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="exemptionDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="exemption-master-table"></div>
            </div>

        </div>
    </div>

    {{-- Row markup comes from the shared partials, printed once here and filled
         per row by the grid's render callbacks (the feed is built in the
         controller, which this redesign does not touch). __LM_*__ are
         placeholders replaced client-side with escaped row values. --}}
    <template id="exemptionStatusOn">@include('admin.master.partials.grid-status', ['active' => true])</template>
    <template id="exemptionStatusOff">@include('admin.master.partials.grid-status', ['active' => false])</template>
    <template id="exemptionActionsOn">
        @include('admin.master.partials.grid-actions', [
            'name'   => '__LM_NAME__',
            'edit'   => ['href' => '__LM_EDIT__', 'attrs' => ['data-mst-modal-form' => true]],
            'toggle' => ['active' => true, 'id' => '__LM_ID__', 'class' => 'plain-status-toggle exemption-status-toggle'],
            'delete' => ['disabled' => true, 'reason' => 'Only inactive records can be deleted. Deactivate it first.'],
        ])
    </template>
    <template id="exemptionActionsOff">
        @include('admin.master.partials.grid-actions', [
            'name'   => '__LM_NAME__',
            'edit'   => ['href' => '__LM_EDIT__', 'attrs' => ['data-mst-modal-form' => true]],
            'toggle' => ['active' => false, 'id' => '__LM_ID__', 'class' => 'plain-status-toggle exemption-status-toggle'],
            'delete' => ['class' => 'exemption-delete-btn', 'attrs' => ['data-id' => '__LM_ID__']],
        ])
    </template>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="exemptionColumnVisibilityModal" tabindex="-1"
     aria-labelledby="exemptionColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="exemptionColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="exemptionColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
$(function () {
    const exportUrl = "{{ route('admin.pt-exemption-master.export') }}";
    const coursesByStatus = {
        active: @json($coursesActive ?? []),
        archive: @json($coursesArchive ?? []),
    };
    let currentStatus = 'active';

    /* ── Row templates (shared grid-status / grid-actions partials) ── */
    function lmTemplate(id) {
        const tpl = document.getElementById(id);
        if (!tpl) {
            return '';
        }
        const box = document.createElement('div');
        box.innerHTML = tpl.innerHTML.trim();
        // custom.js binds every .status-toggle to the generic table/column
        // endpoint. This switch posts to the module's own status route (the
        // handler below), so it must not also match that global handler.
        box.querySelectorAll('.status-toggle').forEach(function (el) {
            el.classList.remove('status-toggle');
        });
        return box.innerHTML;
    }

    function lmEscape(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // The Edit link is read from the controller's own action HTML so it stays
    // exactly the URL the server built (course + effective-from date).
    function lmEditHref(serverHtml) {
        const doc = new DOMParser().parseFromString(String(serverHtml || ''), 'text/html');
        const link = doc.querySelector('a[href]');
        return link ? link.getAttribute('href') : '#';
    }

    const tpl = {
        statusOn: lmTemplate('exemptionStatusOn'),
        statusOff: lmTemplate('exemptionStatusOff'),
        actionsOn: lmTemplate('exemptionActionsOn'),
        actionsOff: lmTemplate('exemptionActionsOff'),
    };

    function isActiveRow(row) {
        return parseInt(row && row.active_inactive, 10) === 1;
    }

    function renderStatus(data, type, row) {
        if (type !== 'display') {
            return data;
        }
        return isActiveRow(row) ? tpl.statusOn : tpl.statusOff;
    }

    function renderActions(data, type, row) {
        if (type !== 'display') {
            return data;
        }
        const name = (row.course_name || '') + (row.gender ? ' (' + row.gender + ')' : '');
        return (isActiveRow(row) ? tpl.actionsOn : tpl.actionsOff)
            .split('__LM_ID__').join(lmEscape(row.pk))
            .split('__LM_EDIT__').join(lmEscape(lmEditHref(data)))
            .split('__LM_NAME__').join(lmEscape(name));
    }

    const table = $('#exemption-master-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        // The layout's global default is responsive:true, which collapses the
        // (wide) Action stack into a "+" child row. .table-responsive scrolls instead.
        responsive: false,
        autoWidth: false,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.pt-exemption-master.index') }}",
            data: function (d) {
                d.pk = $('#pk').val();
                d.active_inactive = $('#active_inactive').val();
                d.status_filter = currentStatus;
                d.course_filter = $('#courseFilter').val();
                d.from_date = $('#timePeriodFilter').data('from') || '';
                d.to_date = $('#timePeriodFilter').data('to') || '';
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course.course_name' },
            { data: 'effective_from_display', name: 'effective_from' },
            { data: 'apply_cutoff_time_display', name: 'apply_cutoff_time', orderable: false, searchable: false },
            { data: 'gender', name: 'gender' },
            { data: 'exemption_days_display', name: 'exemption_days' },
            { data: 'status', name: 'status', orderable: false, searchable: false, render: renderStatus },
            { data: 'action', name: 'action', orderable: false, searchable: false, render: renderActions },
        ],
        language: {
            emptyTable: 'No PT exemption configuration found.',
        },
    });

    /* ── Time Period: date-range picker on effective_from ── */
    const $period = $('#timePeriodFilter');

    $period.daterangepicker({
        autoUpdateInput: false,
        opens: 'right',
        locale: {
            format: 'DD-MM-YYYY',
            cancelLabel: 'Clear',
            applyLabel: 'Apply',
        },
        ranges: {
            'Today': [moment(), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last 3 Months': [moment().subtract(3, 'months').startOf('day'), moment()],
            'Last 6 Months': [moment().subtract(6, 'months').startOf('day'), moment()],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
        },
    });

    $period.on('apply.daterangepicker', function (ev, picker) {
        $(this)
            .val(picker.startDate.format('DD-MM-YYYY') + ' – ' + picker.endDate.format('DD-MM-YYYY'))
            .data('from', picker.startDate.format('YYYY-MM-DD'))
            .data('to', picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });

    $period.on('cancel.daterangepicker', function () {
        $(this).val('').removeData('from').removeData('to');
        table.ajax.reload();
    });

    /* ── Active / Archive status tabs ── */
    function populateCourseFilter(status) {
        const $sel = $('#courseFilter');
        const data = coursesByStatus[status] || {};
        $sel.find('option:not(:first)').remove();
        $.each(data, function (pk, name) {
            $sel.append($('<option>', { value: pk, text: name }));
        });
        $sel.val('').trigger('change.select2');
    }

    function setStatusTab($btn, status) {
        $('#filterActive, #filterArchive')
            .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $btn.addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
        currentStatus = status;
        populateCourseFilter(status);
        table.ajax.reload();
    }

    $('#filterActive').on('click', function () {
        setStatusTab($(this), 'active');
    });

    $('#filterArchive').on('click', function () {
        setStatusTab($(this), 'archive');
    });

    /* ── Course filter → reload table (jQuery binding: Select2 fires a jQuery change) ── */
    $('#courseFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        $('#courseFilter').val('').trigger('change.select2');
        $period.val('').removeData('from').removeData('to');
        table.search('');
        setStatusTab($('#filterActive'), 'active');
    });

    /* ── Download: export current filters/search to PDF/Excel ── */
    function exemptionExportUrl(format) {
        const params = $.param({
            format: format,
            status_filter: currentStatus,
            course_filter: $('#courseFilter').val() || '',
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
            search: table.search() || '',
        });
        return exportUrl + '?' + params;
    }

    $('#exemptionExportPdf').on('click', function () {
        window.location.href = exemptionExportUrl('pdf');
    });

    $('#exemptionExportExcel').on('click', function () {
        window.location.href = exemptionExportUrl('excel');
    });

    /* ── Status toggle (own route: POST status/{id}) ── */
    $(document).on('change', '.exemption-status-toggle', function () {
        const id = $(this).data('id');
        const active = $(this).is(':checked') ? 1 : 0;
        const $toggle = $(this);

        const actionWord = active ? 'activate' : 'inactivate';

        Swal.fire({
            title: 'Are you sure?',
            html: 'This will ' + actionWord + ' <strong style="color:#d33;">both Male and Female</strong> PT exemption for this course.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, ' + actionWord + ' both',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (!result.isConfirmed) {
                $toggle.prop('checked', !active);
                return;
            }

            $.ajax({
                url: "{{ route('admin.pt-exemption-master.status', ':id') }}".replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    active_inactive: active,
                },
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    }
                },
                error: function () {
                    $toggle.prop('checked', !active);
                    toastr.error('Failed to update status.');
                }
            });
        });
    });

    /* ── Delete ── */
    $(document).on('click', '.exemption-delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            html: 'This will permanently delete <strong style="color:#d33;">both Male and Female</strong> PT exemption for this course.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete both',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('admin.pt-exemption-master.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function (res) {
                    toastr.success(res.message || 'Record deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete record.';
                    toastr.error(message);
                },
            });
        });
    });

    /* ---------------- Column show / hide (DataTables API) ---------------- */
    const exemptionColStorageKey = 'exemptionGrid:hiddenColumns:v1';

    function exemptionGetHiddenCols() {
        try {
            const raw = localStorage.getItem(exemptionColStorageKey);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function exemptionPersistHiddenCols(arr) {
        try { localStorage.setItem(exemptionColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupExemptionColumns(dt) {
        if (!dt) {
            return;
        }
        const hidden = exemptionGetHiddenCols();

        dt.columns().every(function () {
            const idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        const $grid = $('#exemptionColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            const idx = this.index();
            const title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            const inputId = 'exemptioncolvis_' + idx;
            const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                const h = exemptionGetHiddenCols();
                const pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                exemptionPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    table.on('init.dt', function () {
        setupExemptionColumns(table);
    });
    // Fallback in case the init event has already fired.
    setTimeout(function () {
        if ($('#exemptionColumnToggleGrid').children().length === 0) {
            setupExemptionColumns(table);
        }
    }, 400);
});
</script>
@endpush
