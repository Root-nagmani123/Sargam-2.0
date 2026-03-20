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
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Card Type Name</th>
                            <th style="width:140px;">Status</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cardTypes as $index => $ct)
                            <tr>
                                <td>{{ $cardTypes->firstItem() + $index }}</td>
                                <td>{{ $ct->sec_card_name }}</td>
                                <td>
                                    @php
                                        $hasStatus = property_exists($ct, 'active_inactive');
                                        $isActive = $hasStatus ? ((int) $ct->active_inactive === 1) : true;
                                    @endphp
                                    @if($hasStatus)
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle"
                                                   type="checkbox"
                                                   role="switch"
                                                   data-table="sec_id_cardno_master"
                                                   data-column="active_inactive"
                                                   data-id="{{ $ct->pk }}"
                                                   data-id_column="pk"
                                                   {{ $isActive ? 'checked' : '' }}>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.idcard_card_type.edit', encrypt($ct->pk)) }}" class="text-success openEditCardType" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        @php
                                            $canDeleteCardType = ! $hasStatus || ! $isActive;
                                        @endphp
                                        @if($canDeleteCardType)
                                            <form action="{{ route('admin.security.idcard_card_type.delete', encrypt($ct->pk)) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this Card Type?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="cursor:not-allowed;" title="Set status to Inactive before delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;opacity:0.4;">delete</i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No Card Types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $cardTypes->links() }}
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
(function () {
    // After status toggle (global custom.js → /admin/toggle-status), reload so delete icons match DB.
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
            window.location.reload();
        }
    });
})();

$(document).ready(function () {
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

