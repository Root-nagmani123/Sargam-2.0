@extends('admin.layouts.master')

@section('title', 'Edit Stream')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Edit Stream" />
    <x-session_message />

    {{-- Form root: the index opens this same form in a modal
         (public/js/master-admin.js openFormModal). Keep form-specific JS in
         the x-mst-form-init block inside it, bound to elements under root. --}}
    <div data-mst-form-root data-mst-form-title="Edit Stream">
        <form action="{{ route('stream.update', $stream->pk) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card mst-form-card">
                <div class="card-body">
                    <h2 class="mst-form-section-title h6">Stream Details</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="stream_name" class="mst-form-label d-block">
                                Stream Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="stream_name" name="stream_name"
                                   class="form-control mst-control @error('stream_name') is-invalid @enderror"
                                   placeholder="Enter Stream Name"
                                   value="{{ old('stream_name', $stream->stream_name) }}"
                                   maxlength="100" required aria-required="true">
                            @error('stream_name')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mst-form-footer">
                        <a href="{{ route('stream.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                        <button type="submit" class="btn mst-btn-submit px-4">Update</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
