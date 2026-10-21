{{-- Add Subject modal --}}
<div class="modal fade sm-subject-form-modal" id="smAddSubjectModal" tabindex="-1"
    aria-labelledby="smAddSubjectModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-subject-modal-dialog">
        <div class="modal-content sm-subject-modal-content border-0 shadow">
            <div class="modal-header sm-subject-modal-header border-0 pb-0">
                <h2 class="modal-title sm-subject-modal-title mb-0" id="smAddSubjectModalLabel">Add Subject</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body sm-subject-modal-body pt-3">
                <form action="{{ route('subject.store') }}" method="POST" id="smAddSubjectForm" novalidate>
                    @csrf
                    <input type="hidden" name="subject_form" value="add">

                    <div class="d-flex flex-column gap-3 sm-subject-modal-fields">
                        <div>
                            <label for="sm_add_major_subject_name" class="form-label">Major Subject Name <span class="text-danger">*</span></label>
                            <input type="text"
                                name="major_subject_name"
                                id="sm_add_major_subject_name"
                                class="form-control"
                                value="{{ old('subject_form') === 'add' ? old('major_subject_name') : '' }}"
                                placeholder="eg. General Medicine"
                                required
                                aria-label="Major Subject Name">
                            @if (old('subject_form') === 'add')
                            @error('major_subject_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        <div>
                            <label for="sm_add_short_name" class="form-label">Short Name <span class="text-danger">*</span></label>
                            <input type="text"
                                name="short_name"
                                id="sm_add_short_name"
                                class="form-control"
                                value="{{ old('subject_form') === 'add' ? old('short_name') : '' }}"
                                placeholder="eg. GCM"
                                required
                                aria-label="Short Name">
                            @if (old('subject_form') === 'add')
                            @error('short_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        <div>
                            <label for="sm_add_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="sm_add_status" class="form-select" required aria-label="Status">
                                <option value="" disabled {{ old('subject_form') === 'add' && old('status') !== null && old('status') !== '' ? '' : 'selected' }}>Select Status</option>
                                <option value="1" {{ old('subject_form') === 'add' && (string) old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('subject_form') === 'add' && (string) old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 pt-4 sm-subject-modal-actions">
                        <button type="button"
                            class="btn sm-btn-cancel px-4 py-2 rounded-2 fw-semibold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit"
                            class="btn btn-primary sm-btn-submit px-4 py-2 rounded-2 fw-semibold"
                            id="smAddSubjectSubmit">Create Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
