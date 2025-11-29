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
                            <label class="form-label" for="title">USER ROLE NAME :</label>
                            <input type="text" class="form-control" id="title" placeholder="" name="name"
                                value="{{ old('name') }}">
                        </div>
                    </div>
                    @error('name')
                    <p class="text-danger">{{ $message }}</p>
                    @enderror
                     <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label" for="title">USER_ROLE_DISPLAY_NAME :</label>
                            <input type="text" class="form-control" id="title" placeholder="" name="display_name"
                                value="{{ old('display_name') }}">
                        </div>
                    </div>
                    @error('display_name')
                    <p class="text-danger">{{ $message }}</p>
                    @enderror

                    {{-- <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label" for="caste">Permission :</label> <br />
                            @foreach($all_permissions as $value)
                            <div class="form-check">
                                <input name="permission[]" class="form-check-input" type="checkbox"
                                    value="{{$value->id}}" id="flexCheckDefault_{{$value->id}}">
                                <label class="form-check-label" for="flexCheckDefault_{{$value->id}}">
                                    {{ $value->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div> --}}
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