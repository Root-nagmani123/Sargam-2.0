@extends('admin.layouts.master')

@section('title', 'Edit Notice')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Notice List" />
    <x-session_message />

    <div class="card">
        <div class="card-header">Edit Notice</div>

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
            <form method="POST" action="{{ route('admin.notice.update', $encId) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label>Notice Title</label>
                    <input type="text" name="notice_title" class="form-control"
                           value="{{ $notice->notice_title }}">
                </div>

                <div class="mb-3">
                    <label>Description</label>
                    <textarea id="editor" name="description" class="form-control">{!! $notice->description !!}</textarea>
                </div>

                <div class="mb-3">
                    <label>Notice Type</label>
                    <select name="notice_type" class="form-control">
                        <option value="">Select Notice Type</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}" @if($notice->notice_type == $t) selected @endif>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Display Date</label>
                    <input type="date" name="display_date" class="form-control"
                           value="{{ $notice->display_date }}">
                </div>

                <div class="mb-3">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control"
                           value="{{ $notice->expiry_date }}">
                </div>

                <div class="mb-3">
                    <label>Document (Optional)</label>
                    <input type="file" name="document" class="form-control">
                    @if($notice->document)
                        <a href="{{ asset('storage/'.$notice->document) }}" target="_blank">View Document</a>
                    @endif
                </div>

                <div class="mb-3">
                    <label>Target Audience</label>
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
                    <label>Select Course</label>
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
