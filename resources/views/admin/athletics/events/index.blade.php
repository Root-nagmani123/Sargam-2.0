@extends('admin.layouts.master')

@section('title', 'Event Master')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="Event Master" :showBack="false">
        <button type="button" class="ux4g-btn-primary ux4g-btn-m d-inline-flex align-items-center gap-2"
                data-am-create data-am-modal="#amEventModal">
            <span class="material-icons material-symbols-rounded" aria-hidden="true">add</span>
            <span>Create Event Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        @include('admin.athletics.partials.export_actions', ['route' => 'admin.athletics.events.export', 'prefix' => 'amEvent'])
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amEventTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amEventTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Event Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Gender</th>
                                <th scope="col">Participation</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $event->name }}</td>
                                    <td>{{ \App\Models\Athletics\AthleticsEvent::CATEGORIES[$event->category] ?? $event->category }}</td>
                                    <td>{{ \App\Models\Athletics\AthleticsEvent::GENDERS[$event->gender] ?? $event->gender }}</td>
                                    <td>{{ \App\Models\Athletics\AthleticsEvent::PARTICIPATION[$event->participation] ?? $event->participation }}</td>
                                    <td data-order="{{ (int) $event->status }}">@include('admin.athletics.partials.status_tag', ['status' => $event->status])</td>
                                    <td>
                                        @include('admin.athletics.partials.master_actions', [
                                            'modal' => 'amEventModal',
                                            'updateUrl' => route('admin.athletics.events.update', $event),
                                            'values' => $event->only(['name', 'category', 'gender', 'participation', 'status']),
                                            'table' => 'athletics_events',
                                            'id' => $event->id,
                                            'active' => (int) $event->status === 1,
                                            'name' => $event->name,
                                            'deleteUrl' => route('admin.athletics.events.destroy', $event),
                                            'deleteBlocked' => $inUse->has($event->id) ? 'Used in an athletics drive — deactivate instead' : null,
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amEventTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade am-modal" id="amEventModal" tabindex="-1" aria-labelledby="amEventModalTitle" aria-hidden="true" data-bs-backdrop="static"
     data-store="{{ route('admin.athletics.events.store') }}"
     data-create-title="Create Event" data-edit-title="Edit Event" data-create-submit="Create" data-edit-submit="Update">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form method="POST" action="{{ route('admin.athletics.events.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="amEventModalTitle" data-am-title>Create Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="am-label" for="amEventName">Event Name<span class="am-req">*</span></label>
                        <input type="text" class="form-control" id="amEventName" name="name" maxlength="150" required placeholder="eg. 100 Mts Race (Men)">
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amEventCategory">Category<span class="am-req">*</span></label>
                        <select class="form-select" id="amEventCategory" name="category" required data-searchable="true" data-placeholder="Select Category">
                            <option value="">Select Category</option>
                            @foreach (\App\Models\Athletics\AthleticsEvent::CATEGORIES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amEventGender">Gender<span class="am-req">*</span></label>
                        <select class="form-select" id="amEventGender" name="gender" required data-searchable="true" data-placeholder="Select Gender">
                            <option value="">Select Gender</option>
                            @foreach (\App\Models\Athletics\AthleticsEvent::GENDERS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amEventParticipation">Participation<span class="am-req">*</span></label>
                        <select class="form-select" id="amEventParticipation" name="participation" required data-searchable="true" data-placeholder="Select Participation">
                            <option value="">Select Participation</option>
                            @foreach (\App\Models\Athletics\AthleticsEvent::PARTICIPATION as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="am-label" for="amEventStatus">Status<span class="am-req">*</span></label>
                        <select class="form-select" id="amEventStatus" name="status" required data-searchable="true">
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

@include('admin.athletics.partials.colvis_modal', ['table' => 'amEventTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amEventTable',
        key: 'amEventGrid:hidden:v1',
        serial: 0,
        noSort: [0, 6],
        exportKeys: ['sno', 'name', 'category', 'gender', 'participation', 'status', ''],
        links: ['amEventCsv', 'amEventExcel', 'amEventPdf', 'amEventPrint'],
        colvisGrid: '#amEventTableColvisGrid'
    });
});
</script>
@endpush
