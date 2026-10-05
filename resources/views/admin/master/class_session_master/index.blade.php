@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Class Session Master" :showBack="false">
        <a href="{{ route('master.class.session.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Class Session</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Server-paginated (ClassSessionMasterController::index, 10 per
                 page): no DataTable on this grid, so the footer below is
                 hand-written (docs/new-design-index-page.md §4 variant B). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="classSessionTable">
                        <caption class="visually-hidden">Class sessions</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Shift Name</th>
                                <th scope="col" class="text-nowrap">Start Time</th>
                                <th scope="col" class="text-nowrap">End Time</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classSessionMaster as $index => $classSession)
                                @php
                                    $isActive = (int) $classSession->active_inactive === 1;
                                    $encId = encrypt($classSession->pk);
                                @endphp
                                <tr>
                                    <td>{{ $classSessionMaster->firstItem() + $index }}</td>
                                    <td>{{ $classSession->shift_name ?? 'N/A' }}</td>
                                    <td class="text-nowrap">{{ $classSession->start_time ?? 'N/A' }}</td>
                                    <td class="text-nowrap">{{ $classSession->end_time ?? 'N/A' }}</td>
                                    <td data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $classSession->shift_name,
                                            'edit'   => ['href' => route('master.class.session.edit', ['id' => $encId])],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'class_session_master',
                                                'column' => 'active_inactive',
                                                'id'     => $classSession->pk,
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active class session. Deactivate it first.']
                                                : ['action' => route('master.class.session.delete', ['id' => $encId])],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty">
                                    <td colspan="6">No class sessions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $classSessionMaster->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info" aria-live="polite">
                            @if ($classSessionMaster->total())
                                Showing {{ $classSessionMaster->firstItem() }}–{{ $classSessionMaster->lastItem() }} of {{ number_format($classSessionMaster->total()) }} items
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
