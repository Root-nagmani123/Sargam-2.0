@extends('admin.layouts.master')

@section('title', 'Hostel Floor Room Map')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
    /* Inline-editable comment: reads as text, becomes a field on hover / focus. */
    .mst-page.hostel-room-page .comment-input {
        width: 100%;
        min-width: 9rem;
        border: 1px solid transparent;
        background: transparent;
        border-radius: var(--ds-radius-1);
        padding: var(--ds-space-1) var(--ds-space-2);
        font-size: 0.875rem;
        color: var(--ds-ink);
    }
    .mst-page.hostel-room-page .comment-input:hover {
        border-color: var(--ds-line);
        background: var(--ds-surface);
    }
    .mst-page.hostel-room-page .comment-input:focus {
        outline: 0;
        border-color: var(--ds-primary);
        background: var(--ds-surface);
        box-shadow: var(--ds-focus-ring);
    }
</style>
@endpush

@section('setup_content')
@php
    $currentQuery = request()->getQueryString();
    $exportUrl = route('hostel.building.floor.room.map.export') . ($currentQuery ? ('?' . $currentQuery) : '');
@endphp
<div class="container-fluid mst-page hostel-room-page">
    <x-breadcrum title="Hostel Floor Room Map" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="hrAddBtn" data-bs-toggle="modal" data-bs-target="#hrFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Hostel Floor Room</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card (§1). Download
         carries the current filter query string, so the .xlsx matches the grid;
         Print prints this screen (master-admin.css drops the toolbar, pager
         and Action column on paper). --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <a href="{{ $exportUrl }}" class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel (.xlsx)">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="hrPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Filters + Columns + Search. Server-side: every control is a GET
                 parameter of HostelBuildingFloorRoomMappingController::index(). --}}
            <form method="GET" action="{{ route('hostel.building.floor.room.map.index') }}" id="hrFilterForm"
                  class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <input type="hidden" name="per_page" id="hrPerPage" value="{{ request('per_page', 10) }}">

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select name="building_id" class="form-select mst-control mst-searchable js-hr-filter"
                                data-placeholder="Building" aria-label="Filter by building">
                            <option value="">Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->pk }}" {{ request('building_id') == $building->pk ? 'selected' : '' }}>
                                    {{ $building->building_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="room_type" class="form-select mst-control mst-searchable js-hr-filter"
                                data-placeholder="Room Type" aria-label="Filter by room type">
                            <option value="">Room Type</option>
                            @foreach($roomTypes as $key => $type)
                                <option value="{{ $key }}" {{ request('room_type') == $key ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="status" class="form-select mst-control mst-searchable js-hr-filter"
                                data-placeholder="Status" aria-label="Filter by status">
                            <option value="">Status</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <a href="{{ route('hostel.building.floor.room.map.index') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hrBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#hrColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- Server-side search: Enter submits the filter form. --}}
                    <div class="programme-dt-search">
                        <div class="dataTables_filter">
                            <label class="mb-0 w-100">
                                <input type="search" name="search" class="form-control shadow-none"
                                       placeholder="Search" value="{{ request('search') }}" aria-label="Search rooms">
                            </label>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Server-paginated (HostelBuildingFloorRoomMappingController::index):
                 no DataTable on this grid, so the footer below is hand-written
                 (docs/new-design-index-page.md §4 variant B). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="hrRoomTable">
                        <caption class="visually-hidden">Hostel floor room mappings</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Building Name</th>
                                <th scope="col">Floor Name</th>
                                <th scope="col">Room Name</th>
                                <th scope="col">Room Type</th>
                                <th scope="col" class="text-center">Capacity</th>
                                <th scope="col">Comment</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $index => $row)
                                @php
                                    $isActive = (int) $row->active_inactive === 1;
                                    // Edit prefills only the middle of the stored room name
                                    // (store() rebuilds the building / floor prefix and type suffix).
                                    $rn = $row->room_name ?? '';
                                    $roomMiddle = '';
                                    if ($rn !== '') {
                                        $roomSuffix = substr($rn, 6);
                                        $roomMiddle = explode('-', $roomSuffix)[0] ?? '';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $mappings->firstItem() + $index }}</td>
                                    <td>{{ $row->building->building_name ?? '—' }}</td>
                                    <td>{{ $row->floor->floor_name ?? '—' }}</td>
                                    <td>{{ $row->room_name }}</td>
                                    <td>{{ $row->room_type }}</td>
                                    <td class="text-center">{{ $row->capacity }}</td>
                                    <td>
                                        <input type="text" class="comment-input" data-id="{{ $row->pk }}"
                                               value="{{ $row->comment }}" placeholder="Add comment"
                                               aria-label="Comment for room {{ $row->room_name }}">
                                    </td>
                                    <td data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $row->room_name ?? '',
                                            'edit'   => [
                                                'class' => 'hr-edit-btn',
                                                'attrs' => [
                                                    'data-id'       => encrypt($row->pk),
                                                    'data-building' => $row->building_master_pk,
                                                    'data-floor'    => $row->floor_master_pk,
                                                    'data-roomtype' => $row->room_type,
                                                    'data-roomname' => $roomMiddle,
                                                    'data-capacity' => $row->capacity,
                                                    'data-comment'  => $row->comment,
                                                    'data-status'   => (int) $row->active_inactive,
                                                ],
                                            ],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'building_floor_room_mapping',
                                                'column' => 'active_inactive',
                                                'id'     => $row->pk,
                                            ],
                                            // destroy() refuses nothing, and this grid never
                                            // guarded Delete — it stays enabled on every row.
                                            'delete' => ['action' => route('hostel.building.floor.room.map.destroy', encrypt($row->pk))],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty">
                                    <td colspan="9">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $mappings->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_length">
                            <label class="mb-0">Showing
                                <select id="rowsPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                    @foreach([10, 25, 50, 100, 200] as $size)
                                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info" aria-live="polite">of {{ number_format($mappings->total()) }} items</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Hostel Floor Room -->
<div class="modal fade mst-modal" id="hrFormModal" tabindex="-1" aria-labelledby="hrFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            {{-- The form sits between .modal-content and .modal-body, so it has to
                 be the flex column itself or modal-dialog-scrollable clips the
                 footer (Cancel / submit) on short viewports. --}}
            <form id="hrRoomForm" action="{{ route('hostel.building.floor.room.map.store') }}" method="POST" novalidate
                  class="d-flex flex-column overflow-hidden">
                @csrf
                <input type="hidden" name="pk" id="hrPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="hrFormModalLabel">Add Hostel Floor Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hrFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mst-field-card">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="hrBuilding" class="mst-form-label d-block">
                                    Building <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hrBuilding" name="building_master_pk"
                                        data-placeholder="Select Building" required aria-required="true">
                                    <option value="">Select Building</option>
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-field="building_master_pk"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hrFloor" class="mst-form-label d-block">
                                    Floor <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hrFloor" name="floor_master_pk"
                                        data-placeholder="Select Floor" required aria-required="true">
                                    <option value="">Select Floor</option>
                                    @foreach($floors as $floor)
                                        <option value="{{ $floor->pk }}">{{ $floor->floor_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-field="floor_master_pk"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hrRoomType" class="mst-form-label d-block">
                                    Room Type <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hrRoomType" name="room_type"
                                        data-placeholder="Select Type" required aria-required="true">
                                    <option value="">Select Type</option>
                                    @foreach($roomTypes as $key => $type)
                                        <option value="{{ $key }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-field="room_type"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hrRoomName" class="mst-form-label d-block">
                                    Room Name <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="text" class="form-control mst-control" id="hrRoomName" name="room_name"
                                       placeholder="eg. Naramada Hostel" maxlength="255" required aria-required="true">
                                <div class="invalid-feedback" data-field="room_name"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hrCapacity" class="mst-form-label d-block">
                                    Capacity of Room <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="number" class="form-control mst-control" id="hrCapacity" name="capacity"
                                       placeholder="eg. 25" min="1" required aria-required="true">
                                <div class="invalid-feedback" data-field="capacity"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="hrStatus" class="mst-form-label d-block">
                                    Room Status <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select mst-control mst-searchable" id="hrStatus" name="active_inactive"
                                        data-placeholder="Select Status" required aria-required="true">
                                    <option value="">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <div class="invalid-feedback" data-field="active_inactive"></div>
                            </div>

                            <div class="col-12">
                                <label for="hrComment" class="mst-form-label d-block">Comments</label>
                                <input type="text" class="form-control mst-control" id="hrComment" name="comment"
                                       placeholder="eg. Lorem ipsum dolor sit amet" maxlength="255">
                                <div class="invalid-feedback" data-field="comment"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="hrSubmitBtn">Add Hostel Floor Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="hrColumnVisibilityModal" tabindex="-1" aria-labelledby="hrColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hrColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="hrColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    // Server-rendered grid: the badge and the switch live in different
    // columns, so refresh the page once custom.js has saved the new status.
    MstAdmin.reloadPageOnStatusToggle();

    $(document).ready(function () {
        /* ---- Filters auto-apply (jQuery change: also fires from Select2) ---- */
        $('.js-hr-filter').on('change', function () {
            $('#hrFilterForm').trigger('submit');
        });

        /* ---- Page size ---- */
        $('#rowsPerPage').on('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });

        /* ---- Print ---- */
        $('#hrPrintBtn').on('click', function () {
            window.print();
        });

        /* ---- Inline comment edit (unchanged behaviour) ---- */
        $(document).on('change', '.comment-input', function () {
            var id = $(this).data('id');
            var value = $(this).val();

            $.ajax({
                url: '{{ route("hostel.building.floor.room.map.update.comment") }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: id,
                    comment: value
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof toastr !== 'undefined') toastr.success('Comment updated successfully');
                    } else {
                        if (typeof toastr !== 'undefined') toastr.error('Failed to update comment');
                    }
                },
                error: function () {
                    if (typeof toastr !== 'undefined') toastr.error('Error occurred');
                }
            });
        });

        /* ---- Column show / hide (manual table — no DataTable on this grid) ---- */
        var hrColStorageKey = 'hrGrid:hiddenColumns:v1';
        var $table = $('#hrRoomTable');

        function hrGetHiddenCols() {
            try {
                var raw = localStorage.getItem(hrColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function hrPersistHiddenCols(arr) {
            try { localStorage.setItem(hrColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function hrApplyCols() {
            var hidden = hrGetHiddenCols();
            // Body rows only by cell index; the empty-state row spans every column.
            $table.find('thead tr, tbody tr:not(.mst-empty)').each(function () {
                $(this).children().each(function (idx) {
                    $(this).toggle(hidden.indexOf(idx) === -1);
                });
            });
        }

        function hrBuildColumnGrid() {
            var hidden = hrGetHiddenCols();
            var $grid = $('#hrColumnToggleGrid').empty();

            $table.find('thead th').each(function (idx) {
                var title = $(this).text().replace(/\s+/g, ' ').trim();
                if (!title) {
                    return;
                }
                var inputId = 'hrcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr({ 'for': inputId, title: title });
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = hrGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    hrPersistHiddenCols(h);
                    hrApplyCols();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        hrApplyCols();
        hrBuildColumnGrid();

        /* ---- Add / Edit modal ---- */
        var $form = $('#hrRoomForm');
        var $alert = $('#hrFormAlert');

        // Selects are Select2 (.mst-searchable): after any value change made
        // here, repaint the rendered selection.
        function hrSyncSelects() {
            $form.find('select.mst-searchable').trigger('change.select2');
        }

        function hrClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function hrResetForm() {
            $form[0].reset();
            $('#hrPk').val('');
            hrClearErrors();
            hrSyncSelects();
        }

        // Open for "Add"
        $('#hrAddBtn').on('click', function () {
            hrResetForm();
            $('#hrFormModalLabel').text('Add Hostel Floor Room');
            $('#hrSubmitBtn').text('Add Hostel Floor Room');
            $('#hrStatus').val('1');
            hrSyncSelects();
        });

        // Open for "Edit"
        $(document).on('click', '.hr-edit-btn', function () {
            var $btn = $(this);
            hrResetForm();
            $('#hrFormModalLabel').text('Edit Hostel Floor Room');
            $('#hrSubmitBtn').text('Update');

            $('#hrPk').val($btn.data('id'));
            $('#hrBuilding').val(String($btn.data('building')));
            $('#hrFloor').val(String($btn.data('floor')));
            $('#hrRoomType').val(String($btn.data('roomtype')));
            $('#hrRoomName').val($btn.data('roomname'));
            $('#hrCapacity').val($btn.data('capacity'));
            $('#hrStatus').val(String($btn.data('status')));
            $('#hrComment').val($btn.data('comment'));
            hrSyncSelects();

            bootstrap.Modal.getOrCreateInstance(document.getElementById('hrFormModal')).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            hrClearErrors();

            var $submit = $('#hrSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success')
                          .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));
                    setTimeout(function () { window.location.reload(); }, 800);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            $form.find('[name="' + field + '"]').addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An error occurred while saving. Please try again.';
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                    }
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        });

        // Reset on close so a stale edit can't leak into Add
        document.getElementById('hrFormModal').addEventListener('hidden.bs.modal', function () {
            hrResetForm();
            $('#hrFormModalLabel').text('Add Hostel Floor Room');
            $('#hrSubmitBtn').text('Add Hostel Floor Room');
        });
    });
</script>
@endpush
