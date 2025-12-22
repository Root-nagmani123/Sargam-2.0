@extends('admin.layouts.master')

@section('title', 'User Management - Sargam | Lal Bahadur')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Users"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Users</h4>
                    </div>
                    <div class="col-6">
                        <!-- <div class="float-end gap-2">
                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Add Users</a>
                            </div> -->
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
@section('scripts')
{{ $dataTable->scripts() }}
@endsection