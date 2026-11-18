@extends('admin.layouts.master')

@section('title', 'Venue Master')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="Venue Master" :showBack="false">
        <button type="button" class="ux4g-btn-primary ux4g-btn-m d-inline-flex align-items-center gap-2"
                data-am-create data-am-modal="#amVenueModal">
            <span class="material-icons material-symbols-rounded" aria-hidden="true">add</span>
            <span>Create Venue Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        @include('admin.athletics.partials.export_actions', ['route' => 'admin.athletics.venues.export', 'prefix' => 'amVenue'])
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amVenueTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amVenueTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Venue Name</th>
                                <th scope="col">Venue Type</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venues as $venue)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $venue->name }}</td>
                                    <td>{{ \App\Models\Athletics\AthleticsVenue::TYPES[$venue->venue_type] ?? $venue->venue_type }}</td>
                                    <td data-order="{{ (int) $venue->status }}">@include('admin.athletics.partials.status_tag', ['status' => $venue->status])</td>
                                    <td>
                                        @include('admin.athletics.partials.master_actions', [
                                            'modal' => 'amVenueModal',
                                            'updateUrl' => route('admin.athletics.venues.update', $venue),
                                            'values' => $venue->only(['name', 'venue_type', 'status']),
                                            'table' => 'athletics_venues',
                                            'id' => $venue->id,
                                            'active' => (int) $venue->status === 1,
                                            'name' => $venue->name,
                                            'deleteUrl' => route('admin.athletics.venues.destroy', $venue),
                                            'deleteBlocked' => $inUse->has($venue->id) ? 'Used in an athletics drive — deactivate instead' : null,
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amVenueTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade am-modal" id="amVenueModal" tabindex="-1" aria-labelledby="amVenueModalTitle" aria-hidden="true" data-bs-backdrop="static"
     data-store="{{ route('admin.athletics.venues.store') }}"
     data-create-title="Create Venue" data-edit-title="Edit Venue" data-create-submit="Create" data-edit-submit="Update">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form method="POST" action="{{ route('admin.athletics.venues.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="amVenueModalTitle" data-am-title>Create Venue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="am-label" for="amVenueName">Venue Name<span class="am-req">*</span></label>
                        <input type="text" class="form-control" id="amVenueName" name="name" maxlength="150" required placeholder="eg. Mahanadi">
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amVenueType">Venue Type<span class="am-req">*</span></label>
                        <select class="form-select" id="amVenueType" name="venue_type" required data-searchable="true" data-placeholder="Select Type">
                            <option value="">Select Type</option>
                            @foreach (\App\Models\Athletics\AthleticsVenue::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="am-label" for="amVenueStatus">Status<span class="am-req">*</span></label>
                        <select class="form-select" id="amVenueStatus" name="status" required data-searchable="true">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ux4g-btn-outline-danger ux4g-btn-m" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ux4g-btn-primary ux4g-btn-m" data-am-submit>Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amVenueTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amVenueTable',
        key: 'amVenueGrid:hidden:v1',
        serial: 0,
        noSort: [0, 4],
        exportKeys: ['sno', 'name', 'venue_type', 'status', ''],
        links: ['amVenueCsv', 'amVenueExcel', 'amVenuePdf', 'amVenuePrint'],
        colvisGrid: '#amVenueTableColvisGrid'
    });
});
</script>
@endpush
