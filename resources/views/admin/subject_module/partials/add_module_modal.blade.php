{{-- Add Module modal --}}
@php
    // One value drives the Status select. The old markup left "Select Status"
    // selected on first open — and SubjectModuleController::store requires
    // active_inactive, so a first submit failed validation — but "Active" after
    // a close/reopen (the hidden handler resets to 1). Active is the default
    // everywhere now.
    $smAddIsOld = old('module_form') === 'add';
    $smAddStatus = $smAddIsOld ? (string) old('active_inactive', '1') : '1';
@endphp
<div class="modal fade mst-modal sm-module-form-modal" id="smAddModuleModal" tabindex="-1"
     aria-labelledby="smAddModuleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('subject-module.store') }}" method="POST" id="smAddModuleForm" novalidate>
                @csrf
                <input type="hidden" name="module_form" value="add">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="smAddModuleModalLabel">Add Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="sm_add_module_name" class="mst-form-label d-block">
                                Module Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="module_name" id="sm_add_module_name"
                                   class="form-control mst-control @if ($smAddIsOld) @error('module_name') is-invalid @enderror @endif"
                                   value="{{ $smAddIsOld ? old('module_name') : '' }}"
                                   placeholder="eg. General Medicine" maxlength="200"
                                   required aria-required="true">
                            @if ($smAddIsOld)
                                @error('module_name')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-0">
                            <label for="sm_add_active_inactive" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="sm_add_active_inactive"
                                    class="form-select mst-control mst-searchable @if ($smAddIsOld) @error('active_inactive') is-invalid @enderror @endif"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="" disabled>Select Status</option>
                                <option value="1" @selected($smAddStatus === '1')>Active</option>
                                <option value="0" @selected($smAddStatus === '0')>Inactive</option>
                            </select>
                            @if ($smAddIsOld)
                                @error('active_inactive')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="smAddModuleSubmit">Create Module</button>
                </div>
            </form>
        </div>
    </div>
</div>
