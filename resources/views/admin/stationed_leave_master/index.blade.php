@extends('admin.layouts.master')

@section('title', 'Stationed Leave Master')

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
<div class="container-fluid mst-page lm-page slm-page">
    <x-breadcrum title="Stationed Leave Master" :showBack="false">
        <a href="{{ route('admin.stationed-leave-master.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Configure Stationed Leave</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    {{-- Status pills (course lifecycle, with counts) left · Download right, above the card. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter by course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill sl-status-tab active"
                        data-status-filter="active" aria-pressed="true">Active: {{ (int) ($activeCount ?? 0) }}</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill sl-status-tab"
                        data-status-filter="archive" aria-pressed="false">Archived: {{ (int) ($archiveCount ?? 0) }}</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap justify-content-end gap-2 mst-secondary-actions">
            <div class="dropdown">
                <button type="button" id="stationedDownload"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm py-2" aria-labelledby="stationedDownload">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="stationedExportPdf">
                            <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i>
                            <span>Download PDF</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="stationedExportExcel">
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
                            @foreach ($activeCourses ?? [] as $pk => $name)
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
                    <button type="button" class="btn programme-dt-btn-columns" id="btnStationedColumns"
                            data-bs-toggle="modal" data-bs-target="#stationedColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="stationedDtSearch" class="programme-dt-search"
                         data-dt-search-for="stationed-leave-table"></div>
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
                           id="stationed-leave-table">
                        <caption class="visually-hidden">Stationed leave configurations</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Course</th>
                                <th scope="col" class="text-nowrap">Effective From</th>
                                <th scope="col" class="text-nowrap">PT Timing</th>
                                <th scope="col">Approval Required</th>
                                <th scope="col">Faculty Count</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="stationedDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="stationed-leave-table"></div>
            </div>

        </div>
    </div>

    {{-- Row markup comes from the shared partials, printed once here and filled
         per row by the grid's render callbacks (the feed is built in the
         controller, which this redesign does not touch). __LM_*__ are
         placeholders replaced client-side with escaped row values. --}}
    <template id="stationedStatusOn">@include('admin.master.partials.grid-status', ['active' => true])</template>
    <template id="stationedStatusOff">@include('admin.master.partials.grid-status', ['active' => false])</template>
    <template id="stationedActionsOn">
        @include('admin.master.partials.grid-actions', [
            'name'   => '__LM_NAME__',
            'edit'   => ['href' => '__LM_EDIT__'],
            'toggle' => ['active' => true, 'id' => '__LM_ID__', 'class' => 'plain-status-toggle stationed-leave-status-toggle'],
            'delete' => ['disabled' => true, 'reason' => 'Only inactive records can be deleted. Deactivate it first.'],
        ])
    </template>
    <template id="stationedActionsOff">
        @include('admin.master.partials.grid-actions', [
            'name'   => '__LM_NAME__',
            'edit'   => ['href' => '__LM_EDIT__'],
            'toggle' => ['active' => false, 'id' => '__LM_ID__', 'class' => 'plain-status-toggle stationed-leave-status-toggle'],
            'delete' => ['class' => 'stationed-leave-delete-btn', 'attrs' => ['data-id' => '__LM_ID__']],
        ])
    </template>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="stationedColumnVisibilityModal" tabindex="-1"
     aria-labelledby="stationedColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="stationedColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="stationedColumnToggleGrid"></div>
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
    const exportUrl = "{{ route('admin.stationed-leave-master.export') }}";
    const query = new URLSearchParams(window.location.search);
    let statusFilter = (query.get('status_filter') || 'active').toLowerCase() === 'archive' ? 'archive' : 'active';
    let courseFilterFromUrl = query.get('course_filter') || '';
    const activeCourses = @json(($activeCourses ?? collect())->toArray());
    const archiveCourses = @json(($archiveCourses ?? collect())->toArray());

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
        statusOn: lmTemplate('stationedStatusOn'),
        statusOff: lmTemplate('stationedStatusOff'),
        actionsOn: lmTemplate('stationedActionsOn'),
        actionsOff: lmTemplate('stationedActionsOff'),
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
        return (isActiveRow(row) ? tpl.actionsOn : tpl.actionsOff)
            .split('__LM_ID__').join(lmEscape(row.pk))
            .split('__LM_EDIT__').join(lmEscape(lmEditHref(data)))
            .split('__LM_NAME__').join(lmEscape(row.course_name || ''));
    }

    function syncListUrl() {
        const params = new URLSearchParams(window.location.search);
        if (statusFilter === 'archive') {
            params.set('status_filter', 'archive');
        } else {
            params.delete('status_filter');
        }

        const selectedCourse = $('#courseFilter').val() || '';
        if (selectedCourse) {
            params.set('course_filter', selectedCourse);
        } else {
            params.delete('course_filter');
        }

        const next = params.toString();
        window.history.replaceState(null, '', window.location.pathname + (next ? '?' + next : ''));
    }

    function applyStatusTabUi() {
        $('.sl-status-tab').removeClass('active').attr('aria-pressed', 'false');
        $('.sl-status-tab[data-status-filter="' + statusFilter + '"]').addClass('active').attr('aria-pressed', 'true');
    }

    function getCoursesForStatus(status) {
        return status === 'archive' ? archiveCourses : activeCourses;
    }

    function setCourseFilterOptions(status, keepCurrent, preferredValue) {
        const selectedBefore = keepCurrent ? ($('#courseFilter').val() || '') : '';
        const targetValue = preferredValue || selectedBefore || '';
        const courses = getCoursesForStatus(status);
        const $courseFilter = $('#courseFilter');

        $courseFilter.empty();
        $courseFilter.append('<option value="">Course Name</option>');

        Object.keys(courses || {}).forEach(function (pk) {
            $courseFilter.append($('<option></option>').val(pk).text(courses[pk]));
        });

        if (targetValue && Object.prototype.hasOwnProperty.call(courses || {}, targetValue)) {
            $courseFilter.val(targetValue);
        } else {
            $courseFilter.val('');
        }
        // Searchable dropdown: repaint the rendered selection after the rebuild.
        $courseFilter.trigger('change.select2');
    }

    applyStatusTabUi();
    setCourseFilterOptions(statusFilter, false, courseFilterFromUrl);

    const table = $('#stationed-leave-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        // The layout's global default is responsive:true, which collapses the
        // (wide) Action stack into a "+" child row. .table-responsive scrolls instead.
        responsive: false,
        autoWidth: false,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.stationed-leave-master.index') }}",
            data: function (d) {
                d.course_filter = $('#courseFilter').val();
                d.from_date = $('#timePeriodFilter').data('from') || '';
                d.to_date = $('#timePeriodFilter').data('to') || '';
                d.status_filter = statusFilter;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course.course_name' },
            { data: 'effective_from_display', name: 'effective_from' },
            { data: 'apply_cutoff_time_display', name: 'apply_cutoff_time', orderable: false, searchable: false },
            { data: 'approval_required_display', name: 'is_faculty_approval_required' },
            { data: 'faculty_count_display', name: 'approvers_count' },
            { data: 'status', name: 'status', orderable: false, searchable: false, render: renderStatus },
            { data: 'action', name: 'action', orderable: false, searchable: false, render: renderActions },
        ],
        language: { emptyTable: 'No stationed leave configuration found.' },
    });

    /* ── Time Period date-range on effective_from ── */
    const $period = $('#timePeriodFilter');

    $period.daterangepicker({
        autoUpdateInput: false,
        opens: 'left',
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

    /* ── Course filter (jQuery binding: Select2 fires a jQuery change) ── */
    $('#courseFilter').on('change', function () {
        syncListUrl();
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        setCourseFilterOptions(statusFilter, false, '');
        $period.val('').removeData('from').removeData('to');
        table.search('');
        syncListUrl();
        table.ajax.reload();
    });

    /* ── Download ── */
    function stationedExportUrl(format) {
        const params = $.param({
            format: format,
            course_filter: $('#courseFilter').val() || '',
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
            status_filter: statusFilter,
        });
        return exportUrl + '?' + params;
    }

    $('#stationedExportPdf').on('click', function () {
        window.location.href = stationedExportUrl('pdf');
    });

    $('#stationedExportExcel').on('click', function () {
        window.location.href = stationedExportUrl('excel');
    });

    /* ── Active / Archive tabs ── */
    $(document).on('click', '.sl-status-tab', function () {
        const next = ($(this).data('status-filter') || '').toString();
        if (!next || next === statusFilter) {
            return;
        }
        statusFilter = next;
        applyStatusTabUi();
        setCourseFilterOptions(statusFilter, false, '');
        syncListUrl();
        table.ajax.reload();
    });

    /* ── Status toggle (own route: POST status/{id}) ── */
    $(document).on('change', '.stationed-leave-status-toggle', function () {
        const id = $(this).data('id');
        const active = $(this).is(':checked') ? 1 : 0;
        const $toggle = $(this);

        $.ajax({
            url: "{{ route('admin.stationed-leave-master.status', ':id') }}".replace(':id', id),
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', active_inactive: active },
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

    /* ── Delete ── */
    $(document).on('click', '.stationed-leave-delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('admin.stationed-leave-master.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message || 'Record deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete record.');
                },
            });
        });
    });

    /* ---------------- Column show / hide ---------------- */
    const stationedColStorageKey = 'stationedGrid:hiddenColumns:v1';

    function stationedGetHiddenCols() {
        try {
            const raw = localStorage.getItem(stationedColStorageKey);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function stationedPersistHiddenCols(arr) {
        try { localStorage.setItem(stationedColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupStationedColumns(dt) {
        if (!dt) {
            return;
        }
        const hidden = stationedGetHiddenCols();

        dt.columns().every(function () {
            const idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        const $grid = $('#stationedColumnToggleGrid');
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

            const inputId = 'stationedcolvis_' + idx;
            const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                const h = stationedGetHiddenCols();
                const pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                stationedPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    table.on('init.dt', function () {
        setupStationedColumns(table);
    });
    setTimeout(function () {
        if ($('#stationedColumnToggleGrid').children().length === 0) {
            setupStationedColumns(table);
        }
    }, 400);
});
</script>
@endpush
