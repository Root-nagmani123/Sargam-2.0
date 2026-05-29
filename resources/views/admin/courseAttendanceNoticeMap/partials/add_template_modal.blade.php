{{-- Add Template modal (requires $courses) --}}
<div class="modal fade mnm-add-template-modal" id="mnmAddTemplateModal" tabindex="-1"
    aria-labelledby="mnmAddTemplateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable mnm-add-template-dialog">
        <div class="modal-content mnm-add-template-content border-0 shadow">
            <div class="modal-header mnm-add-template-header border-0">
                <h2 class="modal-title mnm-wizard-title mb-0" id="mnmAddTemplateModalLabel">Add Template</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body mnm-add-template-body">
                <form action="{{ route('admin.memo-notice.store') }}" method="POST" id="mnmAddTemplateForm" novalidate>
                    @csrf
                    <input type="hidden" name="template_form" value="add">

                    <div class="row g-3 mnm-add-template-fields">
                        <div class="col-12">
                            <label for="mnm_template_course_master_pk" class="form-label">Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" id="mnm_template_course_master_pk" class="form-select" required aria-label="Course">
                                <option value="" disabled {{ old('course_master_pk') ? '' : 'selected' }}>Select the course</option>
                                @foreach ($courses as $course)
                                <option value="{{ $course->pk }}" {{ (string) old('course_master_pk') === (string) $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('course_master_pk')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="mnm_template_memo_notice_type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="memo_notice_type" id="mnm_template_memo_notice_type" class="form-select" required aria-label="Type">
                                <option value="" disabled {{ old('memo_notice_type') ? '' : 'selected' }}>Select the type</option>
                                <option value="Memo" {{ old('memo_notice_type') === 'Memo' ? 'selected' : '' }}>Memo</option>
                                <option value="Notice" {{ old('memo_notice_type') === 'Notice' ? 'selected' : '' }}>Notice</option>
                                <option value="Discipline Memo" {{ old('memo_notice_type') === 'Discipline Memo' ? 'selected' : '' }}>Discipline Memo</option>
                            </select>
                            @error('memo_notice_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="mnm_template_title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="mnm_template_title" class="form-control"
                                value="{{ old('title') }}" placeholder="eg. Notice 01" required aria-label="Title">
                            @error('title')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="mnm_template_director" class="form-label">Director's Name <span class="text-danger">*</span></label>
                            <input type="text" name="director" id="mnm_template_director" class="form-control"
                                value="{{ old('director') }}" placeholder="Enter Director's Name" required aria-label="Director's Name">
                            @error('director')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="mnm_template_designation" class="form-label">Director's Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="mnm_template_designation" class="form-control"
                                value="{{ old('designation') }}" placeholder="Enter designation" required aria-label="Director's Designation">
                            @error('designation')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="mnm_add_content" class="form-label">Description <span class="text-danger">*</span></label>
                            <div class="mnm-summernote-wrap">
                                <textarea name="content" class="form-control" id="mnm_add_content" rows="4" required aria-label="Description">{{ old('content') }}</textarea>
                            </div>
                            @error('content')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 pt-4 mnm-add-template-actions">
                        <button type="button"
                            class="btn mnm-btn-cancel px-4 py-2 rounded-2 fw-semibold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit"
                            class="btn btn-primary mnm-btn-submit px-4 py-2 rounded-2 fw-semibold"
                            id="mnmAddTemplateSubmit">Add Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
