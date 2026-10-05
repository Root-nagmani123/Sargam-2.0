@extends('admin.layouts.master')

@section('title', 'Venue Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Venue Master" :showBack="false">
        <a href="{{ route('Venue-Master.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add New Venue</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Server-paginated (VenueMasterController::index, fixed 10 per
                 page, no per_page parameter): no DataTable on this grid, so
                 the footer below is hand-written (docs/new-design-index-page.md
                 §4 variant B). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="venueMasterTable">
                        <caption class="visually-hidden">Venues</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Venue Name</th>
                                <th scope="col" class="text-nowrap">Short Name</th>
                                <th scope="col">Description</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($venues as $index => $venue)
                                @php $isActive = (int) $venue->active_inactive === 1; @endphp
                                <tr>
                                    <td>{{ $venues->firstItem() + $index }}</td>
                                    <td>{{ $venue->venue_name }}</td>
                                    <td>{{ $venue->venue_short_name }}</td>
                                    <td class="mst-col-wrap">{{ $venue->description }}</td>
                                    <td data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $venue->venue_name,
                                            'edit'   => ['href' => route('Venue-Master.edit', $venue->venue_id)],
                                            'toggle' => [
                                                'active'    => $isActive,
                                                'table'     => 'venue_master',
                                                'column'    => 'active_inactive',
                                                'id'        => $venue->venue_id,
                                                'id_column' => 'venue_id',
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active venue. Deactivate it first.']
                                                : ['action' => route('Venue-Master.destroy', $venue->venue_id)],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty">
                                    <td colspan="6">No venues found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $venues->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info" aria-live="polite">
                            @if ($venues->total())
                                Showing {{ $venues->firstItem() }}–{{ $venues->lastItem() }} of {{ number_format($venues->total()) }} items
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
    window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";

    // Server-rendered grid: the badge and the switch live in different
    // columns, so refresh the page once custom.js has saved the new status.
    MstAdmin.reloadPageOnStatusToggle();
</script>
@endpush
