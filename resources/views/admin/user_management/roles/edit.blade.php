@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Roles" />

    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-0">Edit Role</h4>
            <h6 class="card-subtitle mb-3"></h6>
            <hr>
                
              <form action="{{ route('admin.roles.update', encrypt($role->pk)) }}" method="POST">
    @csrf
    @method('PUT')
                
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="title">Name :</label>
                                <input type="text" class="form-control" id="title" placeholder="" name="name" value="{{ $role->user_role_name }}">
                            </div>
                        </div>
                        @error('name')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror
                         <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="title">Display Name :</label>
                                <input type="text" class="form-control" id="title" placeholder="" name="display_name" value="{{ $role->user_role_display_name }}">
                            </div>
                        </div>
                        @error('display_name')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror
                        
                    </div>
<hr>
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">send</i>
                            Submit
                    </button>
                </form>
                
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection