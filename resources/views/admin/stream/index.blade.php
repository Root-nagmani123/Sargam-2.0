@extends('admin.layouts.master')

@section('title', 'Stream - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Stream</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{route('stream.create')}}" class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Stream
                                </a>

                                <!-- Search Expand -->
                                <div class="search-expand d-flex align-items-center">
                                    <a href="javascript:void(0)" id="searchToggle">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">search</i>
                                    </a>

                                    <input type="text" class="form-control search-input ms-2" id="searchInput"
                                        placeholder="Search…" aria-label="Search">
                                </div>

                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">

                        <table class="table">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Stream Name</th>
                                    <th>Status</th>
                                    <th>Action</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($streams as $key => $stream)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        {{ $stream->stream_name }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="stream_master" data-column="status"
                                                data-id="{{ $stream->pk }}" {{ $stream->status == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">

                                        <div class="dropdown">
                                            <a href="javascript:void(0)" id="actionMenu{{ $stream->pk }}"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="material-symbols-rounded fs-5">more_horiz</span>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                                aria-labelledby="actionMenu{{ $stream->pk }}">

                                                <!-- Edit -->
                                                <li>
                                                    <a href="{{ route('stream.edit', $stream->pk) }}"
                                                        class="dropdown-item d-flex align-items-center gap-2">
                                                        <span
                                                            class="material-symbols-rounded text-primary fs-6">edit</span>
                                                        Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form action="{{ route('stream.destroy', $stream->pk) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="button"
                                                            class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                            onclick="event.preventDefault();
                                if({{ $stream->status }} == 1) return; 
                                if(confirm('Are you sure you want to delete this stream?')) {
                                    this.closest('form').submit();
                                }" {{ $stream->status == 1 ? 'disabled' : '' }}>
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
                                Showing {{ $streams->firstItem() }}
                                to {{ $streams->lastItem() }}
                                of {{ $streams->total() }} items
                            </div>

                            <div>
                                {{ $streams->links('vendor.pagination.custom') }}
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