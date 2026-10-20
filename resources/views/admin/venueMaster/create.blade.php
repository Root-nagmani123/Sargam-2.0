@extends('admin.layouts.master')

@section('title', 'Create Venue Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Add Venue" />
    <x-session_message />

    {{-- Form root: the index opens this same form in a modal
         (public/js/master-admin.js openFormModal). Keep form-specific JS in
         the x-mst-form-init block inside it, bound to elements under root. --}}
    <div data-mst-form-root data-mst-form-title="Add Venue">
        <form action="{{ route('Venue-Master.store') }}" method="POST">
            @csrf

            <div class="card mst-form-card">
                <div class="card-body">
                    <h2 class="mst-form-section-title h6">Venue Details</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="venue_name" class="mst-form-label d-block">
                                Venue Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="venue_name" name="venue_name"
                                   class="form-control mst-control @error('venue_name') is-invalid @enderror"
                                   value="{{ old('venue_name') }}" maxlength="255"
                                   required aria-required="true">
                            @error('venue_name')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="venue_short_name" class="mst-form-label d-block">
                                Short Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="venue_short_name" name="venue_short_name"
                                   class="form-control mst-control @error('venue_short_name') is-invalid @enderror"
                                   value="{{ old('venue_short_name') }}" maxlength="100"
                                   required aria-required="true">
                            @error('venue_short_name')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="mst-form-label d-block">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control mst-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mst-form-footer">
                        <a href="{{ route('Venue-Master.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                        <button type="submit" class="btn mst-btn-submit px-4">Save</button>
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
