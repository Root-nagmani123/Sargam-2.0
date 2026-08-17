{{-- Add Module modal (docs/new-design-index-page.md §3c) --}}
<div class="modal fade sm-module-form-modal" id="smAddModuleModal" tabindex="-1"
    aria-labelledby="smAddModuleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-module-modal-dialog">
        <div class="modal-content sm-module-modal-content shadow-lg">
            <form action="{{ route('subject-module.store') }}" method="POST" id="smAddModuleForm" novalidate>
                @csrf
                <input type="hidden" name="module_form" value="add">

                <div class="sm-modal-header">
                    <h2 class="sm-modal-title" id="smAddModuleModalLabel">Add Module</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="sm-modal-body">
                    <div class="sm-field-card">
                        <div class="sm-field">
                            <label for="sm_add_module_name" class="sm-field-label">
                                Module Name <span class="sm-req">*</span>
                            </label>
                            <input type="text"
                                name="module_name"
                                id="sm_add_module_name"
                                class="sm-control"
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

                        <div class="sm-field">
                            <label for="sm_add_active_inactive" class="sm-field-label">
                                Status <span class="sm-req">*</span>
                            </label>
                            <select name="active_inactive" id="sm_add_active_inactive" class="sm-control" required aria-label="Status">
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
                </div>

                <div class="sm-modal-footer">
                    <button type="button" class="btn sm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sm-btn-submit" id="smAddModuleSubmit">Create Module</button>
                </div>
            </form>
        </div>
    </div>
</div>
