@extends('admin.layouts.master')

@section('title', 'Login Carousel Images')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Login Carousel Images" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger rounded-3" role="alert">
            <strong>Upload could not be saved.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 px-4 py-3">
                    <h6 class="mb-0 fw-semibold">Upload images</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('admin.login-carousel-images.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label for="loginCarouselImages" class="form-label fw-semibold">Background image(s)</label>
                        <input id="loginCarouselImages" type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
                        <p class="small text-body-secondary mt-2 mb-3">
                            Upload up to 10 JPG, PNG, or WEBP images at once. Each file can be up to 5 MB.
                        </p>
                        <button type="submit" class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-1">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;">upload</i>
                            Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 px-4 py-3">
                    <h6 class="mb-1 fw-semibold">Login background rotation</h6>
                    <p class="small text-body-secondary mb-0">Active images are shown first by order number, then upload order.</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width:180px;">Preview</th>
                                    <th>Image</th>
                                    <th style="width:250px;">Controls</th>
                                    <th class="pe-4" style="width:80px;">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($images as $image)
                                    <tr>
                                        <td class="ps-4">
                                            <img src="{{ asset('storage/' . $image->image_path) }}"
                                                alt="Login carousel preview"
                                                class="img-fluid rounded-3 border"
                                                style="width:160px;height:90px;object-fit:cover;">
                                        </td>
                                        <td>
                                            <div class="small text-body-secondary text-break">{{ $image->image_path }}</div>
                                            <span class="badge rounded-pill {{ $image->active_inactive ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                {{ $image->active_inactive ? 'Active' : 'Hidden' }}
                                            </span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.login-carousel-images.update', $image) }}" method="POST" enctype="multipart/form-data" class="d-grid gap-2">
                                                @csrf
                                                @method('PUT')
                                                <div class="d-flex align-items-center gap-2">
                                                    <label for="carouselOrder{{ $image->id }}" class="small fw-semibold mb-0">Order</label>
                                                    <input id="carouselOrder{{ $image->id }}" type="number" name="sort_order" min="0" max="65535" value="{{ $image->sort_order }}" class="form-control form-control-sm" style="width:90px;" required>
                                                    <div class="form-check form-switch mb-0">
                                                        <input id="carouselActive{{ $image->id }}" type="checkbox" name="active_inactive" value="1" class="form-check-input" {{ $image->active_inactive ? 'checked' : '' }}>
                                                        <label for="carouselActive{{ $image->id }}" class="form-check-label small">Active</label>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="carouselReplace{{ $image->id }}" class="form-label small fw-semibold mb-1">Replace file</label>
                                                    <input id="carouselReplace{{ $image->id }}" type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                                </div>
                                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill justify-self-start">Save</button>
                                            </form>
                                        </td>
                                        <td class="pe-4">
                                            <form action="{{ route('admin.login-carousel-images.destroy', $image) }}" method="POST" onsubmit="return confirm('Delete this login carousel image?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-body-secondary py-5">
                                            No uploaded images yet. The login page is using the bundled carousel images.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
