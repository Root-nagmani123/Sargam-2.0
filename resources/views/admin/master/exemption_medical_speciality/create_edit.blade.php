@extends('admin.layouts.master')

@section('title', isset($speciality) ? 'Edit Exemption Medical Speciality' : 'Add Exemption Medical Speciality')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One value drives the Status select (Active when nothing is set yet).
    $emsStatus = (string) old('active_inactive', $speciality->active_inactive ?? 1);
@endphp
<div class="container-fluid mst-page ems-page">

    <x-breadcrum title="{{ isset($speciality) ? 'Edit Exemption Medical Speciality' : 'Add Exemption Medical Speciality' }}" />
    <x-session_message />

    <form method="POST" action="{{ route('master.exemption.medical.speciality.store') }}">
        @csrf
        @if(isset($speciality))
            <input type="hidden" name="id" value="{{ encrypt($speciality->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Medical Speciality Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="medical_speciality_name" class="mst-form-label d-block">
                            Speciality Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="medical_speciality_name" id="medical_speciality_name"
                               class="form-control mst-control @error('medical_speciality_name') is-invalid @enderror"
                               value="{{ old('medical_speciality_name', $speciality->speciality_name ?? '') }}"
                               placeholder="Enter speciality name" required aria-required="true"
                               @error('medical_speciality_name') aria-describedby="emsNameError" @enderror>
                        @error('medical_speciality_name')
                            <span class="mst-field-error" id="emsNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="active_inactive" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="active_inactive"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="emsStatusError" @enderror>
                            <option value="1" @selected($emsStatus === '1')>Active</option>
                            <option value="0" @selected($emsStatus === '0')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="emsStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.exemption.medical.speciality.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">
                        {{ isset($speciality) ? 'Update' : 'Save' }}
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
