@extends('admin.layouts.master')

@section('title', 'Login Carousel Images')

@section('content')
<div class="container-fluid login-carousel-admin-page">
    <x-breadcrum title="Login Carousel Images" section="General">
        <button type="button" data-bs-toggle="modal" data-bs-target="#uploadLoginCarouselModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">upload</i>
            <span>Upload images</span>
        </button>
    </x-breadcrum>

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
                            <th>Status</th>
                            <th style="width:200px;">Order</th>
                            <th class="pe-4 text-end" style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($images as $image)
                            <tr>
                                <td class="ps-4">
                                    <img src="{{ $image->previewUrl() }}"
                                        alt="Login carousel preview"
                                        class="img-fluid rounded-3 border login-carousel-preview"
                                        width="160" height="90"
                                        loading="lazy" decoding="async">
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $image->active_inactive ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $image->active_inactive ? 'Active' : 'Hidden' }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('admin.login-carousel-images.update', $image) }}" method="POST" class="d-flex align-items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="sort_order" min="0" max="65535"
                                            value="{{ $image->sort_order }}"
                                            class="form-control form-control-sm" style="width:90px;" required
                                            aria-label="Sort order for image {{ $image->id }}">
                                        <div class="form-check form-switch mb-0">
                                            <input type="checkbox" name="active_inactive" value="1"
                                                class="form-check-input" id="carouselActive{{ $image->id }}"
                                                {{ $image->active_inactive ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="carouselActive{{ $image->id }}">Active</label>
                                        </div>
                                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">Save</button>
                                    </form>
                                </td>
                                <td class="pe-4 text-end">
                                    <button type="button"
                                        class="btn btn-outline-secondary btn-sm rounded-pill edit-carousel-btn"
                                        data-bs-toggle="modal" data-bs-target="#editLoginCarouselModal"
                                        data-update-url="{{ route('admin.login-carousel-images.update', $image) }}"
                                        data-sort-order="{{ $image->sort_order }}"
                                        data-active="{{ $image->active_inactive ? '1' : '0' }}">
                                        Replace
                                    </button>
                                    <form action="{{ route('admin.login-carousel-images.destroy', $image) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Delete this login carousel image?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger ms-1" title="Delete">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-secondary py-5">
                                    No uploaded images yet. The login page is using the bundled carousel images.
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill"
                                            data-bs-toggle="modal" data-bs-target="#uploadLoginCarouselModal">
                                            Upload images
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Upload modal --}}
<div class="modal fade" id="uploadLoginCarouselModal" tabindex="-1"
    aria-labelledby="uploadLoginCarouselModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-semibold" id="uploadLoginCarouselModalLabel">Upload images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.login-carousel-images.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-3">
                    <label for="loginCarouselImages" class="form-label fw-semibold">Background image(s)</label>
                    <input id="loginCarouselImages" type="file" name="images[]" class="form-control"
                        accept="image/jpeg,image/png,image/webp" multiple required>
                    <p class="small text-body-secondary mt-2 mb-0">
                        Upload up to 10 JPG, PNG, or WEBP images at once. Each file can be up to 5 MB.
                    </p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-1">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">upload</i>
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Replace image modal --}}
<div class="modal fade" id="editLoginCarouselModal" tabindex="-1"
    aria-labelledby="editLoginCarouselModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-semibold" id="editLoginCarouselModalLabel">Replace image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editLoginCarouselForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label for="editCarouselSortOrder" class="form-label fw-semibold">Order</label>
                        <input type="number" id="editCarouselSortOrder" name="sort_order" min="0" max="65535"
                            class="form-control" required>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="editCarouselActive" name="active_inactive" value="1">
                        <label class="form-check-label" for="editCarouselActive">Active on login page</label>
                    </div>
                    <div>
                        <label for="editCarouselImage" class="form-label fw-semibold">New image file</label>
                        <input type="file" id="editCarouselImage" name="image" class="form-control"
                            accept="image/jpeg,image/png,image/webp" required>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var editForm = document.getElementById('editLoginCarouselForm');
    var sortInput = document.getElementById('editCarouselSortOrder');
    var activeInput = document.getElementById('editCarouselActive');

    document.querySelectorAll('.edit-carousel-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!editForm) return;
            editForm.action = btn.getAttribute('data-update-url') || '';
            if (sortInput) sortInput.value = btn.getAttribute('data-sort-order') || '0';
            if (activeInput) activeInput.checked = btn.getAttribute('data-active') === '1';
            var fileInput = document.getElementById('editCarouselImage');
            if (fileInput) fileInput.value = '';
        });
    });

    @if($errors->any())
    var uploadModal = document.getElementById('uploadLoginCarouselModal');
    if (uploadModal && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(uploadModal).show();
    }
    @endif
});
</script>
@endpush
