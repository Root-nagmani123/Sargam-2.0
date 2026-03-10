@extends('admin.layouts.master')

@section('title', 'Put In HAC - Request For Estate - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Put In HAC" />
    <x-estate-workflow-stepper current="put-in-hac" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-building-check me-2"></i>Put In HAC</h5>
                    <p class="small mb-0 opacity-90 mt-1">Select estate requests and put them in House Allotment Committee (HAC)</p>
                </div>
                @if(!hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST') || hasRole('Staff') || hasRole('Student-OT') || hasRole('Doctor') || hasRole('Guest Faculty') || hasRole('Internal Faculty'))
                <div>
                    <a href="{{ route('admin.estate.request-for-estate') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Request for Estate
                    </a>
                </div>
                @endif
            </div>
        </div>
        <div class="card-body p-4 p-lg-5">
            <div class="alert alert-info border-0 rounded-3 d-flex align-items-start gap-2 mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Authority workflow:</strong> This page displays all estate requests that have not been put in HAC. Check the boxes for requests you want to send to HAC, then click <strong>"Put Selected in HAC"</strong>.
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <button type="button" class="btn btn-success" id="btnPutInHac" disabled>
                    <i class="bi bi-check2-square me-1"></i> Put Selected in HAC
                </button>
                <span class="text-body-secondary small" id="selectedCountText">0 selected</span>
            </div>

            <div id="put-in-hac-card-body">
                <div class="table-responsive text-nowrap put-in-hac-table-wrap">
                    {!! $dataTable->table([
                        'class' => 'table table-bordered table-striped table-hover text-nowrap align-middle mb-0 w-100',
                        'aria-describedby' => 'put-in-hac-caption'
                    ]) !!}
                </div>
                <div id="put-in-hac-caption" class="visually-hidden">Put In HAC - Estate requests list</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .put-in-hac-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .put-in-hac-table-wrap table {
        min-width: 1200px;
    }

    #putInHacTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        white-space: nowrap;
    }

    #putInHacTable_wrapper .dataTables_length,
    #putInHacTable_wrapper .dt-length,
    #putInHacTable_wrapper .dataTables_filter,
    #putInHacTable_wrapper .dt-search {
        margin-bottom: 0.75rem;
    }

    #putInHacTable_wrapper .dataTables_length label,
    #putInHacTable_wrapper .dt-length label,
    #putInHacTable_wrapper .dataTables_filter label,
    #putInHacTable_wrapper .dt-search label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        flex-wrap: wrap;
    }

    #putInHacTable_wrapper .dataTables_length select,
    #putInHacTable_wrapper .dt-length select,
    #putInHacTable_wrapper .dataTables_filter input,
    #putInHacTable_wrapper .dt-search input {
        min-height: calc(1.5em + 0.5rem + 2px);
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        background-color: var(--bs-body-bg);
    }

    #putInHacTable_wrapper .dataTables_length select,
    #putInHacTable_wrapper .dt-length select {
        width: 5.5rem !important;
        min-width: 5.5rem !important;
        padding: 0.25rem 2.25rem 0.25rem 0.65rem !important;
        line-height: 1.25;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-position: right 0.6rem center !important;
        background-size: 0.9rem !important;
    }

    #putInHacTable_wrapper .dataTables_filter input,
    #putInHacTable_wrapper .dt-search input {
        min-width: 260px;
    }

    #putInHacTable_wrapper .dataTables_filter input::placeholder,
    #putInHacTable_wrapper .dt-search input::placeholder {
        color: var(--bs-secondary-color);
    }

    #putInHacTable_wrapper .dataTables_length select:focus,
    #putInHacTable_wrapper .dt-length select:focus,
    #putInHacTable_wrapper .dataTables_filter input:focus,
    #putInHacTable_wrapper .dt-search input:focus {
        outline: 0;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15);
        border-color: var(--bs-primary);
    }

    #putInHacTable_wrapper .dataTables_info,
    #putInHacTable_wrapper .dt-info {
        padding-top: 0.5rem;
        font-size: 0.875rem;
        color: var(--bs-secondary-color);
    }

    #putInHacTable_wrapper .dataTables_paginate .pagination,
    #putInHacTable_wrapper .dt-paging .pagination {
        margin-bottom: 0;
        gap: 0.25rem;
    }

    #putInHacTable_wrapper .dataTables_paginate .page-item .page-link,
    #putInHacTable_wrapper .dt-paging .page-item .page-link {
        border-radius: 0.375rem;
        padding: 0.35rem 0.65rem;
        border: 1px solid var(--bs-border-color);
    }

    #putInHacTable_wrapper .dataTables_paginate .page-item.active .page-link,
    #putInHacTable_wrapper .dt-paging .page-item.active .page-link {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    #putInHacTable_wrapper .dataTables_paginate .page-item.disabled .page-link,
    #putInHacTable_wrapper .dt-paging .page-item.disabled .page-link {
        color: var(--bs-secondary-color);
        background-color: var(--bs-secondary-bg);
        border-color: var(--bs-border-color);
    }

    #putInHacTable_wrapper .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover,
    #putInHacTable_wrapper .dt-paging .page-item:not(.active):not(.disabled) .page-link:hover {
        color: var(--bs-body-color);
        background-color: var(--bs-secondary-bg);
        border-color: var(--bs-border-color);
    }

    #putInHacTable_wrapper table.dataTable thead > tr > th.sorting::before,
    #putInHacTable_wrapper table.dataTable thead > tr > th.sorting::after,
    #putInHacTable_wrapper table.dataTable thead > tr > th.sorting_asc::before,
    #putInHacTable_wrapper table.dataTable thead > tr > th.sorting_asc::after,
    #putInHacTable_wrapper table.dataTable thead > tr > th.sorting_desc::before,
    #putInHacTable_wrapper table.dataTable thead > tr > th.sorting_desc::after {
        color: rgba(255, 255, 255, 0.85);
    }

    .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    @media (max-width: 767.98px) {
        #putInHacTable_wrapper .dataTables_filter input,
        #putInHacTable_wrapper .dt-search input {
            min-width: 180px;
            width: 100%;
        }
    }
</style>
@endpush


@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(function() {
        var putInHacUrl = '{{ route("admin.estate.put-in-hac.action") }}';
        var csrf = '{{ csrf_token() }}';

        function updateSelectedCount() {
            var checked = $('.put-in-hac-checkbox:checked');
            var n = checked.length;
            $('#btnPutInHac').prop('disabled', n === 0);
            $('#selectedCountText').text(n + ' selected');
        }

        function syncPutInHacTableUi() {
            var wrapper = $('#putInHacTable_wrapper');
            if (!wrapper.length) return;

            wrapper.find('.dataTables_length label, .dt-length label')
                .addClass('d-inline-flex align-items-center gap-2 mb-0 flex-wrap');
            wrapper.find('.dataTables_filter label, .dt-search label')
                .addClass('d-inline-flex align-items-center gap-2 mb-0 flex-wrap');
            wrapper.find('.dataTables_length select, .dt-length select')
                .removeClass('form-select form-select-sm')
                .attr('aria-label', 'Rows per page')
                .css('width', '')
                .each(function() {
                    this.style.removeProperty('width');
                });
            wrapper.find('.dataTables_filter input, .dt-search input')
                .addClass('form-control form-control-sm')
                .attr('placeholder', 'Search records...')
                .attr('aria-label', 'Search records');
            wrapper.find('.dataTables_info, .dt-info')
                .addClass('small text-body-secondary');
            wrapper.find('.dataTables_paginate .pagination, .dt-paging .pagination')
                .addClass('pagination-sm mb-0 justify-content-md-end');
        }

        $(document).on('init.dt draw.dt', function(e, settings) {
            if (!settings || !settings.nTable || settings.nTable.id !== 'putInHacTable') return;
            syncPutInHacTableUi();
            updateSelectedCount();
        });

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#putInHacTable')) {
            syncPutInHacTableUi();
            updateSelectedCount();
        }

        $(document).on('change', '.put-in-hac-checkbox', updateSelectedCount);

        $('#btnPutInHac').on('click', function() {
            var checked = $('.put-in-hac-checkbox:checked');
            var ids = checked.map(function() { return $(this).data('pk'); }).get();
            if (ids.length === 0) return;

            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...');

            var requestSucceeded = false;
            $.ajax({
                url: putInHacUrl,
                type: 'POST',
                data: {
                    _token: csrf,
                    ids: ids
                },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res.success && res.message) {
                        requestSucceeded = true;
                        $('#putInHacTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + res.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#put-in-hac-card-body').find('.alert-success').remove();
                        $('#put-in-hac-card-body').prepend(alertHtml);
                        setTimeout(function() { $('#put-in-hac-card-body .alert-success').fadeOut(); }, 4000);
                        btn.prop('disabled', true).html('<i class="bi bi-check2-square me-1"></i> Put Selected in HAC');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to put in HAC. Please try again.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#put-in-hac-card-body').find('.alert-danger').remove();
                    $('#put-in-hac-card-body').prepend(alertHtml);
                },
                complete: function() {
                    btn.html('<i class="bi bi-check2-square me-1"></i> Put Selected in HAC');
                    if (requestSucceeded) {
                        btn.prop('disabled', true);
                        $('#selectedCountText').text('0 selected');
                    } else {
                        updateSelectedCount();
                    }
                }
            });
        });
    });
    </script>
@endpush
