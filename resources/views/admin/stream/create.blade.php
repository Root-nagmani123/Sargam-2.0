@extends('admin.layouts.master')

@section('title', 'Add Stream - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid stream-create-page">
    {{-- Breadcrumb - GIGW compliant, semantic navigation --}}
    <x-breadcrum title="Add Stream" />

    {{-- Main form card - Bootstrap 5.3 enhanced --}}
    <main id="main-content" class="stream-create-main" role="main">
        <section aria-labelledby="stream-form-heading">
            <div class="card border-0 shadow stream-create-card overflow-hidden" style="border-left: 4px solid #004a93;">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h2 id="stream-form-heading" class="h5 mb-0 fw-semibold d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded text-primary" style="font-size: 1.25rem;" aria-hidden="true">school</i>
                        Stream Details
                    </h2>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('stream.store') }}" method="POST" id="stream-form" novalidate aria-describedby="stream-form-help">
                        @csrf

                        <div id="stream-form-help" class="visually-hidden">
                            Add one or more stream names. Use the add button to include additional streams.
                        </div>

                        <fieldset class="border-0 p-0 m-0">
                            <legend class="visually-hidden">Stream names</legend>

                            <div id="stream_fields" class="stream-fields-list" role="list" aria-label="List of stream name fields">
                                @if(old('stream_name'))
                                    @foreach(old('stream_name') as $key => $value)
                                        <div class="stream-field-row row g-2 align-items-end mb-3" role="listitem">
                                            <div class="col-12 col-md-11">
                                                <label for="stream_name_{{ $key }}" class="form-label visually-hidden">Stream name {{ $loop->iteration }}</label>
                                                <input type="text"
                                                    id="stream_name_{{ $key }}"
                                                    name="stream_name[]"
                                                    class="form-control form-control-lg @error('stream_name.' . $key) is-invalid @enderror"
                                                    value="{{ $value }}"
                                                    placeholder="Enter stream name (e.g. Science, Arts, Commerce)"
                                                    required
                                                    autocomplete="organization"
                                                    aria-required="true"
                                                    aria-invalid="{{ $errors->has('stream_name.' . $key) ? 'true' : 'false' }}"
                                                    aria-describedby="{{ $errors->has('stream_name.' . $key) ? 'stream_error_' . $key : null }}">
                                                @error('stream_name.' . $key)
                                                    <div id="stream_error_{{ $key }}" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12 col-md-1 d-flex justify-content-md-start justify-content-end">
                                                <button type="button"
                                                    class="btn btn-danger btn-lg px-3"
                                                    onclick="removeField(this)"
                                                    aria-label="Remove stream {{ $loop->iteration }}">
                                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="stream-field-row row g-2 align-items-end mb-3" role="listitem">
                                        <div class="col-12 col-md-11">
                                            <label for="stream_name_0" class="form-label visually-hidden">Stream name 1</label>
                                            <input type="text"
                                                id="stream_name_0"
                                                name="stream_name[]"
                                                class="form-control form-control-lg"
                                                placeholder="Enter stream name (e.g. Science, Arts, Commerce)"
                                                required
                                                autocomplete="organization"
                                                aria-required="true"
                                                aria-describedby="stream_help_text">
                                            <div id="stream_help_text" class="form-text text-muted mt-1">
                                                Enter the name of the stream. You can add more streams using the add button.
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-1 d-flex justify-content-md-start justify-content-end">
                                            <button type="button"
                                                class="btn btn-success btn-lg px-3"
                                                id="add-stream-btn"
                                                onclick="addStreamField()"
                                                aria-label="Add another stream">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">add</i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Add button for when we have old values (multiple rows) --}}
                            @if(old('stream_name') && count(old('stream_name')) > 0)
                                <div class="mb-3">
                                    <button type="button"
                                        class="btn btn-outline-primary btn-lg d-flex align-items-center gap-2"
                                        onclick="addStreamField()"
                                        aria-label="Add another stream">
                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">add_circle</i>
                                        Add Another Stream
                                    </button>
                                </div>
                            @endif
                        </fieldset>

                        <hr class="my-4">

                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 gap-sm-3" role="group" aria-label="Form actions">
                            <a href="{{ route('stream.index') }}"
                                class="btn btn-outline-secondary btn-lg order-2 order-sm-1">
                                <i class="material-icons material-symbols-rounded me-2" style="font-size: 1.25rem;" aria-hidden="true">arrow_back</i>
                                Back
                            </a>
                            <button type="submit"
                                class="btn btn-primary btn-lg order-1 order-sm-2 px-4 shadow-sm">
                                <i class="material-icons material-symbols-rounded me-2" style="font-size: 1.25rem;" aria-hidden="true">save</i>
                                Save Streams
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    let streamFieldCounter = {{ old('stream_name') ? count(old('stream_name')) : 1 }};

    window.addStreamField = function() {
        streamFieldCounter++;
        const container = document.getElementById('stream_fields');
        const fieldRow = document.createElement('div');
        fieldRow.className = 'stream-field-row row g-2 align-items-end mb-3';
        fieldRow.setAttribute('role', 'listitem');

        const inputId = 'stream_name_' + streamFieldCounter;
        fieldRow.innerHTML = `
            <div class="col-12 col-md-11">
                <label for="${inputId}" class="form-label visually-hidden">Stream name ${streamFieldCounter}</label>
                <input type="text"
                    id="${inputId}"
                    name="stream_name[]"
                    class="form-control form-control-lg"
                    placeholder="Enter stream name (e.g. Science, Arts, Commerce)"
                    required
                    autocomplete="organization"
                    aria-required="true">
            </div>
            <div class="col-12 col-md-1 d-flex justify-content-md-start justify-content-end">
                <button type="button"
                    class="btn btn-danger btn-lg px-3"
                    onclick="removeField(this)"
                    aria-label="Remove stream ${streamFieldCounter}">
                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                </button>
            </div>
        `;

        container.appendChild(fieldRow);

        // Focus the new input for keyboard/screen reader users (GIGW)
        const newInput = fieldRow.querySelector('input');
        if (newInput) {
            newInput.focus();
        }

        // Announce to screen readers (GIGW - assistive tech support)
        const liveRegion = document.getElementById('stream-aria-live') || createLiveRegion();
        liveRegion.textContent = 'Stream field ' + streamFieldCounter + ' added.';
        setTimeout(function() { liveRegion.textContent = ''; }, 1000);
    };

    window.removeField = function(button) {
        const row = button.closest('.stream-field-row');
        const list = document.getElementById('stream_fields');
        if (row && list && list.querySelectorAll('.stream-field-row').length > 1) {
            row.remove();
        }
    };

    function createLiveRegion() {
        const live = document.createElement('div');
        live.id = 'stream-aria-live';
        live.setAttribute('aria-live', 'polite');
        live.setAttribute('aria-atomic', 'true');
        live.className = 'visually-hidden';
        document.body.appendChild(live);
        return live;
    }
})();
</script>
@endpush
    