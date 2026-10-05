@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php $isEdit = !empty($classSessionMaster); @endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="{{ $isEdit ? 'Edit Class Session' : 'Add Class Session' }}" />
    <x-session_message />

    {{-- One view serves create and edit: ClassSessionMasterController::store()
         updates when the encrypted `id` is posted, creates otherwise. --}}
    <form action="{{ route('master.class.session.store') }}" method="POST" id="classSessionForm">
        @csrf
        @if ($isEdit)
            <input type="hidden" name="id" value="{{ encrypt($classSessionMaster->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Class Session Details</h2>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="shift_name" class="mst-form-label d-block">
                            Shift Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="shift_name" name="shift_name"
                               class="form-control mst-control @error('shift_name') is-invalid @enderror"
                               placeholder="Shift Name"
                               value="{{ old('shift_name', $classSessionMaster->shift_name ?? '') }}"
                               required aria-required="true">
                        @error('shift_name')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="start_time" class="mst-form-label d-block">
                            Start Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" id="start_time" name="start_time"
                               class="form-control mst-control @error('start_time') is-invalid @enderror"
                               placeholder="Start Time"
                               value="{{ old('start_time', $classSessionMaster->start_time ?? '') }}"
                               required aria-required="true">
                        @error('start_time')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="end_time" class="mst-form-label d-block">
                            End Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" id="end_time" name="end_time"
                               class="form-control mst-control @error('end_time') is-invalid @enderror"
                               placeholder="End Time"
                               value="{{ old('end_time', $classSessionMaster->end_time ?? '') }}"
                               required aria-required="true">
                        @error('end_time')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.class.session.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" id="saveClassSessionForm">
                        {{ $isEdit ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
