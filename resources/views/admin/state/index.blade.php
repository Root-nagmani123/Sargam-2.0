@extends('admin.layouts.master')

@section('title', 'State List')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="State List" :showBack="false">
        <a href="{{ route('master.state.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add State</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Server-paginated (LocationController::stateIndex, 10 per page):
                 no DataTable on this grid, so the footer below is hand-written
                 (docs/new-design-index-page.md §4 variant B). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="stateTable">
                        <caption class="visually-hidden">States</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">State Name</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($states as $index => $state)
                                @php $isActive = (int) $state->active_inactive === 1; @endphp
                                <tr>
                                    <td>{{ $states->firstItem() + $index }}</td>
                                    <td>{{ $state->state_name }}</td>
                                    <td data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $state->state_name,
                                            'edit'   => ['href' => route('master.state.edit', $state->pk)],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'state_master',
                                                'column' => 'active_inactive',
                                                'id'     => $state->pk,
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active state. Deactivate it first.']
                                                : ['action' => route('master.state.delete', $state->pk)],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty">
                                    <td colspan="4">No states found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $states->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info" aria-live="polite">
                            @if ($states->total())
                                Showing {{ $states->firstItem() }}–{{ $states->lastItem() }} of {{ number_format($states->total()) }} items
                            @else
                                0 items
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    // Server-rendered grid: the badge and the switch live in different
    // columns, so refresh the page once custom.js has saved the new status.
    MstAdmin.reloadPageOnStatusToggle();
</script>
@endpush
