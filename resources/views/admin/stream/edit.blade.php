@extends('admin.layouts.master')

@section('title', 'Edit Stream - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit Stream</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                Stream
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- start Vertical Steps Example -->
    <div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Edit Stream</h4>
        <hr>
        <form action="{{ route('stream.update', $stream->pk) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-sm-10">
                    <label for="stream_name" class="form-label">Stream :</label>
                    <div class="mb-3">
                        <input type="text" 
                               class="form-control" 
                               id="stream_name" 
                               name="stream_name" 
                               placeholder="Enter Stream Name"
                               value="{{ old('stream_name', $stream->stream_name) }}">
                        @error('stream_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <hr>
            <div class="mb-3">
                <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i> Update
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- end Vertical Steps Example -->
</div>


@endsection