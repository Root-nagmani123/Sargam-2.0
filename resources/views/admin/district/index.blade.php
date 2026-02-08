@extends('admin.layouts.master')

@section('title', 'District - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid district-index">
<x-breadcrum title="District" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row district-header-row">
                        <div class="col-12 col-md-6">
                            <h1 class="h4 mb-0">District</h1>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end justify-content-start align-items-center gap-2 mt-2 mt-md-0">
                                <a href="{{ route('master.district.create') }}" class="btn btn-primary d-flex align-items-center w-100 w-md-auto justify-content-center justify-content-md-start">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add New District
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="district-table">
                        <caption class="visually-hidden">District list with status and actions</caption>
                        <thead>
                            <!-- start row -->
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">District</th>
                                <th class="col">Status</th>
                                <th class="col">Action</th>
                            </tr>
                            <!-- end row -->
                        </thead>
                        <tbody>
                            @foreach($districts as $key => $district)
                            <tr>
                                <td>{{ $districts->firstItem() + $key }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-6">
                                        <h6 class="mb-0">{{ $district->district_name }}</h6>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-flex align-items-center gap-2 m-0">
                                        @php($toggleId = 'district_status_' . $district->pk)
                                        <input
                                            id="{{ $toggleId }}"
                                            class="form-check-input status-toggle"
                                            type="checkbox"
                                            role="switch"
                                            data-table="state_district_mapping"
                                            data-column="active_inactive"
                                            data-id="{{ $district->pk }}"
                                            {{ $district->active_inactive == 1 ? 'checked' : '' }}
                                            aria-label="Toggle status for {{ $district->district_name }}">
                                        <label class="form-check-label visually-hidden" for="{{ $toggleId }}">
                                            Status for {{ $district->district_name }}
                                        </label>
                                    </div>
                                </td>

                                <td>
                                    <div class="dropdown">
                                        <a href="javascript:void(0)"
                                            id="actionMenu{{ $district->pk }}" data-bs-toggle="dropdown"
                                            aria-expanded="false" aria-label="Actions for {{ $district->district_name }}">
                                            <span class="material-symbols-rounded fs-5">more_horiz</span>
                                        </a>

                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                            aria-labelledby="actionMenu{{ $district->pk }}">

                                            <!-- Edit -->
                                            <li>
                                                <a href="{{ route('master.district.edit', $district->pk) }}"
                                                    class="dropdown-item d-flex align-items-center gap-2">
                                                    <span class="material-symbols-rounded text-primary fs-6">edit</span>
                                                    Edit
                                                </a>
                                            </li>

                                            <!-- Delete -->
                                            <li>
                                                <form action="{{ route('master.district.delete', $district->pk) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                        class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                        onclick="event.preventDefault();
                            if({{ $district->active_inactive }} == 1) return;
                            if(confirm('Are you sure you want to delete this?')) {
                                this.closest('form').submit();
                            }" {{ $district->active_inactive == 1 ? 'disabled' : '' }}>
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
                            Showing {{ $districts->firstItem() }}
                            to {{ $districts->lastItem() }}
                            of {{ $districts->total() }} items
                        </div>

                        <div>
                            {{ $districts->links('vendor.pagination.custom') }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection