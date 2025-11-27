@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>City</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

                                    <!-- Add New Button -->
                                    <a href="{{ route('master.city.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New City
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
                    <table class="table">

                        <thead>
                            <!-- start row -->
                            <tr>
                                <th>S.No</th>
                                <th>City Name</th>
                                <th>District</th>
                                <th>State</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            <!-- end row -->
                        </thead>
                        <tbody>
                            @foreach($cities as $key => $city)
                            <tr>
                                <td>{{ $cities->firstItem() + $key }}</td>
                                <td>{{ $city->city_name }}</td>
                                <td>{{ optional($city->state)->state_name ?? 'N/A' }}</td>
                                <td>{{ $city->district?->district_name ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="city_master" data-column="active_inactive"
                                            data-id="{{ $city->pk }}"
                                            {{ $city->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-start">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" id="actionMenu{{ $city->pk }}"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="material-symbols-rounded fs-5">more_horiz</span>
                                        </a>

                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                            aria-labelledby="actionMenu{{ $city->pk }}">

                                            <!-- Edit -->
                                            <li>
                                                <a href="{{ route('master.city.edit', $city->pk) }}"
                                                    class="dropdown-item d-flex align-items-center gap-2">
                                                    <span class="material-symbols-rounded text-primary fs-6">edit</span>
                                                    Edit
                                                </a>
                                            </li>

                                            <!-- Delete -->
                                            <li>
                                                <form action="{{ route('master.city.delete', $city->pk) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                        class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                        onclick="event.preventDefault();
                            if({{ $city->active_inactive }} == 1) return;
                            if(confirm('Are you sure you want to delete this?')) {
                                this.closest('form').submit();
                            }" {{ $city->active_inactive == 1 ? 'disabled' : '' }}>
                                                        <span class="material-symbols-rounded fs-6">delete</span>
                                                        Delete
                                                    </button>
                                                </form>
                                            </li>

                                        </ul>
                                    </div>
                                </td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                        <div class="text-muted small mb-2">
                            Showing {{ $cities->firstItem() }}
                            to {{ $cities->lastItem() }}
                            of {{ $cities->total() }} items
                        </div>

                        <div>
                            {{ $cities->links('vendor.pagination.custom') }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection