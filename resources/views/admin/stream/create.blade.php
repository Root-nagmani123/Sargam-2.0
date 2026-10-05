@extends('admin.layouts.master')

@section('title', 'Stream')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One card per posted name, so a rejected submit comes back with every row.
    $streamNames = old('stream_name', ['']);
    $streamNames = is_array($streamNames) && count($streamNames) ? array_values($streamNames) : [''];
@endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="Add Stream" />
    <x-session_message />

    <form action="{{ route('stream.store') }}" method="POST">
        @csrf

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Stream Details</h2>

                {{-- stream_name[] — StreamController::store() saves one row per name. --}}
                <div id="stream_fields">
                    @foreach ($streamNames as $i => $streamName)
                        <div class="mst-field-card mst-repeat">
                            <div class="row g-3 align-items-end">
                                <div class="col">
                                    <label for="streamName{{ $i }}" class="mst-form-label d-block">
                                        Stream Name <span class="mst-req" aria-hidden="true">*</span>
                                    </label>
                                    <input type="text" id="streamName{{ $i }}" name="stream_name[]"
                                           class="form-control mst-control @error('stream_name.' . $i) is-invalid @enderror"
                                           placeholder="Stream" maxlength="100"
                                           value="{{ $streamName }}" required aria-required="true">
                                    @error('stream_name.' . $i)
                                        <span class="mst-field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-auto mst-field-actions">
                                    <button type="button" class="mst-field-btn mst-field-btn--remove" aria-label="Remove this stream">
                                        <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="mst-field-btn mst-field-btn--add" aria-label="Add another stream">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('stream.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    $(function () {
        MstAdmin.repeatable({ container: '#stream_fields' });
    });
</script>
@endpush
