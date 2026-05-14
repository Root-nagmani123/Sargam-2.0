@extends('admin.layouts.master')
@section('title', 'Card Sub Type Master - Security')
@section('content')
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
                <table class="table mb-0">
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
                    <tbody>
                        @forelse($subTypes as $index => $st)
                            <tr>
                                <td>{{ $subTypes->firstItem() + $index }}</td>
                                <td>{{ $st->sec_card_name }}</td>
                                <td>
                                    @if($st->card_name === 'p')
                                        <span class="badge bg-primary">Permanent</span>
                                    @elseif($st->card_name === 'c')
                                        <span class="badge bg-info">Contractual</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $st->card_name }}</span>
                                    @endif
                                </td>
                                <td>{{ $st->config_name }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle"
                                               type="checkbox"
                                               role="switch"
                                               data-table="sec_id_cardno_config_map"
                                               data-column="active_inactive"
                                               data-id="{{ $st->pk }}"
                                               data-id_column="pk"
                                               {{ ($st->active_inactive ?? 1) == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    @php $subIsActive = (int) ($st->active_inactive ?? 1) === 1; @endphp
                                    <div class="d-flex gap-2 align-items-center">
                                        <a href="{{ route('admin.security.idcard_sub_type.edit', encrypt($st->pk)) }}" class="text-success openEditSubType" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        @if($subIsActive)
                                            <button type="button"
                                                    class="btn btn-link p-0 text-secondary"
                                                    disabled
                                                    aria-disabled="true"
                                                    title="Cannot delete while active. Set status to inactive first.">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.security.idcard_sub_type.delete', encrypt($st->pk)) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this Sub Type mapping?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No Sub Types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $subTypes->links() }}
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
    // reload the page so Active/Inactive UI + delete restrictions match DB.
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
            window.location.reload();
        }
    });
});
</script>
@endpush

