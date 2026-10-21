{{-- Edit Module modal --}}
<div class="modal fade sm-module-form-modal sm-edit-module-modal" id="smEditModuleModal" tabindex="-1"
    aria-labelledby="smEditModuleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-module-modal-dialog">
        <div class="modal-content sm-module-modal-content border-0 shadow">
            <div class="modal-header sm-module-modal-header border-0 pb-0">
                <h2 class="modal-title sm-module-modal-title mb-0" id="smEditModuleModalLabel">Edit Module</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body sm-module-modal-body pt-3">
                <form action="{{ route('subject-module.update', 0) }}" method="POST" id="smEditModuleForm" novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="module_form" value="edit">
                    <input type="hidden" name="sm_edit_module_pk" id="sm_edit_module_pk_hidden" value="{{ old('sm_edit_module_pk') }}">

                    <div class="d-flex flex-column gap-3 sm-module-modal-fields">
                        <div>
                            <label for="sm_edit_module_name" class="form-label">Module Name <span class="text-danger">*</span></label>
                            <input type="text"
                                name="module_name"
                                id="sm_edit_module_name"
                                class="form-control"
                                value="{{ old('module_form') === 'edit' ? old('module_name') : '' }}"
                                placeholder="eg. General Medicine"
                                required
                                aria-label="Module Name">
                            @if (old('module_form') === 'edit')
                            @error('module_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        <div>
                            <label for="sm_edit_active_inactive" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="active_inactive" id="sm_edit_active_inactive" class="form-select" required aria-label="Status">
                                <option value="" disabled>Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            @if (old('module_form') === 'edit')
                            @error('active_inactive')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 pt-4 sm-module-modal-actions">
                        <button type="button"
                            class="btn sm-btn-cancel px-4 py-2 rounded-2 fw-semibold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit"
                            class="btn btn-primary sm-btn-submit px-4 py-2 rounded-2 fw-semibold"
                            id="smEditModuleSubmit">Update Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
