@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Permissions" />

    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-0">Add Permissions</h4>
            <h6 class="card-subtitle mb-3"></h6>
            <hr>
            <div id="example-vertical" class="mt-5">
                
                <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="title">PermissionName :</label>
                                <input type="text" class="form-control" id="title" placeholder="Enter Permissions Name" name="name" value="{{ $permission->name }}">
                            </div>
                        </div>  
                        @error('name')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror                      
                    </div>
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">send</i>
                            Submit
                    </button>
                </form>
                
            </div>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection