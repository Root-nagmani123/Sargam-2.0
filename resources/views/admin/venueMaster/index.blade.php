@extends('admin.layouts.master')

@section('title', 'Venue-Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Venue Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

                                    <!-- Add New Button -->
                                    <a href="{{ route('Venue-Master.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New Venue
                                    </a>

                                    <!-- Search Box + Icon -->
                                    <!-- Search Expand -->
                                    <div class="search-expand d-flex align-items-center">
                                        <a href="javascript:void(0)" id="searchToggle">
                                            <i class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 20px; vertical-align: middle;">search</i>
                                        </a>

                                        <input type="text" class="form-control search-input ms-2" id="searchInput"
                                            placeholder="Search…" aria-label="Search">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table w-100 nowrap" style="border-radius: 10px; overflow: hidden;">
                            <thead style="background-color: #af2910;">
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Venue Name</th>
                                    <th class="col">Short Name</th>
                                    <th class="col">Description</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($venues as $key =>$venue)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $venues->firstItem() + $key }}</td>
                                    <td>
                                        {{ $venue->venue_name  }}
                                    </td>
                                    <td>
                                        {{ $venue->venue_short_name   }}
                                    </td>
                                    <td>
                                        {{ $venue->description   }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="venue_master" data-column="active_inactive"
                                                data-id="{{ $venue->venue_id }}" data-id_column="venue_id"
                                                {{ $venue->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">

                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="px-2"
                                                id="actionMenu{{ $venue->venue_id }}" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <span class="material-symbols-rounded fs-5">more_horiz</span>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                                aria-labelledby="actionMenu{{ $venue->venue_id }}">

                                                <!-- Edit -->
                                                <li>
                                                    <a href="{{ route('Venue-Master.edit', $venue->venue_id) }}"
                                                        class="dropdown-item d-flex align-items-center gap-2">
                                                        <span
                                                            class="material-symbols-rounded text-primary fs-6">edit</span>
                                                        Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form action="{{ route('Venue-Master.destroy', $venue->venue_id) }}"
                                                        method="POST" class="d-inline delete-form"
                                                        data-status="{{ $venue->active_inactive }}">

                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="button"
                                                            class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                            onclick="event.preventDefault();
                                if({{ $venue->active_inactive }} == 1) return;
                                if(confirm('Are you sure you want to delete this venue?')) {
                                    this.closest('form').submit();
                                }" {{ $venue->active_inactive == 1 ? 'disabled' : '' }}>

                                                            <span class="material-symbols-rounded fs-6">delete</span>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </li>

                                                </ </tr>
                                                @endforeach
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <div class="text-muted small mb-2">
                                Showing {{ $venues->firstItem() }}
                                to {{ $venues->lastItem() }}
                                of {{ $venues->total() }} items
                            </div>

                            <div>
                                {{ $venues->links('vendor.pagination.custom') }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('searchToggle');
    const input = document.getElementById('searchInput');

    toggle.addEventListener('click', () => {
        input.classList.toggle('active');
        if (input.classList.contains('active')) {
            input.focus();
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-expand')) {
            input.classList.remove('active');
        }
    });
});
</script>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection