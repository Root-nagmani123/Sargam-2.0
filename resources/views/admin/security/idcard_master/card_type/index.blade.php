@extends('admin.layouts.master')
@section('title', 'Card Type Master - Security')

@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'ID Card - Card Types'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Card Type Master</h4>
                <a href="{{ route('admin.security.idcard_card_type.create') }}" class="btn btn-primary" id="openCreateCardType">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Card Type
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
                <table class="table mb-0" id="cardTypeTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Card Type Name</th>
                            <th style="width:140px;">Status</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cardTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" id="cardTypeModalContent">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var cardTypeTable = $('#cardTypeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.security.idcard_card_type.index') }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'sec_card_name', name: 'sec_card_name' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        language: {
            search: 'Search card types:',
            zeroRecords: 'No Card Types found.',
            emptyTable: 'No Card Types found.'
        }
    });

    // After status toggle (global custom.js → /admin/toggle-status), reload table so delete icons match DB.
    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (!window.location.pathname.includes('idcard-card-type')) {
            return;
        }
        var type = (settings.type || '').toUpperCase();
        if (type !== 'POST') {
            return;
        }
        var url = String(settings.url || '');
        var isCardTypeToggleUrl = url.indexOf('idcard-card-type') !== -1 && url.indexOf('toggle-status') !== -1;
        if (!isCardTypeToggleUrl && url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) {
            return;
        }
        var isCardTypeTable = false;
        var data = settings.data;
        if (isCardTypeToggleUrl) {
            isCardTypeTable = true;
        } else if (typeof data === 'string') {
            isCardTypeTable = data.indexOf('sec_id_cardno_master') !== -1;
        } else if (data && typeof data === 'object' && !(data instanceof FormData)) {
            isCardTypeTable = data.table === 'sec_id_cardno_master';
        }
        if (isCardTypeTable) {
            cardTypeTable.ajax.reload(null, false);
        }
    });

    $('#openCreateCardType').on('click', function (e) {
        e.preventDefault();
        $.get($(this).attr('href'), function (data) {
            $('#cardTypeModalContent').html(data);
            $('#cardTypeModal').modal('show');
        });
    });

    // Edit: open same modal via AJAX (controller returns _form only for AJAX)
    $(document).on('click', '.openEditCardType', function (e) {
        e.preventDefault();
        $.get($(this).attr('href'), function (data) {
            $('#cardTypeModalContent').html(data);
            $('#cardTypeModal').modal('show');
        });
    });

});
</script>
@endpush

