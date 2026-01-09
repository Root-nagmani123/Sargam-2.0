@extends('admin.layouts.master')

@section('title', 'Country List')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Country List" />
    <div class="card" style="border-left: 4px solid #004a93;">
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

                            <!-- Export Button -->
                            <a href="" class="px-3 py-2">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">search</i>
                            </a>

                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table w-100 text-nowrap">
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

                                <div class="d-inline-flex align-items-center gap-2" role="group"
                                    aria-label="Country actions">

                                    <!-- Edit -->
                                    <a href="{{ route('master.country.edit', $country->pk) }}"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                        aria-label="Edit country">
                                        <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                                        <span class="d-none d-md-inline">Edit</span>
                                    </a>

                                    <!-- Delete -->
                                    @if($country->active_inactive == 1)
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                        disabled aria-disabled="true" title="Cannot delete active country">
                                        <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                                        <span class="d-none d-md-inline">Delete</span>
                                    </button>
                                    @else
                                    <form action="{{ route('master.country.delete', $country->pk) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                            aria-label="Delete country">
                                            <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                                            <span class="d-none d-md-inline">Delete</span>
                                        </button>
                                    </form>
                                    @endif

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