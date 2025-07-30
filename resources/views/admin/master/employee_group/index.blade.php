@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Employee Group Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                
                <div class="row">
                    <div class="col-6">
                        <h4>Employee Group Master</h4>
                    </div>
                    @can('master.employee.group.create')
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.employee.group.create')}}" class="btn btn-primary">+ Add Employee Group</a>
                            </div>
                        </div>
                    @endcan
                </div>
                <hr>
                <div class="table-responsive">
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