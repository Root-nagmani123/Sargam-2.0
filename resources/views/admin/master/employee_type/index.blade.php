@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@section('setup_content')
<div class="container-fluid etm-master-page">
    <x-breadcrum title="Employee Type Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm etm-open-add-btn"
            aria-controls="etmTypeModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card etm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="etmDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="employeetypemaster-table"></div>
            </div>

            <div class="programme-dt-panel etm-dt-panel">
                <div class="table-responsive etm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="etmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeetypemaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade etm-type-modal" id="etmTypeModal" tabindex="-1" aria-labelledby="etmTypeModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered etm-type-modal-dialog">
        <div class="modal-content cgt-form-modal etm-type-modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="etmTypeModalLabel">Add Employee Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="etmTypeForm" class="etm-type-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="etm_pk" value="">

                    <label for="etm_employee_type_name" class="form-label cgt-field-label mb-2">
                        Employee Type Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="employee_type_name"
                        id="etm_employee_type_name"
                        class="form-control rounded-3"
                        placeholder="eg. General Medicine"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="etm_employee_type_name_error">
                        Employee Type Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="etmFormSubmit">Create Employee Type</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
{{ $dataTable->scripts() }}
@endpush