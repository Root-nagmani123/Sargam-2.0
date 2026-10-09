@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // store() requires active_inactive, but this page never sent it, so every
    // full-page save failed validation. One value drives the Status select
    // (Active unless the record says otherwise); 1 / 0 as the grid writes them.
    $mdoStatus = (string) old('active_inactive', $mdoDutyType->active_inactive ?? 1);
@endphp
<div class="container-fluid mst-page">

    <x-breadcrum title="{{ !empty($mdoDutyType) ? 'Edit MDO Duty Type' : 'Add MDO Duty Type' }}" />
    <x-session_message />

    <form action="{{ route('master.mdo_duty_type.store') }}" method="POST" id="facultyForm">
        @csrf
        @if(!empty($mdoDutyType))
            <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Duty Type Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="mdoDutyTypeName" class="mst-form-label d-block">
                            Duty Type Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="mdo_duty_type_name" id="mdoDutyTypeName"
                               class="form-control mst-control @error('mdo_duty_type_name') is-invalid @enderror"
                               value="{{ old('mdo_duty_type_name', $mdoDutyType->mdo_duty_type_name ?? '') }}"
                               placeholder="Enter duty type name" maxlength="255" required aria-required="true"
                               @error('mdo_duty_type_name') aria-describedby="mdoDutyTypeNameError" @enderror>
                        @error('mdo_duty_type_name')
                            <span class="mst-field-error" id="mdoDutyTypeNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="mdoDutyTypeStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="mdoDutyTypeStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="mdoDutyTypeStatusError" @enderror>
                            <option value="1" @selected($mdoStatus === '1')>Active</option>
                            <option value="0" @selected($mdoStatus !== '1')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="mdoDutyTypeStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.mdo_duty_type.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" id="saveFacultyForm">
                        {{ !empty($mdoDutyType) ? 'Update' : 'Save' }}
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
