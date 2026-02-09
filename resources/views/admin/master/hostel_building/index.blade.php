@extends('admin.layouts.master')

@section('title', 'Hostel Building')

@section('setup_content')
<div class="container-fluid building-master-index">

    <x-breadcrum title="Building Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <h4>Building Master</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
                                <a href="{{route('master.hostel.building.create')}}" class="btn btn-primary">+ Add Building Master</a>
                                <a href="{{ route('master.hostel.building.export') }}" class="btn btn-secondary">Export</a>
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