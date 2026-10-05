@extends('admin.layouts.master')

@section('title', 'District List')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="District List" :showBack="false">
        <a href="{{ route('master.district.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add District</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Server-paginated (LocationController::districtIndex, 10 per page):
                 no DataTable on this grid, so the footer below is hand-written
                 (docs/new-design-index-page.md §4 variant B). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="districtTable">
                        <caption class="visually-hidden">Districts</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">District</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($districts as $index => $district)
                                @php $isActive = (int) $district->active_inactive === 1; @endphp
                                <tr>
                                    <td>{{ $districts->firstItem() + $index }}</td>
                                    <td>{{ $district->district_name }}</td>
                                    <td data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $district->district_name,
                                            'edit'   => ['href' => route('master.district.edit', $district->pk)],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'state_district_mapping',
                                                'column' => 'active_inactive',
                                                'id'     => $district->pk,
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active district. Deactivate it first.']
                                                : ['action' => route('master.district.delete', $district->pk)],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty">
                                    <td colspan="4">No districts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $districts->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info" aria-live="polite">
                            @if ($districts->total())
                                Showing {{ $districts->firstItem() }}–{{ $districts->lastItem() }} of {{ number_format($districts->total()) }} items
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
