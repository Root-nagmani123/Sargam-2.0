{{-- Add Module modal --}}
<div class="modal fade sm-module-form-modal" id="smAddModuleModal" tabindex="-1"
    aria-labelledby="smAddModuleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-module-modal-dialog">
        <div class="modal-content sm-module-modal-content border-0 shadow">
            <div class="modal-header sm-module-modal-header border-0 pb-0">
                <h2 class="modal-title sm-module-modal-title mb-0" id="smAddModuleModalLabel">Add Module</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body sm-module-modal-body pt-3">
                <form action="{{ route('subject-module.store') }}" method="POST" id="smAddModuleForm" novalidate>
                    @csrf
                    <input type="hidden" name="module_form" value="add">

                    <div class="d-flex flex-column gap-3 sm-module-modal-fields">
                        <div>
                            <label for="sm_add_module_name" class="form-label">Module Name <span class="text-danger">*</span></label>
                            <input type="text"
                                name="module_name"
                                id="sm_add_module_name"
                                class="form-control"
                                value="{{ old('module_form') === 'add' ? old('module_name') : '' }}"
                                placeholder="eg. General Medicine"
                                required
                                aria-label="Module Name">
                            @if (old('module_form') === 'add')
                            @error('module_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        <div>
                            <label for="sm_add_active_inactive" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="active_inactive" id="sm_add_active_inactive" class="form-select" required aria-label="Status">
                                <option value="" disabled {{ old('module_form') === 'add' && old('active_inactive') !== null && old('active_inactive') !== '' ? '' : 'selected' }}>Select Status</option>
                                <option value="1" {{ old('module_form') === 'add' && (string) old('active_inactive', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('module_form') === 'add' && (string) old('active_inactive') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @if (old('module_form') === 'add')
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
                            id="smAddModuleSubmit">Create Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
