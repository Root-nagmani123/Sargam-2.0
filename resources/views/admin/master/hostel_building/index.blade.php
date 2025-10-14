@extends('admin.layouts.master')

@section('title', 'Hostel Building')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Building Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Building Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.hostel.building.create')}}" class="btn btn-primary">+ Add Building Master</a>
                                <a href="{{ route('master.hostel.building.export') }}" class="btn btn-secondary">Export</a>
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