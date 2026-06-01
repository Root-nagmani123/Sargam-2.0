@extends('admin.layouts.master')

@section('title', 'Vendor Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/vendor-master-admin.css') }}?v={{ @filemtime(public_path('css/vendor-master-admin.css')) ?: time() }}">
@endpush

@section('content')
@php
    $canDeleteVendor = hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess Admin') || hasRole('mess admin');
    $isVendorActive = static function ($vendor) {
        return ($vendor->status ?? 'active') === 'active';
    };
    $openCreateModal = request('open') === 'create' || ($errors->any() && old('_method') !== 'PUT');
    $openEditModal = request('open') === 'edit' || ($errors->any() && old('_method') === 'PUT');
@endphp
<div class="container-fluid vnd-master-page py-4">
    <x-breadcrum title="Vendor Master">
        <button type="button" id="openCreateVendor"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createVendorModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Vendor</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card vnd-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="programme-dt-toolbar vnd-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 mb-4">
                <div id="vndDtSearch" class="programme-dt-search" data-dt-search-for="vendorsTable"></div>
                <div id="messColManagerMount-vendorsTable" class="vnd-dt-columns-mount flex-shrink-0"></div>
            </div>

            <div class="programme-dt-panel vnd-dt-panel">
                <div class="table-responsive vnd-dt-scroll">
                    <table id="vendorsTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Vendor Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Contact Person</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Address</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendors as $index => $vendor)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="vnd-vendor-name">{{ $vendor->name }}</div>
                                </td>
                                <td>{{ $vendor->email ?? '-' }}</td>
                                <td>{{ $vendor->contact_person ?? '-' }}</td>
                                <td>{{ $vendor->phone ?? '-' }}</td>
                                <td class="text-start">{{ $vendor->address ?? '-' }}</td>
                                <td class="vnd-status-cell">
                                    <span class="badge rounded-pill programme-status-badge vnd-status-badge programme-status-badge--{{ $isVendorActive($vendor) ? 'active' : 'inactive' }}">
                                        {{ $isVendorActive($vendor) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="vnd-action-cell">
                                    <div class="vnd-vendor-actions d-inline-flex align-items-center gap-2 programme-action-group" role="group"
                                        aria-label="Vendor actions">
                                        <button type="button"
                                            class="btn-view-vendor programme-action-btn"
                                            data-id="{{ $vendor->id }}"
                                            data-name="{{ e($vendor->name) }}"
                                            data-email="{{ e($vendor->email ?? '') }}"
                                            data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                            data-phone="{{ e($vendor->phone ?? '') }}"
                                            data-address="{{ e($vendor->address ?? '') }}"
                                            data-gst-number="{{ e($vendor->gst_number ?? '') }}"
                                            data-bank-name="{{ e($vendor->bank_name ?? '') }}"
                                            data-ifsc-code="{{ e($vendor->ifsc_code ?? '') }}"
                                            data-account-number="{{ e($vendor->account_number ?? '') }}"
                                            aria-label="View vendor"
                                            title="View vendor">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                            class="btn-edit-vendor programme-action-btn"
                                            data-id="{{ $vendor->id }}"
                                            data-name="{{ e($vendor->name) }}"
                                            data-email="{{ e($vendor->email ?? '') }}"
                                            data-contact-person="{{ e($vendor->contact_person ?? '') }}"
                                            data-phone="{{ e($vendor->phone ?? '') }}"
                                            data-address="{{ e($vendor->address ?? '') }}"
                                            data-gst-number="{{ e($vendor->gst_number ?? '') }}"
                                            data-bank-name="{{ e($vendor->bank_name ?? '') }}"
                                            data-ifsc-code="{{ e($vendor->ifsc_code ?? '') }}"
                                            data-account-number="{{ e($vendor->account_number ?? '') }}"
                                            data-status="{{ e($vendor->status ?? 'active') }}"
                                            aria-label="Edit vendor"
                                            title="Edit vendor">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>
                                        <div class="form-check form-switch vnd-action-switch-wrap mb-0">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="mess_vendors"
                                                data-column="status"
                                                data-id="{{ $vendor->id }}"
                                                data-id_column="id"
                                                aria-label="Toggle vendor status"
                                                {{ $isVendorActive($vendor) ? 'checked' : '' }}>
                                        </div>
                                        @if($isVendorActive($vendor))
                                            <button type="button"
                                                class="vnd-delete-btn programme-action-btn programme-action-btn--danger"
                                                disabled
                                                aria-disabled="true"
                                                title="Cannot delete active vendor"
                                                aria-label="Delete vendor">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        @elseif($canDeleteVendor)
                                            <form method="POST" action="{{ route('admin.mess.vendors.destroy', $vendor->id) }}" class="d-inline vnd-delete-form m-0"
                                                onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="vnd-delete-btn programme-action-btn programme-action-btn--danger"
                                                    aria-label="Delete vendor"
                                                    title="Delete vendor">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                class="vnd-delete-btn programme-action-btn programme-action-btn--danger"
                                                disabled
                                                aria-disabled="true"
                                                title="You do not have permission to delete vendors"
                                                aria-label="Delete vendor">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="vnd-empty-row">
                                <td colspan="8" class="vnd-empty-state text-center">
                                    <i class="bi bi-truck display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Vendors Found</h5>
                                    <p class="text-secondary mb-0">Add a vendor to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="vndDtFooter"
                    class="programme-dt-footer vnd-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="vendorsTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create Vendor Modal --}}
<div class="modal fade" id="createVendorModal" tabindex="-1" aria-labelledby="createVendorModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered vnd-vendor-modal-dialog vnd-vendor-modal-dialog--xl">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 vnd-modal-form">
            <form method="POST" action="{{ route('admin.mess.vendors.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="createVendorModalLabel">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="create_vendor_name" class="form-control" required
                                value="{{ old('name') }}" pattern="[a-zA-Z0-9\s\-]+" maxlength="255" autocomplete="off">
                            <div class="text-danger small mt-1" id="create_vendor_name_error" role="alert">
                                @error('name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="create_email" class="form-control"
                                value="{{ old('email') }}" maxlength="255" placeholder="Optional">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" id="create_contact_person" class="form-control"
                                required value="{{ old('contact_person') }}" pattern="[a-zA-Z0-9\s\-]+" maxlength="255"
                                autocomplete="off">
                            <div class="text-danger small mt-1" id="create_contact_person_error" role="alert">
                                @error('contact_person'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="create_phone" class="form-control" required
                                value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                placeholder="10 digit mobile number">
                            <div class="text-danger small mt-1" id="create_phone_error" role="alert">
                                @error('phone'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="create_address" class="form-control" rows="3" required
                                maxlength="2000" autocomplete="off"
                                placeholder="Up to 2000 characters">{{ old('address') }}</textarea>
                            <div class="text-danger small mt-1" id="create_address_error" role="alert">
                                @error('address'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" id="create_gst_number" class="form-control"
                                value="{{ old('gst_number') }}" maxlength="15" pattern="[A-Za-z0-9]+"
                                placeholder="Letters & numbers, max 15">
                            <div class="text-danger small mt-1" id="create_gst_number_error" role="alert">
                                @error('gst_number'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" id="create_bank_name" class="form-control"
                                value="{{ old('bank_name') }}" maxlength="255" pattern="[a-zA-Z0-9\s\-]+"
                                placeholder="No special characters, max 255">
                            <div class="text-danger small mt-1" id="create_bank_name_error" role="alert">
                                @error('bank_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" id="create_ifsc_code" class="form-control"
                                value="{{ old('ifsc_code') }}" maxlength="11" pattern="[A-Za-z0-9]+"
                                placeholder="Letters & numbers, max 11">
                            <div class="text-danger small mt-1" id="create_ifsc_code_error" role="alert">
                                @error('ifsc_code'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" id="create_account_number" class="form-control"
                                value="{{ old('account_number') }}" inputmode="numeric" pattern="[0-9]*" maxlength="18"
                                placeholder="Digits only, max 18">
                            <div class="text-danger small mt-1" id="create_account_number_error" role="alert">
                                @error('account_number'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="create_status">Status</label>
                            <select name="status" id="create_status" class="form-select rounded-3">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="form-text">Default is Active.</div>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Upload Licence</label>
                            <input type="file" name="licence_document" class="form-control rounded-3">
                            @error('licence_document')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Vendor Modal --}}
<div class="modal fade" id="viewVendorModal" tabindex="-1" aria-labelledby="viewVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-body-tertiary border-0 py-3 px-4">
                <h5 class="modal-title fw-semibold" id="viewVendorModalLabel">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-lg-4 bg-body-tertiary">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-3 p-lg-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Vendor Name</label>
                                    <p class="mb-0 fw-semibold text-body" id="view_vendor_name">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Email</label>
                                    <p class="mb-0 text-body" id="view_vendor_email">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Contact Person</label>
                                    <p class="mb-0 text-body" id="view_vendor_contact_person">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Phone</label>
                                    <p class="mb-0 text-body" id="view_vendor_phone">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <label class="form-label text-body-secondary small mb-1">Address</label>
                                    <p class="mb-0 text-body" id="view_vendor_address">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">GST Number</label>
                                    <p class="mb-0 text-body" id="view_vendor_gst_number">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Bank Name</label>
                                    <p class="mb-0 text-body" id="view_vendor_bank_name">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">IFSC Code</label>
                                    <p class="mb-0 text-body" id="view_vendor_ifsc_code">&mdash;</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                                    <label class="form-label text-body-secondary small mb-1">Account Number</label>
                                    <p class="mb-0 text-body" id="view_vendor_account_number">&mdash;</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 pt-2 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Vendor Modal --}}
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable vnd-vendor-modal-dialog vnd-vendor-modal-dialog--xl">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 vnd-modal-form">
            <form id="editVendorForm" method="POST" action="{{ $openEditModal && old('vendor_modal_id') ? route('admin.mess.vendors.update', old('vendor_modal_id')) : '' }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="vendor_modal_id" id="edit_vendor_modal_id" value="{{ old('vendor_modal_id', $editVendor?->id ?? '') }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="editVendorModalLabel">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Vendor Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="edit_vendor_name" class="form-control form-control-sm" required
                                        pattern="[a-zA-Z0-9\s\-]+" maxlength="255" autocomplete="off">
                                    <div class="text-danger small mt-1" id="edit_vendor_name_error" role="alert"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Email</label>
                                    <input type="email" name="email" id="edit_vendor_email" class="form-control form-control-sm"
                                        maxlength="255" placeholder="Optional">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Contact Person <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="contact_person" id="edit_vendor_contact_person"
                                        class="form-control form-control-sm" required pattern="[a-zA-Z0-9\s\-]+" maxlength="255"
                                        autocomplete="off">
                                    <div class="text-danger small mt-1" id="edit_vendor_contact_person_error"
                                        role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="edit_vendor_phone" class="form-control form-control-sm" required
                                        inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                        placeholder="10 digit mobile number">
                                    <div class="text-danger small mt-1" id="edit_phone_error" role="alert"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Address <span
                                            class="text-danger">*</span></label>
                                    <textarea name="address" id="edit_vendor_address" class="form-control form-control-sm" rows="3"
                                        required maxlength="2000" autocomplete="off"
                                        placeholder="Up to 2000 characters"></textarea>
                                    <div class="text-danger small mt-1" id="edit_vendor_address_error" role="alert">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">GST Number</label>
                                    <input type="text" name="gst_number" id="edit_vendor_gst_number"
                                        class="form-control form-control-sm" maxlength="15" pattern="[A-Za-z0-9]+"
                                        placeholder="Letters & numbers, max 15">
                                    <div class="text-danger small mt-1" id="edit_gst_number_error" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Bank Name</label>
                                    <input type="text" name="bank_name" id="edit_vendor_bank_name" class="form-control form-control-sm"
                                        maxlength="255" pattern="[a-zA-Z0-9\s\-]+"
                                        placeholder="No special characters, max 255">
                                    <div class="text-danger small mt-1" id="edit_bank_name_error" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">IFSC Code</label>
                                    <input type="text" name="ifsc_code" id="edit_vendor_ifsc_code"
                                        class="form-control form-control-sm text-uppercase" maxlength="11" pattern="[A-Za-z0-9]+"
                                        placeholder="Letters & numbers, max 11">
                                    <div class="text-danger small mt-1" id="edit_ifsc_code_error" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Account Number</label>
                                    <input type="text" name="account_number" id="edit_vendor_account_number"
                                        class="form-control form-control-sm" inputmode="numeric" pattern="[0-9]*" maxlength="18"
                                        placeholder="Digits only, max 18">
                                    <div class="text-danger small mt-1" id="edit_account_number_error" role="alert">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium" for="edit_vendor_status">Status</label>
                                    <select name="status" id="edit_vendor_status" class="form-select rounded-3">
                                        <option value="active" {{ old('status', $editVendor->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $editVendor->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Upload Licence</label>
                                    <input type="file" name="licence_document" class="form-control rounded-3">
                                </div>
                            </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'vendorsTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 1,
    'actionColumnIndex' => 7,
    'infoLabel' => 'vendors',
    'pageLength' => 10,
])
@push('scripts')
<script>
(function () {
    var tableSelector = '#vendorsTable';
    var canDeleteVendor = @json($canDeleteVendor);
    var vendorsDestroyBaseUrl = @json(url('admin/mess/vendors'));

    function updateVndStatusBadge($row, isActive) {
        if (typeof jQuery === 'undefined') return;
        var $badge = jQuery($row).find('.vnd-status-badge').first();
        if (!$badge.length) return;
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function buildVndDeleteControl(isActive, vendorId) {
        if (typeof jQuery === 'undefined') return null;
        var $ = jQuery;
        var baseClass = 'vnd-delete-btn programme-action-btn programme-action-btn--danger';

        if (isActive) {
            return $('<button>', {
                type: 'button',
                class: baseClass,
                disabled: true,
                'aria-disabled': 'true',
                title: 'Cannot delete active vendor',
                'aria-label': 'Delete vendor'
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        }

        if (!canDeleteVendor) {
            return $('<button>', {
                type: 'button',
                class: baseClass,
                disabled: true,
                'aria-disabled': 'true',
                title: 'You do not have permission to delete vendors',
                'aria-label': 'Delete vendor'
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        }

        var $form = $('<form>', {
            method: 'POST',
            action: vendorsDestroyBaseUrl + '/' + vendorId,
            class: 'd-inline vnd-delete-form m-0'
        });
        $form.append('<input type="hidden" name="_token" value="' + ($('meta[name="csrf-token"]').attr('content') || '') + '">');
        $form.append('<input type="hidden" name="_method" value="DELETE">');
        var $btn = $('<button>', {
            type: 'submit',
            class: baseClass,
            title: 'Delete vendor',
            'aria-label': 'Delete vendor'
        }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        $form.on('submit', function () {
            return confirm('Are you sure you want to delete this vendor?');
        });
        $form.append($btn);
        return $form;
    }

    function updateVndDeleteControl($row, isActive, vendorId) {
        if (typeof jQuery === 'undefined') return;
        var $group = jQuery($row).find('.vnd-vendor-actions').first();
        if (!$group.length) return;
        $group.find('.vnd-delete-form, .vnd-delete-btn').remove();
        var $deleteControl = buildVndDeleteControl(isActive, vendorId);
        if ($deleteControl) {
            $group.append($deleteControl);
        }
    }

    function bindVendorTableUi() {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;
        $(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = $(this);
            var isActive = $toggle.is(':checked');
            var $row = $toggle.closest('tr');
            var vendorId = $toggle.data('id');
            window.setTimeout(function () {
                updateVndStatusBadge($row, isActive);
                updateVndDeleteControl($row, isActive, vendorId);
                var $editBtn = $row.find('.btn-edit-vendor').first();
                if ($editBtn.length) {
                    $editBtn.attr('data-status', isActive ? 'active' : 'inactive');
                }
            }, 0);
        });
    }

    function moveVendorModalsToBody() {
        ['createVendorModal', 'editVendorModal', 'viewVendorModal'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
    }

    function showVendorModal(modalId) {
        var el = document.getElementById(modalId);
        if (!el || !window.bootstrap || !bootstrap.Modal) return;
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    function hideVendorModal(modalId) {
        var el = document.getElementById(modalId);
        if (!el || !window.bootstrap || !bootstrap.Modal) return;
        var instance = bootstrap.Modal.getInstance(el);
        if (instance) instance.hide();
    }

    function openEditVendorModal(payload) {
        payload = payload || {};
        var id = String(payload.id || '').trim();
        if (!id) return;

        hideVendorModal('createVendorModal');

        var form = document.getElementById('editVendorForm');
        var modalIdInput = document.getElementById('edit_vendor_modal_id');
        form.action = vendorsDestroyBaseUrl + '/' + id;
        if (modalIdInput) modalIdInput.value = id;

        document.getElementById('edit_vendor_name').value = payload.name || '';
        document.getElementById('edit_vendor_email').value = payload.email || '';
        document.getElementById('edit_vendor_contact_person').value = payload.contactPerson || '';
        document.getElementById('edit_vendor_phone').value = payload.phone || '';
        document.getElementById('edit_vendor_address').value = payload.address || '';
        document.getElementById('edit_vendor_gst_number').value = payload.gstNumber || '';
        document.getElementById('edit_vendor_bank_name').value = payload.bankName || '';
        document.getElementById('edit_vendor_ifsc_code').value = payload.ifscCode || '';
        document.getElementById('edit_vendor_account_number').value = payload.accountNumber || '';
        var statusEl = document.getElementById('edit_vendor_status');
        if (statusEl) statusEl.value = payload.status || 'active';

        showVendorModal('editVendorModal');
    }

    function initVendorPage() {
    // Validation rules (must match VendorController)
    var nameRegex = /^[a-zA-Z0-9\s\-]+$/;
    var addressRegex = /^[a-zA-Z0-9\s\-\.\,\r\n]+$/;
    var gstRegex = /^[A-Za-z0-9]*$/;
    var bankNameRegex = /^[a-zA-Z0-9\s\-]*$/;
    var ifscRegex = /^[A-Za-z0-9]*$/;
    var accountNumberRegex = /^[0-9]*$/;
    var nameMessage =
        'Vendor name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var contactPersonMessage =
        'Contact person may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.';
    var addressMessage =
        'Address may only contain letters, numbers, spaces, hyphens, commas, periods and new lines. Special characters are not allowed.';
    var gstMessage = 'GST number may only contain letters and numbers. Max 15 characters.';
    var bankNameMessage =
    'Bank name may only contain letters, numbers, spaces and hyphens. Max 255 characters.';
    var ifscMessage = 'IFSC code may only contain letters and numbers. Max 11 characters.';
    var accountNumberMessage = 'Account number must contain only digits. Max 18 digits.';

    function validateName(value, required) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (required && value.length === 0) return {
            valid: false,
            message: 'This field is required.'
        };
        if (value.length === 0) return {
            valid: true
        };
        return nameRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: nameMessage
        };
    }

    function validateContactPerson(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: false,
            message: 'Contact person is required.'
        };
        return nameRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: contactPersonMessage
        };
    }

    function validateAddress(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: false,
            message: 'Address is required.'
        };
        if (value.length > 2000) return {
            valid: false,
            message: 'Address cannot exceed 2000 characters.'
        };
        return addressRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: addressMessage
        };
    }

    function validateGst(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 15) return {
            valid: false,
            message: 'GST number cannot exceed 15 characters.'
        };
        return gstRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: gstMessage
        };
    }

    function validateBankName(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 255) return {
            valid: false,
            message: 'Bank name cannot exceed 255 characters.'
        };
        return bankNameRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: bankNameMessage
        };
    }

    function validateIfsc(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 11) return {
            valid: false,
            message: 'IFSC code cannot exceed 11 characters.'
        };
        return ifscRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: ifscMessage
        };
    }

    function validateAccountNumber(value) {
        if (typeof value !== 'string') return {
            valid: true
        };
        value = value.trim();
        if (value.length === 0) return {
            valid: true
        };
        if (value.length > 18) return {
            valid: false,
            message: 'Account number cannot exceed 18 digits.'
        };
        return accountNumberRegex.test(value) ? {
            valid: true
        } : {
            valid: false,
            message: accountNumberMessage
        };
    }

    function showLiveError(inputEl, errorEl, result) {
        if (!inputEl || !errorEl) return;
        if (result.valid) {
            inputEl.classList.remove('is-invalid');
            errorEl.textContent = '';
        } else {
            inputEl.classList.add('is-invalid');
            errorEl.textContent = result.message;
        }
    }

    function attachLiveValidation(inputId, errorId, validateFn) {
        var input = document.getElementById(inputId);
        var errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return;

        function run() {
            showLiveError(input, errorEl, validateFn(input.value));
        }
        input.addEventListener('input', run);
        input.addEventListener('blur', run);
    }

    function normalizeAndValidatePhone(value, required) {
        if (typeof value !== 'string') return {
            valid: !required
        };
        var digits = value.replace(/\D/g, '').slice(0, 10);
        return (function() {
            if (required && digits.length === 0) {
                return {
                    valid: false,
                    message: 'Phone number is required.'
                };
            }
            if (digits.length !== 10) {
                return { valid: false, message: 'Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.' };
            }
            if (!/^[6-9][0-9]{9}$/.test(digits)) {
                return { valid: false, message: 'Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.' };
            }
            return {
                valid: true
            };
        })();
    }

    function attachPhoneField(inputId, errorId, required) {
        var input = document.getElementById(inputId);
        var errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return;

        function run() {
            var raw = input.value || '';
            var cleaned = raw.replace(/\D/g, '').slice(0, 10);
            if (cleaned !== raw) {
                input.value = cleaned;
            }
            var result = normalizeAndValidatePhone(cleaned, required);
            showLiveError(input, errorEl, result);
            return result;
        }

        input.addEventListener('input', run);
        input.addEventListener('blur', run);

        return run;
    }

    // Create modal: real-time validation
    attachLiveValidation('create_vendor_name', 'create_vendor_name_error', function(v) {
        return validateName(v, true);
    });
    attachLiveValidation('create_contact_person', 'create_contact_person_error', validateContactPerson);
    attachLiveValidation('create_address', 'create_address_error', validateAddress);
    var createPhoneValidator = attachPhoneField('create_phone', 'create_phone_error', true);

    // Edit modal: real-time validation
    attachLiveValidation('edit_vendor_name', 'edit_vendor_name_error', function(v) {
        return validateName(v, true);
    });
    attachLiveValidation('edit_vendor_contact_person', 'edit_vendor_contact_person_error',
        validateContactPerson);
    attachLiveValidation('edit_vendor_address', 'edit_vendor_address_error', validateAddress);
    var editPhoneValidator = attachPhoneField('edit_vendor_phone', 'edit_phone_error', true);
    attachLiveValidation('edit_vendor_gst_number', 'edit_gst_number_error', validateGst);
    attachLiveValidation('edit_vendor_bank_name', 'edit_bank_name_error', validateBankName);
    attachLiveValidation('edit_vendor_ifsc_code', 'edit_ifsc_code_error', validateIfsc);
    attachLiveValidation('edit_vendor_account_number', 'edit_account_number_error', validateAccountNumber);

    // Create modal: optional fields live validation
    attachLiveValidation('create_gst_number', 'create_gst_number_error', validateGst);
    attachLiveValidation('create_bank_name', 'create_bank_name_error', validateBankName);
    attachLiveValidation('create_ifsc_code', 'create_ifsc_code_error', validateIfsc);
    attachLiveValidation('create_account_number', 'create_account_number_error', validateAccountNumber);

    function runOptionalValidators(prefix) {
        var inputGst, inputBank, inputIfsc, inputAcc, errGst, errBank, errIfsc, errAcc;
        if (prefix === 'create') {
            inputGst = document.getElementById('create_gst_number');
            inputBank = document.getElementById('create_bank_name');
            inputIfsc = document.getElementById('create_ifsc_code');
            inputAcc = document.getElementById('create_account_number');
            errGst = document.getElementById('create_gst_number_error');
            errBank = document.getElementById('create_bank_name_error');
            errIfsc = document.getElementById('create_ifsc_code_error');
            errAcc = document.getElementById('create_account_number_error');
        } else {
            inputGst = document.getElementById('edit_vendor_gst_number');
            inputBank = document.getElementById('edit_vendor_bank_name');
            inputIfsc = document.getElementById('edit_vendor_ifsc_code');
            inputAcc = document.getElementById('edit_vendor_account_number');
            errGst = document.getElementById('edit_gst_number_error');
            errBank = document.getElementById('edit_bank_name_error');
            errIfsc = document.getElementById('edit_ifsc_code_error');
            errAcc = document.getElementById('edit_account_number_error');
        }
        var rGst = validateGst((inputGst && inputGst.value) || '');
        var rBank = validateBankName((inputBank && inputBank.value) || '');
        var rIfsc = validateIfsc((inputIfsc && inputIfsc.value) || '');
        var rAcc = validateAccountNumber((inputAcc && inputAcc.value) || '');
        if (inputGst && errGst) showLiveError(inputGst, errGst, rGst);
        if (inputBank && errBank) showLiveError(inputBank, errBank, rBank);
        if (inputIfsc && errIfsc) showLiveError(inputIfsc, errIfsc, rIfsc);
        if (inputAcc && errAcc) showLiveError(inputAcc, errAcc, rAcc);
        return rGst.valid && rBank.valid && rIfsc.valid && rAcc.valid;
    }

    // Create form: prevent submit if invalid; prevent double submit
    var createForm = document.querySelector('#createVendorModal form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            var r1 = validateName(document.getElementById('create_vendor_name').value, true);
            var r2 = validateContactPerson(document.getElementById('create_contact_person').value);
            var r3 = validateAddress(document.getElementById('create_address').value);
            var r4 = createPhoneValidator ? createPhoneValidator() : {
                valid: true
            };
            var rOpt = runOptionalValidators('create');
            showLiveError(document.getElementById('create_vendor_name'), document.getElementById('create_vendor_name_error'), r1);
            showLiveError(document.getElementById('create_contact_person'), document.getElementById('create_contact_person_error'), r2);
            showLiveError(document.getElementById('create_address'), document.getElementById('create_address_error'), r3);
            if (!r1.valid || !r2.valid || !r3.valid || !r4.valid || !rOpt) {
                e.preventDefault();
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }
        });
    }

    // Edit form: prevent submit if invalid; prevent double submit
    var editForm = document.getElementById('editVendorForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            var r1 = validateName(document.getElementById('edit_vendor_name').value, true);
            var r2 = validateContactPerson(document.getElementById('edit_vendor_contact_person').value);
            var r3 = validateAddress(document.getElementById('edit_vendor_address').value);
            var r4 = editPhoneValidator ? editPhoneValidator() : {
                valid: true
            };
            var rOpt = runOptionalValidators('edit');
            showLiveError(document.getElementById('edit_vendor_name'), document.getElementById('edit_vendor_name_error'), r1);
            showLiveError(document.getElementById('edit_vendor_contact_person'), document.getElementById('edit_vendor_contact_person_error'), r2);
            showLiveError(document.getElementById('edit_vendor_address'), document.getElementById('edit_vendor_address_error'), r3);
            if (!r1.valid || !r2.valid || !r3.valid || !r4.valid || !rOpt) {
                e.preventDefault();
                return;
            }
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }

    var createVendorModal = document.getElementById('createVendorModal');
    if (createVendorModal) {
        createVendorModal.addEventListener('show.bs.modal', function () {
            hideVendorModal('editVendorModal');
        });
        createVendorModal.addEventListener('hidden.bs.modal', function() {
            var form = createVendorModal.querySelector('form');
            if (form) {
                form.reset();
                var statusSelect = form.querySelector('select[name="status"]');
                if (statusSelect) statusSelect.value = 'active';
            }
            ['create_vendor_name_error', 'create_contact_person_error', 'create_address_error',
                'create_phone_error', 'create_gst_number_error', 'create_bank_name_error',
                'create_ifsc_code_error', 'create_account_number_error'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            ['create_vendor_name', 'create_contact_person', 'create_address', 'create_phone',
                'create_gst_number', 'create_bank_name', 'create_ifsc_code', 'create_account_number'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        });
        createVendorModal.addEventListener('shown.bs.modal', function() {
            showLiveError(document.getElementById('create_vendor_name'), document.getElementById(
                'create_vendor_name_error'), validateName(document.getElementById(
                'create_vendor_name').value, true));
            showLiveError(document.getElementById('create_contact_person'), document.getElementById(
                'create_contact_person_error'), validateContactPerson(document.getElementById(
                'create_contact_person').value));
            showLiveError(document.getElementById('create_address'), document.getElementById(
                'create_address_error'), validateAddress(document.getElementById(
                'create_address').value));
            if (createPhoneValidator) {
                createPhoneValidator();
            }
        });
    }

    var editVendorModal = document.getElementById('editVendorModal');
    if (editVendorModal) {
        editVendorModal.addEventListener('show.bs.modal', function () {
            hideVendorModal('createVendorModal');
        });
        editVendorModal.addEventListener('hidden.bs.modal', function() {
            ['edit_vendor_name_error', 'edit_vendor_contact_person_error', 'edit_vendor_address_error',
                'edit_phone_error', 'edit_gst_number_error', 'edit_bank_name_error',
                'edit_ifsc_code_error', 'edit_account_number_error'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            ['edit_vendor_name', 'edit_vendor_contact_person', 'edit_vendor_address',
                'edit_vendor_phone', 'edit_vendor_gst_number', 'edit_vendor_bank_name',
                'edit_vendor_ifsc_code', 'edit_vendor_account_number'
            ].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        });
    }

    moveVendorModalsToBody();
    bindVendorTableUi();

    document.addEventListener('click', function (e) {
        var viewBtn = e.target.closest('.btn-view-vendor');
        if (viewBtn && viewBtn.closest(tableSelector)) {
            e.preventDefault();
            e.stopPropagation();
            var set = function (id, val) {
                var el = document.getElementById(id);
                if (el) el.textContent = val || '—';
            };
            set('view_vendor_name', viewBtn.getAttribute('data-name'));
            set('view_vendor_email', viewBtn.getAttribute('data-email'));
            set('view_vendor_contact_person', viewBtn.getAttribute('data-contact-person'));
            set('view_vendor_phone', viewBtn.getAttribute('data-phone'));
            set('view_vendor_address', viewBtn.getAttribute('data-address'));
            set('view_vendor_gst_number', viewBtn.getAttribute('data-gst-number'));
            set('view_vendor_bank_name', viewBtn.getAttribute('data-bank-name'));
            set('view_vendor_ifsc_code', viewBtn.getAttribute('data-ifsc-code'));
            set('view_vendor_account_number', viewBtn.getAttribute('data-account-number'));
            showVendorModal('viewVendorModal');
            return;
        }

        var editBtn = e.target.closest('.btn-edit-vendor');
        if (!editBtn || !editBtn.closest(tableSelector)) return;
        e.preventDefault();
        e.stopPropagation();
        openEditVendorModal({
            id: editBtn.getAttribute('data-id'),
            name: editBtn.getAttribute('data-name') || '',
            email: editBtn.getAttribute('data-email') || '',
            contactPerson: editBtn.getAttribute('data-contact-person') || '',
            phone: editBtn.getAttribute('data-phone') || '',
            address: editBtn.getAttribute('data-address') || '',
            gstNumber: editBtn.getAttribute('data-gst-number') || '',
            bankName: editBtn.getAttribute('data-bank-name') || '',
            ifscCode: editBtn.getAttribute('data-ifsc-code') || '',
            accountNumber: editBtn.getAttribute('data-account-number') || '',
            status: editBtn.getAttribute('data-status') || 'active'
        });
    });

    @if($openCreateModal)
    showVendorModal('createVendorModal');
    @endif

    @if($openEditModal)
    (function () {
        var editId = document.getElementById('edit_vendor_modal_id');
        if (editId && editId.value) {
            document.getElementById('editVendorForm').action = vendorsDestroyBaseUrl + '/' + editId.value;
        }
        showVendorModal('editVendorModal');
    })();
    @endif

    } // initVendorPage

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVendorPage);
    } else {
        initVendorPage();
    }
})();
</script>
@endpush
@endsection