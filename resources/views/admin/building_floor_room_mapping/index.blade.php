@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Room Mapping')

@section('setup_content')
<div class="container-fluid building-floor-room-mapping-index">

    <x-breadcrum title="Hostel Building Floor Room Mapping" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <h4>Hostel Building Floor Room Mapping</h4>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
                            <a href="{{route('hostel.building.floor.room.map.create')}}" class="btn btn-primary w-100 w-md-auto">+ Add Hostel Building Floor Room</a>
                            <a href="{{ route('hostel.building.floor.room.map.export') }}" class="btn btn-secondary w-100 w-md-auto">Export All</a>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" action="{{ route('hostel.building.floor.room.map.index') }}" class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Search..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="building_id" class="form-select form-select-sm">
                                <option value="">All Buildings</option>
                                @foreach($buildings as $building)
                                <option value="{{ $building->pk }}"
                                    {{ request('building_id') == $building->pk ? 'selected' : '' }}>
                                    {{ $building->building_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="room_type" class="form-select form-select-sm">
                                <option value="">All Room Types</option>
                                @foreach($roomTypes as $key => $type)
                                <option value="{{ $key }}" {{ request('room_type') == $key ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="{{ route('hostel.building.floor.room.map.index') }}"
                                class="btn btn-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </form>

                <hr>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" style="background-color: #004a93; font-weight: 600;">#</th>
                                <th width="15%" style="background-color: #004a93; font-weight: 600;">Building Name</th>
                                <th width="10%" style="background-color: #004a93; font-weight: 600;">Floor Name</th>
                                <th width="15%" style="background-color: #004a93; font-weight: 600;">Room Name</th>
                                <th width="10%" style="background-color: #004a93; font-weight: 600;">Room Type</th>
                                <th width="8%" style="background-color: #004a93; font-weight: 600;">Capacity</th>
                                <th width="20%" style="background-color: #004a93; font-weight: 600;">Comment</th>
                                <th width="8%" style="background-color: #004a93; font-weight: 600;">Status</th>
                                <th width="9%" style="background-color: #004a93; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $index => $row)
                            <tr>
                                <td>{{ $mappings->firstItem() + $index }}</td>
                                <td>{{ $row->building->building_name ?? '—' }}</td>
                                <td>{{ $row->floor->floor_name ?? '—' }}</td>
                                <td>{{ $row->room_name }}</td>
                                <td>{{ $row->room_type }}</td>
                                <td>{{ $row->capacity }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm comment-input"
                                        data-id="{{ $row->pk }}" value="{{ $row->comment }}">
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="building_floor_room_mapping" data-column="active_inactive"
                                            data-id="{{ $row->pk }}" {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('hostel.building.floor.room.map.edit', encrypt($row->pk)) }}"
                                        class="btn btn-sm btn-primary">Edit</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                    <div class="text-muted small mb-2">
                        Showing {{ $mappings->firstItem() }}
                        to {{ $mappings->lastItem() }}
                        of {{ $mappings->total() }} items
                    </div>

                    <div class="text-end mb-2">
                        {{ $mappings->links('vendor.pagination.custom') }}
                    </div>

                              
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


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
</script>
@endpush