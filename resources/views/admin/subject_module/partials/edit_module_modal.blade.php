{{-- Edit Module modal — filled by module_modals_scripts (populateEditForm). --}}
@php $smEditIsOld = old('module_form') === 'edit'; @endphp
<div class="modal fade mst-modal sm-module-form-modal sm-edit-module-modal" id="smEditModuleModal" tabindex="-1"
     aria-labelledby="smEditModuleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('subject-module.update', 0) }}" method="POST" id="smEditModuleForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="module_form" value="edit">
                <input type="hidden" name="sm_edit_module_pk" id="sm_edit_module_pk_hidden" value="{{ old('sm_edit_module_pk') }}">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="smEditModuleModalLabel">Edit Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="sm_edit_module_name" class="mst-form-label d-block">
                                Module Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="module_name" id="sm_edit_module_name"
                                   class="form-control mst-control @if ($smEditIsOld) @error('module_name') is-invalid @enderror @endif"
                                   value="{{ $smEditIsOld ? old('module_name') : '' }}"
                                   placeholder="eg. General Medicine" maxlength="200"
                                   required aria-required="true">
                            @if ($smEditIsOld)
                                @error('module_name')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-0">
                            <label for="sm_edit_active_inactive" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="sm_edit_active_inactive"
                                    class="form-select mst-control mst-searchable @if ($smEditIsOld) @error('active_inactive') is-invalid @enderror @endif"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="" disabled>Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            @if ($smEditIsOld)
                                @error('active_inactive')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="smEditModuleSubmit">Update Module</button>
                </div>
            </form>
        </div>
    </div>
</div>
