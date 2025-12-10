@extends('admin.layouts.master')

@section('title', 'Hostel Floor')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Hostel Floor" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Hostel Floor</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.hostel.floor.create')}}" class="btn btn-primary">+ Add Hostel Floor</a>
                                <a href="{{ route('master.hostel.floor.export') }}" class="btn btn-secondary">Export</a>
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