@extends('admin.layouts.master')

@section('title', 'Venue-Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Venue Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Venue Master</h4>
                        </div>
                        @can('venue-master.create')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{ route('Venue-Master.create') }}" class="btn btn-primary">+Add New Venue</a>
                                </div>
                            </div>
                        @endcan
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config" class="table table-striped table-bordered table-responsive">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Venue Name</th>
                                    <th class="col">Short Name</th>
                                    <th class="col">Description</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($venues as $key =>$venue)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                       {{ $venue->venue_name  }}
                                    </td>
                                    <td>
                                      {{ $venue->venue_short_name   }}
                                    </td>
                                    <td>
                                       {{ $venue->description   }}
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            @can('venue-master.edit')
                                                <a href="{{ route('Venue-Master.edit', $venue->venue_id) }}"
                                                    class="btn btn-primary text-white btn-sm">
                                                    Edit
                                                </a>
                                            @endcan
                                            @can('venue-master.delete')
                                            <form action="{{ route('Venue-Master.destroy', $venue->venue_id) }}"
                                                    method="POST" class="m-0 delete-form"
                                                    data-status="{{ $venue->active_inactive }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger text-white btn-sm" onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this venue?')) {
                                                    this.closest('form').submit();
                                                }"
                                                {{ $venue->active_inactive == 1 ? 'disabled' : '' }}>
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan

                                        </div>
                                    </td>
                                    <td>
                                        @can('venue-master.active_inactive')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="venue_master" data-column="active_inactive"
                                                    data-id="{{ $venue->venue_id }}" data-id_column="venue_id"
                                                    {{ $venue->active_inactive == 1 ? 'checked' : '' }}>
                                            </div>
                                        @endcan
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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