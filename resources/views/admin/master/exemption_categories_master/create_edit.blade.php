@extends('admin.layouts.master')

@section('title', 'Exemption Category')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Exemption Category" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($exemptionCategory) ? 'Edit Exemption Category' : 'Add Exemption Category' }}
            </h4>
            <hr>
            <form action="{{ route('master.exemption.category.master.store') }}" method="POST"
                id="exemptionCategoryForm">
                @csrf
                @if(!empty($exemptionCategory))
                <input type="hidden" name="id" value="{{ encrypt($exemptionCategory->pk) }}">
                @endif
                <div class="row">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input name="exemp_category_name" label="Category Name :"
                                placeholder="Enter Category Name" formLabelClass="form-label" required="true"
                                value="{{ old('exemp_category_name', $exemptionCategory->exemp_category_name ?? '') }}" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input name="exemp_cat_short_name" label="Short Name :" placeholder="Enter Short Name"
                                formLabelClass="form-label" required="true"
                                value="{{ old('exemp_cat_short_name', $exemptionCategory->exemp_cat_short_name ?? '') }}" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="active_inactive" class="form-control" required>
                                <option value="1"
                                    {{ old('active_inactive', $exemptionCategory->active_inactive ?? '') == '1' ? 'selected' : '' }}>
                                    Active</option>
                                <option value="0"
                                    {{ old('active_inactive', $exemptionCategory->active_inactive ?? '') == '0' ? 'selected' : '' }}>
                                    Inactive</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveExemptionCategoryForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($exemptionCategory) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.exemption.category.master.index') }}"
                        class="btn btn-secondary hstack gap-6 float-end me-2">
                        <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>

@endsection