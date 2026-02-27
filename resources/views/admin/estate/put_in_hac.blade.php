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
                <div>
                    <a href="{{ route('admin.estate.request-for-estate') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Request for Estate
                    </a>
                </div>
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
                        'class' => 'table table-bordered table-striped table-hover align-middle mb-0',
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
        padding: 0.75rem;
        white-space: nowrap;
    }
    #putInHacTable_wrapper .dataTables_length select,
    #putInHacTable_wrapper .dataTables_filter input {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
    }
    #putInHacTable_wrapper .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.5rem;
        margin: 0 1px;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-border-color);
    }
    #putInHacTable_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--bs-primary);
        color: #fff !important;
        border-color: var(--bs-primary);
    }
    .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
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

        $(document).on('change', '.put-in-hac-checkbox', updateSelectedCount);

        $('#btnPutInHac').on('click', function() {
            var checked = $('.put-in-hac-checkbox:checked');
            var ids = checked.map(function() { return $(this).data('pk'); }).get();
            if (ids.length === 0) return;

            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Processing...');

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
                        $('#putInHacTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + res.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#put-in-hac-card-body').find('.alert-success').remove();
                        $('#put-in-hac-card-body').prepend(alertHtml);
                        setTimeout(function() { $('#put-in-hac-card-body .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to put in HAC. Please try again.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#put-in-hac-card-body').find('.alert-danger').remove();
                    $('#put-in-hac-card-body').prepend(alertHtml);
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-check2-square me-1"></i> Put Selected in HAC');
                    updateSelectedCount();
                }
            });
        });
    });
    </script>
@endpush
