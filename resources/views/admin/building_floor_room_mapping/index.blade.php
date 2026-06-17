@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Room Mapping')

@section('setup_content')
<div class="container-fluid room-map-index">

    <x-breadcrum title="Hostel Floor Room Map">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" id="roomMapAddBtn"
                class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                <span>Add Hostel Floor Room</span>
            </button>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="roomMapPrintBtn"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color:#fff; color:var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">print</i>
            <span>Print</span>
        </button>
        <a href="{{ route('hostel.building.floor.room.map.export', request()->query()) }}"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color:#fff; color:var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">download</i>
            <span>Download</span>
        </a>
    </div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="ds-card room-map-card">
            <div class="ds-card-body">

                {{-- Filters + Columns + Search (preserves GET filter names: search/building_id/room_type/status) --}}
                <form method="GET" action="{{ route('hostel.building.floor.room.map.index') }}" id="roomMapFilterForm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="text-secondary small fw-medium me-1">Filters</span>
                            <select name="building_id" onchange="this.form.submit()" class="form-select form-select-sm rm-filter">
                                <option value="">Building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->pk }}" {{ request('building_id') == $building->pk ? 'selected' : '' }}>
                                        {{ $building->building_name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="room_type" onchange="this.form.submit()" class="form-select form-select-sm rm-filter">
                                <option value="">Room Type</option>
                                @foreach($roomTypes as $key => $type)
                                    <option value="{{ $key }}" {{ request('room_type') == $key ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            <select name="status" onchange="this.form.submit()" class="form-select form-select-sm rm-filter">
                                <option value="">Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <a href="{{ route('hostel.building.floor.room.map.index') }}"
                               class="btn btn-sm btn-outline-danger rounded-1 d-inline-flex align-items-center gap-1">
                                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">restart_alt</i>
                                <span>Reset Filters</span>
                            </a>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" id="roomMapColsBtn"
                                class="btn rm-btn d-inline-flex align-items-center gap-1">
                                <i class="material-icons material-symbols-rounded" aria-hidden="true">view_column</i><span>Columns</span>
                            </button>
                            <div class="rm-search">
                                <i class="material-icons material-symbols-rounded rm-search-icon" aria-hidden="true">search</i>
                                <input type="text" name="search" class="form-control" placeholder="Search"
                                       value="{{ request('search') }}" aria-label="Search">
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap mb-0 w-100" id="roomMapTable">
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
                            @php
                                // Recover the editable "middle" of the server-built room_name:
                                //   room_name = substr(building,0,4) . '-' . floor_name . MIDDLE [ . '-' . room_type ]
                                $rmBldg = $row->building->building_name ?? '';
                                $rmFlr  = $row->floor->floor_name ?? '';
                                $rmPrefix = \Illuminate\Support\Str::substr($rmBldg, 0, 4) . '-' . $rmFlr;
                                $rmMiddle = (string) $row->room_name;
                                if ($rmPrefix !== '-' && \Illuminate\Support\Str::startsWith($rmMiddle, $rmPrefix)) {
                                    $rmMiddle = \Illuminate\Support\Str::substr($rmMiddle, \Illuminate\Support\Str::length($rmPrefix));
                                }
                                if ($row->room_type !== 'Room') {
                                    $rmTypeSuffix = '-' . $row->room_type;
                                    if (\Illuminate\Support\Str::endsWith($rmMiddle, $rmTypeSuffix)) {
                                        $rmMiddle = \Illuminate\Support\Str::substr($rmMiddle, 0, -\Illuminate\Support\Str::length($rmTypeSuffix));
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="text-center">{{ $mappings->firstItem() + $index }}</td>
                                <td>{{ $row->building->building_name ?? '—' }}</td>
                                <td>{{ $row->floor->floor_name ?? '—' }}</td>
                                <td>{{ $row->room_name }}</td>
                                <td>{{ $row->room_type }}</td>
                                <td class="text-center">{{ $row->capacity }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm comment-input rm-comment"
                                        data-id="{{ $row->pk }}" value="{{ $row->comment }}" placeholder="Add comment…">
                                </td>
                                <td class="text-center">
                                    @if($row->active_inactive == 1)
                                        <span class="badge rounded-1 rm-badge rm-badge-active">Active</span>
                                    @else
                                        <span class="badge rounded-1 rm-badge rm-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="rm-row-actions d-inline-flex align-items-center justify-content-center gap-2">
                                        <a href="{{ route('hostel.building.floor.room.map.edit', encrypt($row->pk)) }}"
                                            class="rm-icon-btn rm-icon-edit rm-edit-trigger" title="Edit" aria-label="Edit"
                                            data-pk="{{ encrypt($row->pk) }}"
                                            data-building="{{ $row->building_master_pk }}"
                                            data-floor="{{ $row->floor_master_pk }}"
                                            data-roomtype="{{ $row->room_type }}"
                                            data-roomname="{{ $rmMiddle }}"
                                            data-capacity="{{ $row->capacity }}"
                                            data-comment="{{ $row->comment }}"
                                            data-status="{{ (int) $row->active_inactive }}">
                                            <i class="material-icons material-symbols-rounded">edit</i>
                                        </a>
                                        <div class="form-check form-switch m-0 rm-row-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="building_floor_room_mapping" data-column="active_inactive"
                                                data-id="{{ $row->pk }}" {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                        <form action="{{ route('hostel.building.floor.room.map.destroy', encrypt($row->pk)) }}"
                                              method="POST" class="d-inline m-0"
                                              onsubmit="return confirm('Are you sure you want to delete this room mapping?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rm-icon-btn rm-icon-delete" title="Delete" aria-label="Delete"
                                                {{ $row->active_inactive == 0 ? '' : 'disabled' }}>
                                                <i class="material-icons material-symbols-rounded">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-secondary">No records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer: pagination (left) + count (right) --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                    <div class="rm-pagination">
                        {{ $mappings->links('vendor.pagination.custom') }}
                    </div>
                    <div class="rm-count d-inline-flex align-items-center gap-2">
                        <span>Showing</span>
                        <select id="rowsPerPage" class="form-select form-select-sm rm-count-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">All</option>
                        </select>
                        <span>of {{ $mappings->total() }} items</span>
                    </div>
                </div>

            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>

    {{-- Add Hostel Floor Room modal (reuses create-form field IDs so the existing
         cascading room-name JS in custom.js binds to it automatically) --}}
    <div class="modal fade rm-form-modal" id="roomMapFormModal" tabindex="-1" aria-labelledby="roomMapFormTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('hostel.building.floor.room.map.store') }}" method="POST" id="hostelFloorForm">
                    @csrf
                    <input type="hidden" name="pk" id="rm_pk" value="">
                    <div class="modal-header border-0 pb-2">
                        <h5 class="modal-title fw-semibold" id="roomMapFormTitle">Add Hostel Floor Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <hr class="mt-0 mb-3">
                        <div class="mb-3">
                            <label for="building_master_pk" class="form-label fw-semibold">Building<span class="text-danger">*</span></label>
                            <select class="form-select" id="building_master_pk" name="building_master_pk" required>
                                <option value="">Select Building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="floor_master_pk" class="form-label fw-semibold">Floor<span class="text-danger">*</span></label>
                            <select class="form-select" id="floor_master_pk" name="floor_master_pk" required>
                                <option value="">Select Floor</option>
                                @foreach($floors as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="room_type" class="form-label fw-semibold">Room Type<span class="text-danger">*</span></label>
                            <select class="form-select" id="room_type" name="room_type" required>
                                <option value="">Select Type</option>
                                @foreach($roomTypes as $key => $type)
                                    <option value="{{ $key }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="rm_room_name" class="form-label fw-semibold">Room Name<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text floor_room_name">-</span>
                                <input type="text" class="form-control" id="rm_room_name" name="room_name"
                                       placeholder="eg. Naramada Hostel" required>
                            </div>
                            <div class="floor_room_name_span small text-secondary mt-1"></div>
                        </div>
                        <div class="mb-3">
                            <label for="rm_capacity" class="form-label fw-semibold">Capacity of Room<span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="rm_capacity" name="capacity"
                                   placeholder="eg. 25" required>
                        </div>
                        <div class="mb-3">
                            <label for="rm_status" class="form-label fw-semibold">Building Status<span class="text-danger">*</span></label>
                            <select class="form-select" id="rm_status" name="active_inactive" required>
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-1">
                            <label for="rm_comment" class="form-label fw-semibold">Comments</label>
                            <input type="text" class="form-control" id="rm_comment" name="comment"
                                   placeholder="eg. Lorem ipsum dolor sit amet">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-3" id="roomMapFormSubmit">Add Hostel Floor Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Clean, government-portal style table presentation */
    .room-map-index #roomMapTable thead th {
        background: var(--ds-surface-2, #f8fafc);
        color: var(--ds-ink-muted, #64748b);
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        border-bottom: 1px solid var(--ds-line, #e5e7eb);
        white-space: nowrap;
        vertical-align: middle;
        padding: 0.85rem 0.9rem;
    }
    .room-map-index #roomMapTable tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--ds-line, #eef1f4);
        padding: 0.75rem 0.9rem;
    }
    .room-map-index #roomMapTable tbody tr { transition: background-color 0.15s ease; }
    .room-map-index #roomMapTable tbody tr:hover { background-color: rgba(var(--bs-primary-rgb, 0 74 147), 0.04); }

    /* Inline comment editor — borderless until focus */
    .room-map-index .rm-comment {
        border: 1px solid transparent;
        background: transparent;
        min-width: 160px;
        border-radius: 0.5rem;
        box-shadow: none;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .room-map-index .rm-comment:hover { border-color: var(--ds-line, #e5e7eb); background: #fff; }
    .room-map-index .rm-comment:focus {
        border-color: var(--bs-primary);
        background: #fff;
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.12);
    }

    /* Status pill badges */
    .room-map-index .rm-badge { padding: 0.4em 0.85em; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.01em; }
    .room-map-index .rm-badge-active { color: #157347; background-color: #d6f5e3; }
    .room-map-index .rm-badge-inactive { color: #b02a37; background-color: #fcdcdf; }

    /* Inline row action icons */
    .room-map-index .rm-row-actions { line-height: 1; }
    .room-map-index .rm-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        padding: 0;
        border: 0;
        border-radius: 0.5rem;
        background: transparent;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
    }
    .room-map-index .rm-icon-btn .material-symbols-rounded { font-size: 20px; line-height: 1; }
    .room-map-index .rm-icon-edit { color: var(--bs-primary, #4f46e5); }
    .room-map-index .rm-icon-edit:hover { background: rgba(var(--bs-primary-rgb, 79 70 229), 0.12); transform: translateY(-1px); }
    .room-map-index .rm-icon-delete { color: #dc3545; }
    .room-map-index .rm-icon-delete:hover:not(:disabled) { background: rgba(220, 53, 69, 0.12); transform: translateY(-1px); }
    .room-map-index .rm-icon-btn:disabled { color: #c4c9d0; cursor: not-allowed; opacity: 1; }
    .room-map-index .rm-row-switch { padding-left: 2.4em; }
    .room-map-index .rm-row-switch .form-check-input { width: 2.1em; height: 1.15em; cursor: pointer; margin-top: 0.15em; }
    .room-map-index .rm-row-switch .form-check-input:checked { background-color: #1fae5b; border-color: #1fae5b; }
    .room-map-index .rm-row-switch .form-check-input:focus { box-shadow: 0 0 0 0.2rem rgba(31, 174, 91, 0.2); border-color: #1fae5b; }

    /* Filters */
    .room-map-index .rm-filter { width: auto; min-width: 9rem; border-radius: 0.6rem; }
    .room-map-index .rm-btn {
        height: calc(1.5em + 0.5rem + 2px);
        min-height: 38px;
        padding: 0 0.85rem;
        border-radius: 0.6rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--ds-ink, #344054);
        background: #fff;
        border: 1px solid var(--ds-line, #e5e7eb);
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .room-map-index .rm-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--bs-primary); }
    .room-map-index .rm-btn .material-symbols-rounded { font-size: 18px; }

    /* Search box with icon */
    .room-map-index .rm-search { position: relative; }
    .room-map-index .rm-search .rm-search-icon {
        position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%);
        font-size: 18px; color: #98a2b3; pointer-events: none;
    }
    .room-map-index .rm-search input {
        width: 240px; max-width: 100%; height: 38px;
        border-radius: 0.6rem; border: 1px solid var(--ds-line, #e5e7eb);
        padding-left: 2.2rem; font-size: 0.9rem;
    }
    .room-map-index .rm-search input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
    }

    /* Footer count + pagination */
    .room-map-index .rm-count { font-size: 0.875rem; color: var(--ds-ink-muted, #64748b); white-space: nowrap; }
    .room-map-index .rm-count-select { width: auto; min-width: 4.25rem; border-radius: 0.5rem; font-weight: 600; }
    .room-map-index .rm-pagination .pagination { margin: 0; gap: 0.3rem; align-items: center; flex-wrap: wrap; }
    .room-map-index .rm-pagination .page-item { margin: 0; }
    .room-map-index .rm-pagination .page-link {
        min-width: 2.1rem; height: 2.1rem;
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0 0.55rem; border: 1px solid transparent; border-radius: 0.6rem;
        background: transparent; color: #475467; font-size: 0.875rem; font-weight: 600;
        box-shadow: none;
    }
    .room-map-index .rm-pagination .page-link:hover { background: #f1f5f9; color: var(--bs-primary); }
    .room-map-index .rm-pagination .page-item.active .page-link { background: #fff; border-color: var(--bs-primary); color: var(--bs-primary); }
    .room-map-index .rm-pagination .page-item.disabled .page-link { color: #cbd5e1; }

    @media (max-width: 767.98px) {
        .room-map-index .rm-search input { width: 100%; }
        .room-map-index .rm-search { flex: 1 1 100%; }
    }

    /* ---- Modals (appended in-page; class-scoped, not page-scoped) ---- */
    .rm-form-modal .modal-content,
    .rm-cols-modal .modal-content {
        border: 0; border-radius: 1rem; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
    }
    .rm-form-modal .modal-title,
    .rm-cols-modal .modal-title { font-size: 1.2rem; color: #1f2937; }
    .rm-form-modal hr,
    .rm-cols-modal hr { color: #e5e7eb; opacity: 1; }
    .rm-form-modal .form-label { font-size: 0.875rem; color: #344054; margin-bottom: 0.35rem; }
    .rm-form-modal .form-control,
    .rm-form-modal .form-select { height: 44px; border-radius: 0.6rem; border-color: #e5e7eb; font-size: 0.9rem; }
    .rm-form-modal .input-group-text { border-radius: 0.6rem 0 0 0.6rem; border-color: #e5e7eb; background: #f8fafc; font-weight: 600; }
    .rm-form-modal .form-control:focus,
    .rm-form-modal .form-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
    }
    .rm-cols-modal .rm-col-chip {
        display: flex; align-items: center; gap: 0.6rem; width: 100%; margin: 0;
        padding: 0.6rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 0.6rem;
        font-size: 0.92rem; color: #344054; cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .rm-cols-modal .rm-col-chip:hover { border-color: #cbd5e1; background: #f8fafc; }
    .rm-cols-modal .rm-col-chip .form-check-input:checked,
    .rm-form-modal .form-check-input:checked { background-color: var(--bs-primary); border-color: var(--bs-primary); }
</style>
@endsection

@push('scripts')
<script>
$(document).on('change', '.comment-input', function() {
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
        success: function(response) {
            if (response.success) {
                toastr.success('Comment updated successfully');
            } else {
                toastr.error('Failed to update comment');
            }
        },
        error: function() {
            toastr.error('Error occurred');
        }
    });
});

// Rows per page functionality
$('#rowsPerPage').on('change', function() {
    const value = $(this).val();
    const url = new URL(window.location.href);

    if (value === 'all') {
        url.searchParams.set('per_page', 10000);
    } else {
        url.searchParams.set('per_page', value);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
});

// Set current rows per page value
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const perPage = urlParams.get('per_page');

    if (perPage) {
        if (perPage >= 10000) {
            $('#rowsPerPage').val('all');
        } else {
            $('#rowsPerPage').val(perPage);
        }
    }
});

// ---- Add modal, Print, and Columns visibility (frontend only) ----
(function() {
    var $ = window.jQuery;
    if (!$) { return; }

    function showFormModal() {
        var el = document.getElementById('roomMapFormModal');
        if (el && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }
    function setMode(isEdit) {
        document.getElementById('roomMapFormTitle').textContent = isEdit ? 'Edit Hostel Floor Room' : 'Add Hostel Floor Room';
        document.getElementById('roomMapFormSubmit').textContent = isEdit ? 'Update' : 'Add Hostel Floor Room';
    }

    // Add → open a clean modal (the cascading room-name JS in custom.js is already
    // bound to the modal's #building_master_pk / #floor_master_pk / #room_type fields).
    $(document).on('click', '#roomMapAddBtn', function() {
        var form = document.getElementById('hostelFloorForm');
        if (form) { form.reset(); }
        document.getElementById('rm_pk').value = '';
        document.getElementById('rm_status').value = '1';
        $('.floor_room_name').text('-');
        $('.floor_room_name_span').html('');
        setMode(false);
        showFormModal();
    });

    // Edit → open the same modal pre-filled from the row's data-* attributes.
    // The server rebuilds the full room_name from building + floor + this middle
    // value + room_type on submit, so only the editable middle is needed here.
    $(document).on('click', '.rm-edit-trigger', function(e) {
        e.preventDefault();
        var $b = $(this);
        document.getElementById('rm_pk').value = $b.attr('data-pk') || '';
        document.getElementById('building_master_pk').value = $b.attr('data-building') || '';
        document.getElementById('floor_master_pk').value = $b.attr('data-floor') || '';
        document.getElementById('room_type').value = $b.attr('data-roomtype') || '';
        document.getElementById('rm_room_name').value = $b.attr('data-roomname') || '';
        document.getElementById('rm_capacity').value = $b.attr('data-capacity') || '';
        document.getElementById('rm_comment').value = $b.attr('data-comment') || '';
        document.getElementById('rm_status').value = $b.attr('data-status') || '1';
        $('.floor_room_name_span').html('');
        // Rebuild the room-name prefix preview from the now-selected building/floor.
        $('#building_master_pk').trigger('change');
        setMode(true);
        showFormModal();
    });

    // Print
    $(document).on('click', '#roomMapPrintBtn', function() { window.print(); });

    // Columns visibility — build a modal from the table headers and toggle columns.
    var table = document.getElementById('roomMapTable');
    if (table) {
        var headers = table.querySelectorAll('thead th');
        var modalId = 'roomMapColsModal';
        var chips = '';
        headers.forEach(function(th, i) {
            var title = (th.textContent || '').trim() || ('Column ' + (i + 1));
            chips += '<div class="col-6 col-md-4"><label class="rm-col-chip">' +
                '<input type="checkbox" class="form-check-input m-0 rm-col-toggle" data-col="' + i + '" checked>' +
                '<span></span></label></div>';
        });
        var $modal = $(
            '<div class="modal fade rm-cols-modal" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                '<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">' +
                    '<div class="modal-header border-0 pb-2"><h5 class="modal-title fw-semibold">Column Visibility</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
                    '<div class="modal-body pt-0"><hr class="mt-0 mb-3"><div class="row g-2">' + chips + '</div></div>' +
                    '<div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-primary px-4 rounded-3" data-bs-dismiss="modal">Close</button></div>' +
                '</div></div>' +
            '</div>'
        );
        // set chip labels safely (avoid HTML injection from header text)
        $modal.find('.rm-col-chip').each(function(i) {
            var title = (headers[i].textContent || '').trim() || ('Column ' + (i + 1));
            $(this).find('span').text(title);
        });
        $('body').append($modal);

        $(document).on('change', '.rm-col-toggle', function() {
            var idx = parseInt($(this).attr('data-col'), 10);
            var visible = $(this).is(':checked');
            table.querySelectorAll('tr').forEach(function(tr) {
                var cell = tr.children[idx];
                if (cell) { cell.style.display = visible ? '' : 'none'; }
            });
        });

        $(document).on('click', '#roomMapColsBtn', function() {
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
            }
        });
    }
})();
</script>
@endpush
