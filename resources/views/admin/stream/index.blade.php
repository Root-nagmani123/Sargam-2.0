@extends('admin.layouts.master')

@section('title', 'Stream')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Stream" :showBack="false">
        <a href="{{ route('stream.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Stream</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Server-paginated (StreamController::index, 10 per page): no
                 DataTable on this grid, so the footer below is hand-written
                 (docs/new-design-index-page.md §4 variant B). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="streamTable">
                        <caption class="visually-hidden">Streams</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Stream Name</th>
                                <th scope="col" class="text-nowrap">Status</th>
                                <th scope="col" class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($streams as $index => $stream)
                                @php $isActive = (int) $stream->active_inactive === 1; @endphp
                                <tr>
                                    <td>{{ $streams->firstItem() + $index }}</td>
                                    <td>{{ $stream->stream_name }}</td>
                                    <td data-order="{{ $isActive ? 1 : 0 }}">
                                        @include('admin.master.partials.grid-status', ['active' => $isActive])
                                    </td>
                                    <td>
                                        @include('admin.master.partials.grid-actions', [
                                            'name'   => $stream->stream_name,
                                            'edit'   => ['href' => route('stream.edit', $stream->pk)],
                                            'toggle' => [
                                                'active' => $isActive,
                                                'table'  => 'stream_master',
                                                'column' => 'active_inactive',
                                                'id'     => $stream->pk,
                                            ],
                                            'delete' => $isActive
                                                ? ['disabled' => true, 'reason' => 'Cannot delete an active stream. Deactivate it first.']
                                                : ['action' => route('stream.destroy', $stream->pk)],
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr class="mst-empty">
                                    <td colspan="4">No streams found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $streams->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info" aria-live="polite">
                            @if ($streams->total())
                                Showing {{ $streams->firstItem() }}–{{ $streams->lastItem() }} of {{ number_format($streams->total()) }} items
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
