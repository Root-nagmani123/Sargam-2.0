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
                                <input type="text" class="form-control" id="title" placeholder="" name="name" value="{{ old('name') }}">
                            </div>
                        </div>
                        @error('name')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror
                        
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="caste">Permission :</label> <br/>
                                @foreach($all_permissions as $value)
                                    <div class="form-check">
                                        <input name="permission[]" class="form-check-input" type="checkbox" value="{{$value->id}}" id="flexCheckDefault_{{$value->id}}">
                                        <label class="form-check-label" for="flexCheckDefault_{{$value->id}}">
                                            {{ $value->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('permission')
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
    <!-- end Vertical Steps Example -->
</div>


@endsection