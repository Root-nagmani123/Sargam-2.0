@extends('admin.layouts.master')

@section('title', 'Nominations')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="Nominations" :showBack="false">
        {{-- Ticking rows swaps these for the bulk pair, as in the Figma. --}}
        <div class="d-flex gap-2" id="amNomDefaultActions">
            <button type="button" class="ux4g-btn-primary ux4g-btn-m d-inline-flex align-items-center gap-2"
                    data-am-create data-am-modal="#amNomModal">
                <span class="material-icons material-symbols-rounded" aria-hidden="true">add</span>
                <span>Add Nomination</span>
            </button>
        </div>
        <div class="d-none gap-2" id="amNomBulkActions">
            <button type="button" class="ux4g-btn-danger ux4g-btn-m" data-bulk="delete">Delete All</button>
            <button type="button" class="btn am-btn-success ux4g-btn-m" data-bulk="approve">Freeze All</button>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" aria-label="Filter nominations by status">
            @foreach ($tabs as $key => $label)
                <li class="nav-item">
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
                       class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $filters['status'] === $key ? 'active' : '' }}"
                       @if ($filters['status'] === $key) aria-current="page" @endif>{{ $label }}</a>
                </li>
            @endforeach
        </ul>
        <div class="d-flex flex-wrap gap-2">
            @include('admin.athletics.partials.export_actions', [
                'route' => 'admin.athletics.nominations.export',
                'params' => array_filter(['status' => $filters['status'], 'drive' => $filters['drive'], 'event' => $filters['event']]),
                'prefix' => 'amNom',
            ])
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <form method="GET" class="d-flex flex-wrap align-items-center gap-3" id="amNomFilters">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="drive" class="form-select" data-searchable="true" data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $d)
                                <option value="{{ $d->id }}" @selected($filters['drive'] === $d->id)>{{ $d->form_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="event" class="form-select" data-searchable="true" data-placeholder="Nomination For" aria-label="Nomination For">
                            <option value="">Nomination For</option>
                            @foreach ($events as $e)
                                <option value="{{ $e->id }}" @selected($filters['event'] === $e->id)>{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.athletics.nominations.index', ['status' => $filters['status']]) }}" class="btn programme-dt-btn-reset d-inline-flex align-items-center">Remove Filter</a>
                </form>
                @include('admin.athletics.partials.grid_tools', ['table' => 'amNomTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amNomTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" class="form-check-input" id="amNomAll" aria-label="Select all nominations on this page"></th>
                                <th scope="col">S. No.</th>
                                <th scope="col">OT Code</th>
                                <th scope="col">OTs</th>
                                <th scope="col">House</th>
                                <th scope="col">Nomination For</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($nominations as $n)
                                @php $frozen = $n->status === 'approved'; @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input am-nom-pick" value="{{ $n->id }}"
                                               aria-label="Select {{ $n->student->display_name ?? 'nomination' }}" @disabled($frozen)>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $n->student->generated_OT_code ?? '—' }}</td>
                                    <td>{{ $n->student->display_name ?? '—' }}</td>
                                    <td>{{ $n->house_name ?? '—' }}</td>
                                    <td class="am-col-wrap">{{ $n->events->pluck('name')->implode(', ') }}</td>
                                    <td class="am-col-wrap">{{ $n->drive->form_title ?? '—' }}</td>
                                    <td>@include('admin.athletics.partials.status_tag', ['status' => $n->status])</td>
                                    <td>
                                        @unless ($frozen)
                                            <div class="am-act-group" role="group" aria-label="Row actions">
                                                <button type="button" class="am-act" data-am-edit data-am-modal="#amNomModal"
                                                        data-action="{{ route('admin.athletics.nominations.update', $n) }}"
                                                        data-values="{{ json_encode([
                                                            'edit_drive' => $n->drive_id,
                                                            'edit_events' => $n->events->pluck('id'),
                                                            'ot' => \App\Http\Controllers\Admin\Athletics\AthleticsController::otLabel($n->student),
                                                        ]) }}">
                                                    <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">edit</span></span>
                                                    <span class="am-act__label">Edit</span>
                                                </button>
                                                <button type="button" class="am-act am-act--approve" data-bulk="approve" data-id="{{ $n->id }}">
                                                    <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">check</span></span>
                                                    <span class="am-act__label">Approve</span>
                                                </button>
                                                @if ($n->status === 'pending')
                                                    <button type="button" class="am-act am-act--decline" data-bulk="reject" data-id="{{ $n->id }}">
                                                        <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">close</span></span>
                                                        <span class="am-act__label">Decline</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amNomTable"></div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.athletics.nominations.bulk') }}" id="amNomBulkForm" class="d-none">
        @csrf
        <input type="hidden" name="action" value="">
    </form>
</div>

<div class="modal fade am-modal" id="amNomModal" tabindex="-1" aria-labelledby="amNomModalTitle" aria-hidden="true" data-bs-backdrop="static"
     data-store="{{ route('admin.athletics.nominations.store') }}"
     data-create-title="Add Nomination" data-edit-title="Edit Nomination" data-create-submit="Add" data-edit-submit="Update">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form method="POST" action="{{ route('admin.athletics.nominations.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="amNomModalTitle" data-am-title>Add Nomination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="am-label" for="amNomDrive">Drive<span class="am-req">*</span></label>
                        <select class="form-select" id="amNomDrive" name="drive_id" required data-searchable="true" data-placeholder="Select Drive">
                            <option value="">Select Drive</option>
                            @foreach ($drives as $d)
                                <option value="{{ $d->id }}">{{ $d->form_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="am-label" for="amNomOt">OT<span class="am-req">*</span></label>
                        <div id="amNomOtWrap">
                            <select class="form-select" id="amNomOt" name="student_master_pk" required data-searchable="true" data-placeholder="Select OT"></select>
                        </div>
                        <div class="form-control-plaintext d-none" id="amNomOtName"></div>
                    </div>
                    <div class="mb-1">
                        <label class="am-label" for="amNomEvents">Nomination For<span class="am-req">*</span></label>
                        <select class="form-select" id="amNomEvents" name="event_ids[]" multiple required data-searchable="true" data-placeholder="Select Events"></select>
                        <div class="form-text">The drive's nomination limit applies; group events don't count toward it.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ux4g-btn-outline-danger ux4g-btn-m" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ux4g-btn-primary ux4g-btn-m" data-am-submit>Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amNomTable'])
@endsection

@push('scripts')
<script>
$(function () {
    var dt = AthleticsGrid.init({
        table: '#amNomTable',
        key: 'amNomGrid:hidden:v1',
        serial: 1,
        noSort: [0, 1, 8],
        exportKeys: ['', 'sno', 'ot_code', 'ot', 'house', 'events', 'drive', 'status', ''],
        links: ['amNomCsv', 'amNomExcel', 'amNomPdf', 'amNomPrint'],
        colvisGrid: '#amNomTableColvisGrid'
    });

    // Filters reload the page (server-side query) — jQuery handler, Select2 fires jQuery change.
    $('#amNomFilters select').on('change', function () { this.form.submit(); });

    /* ── Selection + bulk actions ── */
    function picked() { return dt.$('.am-nom-pick:checked').map(function () { return this.value; }).get(); }
    function syncBulk() {
        var any = picked().length > 0;
        $('#amNomDefaultActions').toggleClass('d-none', any).toggleClass('d-flex', !any);
        $('#amNomBulkActions').toggleClass('d-none', !any).toggleClass('d-flex', any);
    }
    $('#amNomTable').on('change', '.am-nom-pick', syncBulk);
    $('#amNomAll').on('change', function () {
        $(dt.rows({ page: 'current', search: 'applied' }).nodes()).find('.am-nom-pick:not(:disabled)').prop('checked', this.checked);
        syncBulk();
    });

    var COPY = {
        approve: ['Approve nomination(s)?', 'Approved nominations are frozen and can no longer be edited.', 'Yes, Approve', false],
        reject: ['Decline nomination(s)?', 'The OT will see their nomination as rejected.', 'Yes, Decline', true],
        delete: ['Confirm Delete?', 'Are you sure you want to delete the selected records? This action can\'t be undone.', 'Yes, Delete', true]
    };
    $(document).on('click', '[data-bulk]', function () {
        var action = $(this).data('bulk');
        var ids = $(this).data('id') ? [String($(this).data('id'))] : picked();
        if (!ids.length) { return; }
        var c = COPY[action];
        AthleticsGrid.confirm({ title: c[0], text: c[1], confirm: c[2], danger: c[3] }).then(function (ok) {
            if (!ok) { return; }
            var $f = $('#amNomBulkForm');
            $f.find('input[name="action"]').val(action);
            $f.find('input[name="ids[]"]').remove();
            ids.forEach(function (id) { $('<input type="hidden" name="ids[]">').val(id).appendTo($f); });
            $f[0].submit();
        });
    });

    /* ── Add / Edit modal ── */
    var $drive = $('#amNomDrive'), $ot = $('#amNomOt'), $events = $('#amNomEvents');
    var optionsUrl = '{{ route('admin.athletics.nominations.drive-options', ['drive' => '__d__']) }}';
    var CATS = @json(\App\Models\Athletics\AthleticsEvent::CATEGORIES);

    function loadDrive(driveId, keepEvents) {
        $ot.empty().append(new Option('', ''));
        $events.empty();
        if (!driveId) { $ot.add($events).trigger('change.select2'); return; }
        $.getJSON(optionsUrl.replace('__d__', driveId), function (data) {
            if (String($drive.val()) !== String(driveId)) { return; } // a newer pick won
            data.ots.forEach(function (o) { $ot.append(new Option(o.text, o.id)); });
            data.events.forEach(function (e) { $events.append(new Option(e.name + ' (' + (CATS[e.category] || e.category) + ')', e.id)); });
            $events.val((keepEvents || []).map(String));
            $ot.add($events).trigger('change.select2');
        });
    }

    $drive.on('change', function () { loadDrive(this.value); });

    $('#amNomModal').on('am:fill', function (e, mode, values) {
        var edit = mode === 'edit';
        // Edit changes events only; drive and OT are fixed and not posted.
        $drive.prop('disabled', edit).val(edit ? String(values.edit_drive) : '').trigger('change.select2');
        $('#amNomOtWrap').toggleClass('d-none', edit);
        $ot.prop('disabled', edit);
        $('#amNomOtName').toggleClass('d-none', !edit).text(values.ot || '');
        loadDrive(edit ? values.edit_drive : '', edit ? values.edit_events : []);
    });
});
</script>
@endpush
