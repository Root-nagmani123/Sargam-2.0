@extends('admin.layouts.master')

@section('title', $studentName . "'s Comment/ Feedback")

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .ot-comments-page .sl-filter-select {
        width: 200px; flex: 0 0 auto; height: 40px; border-radius: 8px;
        font-size: 0.9375rem; color: #344054;
    }
    .ot-comments-page .sl-filter-item { display: inline-flex; align-items: center; }
    .ot-comments-page .sl-daterange-wrap { position: relative; }
    .ot-comments-page .sl-daterange-input {
        width: 220px; height: 40px; padding: 1.05rem 0.875rem 0.15rem; cursor: pointer;
        background-image: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.85rem;
    }
    .ot-comments-page .sl-daterange-label { position: absolute; top: 4px; left: 0.875rem; font-size: 0.68rem; color: #667085; pointer-events: none; }

    .ot-comments-page .sl-toolbar-btn {
        height: 40px; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0 1.1rem;
        font-size: 0.9375rem; font-weight: 500; color: #004a93; border: 1px solid #d0d5dd; border-radius: 8px; background: #fff;
    }
    .ot-comments-page .sl-toolbar-btn:hover { background: #f9fafb; }

    .ot-comments-page .sl-table-shell { position: relative; }
    .ot-comments-page .sl-table-loading {
        position: absolute; inset: 0; background: rgba(255, 255, 255, 0.74);
        display: none; align-items: center; justify-content: center; z-index: 30;
    }
    .ot-comments-page .sl-table-loading.is-active { display: flex; }
    .ot-comments-page .sl-table-loading-card {
        display: inline-flex; align-items: center; gap: 0.65rem; padding: 0.7rem 0.95rem;
        border: 1px solid #d0d5dd; border-radius: 10px; background: #fff;
        box-shadow: 0 8px 22px rgba(16, 24, 40, 0.12); color: #344054; font-weight: 600; font-size: 0.9rem;
    }

    /* The message is the point of this table — give it the room and let it wrap. */
    .ot-comments-page .programme-dt-table td.msg-cell { white-space: normal; min-width: 26rem; }
    .ot-comments-page .programme-dt-table th,
    .ot-comments-page .programme-dt-table td { vertical-align: top; }
    .ot-comments-page .sl-dt-scroll-host { overflow-x: auto; }
</style>
@endpush

@section('content')
<div class="container-fluid ot-comments-page">
    <x-breadcrum title="{{ $studentName }}'s Comment/ Feedback" :showBack="true" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academics'],
        ['label' => 'Time Table'],
        ['label' => 'OT/ Participants List', 'url' => route('admin.dashboard.ot-participants')],
    ]" />
    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <div class="dropdown">
            <button type="button" class="btn sl-toolbar-btn dropdown-toggle border-0" id="otcDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="otcDownloadBtn">
                <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="otcDownloadCsv"><i class="bi bi-file-earmark-excel text-success"></i><span>Download Excel</span></button></li>
                <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="otcDownloadPdf"><i class="bi bi-filetype-pdf text-danger"></i><span>Download PDF</span></button></li>
            </ul>
        </div>
        <button type="button" class="btn sl-toolbar-btn border-0" id="otcPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="sl-filter-item">
                        <div class="sl-daterange-wrap">
                            <span class="sl-daterange-label">Date Range</span>
                            <input type="text" id="otcDateRange" class="form-control sl-filter-select sl-daterange-input"
                                placeholder="Select dates" autocomplete="off" readonly aria-label="Filter by date range"
                                value="{{ (!empty($filters['from_date']) && !empty($filters['to_date'])) ? \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y').' - '.\Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') : '' }}">
                        </div>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="otcResetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns sl-toolbar-btn" id="btnOtcColumns" data-bs-toggle="modal" data-bs-target="#otcColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="sl-filter-item">
                        <div id="otcDtSearch" class="programme-dt-search" data-dt-search-for="otcTable"></div>
                    </div>
                </div>
            </div>

            <div class="sl-table-shell">
                <div class="sl-table-loading" id="otcTableLoading" aria-live="polite" aria-busy="false">
                    <div class="sl-table-loading-card">
                        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                        <span>Loading comments...</span>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="sl-dt-scroll-host">
                        <table class="table programme-dt-table" id="otcTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Comment/Feedback By</th>
                                    <th>Message</th>
                                    <th>Notify OT</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="otcDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="otcTable"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="otcColumnVisibilityModal" tabindex="-1" aria-labelledby="otcColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="otcColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="otcColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        const filters = @json($filters ?? []);
        const baseUrl = "{{ route('admin.dashboard.ot-participants.comments', $studentId) }}";
        const LOCKED_COLUMNS = [0]; // S.No stays visible
        let dt = null;
        let loadingRequests = 0;

        function setTableLoading(show) {
            const $loader = $('#otcTableLoading');
            if (!$loader.length) { return; }
            loadingRequests = show ? loadingRequests + 1 : Math.max(loadingRequests - 1, 0);
            const active = loadingRequests > 0;
            $loader.toggleClass('is-active', active).attr('aria-busy', active ? 'true' : 'false');
        }

        function getFilterState() {
            return {
                from_date: (filters.from_date || '').toString(),
                to_date: (filters.to_date || '').toString(),
            };
        }

        function syncUrl() {
            const p = new URLSearchParams(window.location.search);
            Object.entries(getFilterState()).forEach(([k, v]) => {
                if (v === '' || v === null || v === undefined) { p.delete(k); } else { p.set(k, v); }
            });
            const qs = p.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }

        dt = $('#otcTable').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            order: [],
            ordering: false,
            language: { emptyTable: 'No comments/feedback yet.' },
            responsive: false,
            autoWidth: false,
            ajax: {
                url: baseUrl,
                type: 'GET',
                data: function(d) { Object.assign(d, getFilterState()); },
            },
            columns: [
                { data: 's_no', name: 's_no' },
                { data: 'comment_by', name: 'comment_by' },
                { data: 'message', name: 'message', className: 'msg-cell' },
                { data: 'notify_ot', name: 'notify_ot' },
                { data: 'date', name: 'date' },
            ],
        });

        dt.on('processing.dt', function(e, settings, processing) { setTableLoading(processing); });

        /* ── Date Range ── */
        const $period = $('#otcDateRange');
        $period.daterangepicker({
            autoUpdateInput: false, opens: 'left',
            locale: { format: 'DD-MM-YYYY', cancelLabel: 'Clear', applyLabel: 'Apply' },
            ranges: {
                'Today': [moment(), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months').startOf('day'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
        });
        $period.on('apply.daterangepicker', function(ev, picker) {
            filters.from_date = picker.startDate.format('YYYY-MM-DD');
            filters.to_date = picker.endDate.format('YYYY-MM-DD');
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            syncUrl();
            dt.ajax.reload(null, true);
        });
        $period.on('cancel.daterangepicker', function() {
            filters.from_date = ''; filters.to_date = '';
            $(this).val('');
            syncUrl();
            dt.ajax.reload(null, true);
        });

        $('#otcResetFilters').on('click', function() { window.location.href = baseUrl; });

        /* ── Download / Print ── */
        function buildExportUrl(format) {
            const params = new URLSearchParams();
            Object.entries(getFilterState()).forEach(function([k, v]) {
                if (v !== '' && v !== null && v !== undefined) { params.set(k, v); }
            });
            const search = dt ? dt.search() : '';
            if (search) { params.set('search', search); }
            const base = "{{ route('admin.dashboard.ot-participants.comments.export', ['id' => $studentId, 'format' => 'FORMAT']) }}"
                .replace('FORMAT', format);
            const q = params.toString();
            return q ? base + '?' + q : base;
        }
        $('#otcDownloadCsv').on('click', function(e) { e.preventDefault(); window.location.href = buildExportUrl('csv'); });
        $('#otcDownloadPdf').on('click', function(e) { e.preventDefault(); window.open(buildExportUrl('pdf'), '_blank'); });
        $('#otcPrintBtn').on('click', function() { window.open(buildExportUrl('print'), '_blank'); });

        /* ── Column show / hide ── */
        const otcColStorageKey = 'otParticipantComments:hiddenColumns:v1';
        function otcGetHidden() {
            try { const raw = localStorage.getItem(otcColStorageKey); const arr = raw ? JSON.parse(raw) : []; return Array.isArray(arr) ? arr : []; }
            catch (e) { return []; }
        }
        function otcPersistHidden(arr) { try { localStorage.setItem(otcColStorageKey, JSON.stringify(arr)); } catch (e) {} }
        function setupOtcColumns() {
            if (!dt) { return; }
            const hidden = otcGetHidden().filter(idx => LOCKED_COLUMNS.indexOf(idx) === -1);
            dt.columns().every(function() {
                const idx = this.index();
                if (LOCKED_COLUMNS.indexOf(idx) !== -1) { this.visible(true, false); return; }
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            const $grid = $('#otcColumnToggleGrid').empty();
            dt.columns().every(function() {
                const idx = this.index();
                const title = $(this.header()).text().trim();
                const isLocked = LOCKED_COLUMNS.indexOf(idx) !== -1;
                const checked = this.visible() ? 'checked' : '';
                $grid.append(
                    '<div class="col-12 col-md-6"><div class="form-check">' +
                    '<input class="form-check-input otc-col-toggle" type="checkbox" value="' + idx + '" id="otcCol' + idx + '" ' + checked + (isLocked ? ' disabled' : '') + '>' +
                    '<label class="form-check-label" for="otcCol' + idx + '">' + title + '</label>' +
                    '</div></div>'
                );
            });
            $('.otc-col-toggle').off('change').on('change', function() {
                const idx = parseInt(this.value, 10);
                if (LOCKED_COLUMNS.indexOf(idx) !== -1) { return; }
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                const hiddenNow = [];
                dt.columns().every(function() { if (!this.visible()) { hiddenNow.push(this.index()); } });
                otcPersistHidden(hiddenNow);
            });
        }
        dt.on('init.dt', setupOtcColumns);
    });
</script>
@endpush
@endsection
