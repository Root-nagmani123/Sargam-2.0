@extends('admin.layouts.master')

@section('title', 'Registration Page - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid">
         <x-breadcrum title="Registration Page" />
        <!-- start Vertical Steps Example -->
        <div class="card" style="border-left: 4px solid #004a93;">
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
                            {{ $data ? 'Update' : 'Save Changes' }}
                        </button>
                    </div>
                </form>


            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>
@endsection
