@extends('admin.layouts.master')

@section('title', 'Define Block/Building - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
  <x-breadcrum title="Define Block/Building" />

    <x-session_message />

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Block/Building</h1>
                    <p class="text-muted small mb-0">This page displays all the Estate Block/Building added in the system and provides options such as add, edit, delete etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-block-building.create') }}" class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 block-building-add-btn"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 w-100" id="blockBuildingTable">
                    <thead>
                        <tr>
                            <th class="col">S.No.</th>
                            <th class="col">Building/Block</th>
                            <th class="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td>{{ $items->firstItem() + $index }}</td>
                            <td>{{ $row->block_name }}</td>
                            <td>
                                <a href="{{ route('admin.estate.define-block-building.edit', $row->pk) }}" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No block/building found. <a href="{{ route('admin.estate.define-block-building.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Single pagination: server-side (Laravel). DataTable not used to avoid duplicate pagination. --}}
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                <div class="text-muted small">
                    Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries
                </div>
                @if($items->hasPages())
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->url(1) }}">First</a></li>
                    <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->previousPageUrl() }}">Previous</a></li>
                    @foreach($items->getUrlRange(max(1, $items->currentPage() - 2), min($items->lastPage(), $items->currentPage() + 2)) as $page => $url)
                    <li class="page-item {{ $page == $items->currentPage() ? 'active' : '' }}"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endforeach
                    <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->nextPageUrl() }}">Next</a></li>
                    <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->url($items->lastPage()) }}">Last</a></li>
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
