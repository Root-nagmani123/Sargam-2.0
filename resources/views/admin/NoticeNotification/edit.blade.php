@extends('admin.layouts.master')

@section('title', 'Edit Notice notification')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Notice notification List" />
    <x-session_message />

    <div class="card" style="border-left: 4px solid #004a93;">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-body">
        <h4 class="card-title mb-0">Edit Notice notification</h4>
        <hr>
            <form method="POST" action="{{ route('admin.notice.update', $encId) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Notice Title <span class="text-danger">*</span></label>
                    <input type="text" name="notice_title" class="form-control"
                           value="{{ $notice->notice_title }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea id="editor" name="description" class="form-control">{!! $notice->description !!}</textarea>
                </div>

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
                                {{ $t }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- COURSE BOX --}}
                <div class="mb-3 {{ $notice->target_audience == 'Office trainee' ? '' : 'd-none' }}" id="courseBox">
                    <label class="form-label">Select Course</label>
                    <select name="course_master_pk" id="courseSelect" class="form-control">
                        <option value="">Select Course</option>
                    </select>
                </div>

                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.notice.index') }}" class="btn btn-secondary">Cancel</a>

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
        height: 200,
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

                // create button
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

                                    // Insert link inside editor
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

    let selectedCourse = "{{ $notice->course_master_pk }}"; // Saved course in DB

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

    // On page load → if Office trainee selected, load courses
    if ("{{ $notice->target_audience }}" === "Office trainee") {
        loadCourses(selectedCourse);
    }

    // When changing target audience
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
