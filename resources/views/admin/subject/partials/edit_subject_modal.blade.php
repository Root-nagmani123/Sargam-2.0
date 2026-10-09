{{-- Edit Subject modal — filled by subject_modals_scripts (populateEditForm). --}}
@php $smEditIsOld = old('subject_form') === 'edit'; @endphp
<div class="modal fade mst-modal sm-subject-form-modal sm-edit-subject-modal" id="smEditSubjectModal" tabindex="-1"
     aria-labelledby="smEditSubjectModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('subject.update', 0) }}" method="POST" id="smEditSubjectForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="subject_form" value="edit">
                <input type="hidden" name="sm_edit_subject_pk" id="sm_edit_subject_pk_hidden" value="{{ old('sm_edit_subject_pk') }}">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="smEditSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="sm_edit_major_subject_name" class="mst-form-label d-block">
                                Major Subject Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="major_subject_name" id="sm_edit_major_subject_name"
                                   class="form-control mst-control @if ($smEditIsOld) @error('major_subject_name') is-invalid @enderror @endif"
                                   value="{{ $smEditIsOld ? old('major_subject_name') : '' }}"
                                   placeholder="eg. General Medicine" maxlength="255"
                                   required aria-required="true">
                            @if ($smEditIsOld)
                                @error('major_subject_name')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="sm_edit_short_name" class="mst-form-label d-block">
                                Short Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="short_name" id="sm_edit_short_name"
                                   class="form-control mst-control @if ($smEditIsOld) @error('short_name') is-invalid @enderror @endif"
                                   value="{{ $smEditIsOld ? old('short_name') : '' }}"
                                   placeholder="eg. GCM" maxlength="100"
                                   required aria-required="true">
                            @if ($smEditIsOld)
                                @error('short_name')
                                    <span class="mst-field-error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div class="mb-0">
                            <label for="sm_edit_status" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="status" id="sm_edit_status"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="" disabled>Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="smEditSubjectSubmit">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>
