@extends('admin.layouts.master')

@section('title', 'Hostel Floor Room Map')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
{{-- Shared by the four OT Hostel pages (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/ot-hostel-admin.css') }}?v={{ @filemtime(public_path('css/ot-hostel-admin.css')) }}">
<style>
    /* Inline-editable comment: looks like text, editable on focus */
    .hostel-room-page .comment-input {
        border: 1px solid transparent;
        background: transparent;
        border-radius: 6px;
        padding: .35rem .5rem;
        font-size: .875rem;
        color: #344054;
        min-width: 140px;
    }
    .hostel-room-page .comment-input:hover {
        border-color: #e4e7ec;
        background: #fff;
    }
    .hostel-room-page .comment-input:focus {
        outline: 0;
        border-color: #004a93;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }
    .hostel-room-page .programme-dt-filter-select select { min-width: 150px; }
</style>
@endpush

@section('setup_content')
@php
    $currentQuery = request()->getQueryString();
    $exportUrl = route('hostel.building.floor.room.map.export') . ($currentQuery ? ('?' . $currentQuery) : '');
@endphp
<div class="container-fluid hostel-room-page">
    <x-breadcrum title="Hostel Floor Room Map">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="hrAddBtn" data-bs-toggle="modal" data-bs-target="#hrFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Hostel Floor Room</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Print / Download) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" class="btn programme-dt-btn-columns" id="hrPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
        <a href="{{ $exportUrl }}" class="btn programme-dt-btn-columns" title="Download">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Filters + Columns + Search --}}
            <form method="GET" action="{{ route('hostel.building.floor.room.map.index') }}" id="hrFilterForm"
                  class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4">
                <input type="hidden" name="per_page" id="hrPerPage" value="{{ request('per_page', 10) }}">

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select name="building_id" class="form-select form-select-sm js-hr-filter" aria-label="Filter by building">
                            <option value="">Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->pk }}" {{ request('building_id') == $building->pk ? 'selected' : '' }}>
                                    {{ $building->building_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="room_type" class="form-select form-select-sm js-hr-filter" aria-label="Filter by room type">
                            <option value="">Room Type</option>
                            @foreach($roomTypes as $key => $type)
                                <option value="{{ $key }}" {{ request('room_type') == $key ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="status" class="form-select form-select-sm js-hr-filter" aria-label="Filter by status">
                            <option value="">Status</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <a href="{{ route('hostel.building.floor.room.map.index') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-xl-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hrBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#hrColumnVisibilityModal" title="Show / hide columns" style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
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

            {{-- Table --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="hrRoomTable">
                        <thead>
                            <tr>
                                <th class="text-center">S. No.</th>
                                <th>Building Name</th>
                                <th>Floor Name</th>
                                <th>Room Name</th>
                                <th>Room Type</th>
                                <th class="text-center">Capacity</th>
                                <th>Comment</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $mappings->firstItem() + $index }}</td>
                                    <td>{{ $row->building->building_name ?? '—' }}</td>
                                    <td>{{ $row->floor->floor_name ?? '—' }}</td>
                                    <td>{{ $row->room_name }}</td>
                                    <td>{{ $row->room_type }}</td>
                                    <td class="text-center">{{ $row->capacity }}</td>
                                    <td>
                                        <input type="text" class="comment-input" data-id="{{ $row->pk }}"
                                               value="{{ $row->comment }}" placeholder="Add comment">
                                    </td>
                                    <td class="text-center">
                                        @if($row->active_inactive == 1)
                                            <span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>
                                        @else
                                            <span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $rn = $row->room_name ?? '';
                                            $roomMiddle = '';
                                            if ($rn !== '') {
                                                $roomSuffix = substr($rn, 6);
                                                $roomMiddle = explode('-', $roomSuffix)[0] ?? '';
                                            }
                                        @endphp
                                        @php $hrIsActive = $row->active_inactive == 1; @endphp
                                        {{-- Edit · the status switch · Delete, each an icon over a
                                             caption (§3b). The .hr-edit-btn hook and its data-*
                                             payload are what the modal reads. --}}
                                        <div class="oth-act-group" role="group" aria-label="Room actions">
                                            <button type="button" class="oth-act oth-act--edit hr-edit-btn"
                                                    title="Edit" aria-label="Edit room"
                                                    data-id="{{ encrypt($row->pk) }}"
                                                    data-building="{{ $row->building_master_pk }}"
                                                    data-floor="{{ $row->floor_master_pk }}"
                                                    data-roomtype="{{ $row->room_type }}"
                                                    data-roomname="{{ $roomMiddle }}"
                                                    data-capacity="{{ $row->capacity }}"
                                                    data-comment="{{ $row->comment }}"
                                                    data-status="{{ (int) $row->active_inactive }}">
                                                <span class="oth-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="oth-act__label">Edit</span>
                                            </button>

                                            {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                 custom.css pulls a .form-check-input inside one left
                                                 by -2.375rem. custom.js binds .status-toggle globally
                                                 off these data-* attributes. --}}
                                            <label class="oth-act oth-act--toggle"
                                                   title="{{ $hrIsActive ? 'Deactivate' : 'Activate' }}">
                                                <span class="oth-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="building_floor_room_mapping" data-column="active_inactive"
                                                           data-id="{{ $row->pk }}" {{ $hrIsActive ? 'checked' : '' }}
                                                           aria-label="{{ $hrIsActive ? 'Deactivate' : 'Activate' }} room">
                                                </span>
                                                <span class="oth-act__label">{{ $hrIsActive ? 'Deactivate' : 'Activate' }}</span>
                                            </label>

                                            <form action="{{ route('hostel.building.floor.room.map.destroy', encrypt($row->pk)) }}"
                                                  method="POST" class="oth-act oth-act--del"
                                                  onsubmit="return confirm('Are you sure you want to delete this room mapping?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="oth-act__btn" title="Delete" aria-label="Delete room">
                                                    <span class="oth-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="oth-act__label">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer: pagination + count + page size --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="programme-dt-pagination">
                        {{ $mappings->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <label class="d-inline-flex align-items-center gap-2 mb-0">
                            <span>Showing</span>
                            <select id="rowsPerPage" class="form-select form-select-sm" style="width:auto;">
                                @foreach([10, 25, 50, 100, 200] as $size)
                                    <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </label>
                        <span class="text-muted">of {{ number_format($mappings->total()) }} items</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Hostel Floor Room Modal -->
<div class="modal fade oth-modal" id="hrFormModal" tabindex="-1" aria-labelledby="hrFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form id="hrRoomForm" action="{{ route('hostel.building.floor.room.map.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="hrPk" value="">
                <div class="oth-modal-header">
                    <h5 class="oth-modal-title" id="hrFormModalLabel">Add Hostel Floor Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="oth-modal-body">
                    <div id="hrFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="oth-field-card">
                    <div class="oth-field">
                        <label for="hrBuilding" class="oth-field-label">Building <span class="oth-req">*</span></label>
                        <select class="oth-control" id="hrBuilding" name="building_master_pk" required>
                            <option value="">Select Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="building_master_pk"></div>
                    </div>

                    <div class="oth-field">
                        <label for="hrFloor" class="oth-field-label">Floor <span class="oth-req">*</span></label>
                        <select class="oth-control" id="hrFloor" name="floor_master_pk" required>
                            <option value="">Select Floor</option>
                            @foreach($floors as $floor)
                                <option value="{{ $floor->pk }}">{{ $floor->floor_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="floor_master_pk"></div>
                    </div>

                    <div class="oth-field">
                        <label for="hrRoomType" class="oth-field-label">Room Type <span class="oth-req">*</span></label>
                        <select class="oth-control" id="hrRoomType" name="room_type" required>
                            <option value="">Select Type</option>
                            @foreach($roomTypes as $key => $type)
                                <option value="{{ $key }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="room_type"></div>
                    </div>

                    <div class="oth-field">
                        <label for="hrRoomName" class="oth-field-label">Room Name <span class="oth-req">*</span></label>
                        <input type="text" class="oth-control" id="hrRoomName" name="room_name"
                               placeholder="eg. Naramada Hostel" maxlength="255" required>
                        <div class="invalid-feedback" data-field="room_name"></div>
                    </div>

                    <div class="oth-field">
                        <label for="hrCapacity" class="oth-field-label">Capacity of Room <span class="oth-req">*</span></label>
                        <input type="number" class="oth-control" id="hrCapacity" name="capacity"
                               placeholder="eg. 25" min="1" required>
                        <div class="invalid-feedback" data-field="capacity"></div>
                    </div>

                    <div class="oth-field">
                        <label for="hrStatus" class="oth-field-label">Building Status <span class="oth-req">*</span></label>
                        <select class="oth-control" id="hrStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>

                    <div class="oth-field">
                        <label for="hrComment" class="oth-field-label">Comments</label>
                        <input type="text" class="oth-control" id="hrComment" name="comment"
                               placeholder="eg. Lorem ipsum dolor sit amet" maxlength="255">
                        <div class="invalid-feedback" data-field="comment"></div>
                    </div>
                    </div>
                </div>
                <div class="oth-modal-footer">
                    <button type="button" class="btn oth-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn oth-btn-submit" id="hrSubmitBtn">Add Hostel Floor Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="hrColumnVisibilityModal" tabindex="-1" aria-labelledby="hrColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hrColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="hrColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        /* ---- Filters auto-apply ---- */
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

        /* ---- Column show / hide (manual table) ---- */
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
            $table.find('tr').each(function () {
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
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
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

        function hrClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function hrResetForm() {
            $form[0].reset();
            $('#hrPk').val('');
            hrClearErrors();
        }

        // Open for "Add"
        $('#hrAddBtn').on('click', function () {
            hrResetForm();
            $('#hrFormModalLabel').text('Add Hostel Floor Room');
            $('#hrSubmitBtn').text('Add Hostel Floor Room');
            $('#hrStatus').val('1');
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
