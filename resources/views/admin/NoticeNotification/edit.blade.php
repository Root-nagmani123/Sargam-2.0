@extends('admin.layouts.master')

@section('title', 'Edit Notice notification')

@push('styles')
@include('admin.NoticeNotification.partials.module-styles')
@endpush

@section('content')
<div class="container-fluid notice-module-page">
    <x-breadcrum title="Notice notification List" />
    <x-session_message />

    <div class="card notice-card border-0 shadow-sm overflow-hidden border-start border-4 border-primary">
        @if ($errors->any())
        <div class="card-body pb-0">
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        <div class="card-body p-4 p-lg-5">
            <div class="notice-form-header mb-4">
                <h4 class="card-title mb-0 fw-bold">
                    Edit <span class="notice-title-highlight">Notice notification</span>
                </h4>
            </div>

            <form method="POST" action="{{ route('admin.notice.update', $encId) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    @include('admin.NoticeNotification.partials.notice-type-fields', ['notice' => $notice])

                    <div class="col-12">
                        <label class="form-label notice-form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="editor" name="description" class="form-control">{!! $notice->description !!}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label notice-form-label">Display Date <span class="text-danger">*</span></label>
                        <input type="date" name="display_date" class="form-control"
                            value="{{ $notice->display_date }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label notice-form-label">Expiry Date <span class="text-danger">*</span></label>
                        <input type="date" name="expiry_date" class="form-control"
                            value="{{ $notice->expiry_date }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label notice-form-label">Document (Optional)</label>
                        <input type="file" name="document" class="form-control">
                        @if($notice->document)
                        <a href="{{ asset('storage/'.$notice->document) }}" target="_blank" rel="noopener"
                            class="notice-doc-link d-inline-flex align-items-center gap-1 mt-2 text-primary">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>View Document
                        </a>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label notice-form-label">Target Audience <span class="text-danger">*</span></label>
                        <select name="target_audience" id="targetAudience" class="form-control">
                            <option value="">Select the target audience</option>
                            @foreach($target as $t)
                            <option value="{{ $t }}" @if($notice->target_audience == $t) selected @endif>
                                {{ $t }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3 {{ $notice->target_audience == 'Office trainee' ? '' : 'd-none' }}" id="courseBox">
                            <label class="form-label notice-form-label">Select Course</label>
                            <select name="course_master_pk" id="courseSelect" class="form-control">
                                <option value="">Select Course</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-4 border-top">
                    <a href="{{ route('admin.notice.index') }}" class="btn btn-notice-cancel btn-outline-primary rounded-3 px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-notice-save text-white rounded-3 px-4">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.js"></script>

<script>
$(document).ready(function() {

   $('#editor').summernote({
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
            pdfUpload: function (context) {
                var ui = $.summernote.ui;

                var button = ui.button({
                    contents: '<i class="note-icon-paperclip"></i> PDF',
                    tooltip: 'Upload PDF',
                    click: function () {

                        let fileInput = $('<input type="file" accept="application/pdf">');
                        fileInput.trigger('click');

                        fileInput.on('change', function () {

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
                                success: function (data) {
                                    let url = data.location;
                                    context.invoke('editor.insertText', url);
                                },
                                error: function (xhr) {
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

    function loadCourses(preselect = null) {
      
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

    if ("{{ $notice->target_audience }}" === "Office trainee") {
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

    @include('admin.NoticeNotification.partials.notice-type-scripts', [
        'selectedSubcategoryPk' => old('notice_subcategory_master_pk', $notice->notice_subcategory_master_pk),
    ])
});
</script>
@endsection
