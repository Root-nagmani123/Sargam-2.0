@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Room Mapping')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Hostel Building Floor Room Mapping" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Hostel Building Floor Room Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('hostel.building.floor.room.map.create')}}" class="btn btn-primary">+ Add Hostel Building Floor Room</a>
                                <a href="{{ route('hostel.building.floor.room.map.export') }}" class="btn btn-secondary">Export</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{ $dataTable->table(['class' => 'table table-striped table-bordered ']) }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection
@push('scripts')
{{ $dataTable->scripts() }}
@endpush