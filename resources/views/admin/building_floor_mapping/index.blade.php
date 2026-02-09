@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Mapping')

@section('setup_content')
<div class="container-fluid building-floor-mapping-index">

    <x-breadcrum title="Hostel Building Floor Mapping" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <h4>Hostel Building Floor Mapping</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
                                <a href="{{route('hostel.building.map.create')}}" class="btn btn-primary w-100 w-md-auto">+ Add Hostel Building Floor</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{ $dataTable->table(['class' => 'table']) }}
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