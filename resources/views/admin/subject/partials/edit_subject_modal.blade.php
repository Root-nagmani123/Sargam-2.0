{{-- Edit Subject modal — looks ALIKE to Add; only the title, the submit caption
     and the pre-filled values differ (docs/new-design-index-page.md §3c). --}}
<div class="modal fade sm-subject-form-modal sm-edit-subject-modal" id="smEditSubjectModal" tabindex="-1"
    aria-labelledby="smEditSubjectModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-subject-modal-dialog">
        <div class="modal-content sm-subject-modal-content shadow-lg">
            <form action="{{ route('subject.update', 0) }}" method="POST" id="smEditSubjectForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="subject_form" value="edit">
                <input type="hidden" name="sm_edit_subject_pk" id="sm_edit_subject_pk_hidden" value="{{ old('sm_edit_subject_pk') }}">

                <div class="sm-modal-header">
                    <h2 class="sm-modal-title" id="smEditSubjectModalLabel">Edit Subject</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="sm-modal-body">
                    <div class="sm-field-card">
                        <div class="sm-field">
                            <label for="sm_edit_major_subject_name" class="sm-field-label">
                                Major Subject Name <span class="sm-req">*</span>
                            </label>
                            <input type="text"
                                name="major_subject_name"
                                id="sm_edit_major_subject_name"
                                class="sm-control"
                                value="{{ old('subject_form') === 'edit' ? old('major_subject_name') : '' }}"
                                placeholder="eg. General Medicine"
                                required
                                aria-label="Major Subject Name">
                            @if (old('subject_form') === 'edit')
                            @error('major_subject_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        <div class="sm-field">
                            <label for="sm_edit_short_name" class="sm-field-label">
                                Short Name <span class="sm-req">*</span>
                            </label>
                            <input type="text"
                                name="short_name"
                                id="sm_edit_short_name"
                                class="sm-control"
                                value="{{ old('subject_form') === 'edit' ? old('short_name') : '' }}"
                                placeholder="eg. GCM"
                                required
                                aria-label="Short Name">
                            @if (old('subject_form') === 'edit')
                            @error('short_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        <div class="sm-field">
                            <label for="sm_edit_status" class="sm-field-label">
                                Status <span class="sm-req">*</span>
                            </label>
                            <select name="status" id="sm_edit_status" class="sm-control" required aria-label="Status">
                                <option value="" disabled>Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="sm-modal-footer">
                    <button type="button" class="btn sm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sm-btn-submit" id="smEditSubjectSubmit">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>
