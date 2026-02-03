@extends('admin.layouts.master')

@section('title', 'Venue Master - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid venue-master-index">
    <x-breadcrum title="Venue Master" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-6">
                            <h4>Venue Master</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end align-items-md-end mb-3">
                                <div class="d-flex align-items-center gap-2 venue-master-actions">

                                    <!-- Add New Button -->
                                    <a href="{{ route('Venue-Master.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New Venue
                                    </a>

                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table w-100 text-nowrap venue-master-table">
                            <thead>
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
                                    <td data-label="S.No.">{{ $venues->firstItem() + $key }}</td>
                                    <td data-label="Venue Name">
                                        {{ $venue->venue_name  }}
                                    </td>
                                    <td data-label="Short Name">
                                        {{ $venue->venue_short_name   }}
                                    </td>
                                    <td data-label="Description">
                                        {{ $venue->description   }}
                                    </td>
                                    <td data-label="Status">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="venue_master" data-column="active_inactive"
                                                data-id="{{ $venue->venue_id }}" data-id_column="venue_id"
                                                {{ $venue->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center" data-label="Action">
                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Venue actions">

                                            <!-- Edit -->
                                            <a href="{{ route('Venue-Master.edit', $venue->venue_id) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                aria-label="Edit venue">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">edit</span>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            <!-- Delete -->
                                            @if($venue->active_inactive == 1)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true" title="Cannot delete active venue">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">delete</span>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                            @else
                                            <form action="{{ route('Venue-Master.destroy', $venue->venue_id) }}"
                                                method="POST" class="d-inline delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this venue?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                    aria-label="Delete venue">
                                                    <span class="material-symbols-rounded fs-6"
                                                        aria-hidden="true">delete</span>
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
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection