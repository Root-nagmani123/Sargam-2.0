@extends('admin.layouts.master')

@section('title', 'Put In HAC - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());
    $estateSelfQuery = $estateSelfHomeTab ? ['scope' => 'self'] : [];

    // HAC Person (and nothing else) reaches this queue from the HAC side and has
    // no Request For Estate listing to go back to.
    $showBackToRequests = ! hasRole('HAC Person')
        || hasRole('Estate Admin') || hasRole('Super Admin')
        || hasRole('Training Induction Admin') || hasRole('Training MCTP Admin')
        || hasRole('Training IST') || hasRole('Staff') || hasRole('Officer Trainee')
        || hasRole('Doctor') || hasRole('Guest Faculty') || hasRole('Internal Faculty');
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page pih-page">
    <x-breadcrum title="Put In HAC" :showBack="false">
        @if($showBackToRequests)
            <a href="{{ route('admin.estate.request-for-estate', $estateSelfQuery) }}" class="btn ds-btn-neutral me-3">
                Back to Request for Estate
            </a>
        @endif
        <span class="pih-selected-count me-3" id="selectedCountText">0 selected</span>
        <button type="button" class="btn ds-btn-submit" id="btnPutInHac" disabled>
            Put Selected in HAC
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">
            <div id="put-in-hac-card-body">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="pihBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#pihColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div id="pihDtSearch" class="programme-dt-search" data-dt-search-for="putInHacTable"></div>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {!! $dataTable->table(['aria-describedby' => 'put-in-hac-caption']) !!}
                    </div>
                    {{-- Left empty on purpose: datatable-global-ui.js fills this with the
                         pagination and the "Showing [10] of N items" count. --}}
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="putInHacTable"></div>
                </div>

                <div id="put-in-hac-caption" class="visually-hidden">Put In HAC - Estate requests list</div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="pihColumnVisibilityModal" tabindex="-1" aria-labelledby="pihColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="pihColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="pihColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(function() {
        var putInHacUrl = '{{ route("admin.estate.put-in-hac.action") }}';
        var csrf = '{{ csrf_token() }}';
        var $table = $('#putInHacTable');

        function pihNotify(type, message) {
            var $host = $('#put-in-hac-card-body');
            var cls = type === 'success' ? 'alert-success' : 'alert-danger';
            var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            $host.find('.' + cls).remove();
            $host.prepend('<div class="alert ' + cls + ' alert-dismissible fade show d-flex align-items-center rounded-1 shadow-sm" role="alert">' +
                '<i class="bi ' + icon + ' me-2"></i><span class="flex-grow-1">' + message + '</span>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            if (type === 'success') {
                setTimeout(function() { $host.find('.alert-success').fadeOut(); }, 4000);
            }
        }

        /* ---------- Selection ---------- */
        function updateSelectedCount() {
            var n = $('.put-in-hac-checkbox:checked').length;
            $('#btnPutInHac').prop('disabled', n === 0);
            $('#selectedCountText').text(n + ' selected');
        }

        $(document).on('change', '.put-in-hac-checkbox', updateSelectedCount);

        // A redraw (page change / search / sort) replaces the rows, so the
        // previous page's ticks are gone — resync the counter.
        $table.on('draw.dt', updateSelectedCount);

        /* ---------- Column visibility (persisted per browser, per user) ---------- */
        var pihColStorageKey = 'sargam.putInHac.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function pihGetHiddenCols() {
            try {
                var raw = localStorage.getItem(pihColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return []; // private mode / storage disabled / corrupt value
            }
        }

        function pihPersistHiddenCols(arr) {
            try { localStorage.setItem(pihColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupPihColumns(dt) {
            if (!dt) return;
            var hidden = pihGetHiddenCols();

            dt.columns().every(function() {
                var idx = this.index();
                // Column 0 is the selection checkbox — hiding it would strand the
                // page's only action, so it is never offered.
                this.visible(idx === 0 || hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#pihColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                if (idx === 0) return;
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) return;

                var inputId = 'pihcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function() {
                    var h = pihGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    pihPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        $table.on('init.dt', function() { setupPihColumns($(this).DataTable()); });
        // Yajra may have finished initialising before this handler was bound.
        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            setupPihColumns($table.DataTable());
        }

        /* ---------- Put selected in HAC ---------- */
        $('#btnPutInHac').on('click', function() {
            var ids = $('.put-in-hac-checkbox:checked').map(function() { return $(this).data('pk'); }).get();
            if (ids.length === 0) return;

            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing…');

            var requestSucceeded = false;
            $.ajax({
                url: putInHacUrl,
                type: 'POST',
                data: { _token: csrf, ids: ids },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res.success && res.message) {
                        requestSucceeded = true;
                        $table.DataTable().ajax.reload(null, false);
                        pihNotify('success', res.message);
                    }
                },
                error: function(xhr) {
                    pihNotify('error', (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to put in HAC. Please try again.');
                },
                complete: function() {
                    btn.text('Put Selected in HAC');
                    if (requestSucceeded) {
                        btn.prop('disabled', true);
                        $('#selectedCountText').text('0 selected');
                    } else {
                        updateSelectedCount();
                    }
                }
            });
        });

        updateSelectedCount();
    });
    </script>
@endpush
