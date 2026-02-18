@extends('admin.layouts.master')

@section('title', 'Stream - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Add Stream</h4>
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
        <h4 class="card-title mb-3">Stream</h4>
        <hr>
        <form action="{{ route('stream.store') }}" method="POST">
            @csrf

            <div id="stream_fields">
                @if(old('stream_name'))
                    @foreach(old('stream_name') as $key => $value)
                        <div class="row my-2">
                            <div class="col-11">
                                <input type="text" name="stream_name[]" class="form-control @error('stream_name.' . $key) is-invalid @enderror" value="{{ $value }}" placeholder="Stream" required>
                                @error('stream_name.' . $key)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger" onclick="removeField(this)">
                                    <i class="material-icons menu-icon">delete</i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="row my-2">
                        <div class="col-11">
                            <input type="text" name="stream_name[]" class="form-control" placeholder="Stream" required>
                        </div>
                        <div class="col-1 d-flex align-items-end">
                            <button onclick="addStreamField()" class="btn btn-success" type="button">
                                <i class="material-icons menu-icon">add</i>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <hr>
            <div class="mb-3 text-end gap-2">
                <button class="btn btn-primary" type="submit">Submit</button>
                <a href="{{ route('stream.index') }}" class="btn btn-secondary">
                        Back
                    </a>
            </div>
        </form>
    </div>
</div>
    <!-- end Vertical Steps Example -->
</div>


@endsection
<script>
    function addStreamField() {
        const field = `
            <div class="row my-2">
                <div class="col-11">
                    <input type="text" name="stream_name[]" class="form-control" placeholder="Stream" required>
                </div>
                <div class="col-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger" onclick="removeField(this)">
                        <i class="material-icons menu-icon">delete</i>
                    </button>
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

        const inputId = 'stream_name_' + streamFieldCounter;

        fieldRow.innerHTML = `
            <div class="col-12 col-md-11">
                <input type="text"
                    id="${inputId}"
                    name="stream_name[]"
                    class="form-control form-control-lg stream-input"
                    placeholder="Enter stream name"
                    autocomplete="organization">
            </div>

            <div class="col-12 col-md-1">
                <button type="button" class="btn btn-danger btn-lg px-3" onclick="removeField(this)">
                    delete
                </button>
            </div>
        `;

        container.appendChild(fieldRow);
    };

    window.removeField = function(btn) {
        const row = btn.closest('.stream-field-row');
        if(document.querySelectorAll('.stream-field-row').length > 1){
            row.remove();
        }
    };

    // 🔥 CLEAN EMPTY INPUTS BEFORE SUBMIT (MOBILE FIX)
    document.getElementById('stream-form').addEventListener('submit', function(e){

        document.querySelectorAll('.stream-input').forEach(function(input){
            if(input.value.trim() === ''){
                input.remove();
            }
        });

    });

})();
</script>
@endpush

