@extends('admin.layouts.master')

@section('title', 'Stream - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Stream" />
    <x-session_message />

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
                            <div class="float-end gap-2">
                                <a href="{{route('stream.create')}}" class="btn btn-primary">+ Add Stream</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">

                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Stream Name</th>
                                    <th>Action</th>
                                    <th>Status</th>

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
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('stream.edit', $stream->pk) }}"
                                                class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('stream.destroy', $stream->pk) }}" method="POST"
                                                class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="stream_master" data-column="status"
                                                data-id="{{ $stream->pk }}" {{ $stream->status == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>


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