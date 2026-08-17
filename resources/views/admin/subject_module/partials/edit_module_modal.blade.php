{{-- Edit Module modal — looks ALIKE to Add; only the title, the submit caption
     and the pre-filled values differ (docs/new-design-index-page.md §3c). --}}
<div class="modal fade sm-module-form-modal sm-edit-module-modal" id="smEditModuleModal" tabindex="-1"
    aria-labelledby="smEditModuleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-module-modal-dialog">
        <div class="modal-content sm-module-modal-content shadow-lg">
            <form action="{{ route('subject-module.update', 0) }}" method="POST" id="smEditModuleForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="module_form" value="edit">
                <input type="hidden" name="sm_edit_module_pk" id="sm_edit_module_pk_hidden" value="{{ old('sm_edit_module_pk') }}">

                <div class="sm-modal-header">
                    <h2 class="sm-modal-title" id="smEditModuleModalLabel">Edit Module</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="sm-modal-body">
                    <div class="sm-field-card">
                        <div class="sm-field">
                            <label for="sm_edit_module_name" class="sm-field-label">
                                Module Name <span class="sm-req">*</span>
                            </label>
                            <input type="text"
                                name="module_name"
                                id="sm_edit_module_name"
                                class="sm-control"
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

                        <div class="sm-field">
                            <label for="sm_edit_active_inactive" class="sm-field-label">
                                Status <span class="sm-req">*</span>
                            </label>
                            <select name="active_inactive" id="sm_edit_active_inactive" class="sm-control" required aria-label="Status">
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
                </div>

                <div class="sm-modal-footer">
                    <button type="button" class="btn sm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sm-btn-submit" id="smEditModuleSubmit">Update Module</button>
                </div>
            </form>
        </div>
    </div>
</div>
