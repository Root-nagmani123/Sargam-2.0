@extends('admin.layouts.master')

@section('title', 'Department Master')

@section('setup_content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Department Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.department.master.create')}}" class="btn btn-primary">+ Add Department</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-striped table-bordered']) }}
                    </div>
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