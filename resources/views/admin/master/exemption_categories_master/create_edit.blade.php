@extends('admin.layouts.master')

@section('title', !empty($exemptionCategory) ? 'Edit Exemption Category' : 'Add Exemption Category')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One value drives the Status select (Active when nothing is set yet).
    $eccmStatus = old_string('active_inactive', $exemptionCategory->active_inactive ?? 1);
@endphp
<div class="container-fluid mst-page eccm-page">

    <x-breadcrum title="{{ !empty($exemptionCategory) ? 'Edit Exemption Category' : 'Add Exemption Category' }}" />
    <x-session_message />

    <form action="{{ route('master.exemption.category.master.store') }}" method="POST" id="exemptionCategoryForm">
        @csrf
        @if(!empty($exemptionCategory))
            <input type="hidden" name="id" value="{{ encrypt($exemptionCategory->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Exemption Category Details</h2>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="exemp_category_name" class="mst-form-label d-block">
                            Category Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="exemp_category_name" id="exemp_category_name"
                               class="form-control mst-control @error('exemp_category_name') is-invalid @enderror"
                               value="{{ old('exemp_category_name', $exemptionCategory->exemp_category_name ?? '') }}"
                               placeholder="Enter Category Name" required aria-required="true"
                               @error('exemp_category_name') aria-describedby="eccmCategoryNameError" @enderror>
                        @error('exemp_category_name')
                            <span class="mst-field-error" id="eccmCategoryNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="exemp_cat_short_name" class="mst-form-label d-block">
                            Short Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="exemp_cat_short_name" id="exemp_cat_short_name"
                               class="form-control mst-control @error('exemp_cat_short_name') is-invalid @enderror"
                               value="{{ old('exemp_cat_short_name', $exemptionCategory->exemp_cat_short_name ?? '') }}"
                               placeholder="Enter Short Name" required aria-required="true"
                               @error('exemp_cat_short_name') aria-describedby="eccmShortNameError" @enderror>
                        @error('exemp_cat_short_name')
                            <span class="mst-field-error" id="eccmShortNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="eccmStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="eccmStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="eccmStatusError" @enderror>
                            <option value="1" @selected($eccmStatus === '1')>Active</option>
                            <option value="0" @selected($eccmStatus === '0')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="eccmStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.exemption.category.master.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" id="saveExemptionCategoryForm">
                        {{ !empty($exemptionCategory) ? 'Update' : 'Save' }}
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
