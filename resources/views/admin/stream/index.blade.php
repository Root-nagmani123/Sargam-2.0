@extends('admin.layouts.master')

@section('title', 'Stream - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Stream" />
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


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">

                        <table class="table text-nowrap w-100">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Stream Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Action</th>

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
                                    <td>

                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Stream actions">

                                            <!-- Edit -->
                                            <a href="{{ route('stream.edit', $stream->pk) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                aria-label="Edit stream">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">edit</span>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            <!-- Delete -->
                                            @if($stream->status == 1)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true" title="Cannot delete active stream">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">delete</span>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                            @else
                                            <form action="{{ route('stream.destroy', $stream->pk) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this stream?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                    aria-label="Delete stream">
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