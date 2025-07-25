@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Roles" />
    <x-session_message />

    {{-- <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Roles</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">+ Add Roles</a>
                            </div>
                        </div>
                    </div>

                    <hr>
                    {{ $dataTable->table(['class' => 'table table-striped table-bordered']) }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div> --}}
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Roles</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">+ Add Roles</a>
                            </div>
                        </div>
                    </div>

                    @foreach ($roles as $role)
                        <div class="role-item">
                            <h5>{{ $role->name }}</h5>
                            <p>Permissions:</p>
                            <ul>
                                @foreach ($role->permissions as $permission)
                                    <li>{{ $permission->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

