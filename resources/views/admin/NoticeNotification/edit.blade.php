@extends('admin.layouts.master')

@section('title', 'Edit Notice notification')

<<<<<<< HEAD
@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css" rel="stylesheet">
<style>
    .notice-form-card {
        border-left: 4px solid #004a93;
    }
=======
@section('setup_content')
>>>>>>> 72c69fa0 (notice_notification category subcategory mapping work)

    .notice-form-card .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.375rem;
    }

    .notice-form-card .form-control,
    .notice-form-card .form-select {
        border-color: #cbd5e1;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .notice-form-card .form-control:focus,
    .notice-form-card .form-select:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.12);
    }

    .notice-form-card .form-control::placeholder {
        color: #94a3b8;
    }

    .notice-form-card .form-control:disabled {
        background-color: #f8fafc;
        cursor: not-allowed;
    }

    .notice-form-card .note-editor.note-frame {
        border-color: #cbd5e1;
        border-radius: 0.375rem;
        overflow: hidden;
    }

    .notice-form-card .note-toolbar {
        background: #f8fafc;
        border-bottom-color: #e2e8f0;
    }

    .notice-form-card .note-editing-area {
        min-height: 180px;
    }

    .notice-form-actions .btn {
        min-width: 6.5rem;
        font-weight: 500;
        border-radius: 0.375rem;
        padding: 0.5rem 1.25rem;
    }

    .notice-form-actions .btn-primary {
        background-color: #004a93;
        border-color: #004a93;
    }

    .notice-form-actions .btn-primary:hover,
    .notice-form-actions .btn-primary:focus {
        background-color: #003d7a;
        border-color: #003d7a;
    }

    .notice-form-actions .btn-outline-primary {
        color: #004a93;
        border-color: #004a93;
    }

    .notice-form-actions .btn-outline-primary:hover {
        background-color: #004a93;
        border-color: #004a93;
        color: #fff;
    }

    .notice-form-current-doc {
        font-size: 0.8125rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid notice-form-page">
    <x-breadcrum title="Notice notification List" />
    <x-session_message />

    <div class="card notice-form-card border-0 shadow-sm rounded-3">
        @if ($errors->any())
        <div class="card-body pb-0">
            <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <div>
                        <strong class="d-block mb-1">Please correct the following:</strong>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-4 pb-3 border-bottom border-light-subtle">
                <span class="badge rounded-1 bg-primary-subtle text-primary fw-semibold text-uppercase px-2 py-2">
                    <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Notice
                </span>
                <h4 class="card-title mb-0 fw-semibold text-dark">Edit Notice notification</h4>
            </div>

            <form method="POST" action="{{ route('admin.notice.update', $encId) }}" enctype="multipart/form-data" class="notice-form">
                @csrf
                @method('PUT')

                <div class="row g-3 g-lg-4">
                    {{-- Row 1: Title | Type | Sub Type --}}
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label" for="notice_title">
                            Notice Title <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                            name="notice_title"
                            id="notice_title"
                            class="form-control"
                            value="{{ old('notice_title', $notice->notice_title) }}"
                            placeholder="eg. Notice 01">
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label" for="notice_type">
                            Notice Type <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select name="notice_type" id="notice_type" class="form-control">
                            <option value="">Select the notice type</option>
                            @foreach($types as $t)
                            <option value="{{ $t }}" {{ old('notice_type', $notice->notice_type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label" for="noticeSubType">Notice Sub Type</label>
                        <select id="noticeSubType" class="form-control" disabled aria-label="Notice sub type">
                            <option value="">Select the sub type</option>
                        </select>
                    </div>

                    {{-- Row 2: Description --}}
                    <div class="col-12">
                        <label class="form-label" for="editor">
                            Description <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <textarea id="editor" name="description" class="form-control">{!! old('description', $notice->description) !!}</textarea>
                    </div>

<<<<<<< HEAD
                    {{-- Row 3: Display | Expiry --}}
                    <div class="col-md-6">
                        <label class="form-label" for="display_date">
                            Display Date <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="date"
                            name="display_date"
                            id="display_date"
                            class="form-control"
                            value="{{ old('display_date', $notice->display_date) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="expiry_date">
                            Expiry Date <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="date"
                            name="expiry_date"
                            id="expiry_date"
                            class="form-control"
                            value="{{ old('expiry_date', $notice->expiry_date) }}">
                    </div>

                    {{-- Row 4: Document | Target Audience --}}
                    <div class="col-md-6">
                        <label class="form-label" for="document">Document</label>
                        <input type="file"
                            name="document"
                            id="document"
                            class="form-control">
                        <div class="form-text text-muted small">
                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Optional — JPG, PNG, or PDF (max 5 MB).
                        </div>
                        @if($notice->document)
                        <div class="notice-form-current-doc mt-2">
                            <i class="bi bi-paperclip me-1 text-primary" aria-hidden="true"></i>
                            <a href="{{ asset('storage/'.$notice->document) }}" target="_blank" rel="noopener noreferrer" class="link-primary">
                                View current document
                            </a>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="targetAudience">
                            Target Audience <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select name="target_audience" id="targetAudience" class="form-control">
                            <option value="">Select the target audience</option>
                            @foreach($target as $t)
                            <option value="{{ $t }}" {{ old('target_audience', $notice->target_audience) == $t ? 'selected' : '' }}>
=======
                <div class="mb-3">
                    <label class="form-label">Notice Type (Category) <span class="text-danger">*</span></label>
                    <select name="notice_category_master_pk" id="noticeCategory" class="form-control" required>
                        <option value="">Select category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->pk }}"
                                @selected((string) ($resolvedCategoryPk ?? $notice->notice_category_master_pk) === (string) $cat->pk)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notice Sub Type (Subcategory)</label>
                    <select name="notice_subcategory_master_pk" id="noticeSubcategory" class="form-control">
                        <option value="">Select sub type</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Display Date <span class="text-danger">*</span></label>
                    <input type="date" name="display_date" class="form-control"
                           value="{{ $notice->display_date }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="expiry_date" class="form-control"
                           value="{{ $notice->expiry_date }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Document (Optional)</label>
                    <input type="file" name="document" class="form-control">
                    @if($notice->document)
                        <a href="{{ asset('storage/'.$notice->document) }}" target="_blank">View Document</a>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Audience <span class="text-danger">*</span></label>
                    <select name="target_audience" id="targetAudience" class="form-control">
                        <option value="">Select Target Audience</option>
                        @foreach($target as $t)
                            <option value="{{ $t }}" @if($notice->target_audience == $t) selected @endif>
>>>>>>> 72c69fa0 (notice_notification category subcategory mapping work)
                                {{ $t }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Conditional: Course --}}
                    <div class="col-md-6 {{ old('target_audience', $notice->target_audience) == 'Office trainee' ? '' : 'd-none' }}" id="courseBox">
                        <label class="form-label" for="courseSelect">Select Course</label>
                        <select name="course_master_pk" id="courseSelect" class="form-control">
                            <option value="">Select Course</option>
                        </select>
                    </div>

                    {{-- Actions --}}
                    <div class="col-12">
                        <div class="notice-form-actions d-flex flex-wrap justify-content-end gap-2 pt-3 mt-2 border-top border-light-subtle">
                            <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-primary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Update
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {
    var noticeSubTypesMap = {
        'Office order': ['General Order', 'Circular', 'Directive', 'Memorandum'],
        'Office notice': ['General Notice', 'Administrative Notice', 'Holiday Notice'],
        'Course notice': ['Academic Notice', 'Schedule Notice', 'Examination Notice'],
        'Personal': ['Individual', 'Confidential'],
        'Service related': ['Transfer', 'Posting', 'Promotion', 'Retirement']
    };

<<<<<<< HEAD
    function refreshNoticeSubType() {
        var type = $('#notice_type').val();
        var $sub = $('#noticeSubType');
        $sub.empty().append('<option value="">Select the sub type</option>');
        if (type && noticeSubTypesMap[type]) {
            noticeSubTypesMap[type].forEach(function(label) {
                $sub.append($('<option></option>').val(label).text(label));
            });
            $sub.prop('disabled', false);
        } else {
            $sub.prop('disabled', true);
        }
    }

    $('#notice_type').on('change', refreshNoticeSubType);
    refreshNoticeSubType();

    $('#editor').summernote({
=======
    function loadNoticeSubcategories(categoryId, selectedId) {
        const $sub = $('#noticeSubcategory');
        $sub.empty().append('<option value="">Select sub type</option>');
        if (!categoryId) {
            return;
        }
        $.get(`{{ url('admin/notice/subcategories') }}/${encodeURIComponent(categoryId)}`, function(res) {
            if (!res.status || !res.data) {
                return;
            }
            $.each(res.data, function(_, item) {
                const sel = selectedId && String(selectedId) === String(item.pk) ? 'selected' : '';
                $sub.append('<option value="' + item.pk + '" ' + sel + '>' + item.name + '</option>');
            });
        });
    }

    const initialCat = @json($resolvedCategoryPk ?? $notice->notice_category_master_pk);
    const initialSub = @json($notice->notice_subcategory_master_pk);
    if (initialCat) {
        loadNoticeSubcategories(initialCat, initialSub);
    }

    $('#noticeCategory').on('change', function() {
        loadNoticeSubcategories($(this).val(), null);
    });

   $('#editor').summernote({
>>>>>>> 72c69fa0 (notice_notification category subcategory mapping work)
        height: 200,
        placeholder: 'Write here...',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['font2', ['strikethrough', 'superscript', 'subscript']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'hr', 'pdfUpload']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        buttons: {
            pdfUpload: function(context) {
                var ui = $.summernote.ui;
                var button = ui.button({
                    contents: '<i class="note-icon-paperclip"></i> PDF',
                    tooltip: 'Upload PDF',
                    click: function() {
                        let fileInput = $('<input type="file" accept="application/pdf">');
                        fileInput.trigger('click');
                        fileInput.on('change', function() {
                            let file = this.files[0];
                            let formData = new FormData();
                            formData.append("file", file);
                            $.ajax({
                                url: "{{ route('admin.summernote.upload') }}",
                                type: "POST",
                                data: formData,
                                processData: false,
                                contentType: false,
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                success: function(data) {
                                    context.invoke('editor.insertText', data.location);
                                },
                                error: function(xhr) {
                                    alert("PDF Upload Failed: " + xhr.responseJSON.error);
                                }
                            });
                        });
                    }
                });
                return button.render();
            }
        }
    });

    let selectedCourse = "{{ $notice->course_master_pk }}";

    function loadCourses(preselect) {
        $.ajax({
            url: "{{ route('admin.notice.getCourses') }}",
            type: "GET",
            success: function(res) {
                $('#courseSelect').empty().append('<option value="">Select Course</option>');
                $.each(res.data, function(index, item) {
                    let selected = (preselect == item.pk) ? 'selected' : '';
                    $('#courseSelect').append(
                        `<option value="${item.pk}" ${selected}>${item.course_name}</option>`
                    );
                });
            }
        });
    }

    if ("{{ old('target_audience', $notice->target_audience) }}" === "Office trainee") {
        loadCourses(selectedCourse);
    }

    $('#targetAudience').on('change', function() {
        let val = $(this).val();
        if (val === 'Office trainee') {
            $('#courseBox').removeClass('d-none');
            loadCourses();
        } else {
            $('#courseBox').addClass('d-none');
            $('#courseSelect').empty();
        }
    });
});
</script>
@endsection
