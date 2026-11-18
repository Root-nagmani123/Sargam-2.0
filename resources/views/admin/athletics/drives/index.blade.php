@php
    use App\Models\Athletics\AthleticsDrive;
    use App\Models\Athletics\AthleticsEvent;
    use App\Http\Controllers\Admin\Athletics\AthleticsController;
@endphp
@extends('admin.layouts.master')

@section('title', 'Athletics Drive')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="Athletics Drive" :showBack="false">
        <button type="button" class="ux4g-btn-primary ux4g-btn-m d-inline-flex align-items-center gap-2"
                data-am-create data-am-modal="#amDriveModal">
            <span class="material-icons material-symbols-rounded" aria-hidden="true">add</span>
            <span>Create Drive</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        @include('admin.athletics.partials.export_actions', ['route' => 'admin.athletics.drives.export', 'prefix' => 'amDrive'])
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amDriveTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amDriveTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course</th>
                                <th scope="col">Date</th>
                                <th scope="col">Form Title</th>
                                <th scope="col">Visible To</th>
                                <th scope="col">Athletics Meet</th>
                                <th scope="col">Result Record As</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drives as $drive)
                                @php
                                    // Edit modal rows: one per (type, venue), as they were entered.
                                    $rows = $drive->events
                                        ->groupBy(fn ($e) => $e->category.'|'.$e->pivot->venue_id)
                                        ->map(fn ($group) => [
                                            'category' => $group->first()->category,
                                            'venue_id' => $group->first()->pivot->venue_id,
                                            'event_ids' => $group->pluck('id')->all(),
                                        ])->values();
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ AthleticsController::courseLabel($drive->course) }}</td>
                                    <td data-order="{{ $drive->start_date->format('Y-m-d') }}">
                                        {{ $drive->start_date->format('d/m/Y') }} -<br>{{ $drive->end_date->format('d/m/Y') }}
                                    </td>
                                    <td>{{ $drive->form_title }}</td>
                                    <td>{{ AthleticsDrive::VISIBLE_TO[$drive->visible_to] ?? $drive->visible_to }}</td>
                                    <td>{{ $drive->categoryList() ?: '—' }}</td>
                                    <td>{{ AthleticsDrive::RESULT_TYPES[$drive->result_recorded_as] ?? $drive->result_recorded_as }}</td>
                                    <td>@include('admin.athletics.partials.status_tag', ['status' => $drive->status])</td>
                                    <td>
                                        <div class="am-act-group justify-content-end" role="group" aria-label="Row actions">
                                            @if ($drive->status === 'draft')
                                                <button type="button" class="am-act" data-am-edit data-am-modal="#amDriveModal"
                                                        data-action="{{ route('admin.athletics.drives.update', $drive) }}"
                                                        data-values="{{ json_encode([
                                                            'course_master_pk' => $drive->course_master_pk,
                                                            'form_title' => $drive->form_title,
                                                            'result_recorded_as' => $drive->result_recorded_as,
                                                            'visible_to' => $drive->visible_to,
                                                            'max_criteria' => $drive->max_criteria,
                                                            'max_nominations' => $drive->max_nominations,
                                                            'start_date' => $drive->start_date->format('Y-m-d'),
                                                            'end_date' => $drive->end_date->format('Y-m-d'),
                                                            'instructions' => $drive->instructions,
                                                            'status' => $drive->status,
                                                            'rows' => $rows,
                                                        ]) }}">
                                                    <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">edit</span></span>
                                                    <span class="am-act__label">Edit</span>
                                                </button>
                                            @endif
                                            <a class="am-act" href="{{ route('admin.athletics.drives.preview', $drive) }}" target="_blank" rel="noopener">
                                                <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">visibility</span></span>
                                                <span class="am-act__label">Preview<br>Form</span>
                                            </a>
                                            @if ($drive->nominations_count > 0 || $drive->results_count > 0)
                                                <span class="am-act am-act--del is-disabled" title="Has nominations or results — cannot be deleted" aria-disabled="true">
                                                    <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">delete</span></span>
                                                    <span class="am-act__label">Delete</span>
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('admin.athletics.drives.destroy', $drive) }}"
                                                      class="am-act-form am-delete-form" data-name="{{ $drive->form_title }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="am-act am-act--del">
                                                        <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">delete</span></span>
                                                        <span class="am-act__label">Delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amDriveTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade am-modal" id="amDriveModal" tabindex="-1" aria-labelledby="amDriveModalTitle" aria-hidden="true" data-bs-backdrop="static"
     data-store="{{ route('admin.athletics.drives.store') }}"
     data-create-title="Create Athletics Drive" data-edit-title="Edit Athletics Drive" data-create-submit="Create" data-edit-submit="Update">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow">
            <form method="POST" action="{{ route('admin.athletics.drives.store') }}" class="d-flex flex-column overflow-hidden" style="min-height:0">{{-- lets modal-body scroll inside the scrollable dialog --}}
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="amDriveModalTitle" data-am-title>Create Athletics Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveCourse">Course<span class="am-req">*</span></label>
                            <select class="form-select" id="amDriveCourse" name="course_master_pk" required data-searchable="true" data-placeholder="Select Course">
                                <option value="">Select Course</option>
                                @foreach ($courses as $pk => $label)
                                    <option value="{{ $pk }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveTitle">Form Title<span class="am-req">*</span></label>
                            <input type="text" class="form-control" id="amDriveTitle" name="form_title" maxlength="200" required placeholder="eg. Athletics Meet Nomination (Men)">
                        </div>
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveResult">Result Recorded As<span class="am-req">*</span></label>
                            <select class="form-select" id="amDriveResult" name="result_recorded_as" required data-searchable="true" data-placeholder="Select">
                                <option value="">Select</option>
                                @foreach (AthleticsDrive::RESULT_TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <span class="am-label" id="amDriveVisibleLabel">Visible to<span class="am-req">*</span></span>
                            <div class="am-radio-row" role="radiogroup" aria-labelledby="amDriveVisibleLabel">
                                @foreach (AthleticsDrive::VISIBLE_TO as $key => $label)
                                    <label class="form-check mb-0 d-inline-flex align-items-center gap-2">
                                        <input class="form-check-input mt-0" type="radio" name="visible_to" value="{{ $key }}" required @checked($loop->first)>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveCriteria">Max Nominations Criteria<span class="am-req">*</span></label>
                            <select class="form-select" id="amDriveCriteria" name="max_criteria" required data-searchable="true" data-placeholder="Select Criteria">
                                <option value="">Select Criteria</option>
                                @foreach (AthleticsDrive::CRITERIA as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveMax">Max Nominations<span class="am-req">*</span></label>
                            <select class="form-select" id="amDriveMax" name="max_nominations" required data-searchable="true" data-placeholder="Select Number">
                                <option value="">Select Number</option>
                                @foreach (range(1, 10) as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveStart">Start Date<span class="am-req">*</span></label>
                            <input type="date" class="form-control" id="amDriveStart" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="am-label" for="amDriveEnd">End Date<span class="am-req">*</span></label>
                            <input type="date" class="form-control" id="amDriveEnd" name="end_date" required>
                        </div>
                        <div class="col-12">
                            <label class="am-label" for="amDriveInstructions">Instructions</label>
                            <textarea class="form-control" id="amDriveInstructions" name="instructions" rows="3" maxlength="2000"
                                      placeholder="eg. Select the events you wish to take part in"></textarea>
                        </div>
                        <div class="col-12">
                            <span class="am-label">Athletics Meet<span class="am-req">*</span></span>
                            <div class="table-responsive">
                                <table class="am-rows">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="width:26%">Type</th>
                                            <th scope="col">Activity</th>
                                            <th scope="col" style="width:24%">Venue</th>
                                            <th scope="col" class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="amDriveRows"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="am-label" for="amDriveStatus">Status<span class="am-req">*</span></label>
                            <select class="form-select" id="amDriveStatus" name="status" required data-searchable="true">
                                @foreach (AthleticsDrive::STATUSES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">An Active drive opens to OTs on its start date and can no longer be edited.</div>
                        </div>
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

<template id="amDriveRowTpl">
    <tr>
        <td>
            <select class="form-select am-row-type" data-name="rows[__i__][category]" aria-label="Type" data-placeholder="Select Type">
                <option value="">Select Type</option>
                @foreach (AthleticsEvent::CATEGORIES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td><select class="form-select am-row-events" data-name="rows[__i__][event_ids][]" multiple aria-label="Activity" data-placeholder="Select Activity"></select></td>
        <td><select class="form-select am-row-venue" data-name="rows[__i__][venue_id]" aria-label="Venue" data-placeholder="Select Venue"></select></td>
        <td>
            <div class="am-row-actions">
                <button type="button" class="am-row-btn am-row-btn--remove" aria-label="Remove row"><span class="material-icons material-symbols-rounded" aria-hidden="true">remove</span></button>
                <button type="button" class="am-row-btn am-row-btn--add" aria-label="Add row"><span class="material-icons material-symbols-rounded" aria-hidden="true">add</span></button>
            </div>
        </td>
    </tr>
</template>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amDriveTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amDriveTable',
        key: 'amDriveGrid:hidden:v1',
        serial: 0,
        noSort: [0, 8],
        exportKeys: ['sno', 'course', 'date', 'form_title', 'visible_to', 'meet', 'result', 'status', ''],
        links: ['amDriveCsv', 'amDriveExcel', 'amDrivePdf', 'amDrivePrint'],
        colvisGrid: '#amDriveTableColvisGrid'
    });

    var EVENTS = @json($events);
    var VENUES = @json($venues);
    var rows = AthleticsGrid.rows('#amDriveRows', '#amDriveRowTpl');

    function audience() { return $('#amDriveModal input[name="visible_to"]:checked').val() || 'both'; }

    // Activity = active events of the row's type open to the drive's audience;
    // Venue = venues of that type. Keeps whatever is still valid when re-filled.
    function fillRow($row, keepEvents, keepVenue) {
        var type = $row.find('.am-row-type').val();
        var who = audience();
        var $ev = $row.find('.am-row-events');
        var $ve = $row.find('.am-row-venue');
        var evKeep = (keepEvents || $ev.val() || []).map(String);
        var veKeep = String(keepVenue || $ve.val() || '');

        $ev.empty();
        $ve.empty().append(new Option('Select Venue', ''));
        EVENTS.forEach(function (e) {
            if (e.category === type && (who === 'both' || e.gender === who)) {
                var on = evKeep.indexOf(String(e.id)) !== -1;
                $ev.append(new Option(e.name, e.id, on, on));
            }
        });
        VENUES.forEach(function (v) {
            if (v.venue_type === type) {
                var on = String(v.id) === veKeep;
                $ve.append(new Option(v.name, v.id, on, on));
            }
        });
        $ev.trigger('change.select2');
        $ve.trigger('change.select2');
    }

    $('#amDriveRows').on('change', '.am-row-type', function () { fillRow($(this).closest('tr')); });
    $('#amDriveModal').on('change', 'input[name="visible_to"]', function () {
        $('#amDriveRows > tr').each(function () { fillRow($(this)); });
    });

    $('#amDriveModal').on('am:fill', function (e, mode, values) {
        rows.clear();
        var list = (values.rows && values.rows.length) ? values.rows : [{}];
        list.forEach(function (r) {
            var $row = rows.add();
            if (r.category) {
                $row.find('.am-row-type').val(r.category).trigger('change.select2');
                fillRow($row, r.event_ids, r.venue_id);
            }
        });
    });
});
</script>
@endpush
