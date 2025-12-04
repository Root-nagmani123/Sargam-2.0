@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@section('setup_content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                
                <div class="row">
                    <div class="col-6">
                        <h4>Employee Group Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="float-end gap-2">
                            <a href="{{route('master.employee.group.create')}}" class="btn btn-primary">+ Add Employee Group</a>
                        </div>
                    </div>
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