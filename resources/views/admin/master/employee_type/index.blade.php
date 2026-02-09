@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@section('setup_content')
<div class="container-fluid employee-type-index">
    <x-breadcrum title="Employee Type Master" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                
                <div class="row employee-type-header-row">
                    <div class="col-6">
                        <h4>Employee Type Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="float-end d-flex gap-2">
                            <a href="{{route('master.employee.type.create')}}" class="btn btn-primary add-btn">+ Add Employee Type</a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
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