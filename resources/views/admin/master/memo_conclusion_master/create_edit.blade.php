@extends('admin.layouts.master')

@section('title', isset($conclusion) ? 'Edit Memo Conclusion' : 'Add Memo Conclusion')

@section('setup_content')
<div class="container-fluid memo-conclusion-form">
    <x-breadcrum title="Memo Conclusion Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ isset($conclusion) ? 'Edit' : 'Add' }} Memo Conclusion
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.memo.conclusion.master.store') }}">
                @csrf
                @if(isset($conclusion))
                    <input type="hidden" name="id" value="{{ encrypt($conclusion->pk) }}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="discussion_name" class="form-label">Conclusion name<span style="color:red;">*</span></label>
                            <input type="text" name="discussion_name" class="form-control memo-conclusion-input"
                                   value="{{ old('discussion_name', $conclusion->discussion_name ?? '') }}" required>
                            @error('discussion_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="pt_discusion" class="form-label">PT Discussion</label>
                            <input type="text" name="pt_discusion" class="form-control memo-conclusion-input"
                                   value="{{ old('pt_discusion', $conclusion->pt_discusion ?? '') }}">
                            @error('pt_discusion')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div> 
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1" {{ (old('active_inactive', $conclusion->active_inactive ?? 1) == 1) ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ (old('active_inactive', $conclusion->active_inactive ?? 2) == 2) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">{{ isset($conclusion) ? 'Update' : 'Submit' }}</button>
                    <a href="{{ route('master.memo.conclusion.master.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- Responsive styles: only apply below desktop (992px), desktop view unchanged --}}
<style>
@media (max-width: 991.98px) {
    .memo-conclusion-form .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    .memo-conclusion-form .card-body {
        padding: 1rem;
    }
    .memo-conclusion-form .card-title {
        font-size: 1.1rem;
    }
    .memo-conclusion-form .text-end {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    .memo-conclusion-form .text-end .btn {
        min-width: 0;
    }
}
@media (max-width: 767.98px) {
    .memo-conclusion-form .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .memo-conclusion-form .card-body {
        padding: 0.875rem;
    }
    .memo-conclusion-form .form-control,
    .memo-conclusion-form .form-select {
        font-size: 16px; /* prevents zoom on iOS */
    }
    .memo-conclusion-form .text-end {
        flex-direction: column;
        align-items: stretch;
    }
    .memo-conclusion-form .text-end .btn {
        width: 100%;
    }
}
@media (max-width: 575.98px) {
    .memo-conclusion-form .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .memo-conclusion-form .card-body {
        padding: 0.75rem;
    }
    .memo-conclusion-form .card-title {
        font-size: 1rem;
    }
    .memo-conclusion-form .form-label {
        font-size: 0.9375rem;
    }
    /* Same size text boxes at Add Discussion */
    .memo-conclusion-form .memo-conclusion-input {
        width: 100%;
        min-height: 38px;
    }
    .memo-conclusion-form .row .col-md-6 .form-control {
        flex: 1 1 auto;
    }
}
</style>
@endsection
