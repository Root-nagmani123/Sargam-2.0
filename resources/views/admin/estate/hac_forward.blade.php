@extends('admin.layouts.master')

@section('title', 'HAC Forward to Allotment - Request For Estate - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="HAC Forward to Allotment" />
    <x-estate-workflow-stepper current="hac-forward" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-send-fill me-2"></i>Display all home approval requests from employees</h5>
                    <p class="small mb-0 opacity-90 mt-1">HAC forwards approved requests to the allotment team</p>
                </div>
                <div>
                    <a href="{{ route('admin.estate.put-in-hac') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Put In HAC
                    </a>
                    <a href="{{ route('admin.estate.request-for-estate') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-building me-1"></i> Request for Estate
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-4 p-lg-5">
            <div class="alert alert-info border-0 rounded-3 d-flex align-items-start gap-2 mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>HAC workflow:</strong> This page lists estate requests that have been put in HAC. Click <strong>"Forward"</strong> on each request to move it to <strong>HAC Approved</strong>; from there the allotment team can allot a house to add the record to <strong>Possession Details</strong>.
                </div>
            </div>

            <div id="hac-forward-card-body">
                <div class="table-responsive hac-forward-table-wrap">
                    {!! $dataTable->table([
                        'class' => 'table table-bordered table-striped table-hover align-middle mb-0',
                        'aria-describedby' => 'hac-forward-caption'
                    ]) !!}
                </div>
                <div id="hac-forward-caption" class="visually-hidden">HAC Forward - Home approval requests list</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hac-forward-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .hac-forward-table-wrap table {
        min-width: 1200px;
    }
    #hacForwardTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
    #hacForwardTable_wrapper .dataTables_length select,
    #hacForwardTable_wrapper .dataTables_filter input {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
    }
    #hacForwardTable_wrapper .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.5rem;
        margin: 0 1px;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-border-color);
    }
    #hacForwardTable_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--bs-primary);
        color: #fff !important;
        border-color: var(--bs-primary);
    }
    .btn-forward-to-allotment {
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(function() {
        $(document).on('click', '.btn-forward-to-allotment', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.data('url');
            var reqId = $btn.data('req-id') || '';
            if (!url) return;
            if (!confirm('Forward request ' + reqId + ' to allotment team?')) return;

            var origHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Forwarding...');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res.success && res.message) {
                        $('#hacForwardTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + res.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#hac-forward-card-body').find('.alert-success').remove();
                        $('#hac-forward-card-body').prepend(alertHtml);
                        setTimeout(function() { $('#hac-forward-card-body .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to forward. Please try again.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#hac-forward-card-body').find('.alert-danger').remove();
                    $('#hac-forward-card-body').prepend(alertHtml);
                },
                complete: function() {
                    $btn.prop('disabled', false).html(origHtml);
                }
            });
        });
    });
    </script>
@endpush