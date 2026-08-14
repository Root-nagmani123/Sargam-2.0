@extends('admin.layouts.master')

@section('title', 'State - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>State</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

                                    <!-- Add New Button -->
                                    <a href="{{route('master.state.create')}}"
                                        class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New State
                                    </a>

                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{-- Toolbar (new-design-index-page.md §2): search right-aligned; this
                         grid has no filters, so the row carries the search alone. --}}
                    <x-programme-dt-toolbar :action="url()->current()"
                        placeholder="Search state" label="Search by state name" />

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" data-sargam-dt-ui="false">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">State Name</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($states as $key => $state)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $states->firstItem() + $key }}</td>
                                    <td>
                                        {{ $state->state_name }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="state_master" data-column="active_inactive"
                                                data-id="{{ $state->pk }}"
                                                {{ $state->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                       <div class="d-inline-flex align-items-center gap-2"
     role="group"
     aria-label="State actions">

    <!-- Edit -->
    <a href="{{ route('master.state.edit', $state->pk) }}"
       class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
       aria-label="Edit state">
        <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
        <span class="d-none d-md-inline">Edit</span>
    </a>

    <!-- Delete -->
    @if($state->active_inactive == 1)
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                disabled
                aria-disabled="true"
                title="Cannot delete active state">
            <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
            <span class="d-none d-md-inline">Delete</span>
        </button>
    @else
        <form action="{{ route('master.state.delete', $state->pk) }}"
              method="POST"
              class="d-inline"
              onsubmit="return confirm('Are you sure you want to delete this?');">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                    aria-label="Delete state">
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
                        <!-- Pagination -->
                        <x-programme-dt-footer :paginator="$states" per-page-id="statePerPage" />

                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>
</div>

@endsection