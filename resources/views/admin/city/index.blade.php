@extends('admin.layouts.master')

@section('title', 'City List')

{{--
    Phase E/F — pixel-perfect index redesign (the "Store Master" / country pattern).
    Client-side DataTable on the shared programme-dt chrome (docs/new-design-index-page.md).
    Status = its own column (soft badge). Action = Edit (modal) · toggle · Delete.
    Create/Edit open in a UX4G modal with a client-side Country → State → District cascade.
    Controller returns City::get() to feed the client-side grid. All functionality preserved.
    NOTE: the legacy table swapped its District/State column DATA vs headers — corrected here
    (District column now shows the district, State column shows the state). Display-only fix.
--}}
@section('setup_content')
<div class="container-fluid city-page">
    <x-breadcrum title="City List" :showBack="false">
        <button type="button" class="btn btn-primary px-3 py-2 rounded-1 shadow-sm"
            data-bs-toggle="modal" data-bs-target="#cityFormModal" data-mode="create">
            <i class="material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
            Add City
        </button>
    </x-breadcrum>

    {{-- Download / Print strip — branded CSV / PDF / Print (shared LBSNAA report chrome) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <div class="dropdown">
            <button type="button" class="btn city-tool-btn border-0 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('master.city.export', 'csv') }}"><i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                <li><a class="dropdown-item" href="{{ route('master.city.export', 'pdf') }}"><i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('master.city.export', 'print') }}" target="_blank" rel="noopener" class="btn city-tool-btn border-0">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            {{-- Toolbar: Columns + search --}}
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 programme-dt-toolbar">
                <button type="button" class="btn programme-dt-btn-columns" id="cityColumnsBtn"
                    data-bs-toggle="modal" data-bs-target="#cityColumnVisibilityModal" title="Show / hide columns">
                    <span>Columns</span>
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div id="cityDtSearch" class="programme-dt-search" data-dt-search-for="cityTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="cityTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th style="width:5rem">S. No.</th>
                                <th>City Name</th>
                                <th>District</th>
                                <th>State</th>
                                <th style="width:8rem">Status</th>
                                <th class="text-center" style="width:10rem">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cities as $city)
                            <tr>
                                <td></td>
                                <td class="fw-medium">{{ $city->city_name }}</td>
                                <td>{{ optional($city->district)->district_name ?? 'N/A' }}</td>
                                <td>{{ optional($city->state)->state_name ?? 'N/A' }}</td>

                                {{-- Status: soft badge (green Active / red Inactive) --}}
                                <td data-order="{{ $city->active_inactive }}">
                                    <span class="status-pill badge {{ $city->active_inactive == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $city->active_inactive == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                {{-- Action: Edit (blue) · toggle · Delete (red) — icon + label --}}
                                <td>
                                    <div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="City actions">
                                        <button type="button" class="city-act city-act--edit"
                                            data-bs-toggle="modal" data-bs-target="#cityFormModal" data-mode="edit"
                                            data-id="{{ $city->pk }}" data-name="{{ $city->city_name }}"
                                            data-country="{{ $city->country_master_pk }}" data-state="{{ $city->state_master_pk }}"
                                            data-district="{{ $city->district_master_pk }}"
                                            data-status="{{ $city->active_inactive }}"
                                            aria-label="Edit {{ $city->city_name }}">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            <span>Edit</span>
                                        </button>

                                        {{-- Status toggle (AJAX via status-toggle-delete.js) --}}
                                        <div class="form-check form-switch m-0">
                                            <input type="checkbox" class="form-check-input status-toggle" role="switch"
                                                data-table="city_master" data-column="active_inactive"
                                                data-id="{{ $city->pk }}" {{ $city->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>

                                        {{-- Delete (guarded: an active city cannot be deleted) --}}
                                        @if($city->active_inactive == 1)
                                        <span class="city-act city-act--del is-disabled"
                                            title="Set the city inactive before deleting" aria-disabled="true">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            <span>Delete</span>
                                        </span>
                                        @else
                                        <form action="{{ route('master.city.delete', $city->pk) }}" method="POST"
                                            class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="city-act city-act--del"
                                                aria-label="Delete {{ $city->city_name }}">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                                <span>Delete</span>
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
            </div>

            {{-- Footer: pagination + page-size + "Showing N of M items" (built by datatable-global-ui.js) --}}
            <div id="cityDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                data-dt-footer-for="cityTable"></div>
        </div>
    </div>
</div>

{{-- Column visibility modal --}}
<div class="modal fade" id="cityColumnVisibilityModal" tabindex="-1" aria-labelledby="cityColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="cityColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2" id="cityColumnToggleGrid"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create / Edit modal (UX4G = Bootstrap 5.3). Country → State → District cascade is
     client-side (states/districts embedded below). Submits to the UNCHANGED store/update
     routes — no backend change. --}}
<div class="modal fade" id="cityFormModal" tabindex="-1" aria-labelledby="cityFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="cityForm" method="POST" action="{{ route('master.city.store') }}">
                @csrf
                <input type="hidden" name="_form_mode" id="cyFormMode" value="create">
                <input type="hidden" name="_edit_id" id="cyEditId" value="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="cityFormModalLabel">Add City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="cyCountry" class="form-label">Country <span class="text-danger">*</span></label>
                        <select class="form-select" id="cyCountry" name="country_master_pk" required>
                            <option value="">-- Select Country --</option>
                            @foreach($countries as $country)
                            <option value="{{ $country->pk }}">{{ $country->country_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cyState" class="form-label">State <span class="text-danger">*</span></label>
                        <select class="form-select" id="cyState" name="state_master_pk" required>
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cyDistrict" class="form-label">District <span class="text-danger">*</span></label>
                        <select class="form-select" id="cyDistrict" name="district_master_pk" required>
                            <option value="">Select District</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cyName" class="form-label">City Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cyName" name="city_name" placeholder="City Name" required>
                    </div>
                    <div class="mb-0">
                        <label for="cyStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="cyStatus" name="active_inactive" required>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Store Master parity — scoped to .city-page (tokens from design.md) */
    .city-page .city-tool-btn {
        background: var(--ds-surface); color: var(--ds-primary);
        border-radius: var(--ds-radius-1); font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: var(--ds-space-2); padding: 0.4rem 0.9rem;
        box-shadow: 0 0 0 1px var(--ds-line) inset;
        border: 0;
    }
    .city-page .city-tool-btn:hover { background: var(--ds-surface-2); }

    /* Soft status badge — theme ships *-subtle backgrounds but not *-emphasis text, so set it */
    .city-page .status-pill { padding: 0.4em 0.85em; font-weight: 600; }
    .city-page .status-pill.bg-success-subtle { color: #146c43; }
    .city-page .status-pill.bg-danger-subtle  { color: #b02a37; }

    /* Row actions — icon over label: Edit (blue) · switch · Delete (red) */
    .city-page .city-act {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: 0.72rem; font-weight: 500; line-height: 1;
        text-decoration: none; background: transparent; border: 0; padding: 0;
    }
    .city-page .city-act i { font-size: 1.1rem; }
    .city-page .city-act--edit { color: #2563eb; }
    .city-page .city-act--del  { color: var(--bs-danger, #dc3545); }
    .city-page .city-act--del.is-disabled { color: var(--ds-ink-muted); cursor: not-allowed; }

    @media print {
        .app-header, .left-sidebar, .city-tool-btn, .programme-dt-toolbar,
        .programme-dt-footer, .city-act { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    var $table = $('#cityTable');
    if (!$table.length || $.fn.dataTable.isDataTable($table)) return;

    var dt = $table.DataTable({
        autoWidth: false,
        pageLength: 10,
        pagingType: 'simple_numbers',
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[1, 'asc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false },
            { targets: -1, orderable: false, searchable: false }
        ]
    });

    dt.on('draw.dt', function () {
        var start = dt.page.info().start;
        dt.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.innerHTML = start + i + 1; });
    });
    dt.draw(false);

    // --- Column visibility modal (persisted) ---
    var KEY = 'cityGrid:hiddenColumns:v1';
    function getHidden() { try { var a = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(a) { try { localStorage.setItem(KEY, JSON.stringify(a)); } catch (e) {} }
    var hidden = getHidden();
    dt.columns().every(function () { this.visible(hidden.indexOf(this.index()) === -1, false); });
    dt.columns.adjust();
    var $grid = $('#cityColumnToggleGrid').empty();
    dt.columns().every(function () {
        var idx = this.index(), title = $(this.header()).text().replace(/\s+/g, ' ').trim();
        if (!title) return;
        var id = 'citycolvis_' + idx;
        var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop('checked', hidden.indexOf(idx) === -1);
        $cb.on('change', function () {
            var h = getHidden(), pos = h.indexOf(idx);
            if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else if (pos === -1) { h.push(idx); }
            setHidden(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
        });
        $('<div class="col-12 col-sm-6"></div>').append(
            $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', id).append($cb).append($('<span></span>').text(title))
        ).appendTo($grid);
    });

    // --- Status toggle (client-side grid) — silence stray ajax.reload(); reload on success ---
    $.fn.dataTable.ext.errMode = 'none';
    $(document).ajaxSuccess(function (e, xhr, settings) {
        if (settings && settings.url && /toggle-?status/i.test(settings.url)) {
            window.location.reload();
        }
    });

    // --- Create / Edit via UX4G modal, with a client-side Country → State → District cascade.
    //     States/districts embedded here so the cascade (and edit prefill) work with no AJAX. ---
    var STATES = @json($states->map(function ($s) {
        return ['pk' => (string) $s->pk, 'name' => $s->state_name, 'country' => (string) $s->country_master_pk];
    })->values());
    var DISTRICTS = @json($districts->map(function ($d) {
        return ['pk' => (string) $d->pk, 'name' => $d->district_name, 'state' => (string) $d->state_master_pk];
    })->values());
    var CY_STORE = @json(route('master.city.store'));
    var CY_UPDATE = @json(route('master.city.update', '__ID__'));

    function fillStates(countryId, selectId) {
        var $s = $('#cyState').empty().append('<option value="">Select State</option>');
        STATES.forEach(function (st) {
            if (String(st.country) === String(countryId)) {
                $('<option></option>').val(st.pk).text(st.name).appendTo($s);
            }
        });
        $s.val(selectId ? String(selectId) : '');
    }
    function fillDistricts(stateId, selectId) {
        var $d = $('#cyDistrict').empty().append('<option value="">Select District</option>');
        DISTRICTS.forEach(function (di) {
            if (String(di.state) === String(stateId)) {
                $('<option></option>').val(di.pk).text(di.name).appendTo($d);
            }
        });
        $d.val(selectId ? String(selectId) : '');
    }

    $('#cyCountry').on('change', function () { fillStates(this.value, null); fillDistricts('', null); });
    $('#cyState').on('change', function () { fillDistricts(this.value, null); });

    $('#cityFormModal').on('show.bs.modal', function (e) {
        var t = e.relatedTarget; if (!t) return;
        var mode = t.getAttribute('data-mode') || 'create';
        if (mode === 'edit') {
            $('#cityFormModalLabel').text('Edit City');
            $('#cityForm').attr('action', CY_UPDATE.replace('__ID__', t.getAttribute('data-id')));
            $('#cyFormMode').val('edit'); $('#cyEditId').val(t.getAttribute('data-id'));
            $('#cyCountry').val(t.getAttribute('data-country') || '');
            fillStates(t.getAttribute('data-country'), t.getAttribute('data-state'));
            fillDistricts(t.getAttribute('data-state'), t.getAttribute('data-district'));
            $('#cyName').val(t.getAttribute('data-name') || '');
            $('#cyStatus').val(t.getAttribute('data-status') === '1' ? '1' : '2');
        } else {
            $('#cityFormModalLabel').text('Add City');
            $('#cityForm').attr('action', CY_STORE);
            $('#cyFormMode').val('create'); $('#cyEditId').val('');
            $('#cyCountry').val(''); fillStates('', null); fillDistricts('', null);
            $('#cyName').val(''); $('#cyStatus').val('1');
        }
    });

    @if($errors->any())
    (function () {
        var t = document.createElement('div');
        t.setAttribute('data-mode', @json(old('_form_mode', 'create')));
        @if(old('_form_mode') === 'edit')
        t.setAttribute('data-id', @json((string) old('_edit_id', '')));
        @endif
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cityFormModal')).show(t);
        $('#cyCountry').val(@json((string) old('country_master_pk', '')));
        fillStates(@json((string) old('country_master_pk', '')), @json((string) old('state_master_pk', '')));
        fillDistricts(@json((string) old('state_master_pk', '')), @json((string) old('district_master_pk', '')));
        $('#cyName').val(@json((string) old('city_name', '')));
        $('#cyStatus').val(@json((string) old('active_inactive', '1')));
    })();
    @endif
});
</script>
@endpush
