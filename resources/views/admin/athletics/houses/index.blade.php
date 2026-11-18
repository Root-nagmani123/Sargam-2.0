@extends('admin.layouts.master')

@section('title', 'House Master')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="House Master" :showBack="false">
        <button type="button" class="ux4g-btn-primary ux4g-btn-m d-inline-flex align-items-center gap-2"
                data-am-create data-am-modal="#amHouseModal">
            <span class="material-icons material-symbols-rounded" aria-hidden="true">add</span>
            <span>Create House Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        @include('admin.athletics.partials.export_actions', ['route' => 'admin.athletics.houses.export', 'prefix' => 'amHouse'])
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amHouseTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amHouseTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">House Name</th>
                                <th scope="col">OT</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($houses as $house)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $house->name }}</td>
                                    <td data-order="{{ $house->members_count }}">
                                        <a class="am-count-link" href="{{ route('admin.athletics.houses.members', $house) }}"
                                           aria-label="{{ $house->members_count }} OTs in {{ $house->name }}">{{ $house->members_count }}</a>
                                    </td>
                                    <td data-order="{{ (int) $house->status }}">@include('admin.athletics.partials.status_tag', ['status' => $house->status])</td>
                                    <td>
                                        @include('admin.athletics.partials.master_actions', [
                                            'modal' => 'amHouseModal',
                                            'updateUrl' => route('admin.athletics.houses.update', $house),
                                            'values' => $house->only(['name', 'status']),
                                            'table' => 'athletics_houses',
                                            'id' => $house->id,
                                            'active' => (int) $house->status === 1,
                                            'name' => $house->name,
                                            'deleteUrl' => route('admin.athletics.houses.destroy', $house),
                                            'deleteBlocked' => $inUse->has($house->id) ? 'Has recorded results — deactivate instead' : null,
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amHouseTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade am-modal" id="amHouseModal" tabindex="-1" aria-labelledby="amHouseModalTitle" aria-hidden="true" data-bs-backdrop="static"
     data-store="{{ route('admin.athletics.houses.store') }}"
     data-create-title="Create House" data-edit-title="Edit House" data-create-submit="Create" data-edit-submit="Update">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form method="POST" action="{{ route('admin.athletics.houses.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="amHouseModalTitle" data-am-title>Create House</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="am-label" for="amHouseName">House Name<span class="am-req">*</span></label>
                        <input type="text" class="form-control" id="amHouseName" name="name" maxlength="100" required placeholder="eg. Mahanadi">
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amHouseCourse">Course</label>
                        <select class="form-select" id="amHouseCourse" name="course_master_pk" data-searchable="true" data-placeholder="Select Course">
                            <option value="">Select Course</option>
                            @foreach ($courses as $pk => $label)
                                <option value="{{ $pk }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amHouseOts">OT</label>
                        {{-- Filled from JSON for the picked course (doc §3c: never server-render long lists). --}}
                        <select class="form-select" id="amHouseOts" name="student_ids[]" multiple data-searchable="true" data-placeholder="Select OTs" disabled></select>
                        <div class="form-text" id="amHouseOtsHint">Pick a course to list its OTs. On edit, picked OTs are added to the house; remove OTs from the house's OT list.</div>
                    </div>
                    <div class="mb-1">
                        <label class="am-label" for="amHouseStatus">Status<span class="am-req">*</span></label>
                        <select class="form-select" id="amHouseStatus" name="status" required data-searchable="true">
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

@include('admin.athletics.partials.colvis_modal', ['table' => 'amHouseTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amHouseTable',
        key: 'amHouseGrid:hidden:v1',
        serial: 0,
        noSort: [0, 4],
        exportKeys: ['sno', 'name', 'ots', 'status', ''],
        links: ['amHouseCsv', 'amHouseExcel', 'amHousePdf', 'amHousePrint'],
        colvisGrid: '#amHouseTableColvisGrid'
    });

    var $ots = $('#amHouseOts');
    // form.reset() clears the course silently, so the OT list is cleared with it.
    $('#amHouseModal').on('am:fill', function () {
        $ots.empty().prop('disabled', true).trigger('change.select2');
    });
    $('#amHouseCourse').on('change', function () {
        var course = this.value;
        $ots.empty().prop('disabled', !course).trigger('change.select2');
        if (!course) { return; }
        $.getJSON('{{ route('admin.athletics.houses.ots') }}', { course: course }, function (list) {
            list.forEach(function (ot) { $ots.append(new Option(ot.text, ot.id)); });
            $ots.trigger('change.select2');
            $('#amHouseOtsHint').text(list.length ? list.length + ' OTs not yet in a house.' : 'Every OT of this course is already in a house.');
        });
    });
});
</script>
@endpush
