{{-- Add Subject modal (docs/new-design-index-page.md §3c) --}}
<div class="modal fade sm-subject-form-modal" id="smAddSubjectModal" tabindex="-1"
    aria-labelledby="smAddSubjectModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered sm-subject-modal-dialog">
        <div class="modal-content sm-subject-modal-content shadow-lg">
            <form action="{{ route('subject.store') }}" method="POST" id="smAddSubjectForm" novalidate>
                @csrf
                <input type="hidden" name="subject_form" value="add">

                <div class="sm-modal-header">
                    <h2 class="sm-modal-title" id="smAddSubjectModalLabel">Add Subject</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="sm-modal-body">
                    <div class="sm-field-card">
                        <div class="sm-field">
                            <label for="sm_add_major_subject_name" class="sm-field-label">
                                Major Subject Name <span class="sm-req">*</span>
                            </label>
                            <input type="text"
                                name="major_subject_name"
                                id="sm_add_major_subject_name"
                                class="sm-control"
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

                        <div class="sm-field">
                            <label for="sm_add_short_name" class="sm-field-label">
                                Short Name <span class="sm-req">*</span>
                            </label>
                            <input type="text"
                                name="short_name"
                                id="sm_add_short_name"
                                class="sm-control"
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

                        <div class="sm-field">
                            <label for="sm_add_status" class="sm-field-label">
                                Status <span class="sm-req">*</span>
                            </label>
                            <select name="status" id="sm_add_status" class="sm-control" required aria-label="Status">
                                <option value="" disabled {{ old('subject_form') === 'add' && old('status') !== null && old('status') !== '' ? '' : 'selected' }}>Select Status</option>
                                <option value="1" {{ old('subject_form') === 'add' && (string) old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('subject_form') === 'add' && (string) old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="sm-modal-footer">
                    <button type="button" class="btn sm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sm-btn-submit" id="smAddSubjectSubmit">Create Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>
