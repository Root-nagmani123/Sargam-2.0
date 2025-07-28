@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')

    <div class="container-fluid">

        <x-breadcrum title="Roles" />

        <!-- start Vertical Steps Example -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-0">Add Role</h4>
                <h6 class="card-subtitle mb-3"></h6>
                <hr>

                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="title">Name :</label>
                                <input type="text" class="form-control" id="title" placeholder="" name="name"
                                    value="{{ old('name') }}">
                            </div>
                        </div>
                        @error('name')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror
                        <h3>Permissions:</h3>
                        @foreach($grouped as $group => $permissions)
                            <div class="mb-4">
                                <h5 style="text-transform: capitalize;">{{ $group }} Module</h5>
                                <div class="row">
                                    @foreach($permissions as $permission)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permission[]"
                                                    value="{{ $permission->id }}" id="perm-{{ $permission->id }}">
                                                <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @error('permission')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-actions">
                        <div class="card-body border-top">
                            <button type="submit" class="btn btn-primary">
                                Submit
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn bg-danger-subtle text-danger ms-6">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>


@endsection