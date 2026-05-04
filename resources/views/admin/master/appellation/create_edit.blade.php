@extends('admin.layouts.master')

@section('title', isset($appellation) ? 'Edit Appellation' : 'Add Appellation')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Appellation Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-3">
                {{ isset($appellation) ? 'Edit' : 'Add' }} Appellation
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.appellation.store') }}" id="appellationForm">
                @csrf

                @if(isset($appellation))
                    <input type="hidden" name="id" value="{{ encrypt($appellation->pk) }}">
                @endif

                <div class="row">

                    <!-- Appellation Name -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input
                                name="appettation_name"
                                label="Appellation Name :"
                                placeholder="Enter appellation name"
                                formLabelClass="form-label"
                                value="{{ old('appettation_name', $appellation->appettation_name ?? '') }}"
                                maxlength="50"
                                required="true"
                                labelRequired="true"
                            />
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-select
                                name="active_inactive"
                                label="Status :"
                                formLabelClass="form-label"
                                :options="['1' => 'Active', '2' => 'Inactive']"
                                value="{{ old('active_inactive', $appellation->active_inactive ?? '') }}"
                                required="true"
                                labelRequired="true"
                            />
                        </div>
                    </div>

                </div>

                <hr>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        {{ isset($appellation) ? 'Update' : 'Add' }}
                    </button>
                    <a href="{{ route('master.appellation.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('appettation_name').addEventListener('keypress', function (e) {
        var char = String.fromCharCode(e.which);
        if (!/^[a-zA-Z\s\.]$/.test(char)) {
            e.preventDefault();
        }
    });

    document.getElementById('appettation_name').addEventListener('paste', function (e) {
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        if (!/^[a-zA-Z\s\.]+$/.test(pasted)) {
            e.preventDefault();
        }
    });
</script>
@endpush
