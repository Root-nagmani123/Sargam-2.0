@extends('admin.layouts.master')

@section('title', 'Registration Page - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Registration Page</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="index.html">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Registration Page
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
                <h4 class="card-title mb-3">Create Registration Page</h4>
                <hr>


                {{-- Display Success and Error Messages --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('registration-page.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        @for ($i = 1; $i <= 4; $i++)
                            <div class="col-6 mb-4">
                                <label class="form-label">Logo{{ $i }}:</label>
                                <input type="file" class="form-control" id="logo{{ $i }}"
                                    name="logo{{ $i }}">

                                {{-- Image Preview --}}
                                @if (!empty($data->{'logo' . $i}))
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $data->{'logo' . $i}) }}"
                                            alt="Logo{{ $i }}" width="100" height="100"
                                            class="border rounded shadow-sm mb-2">
                                    </div>

                                    {{-- Remove Checkbox --}}
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="remove_logo{{ $i }}" id="remove_logo{{ $i }}">
                                        <label class="form-check-label" for="remove_logo{{ $i }}">
                                            Remove current Logo{{ $i }}
                                        </label>
                                    </div>
                                @endif
                            </div>
                        @endfor

                        <div class="col-6">
                            <label class="form-label">Main Heading:</label>
                            <input type="text" class="form-control" name="heading"
                                value="{{ old('heading', $data->heading ?? '') }}">
                            <small class="form-text text-muted fs-5">
                                Format: <code>&lt;b&gt;99 &lt;sup&gt;th&lt;/sup&gt; Foundation Course&lt;/b&gt;</code>
                            </small>
                        </div>


                        <div class="col-6">
                            <label class="form-label">Sub Heading:</label>
                            <input type="text" class="form-control" name="sub_heading"
                                value="{{ old('sub_heading', $data->sub_heading ?? '') }}">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary float-end" type="submit">
                            <i class="material-icons menu-icon">send</i>
                            {{ $data ? 'Update' : 'Save Changes' }}
                        </button>
                    </div>
                </form>


            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>
@endsection
