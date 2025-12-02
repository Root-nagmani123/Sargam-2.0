@extends('admin.layouts.master')

@section('title', 'Country List')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Country List</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <div class="d-flex align-items-center gap-2">

                            <!-- Add New Button -->
                            <a href="{{ route('master.country.create') }}"
                                class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Country
                            </a>

                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col">#</th>
                            <th class="col">Country Name</th>
                            <th class="col">Status</th>
                            <th class="col">Actions</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countries as $index => $country)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $country->country_name }}</td>

                            <td>
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="country_master" data-column="active_inactive"
                                        data-id="{{ $country->pk }}"
                                        {{ $country->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">

                                <div class="dropdown">
                                    <a href="javascript:void(0)"
                                        id="actionMenu{{ $country->pk }}" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <span class="material-symbols-rounded fs-5">more_horiz</span>
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                        aria-labelledby="actionMenu{{ $country->pk }}">

                                        <!-- Edit -->
                                        <li>
                                            <a href="{{ route('master.country.edit', $country->pk) }}"
                                                class="dropdown-item d-flex align-items-center gap-2">
                                                <span class="material-symbols-rounded text-primary fs-6">edit</span>
                                                Edit
                                            </a>
                                        </li>

                                        <!-- Delete -->
                                        <li>
                                            <form action="{{ route('master.country.delete', $country->pk) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')

                                                <button type="button"
                                                    class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                    onclick="event.preventDefault();
                                if({{ $country->active_inactive }} == 1) return;
                                if(confirm('Are you sure you want to delete this?')) {
                                    this.closest('form').submit();
                                }" {{ $country->active_inactive == 1 ? 'disabled' : '' }}>
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

                <!-- Pagination (if applicable) -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                    <div class="text-muted small mb-2">
                        Showing {{ $countries->firstItem() }}
                        to {{ $countries->lastItem() }}
                        of {{ $countries->total() }} items
                    </div>

                    <div>
                        {{ $countries->links('vendor.pagination.custom') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection