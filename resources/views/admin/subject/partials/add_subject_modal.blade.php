{{-- Add Subject modal --}}
@php
    // One value drives the Status select. The old markup left "Select Status"
    // selected on first open but "Active" after a close/reopen (the hidden
    // handler resets to 1), and the controller defaults a missing status to 1 —
    // so Active is the default everywhere now.
    $smAddIsOld = old('subject_form') === 'add';
    $smAddStatus = $smAddIsOld ? (string) old('status', '1') : '1';
@endphp
<div class="modal fade mst-modal sm-subject-form-modal" id="smAddSubjectModal" tabindex="-1"
     aria-labelledby="smAddSubjectModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('subject.store') }}" method="POST" id="smAddSubjectForm" novalidate>
                @csrf
                <input type="hidden" name="subject_form" value="add">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="smAddSubjectModalLabel">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="sm_add_major_subject_name" class="mst-form-label d-block">
                                Major Subject Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="major_subject_name" id="sm_add_major_subject_name"
                                   class="form-control mst-control @if ($smAddIsOld) @error('major_subject_name') is-invalid @enderror @endif"
                                   value="{{ $smAddIsOld ? old('major_subject_name') : '' }}"
                                   placeholder="eg. General Medicine" maxlength="255"
                                   required aria-required="true">
                            @if ($smAddIsOld)
                                @error('major_subject_name')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="sm_add_short_name" class="mst-form-label d-block">
                                Short Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="short_name" id="sm_add_short_name"
                                   class="form-control mst-control @if ($smAddIsOld) @error('short_name') is-invalid @enderror @endif"
                                   value="{{ $smAddIsOld ? old('short_name') : '' }}"
                                   placeholder="eg. GCM" maxlength="100"
                                   required aria-required="true">
                            @if ($smAddIsOld)
                                @error('short_name')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-0">
                            <label for="sm_add_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="status" id="sm_add_status"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="" disabled>Select Status</option>
                                <option value="1" @selected($smAddStatus === '1')>Active</option>
                                <option value="0" @selected($smAddStatus === '0')>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="smAddSubjectSubmit">Create Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>
