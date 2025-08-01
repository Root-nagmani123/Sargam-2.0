@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Mapping')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Hostel Building Floor Mapping" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Hostel Building Floor Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('hostel.building.map.create')}}" class="btn btn-primary">+ Add Hostel Building Floor</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{ $dataTable->table(['class' => 'table table-striped table-bordered']) }}
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