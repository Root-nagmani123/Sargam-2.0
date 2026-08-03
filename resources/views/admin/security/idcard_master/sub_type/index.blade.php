@extends('admin.layouts.master')
@section('title', 'Card Sub Type Master - Security')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'ID Card - Sub Types'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Sub Type & Mapping</h4>
                <a href="{{ route('admin.security.idcard_sub_type.create') }}" class="btn btn-primary" id="openCreateSubType">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Sub Type
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table mb-0" id="subTypeTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Card Type</th>
                            <th>Employee Category</th>
                            <th>Sub Type</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="subTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" id="subTypeModalContent">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var subTypeTable = $('#subTypeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.security.idcard_sub_type.index') }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'sec_card_name', name: 't.sec_card_name' },
            { data: 'card_name_label', name: 'm.card_name' },
            { data: 'config_name', name: 'm.config_name' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc'], [3, 'asc']],
        language: {
            search: 'Search sub types:',
            zeroRecords: 'No Sub Types found.',
            emptyTable: 'No Sub Types found.'
        }
    });

    $('#openCreateSubType').on('click', function (e) {
        e.preventDefault();
        $.get($(this).attr('href'), function (data) {
            $('#subTypeModalContent').html(data);
            $('#subTypeModal').modal('show');
        });
    });

    // Edit: open modal via AJAX (controller returns _form only for AJAX)
    $(document).on('click', '.openEditSubType', function (e) {
        e.preventDefault();
        $.get($(this).attr('href'), function (data) {
            $('#subTypeModalContent').html(data);
            $('#subTypeModal').modal('show');
        });
    });

    // After status toggle success (global custom.js posts to /admin/toggle-status),
    // reload the table so Active/Inactive UI + delete restrictions match DB.
    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (!settings || !settings.url) return;

        var url = String(settings.url);
        var isToggleRequest = url.includes('toggle-status') || url.includes('toggleStatus');
        if (!isToggleRequest) return;

        var tableName = null;
        var data = settings.data;

        if (typeof data === 'string') {
            var m = data.match(/[&?]table=([^&]+)/);
            if (m && m[1]) tableName = decodeURIComponent(m[1]);
        } else if (data && typeof data === 'object') {
            tableName = data.table ?? null;
        }

        if (window.location.pathname.includes('idcard-sub-type') || tableName === 'sec_id_cardno_config_map') {
            subTypeTable.ajax.reload(null, false);
        }
    });
});
</script>
@endpush

