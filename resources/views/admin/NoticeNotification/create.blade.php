@extends('admin.layouts.master')

@section('title', 'Create Notice notification')

@section('setup_content')


<div class="container-fluid">
    <x-breadcrum title="Notice List" />
    <x-session_message />
    <div class="card">
        <div class="card-header">
            Create NotNotice notificationice
        </div>
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
            <form method="POST" action="{{ route('admin.notice.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label>Notice Title <span class="text-danger">*</span></label>
                    <input type="text" name="notice_title" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Description <span class="text-danger">*</span></label>
                    <textarea id="editor" name="description" class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label>Notice Type <span class="text-danger">*</span></label>
                    <select name="notice_type" class="form-control">
                        <option value="">Select Notice Type</option>
                        @foreach($types as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Display Date <span class="text-danger">*</span></label>
                   <input type="date" name="display_date" class="form-control">
                 </div>

                <div class="mb-3">
                    <label>Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="expiry_date" class="form-control" >
                </div>
    
                <div class="mb-3">
                    <label>Upload Document</label>
                    <input type="file" name="document" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Target Audience <span class="text-danger">*</span></label>
                    <select name="target_audience" id="targetAudience" class="form-control">
                        <option value="">Select Target Audience</option>
                        @foreach($target as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="mb-3 d-none" id="courseBox">
                    <label>Select Course</label>
                    <select name="course_master_pk" id="courseSelect" class="form-control">
                        <option value="">Select Course</option>
                    </select>
                </div>

                <button class="btn btn-primary">Save</button>
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

                                    // context.invoke('insertLink', url, file.name);

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

    $('#targetAudience').on('change', function() {
        let val = $(this).val();

        if (val === 'Office trainee') {

            $('#courseBox').removeClass('d-none');  

            $.ajax({
                url: "{{ route('admin.notice.getCourses') }}",
                type: "GET",
                success: function(res) {
                    $('#courseSelect').empty().append('<option value="">Select Course</option>');

                    $.each(res.data, function(index, item) {
                        $('#courseSelect').append(
                            `<option value="${item.pk}">${item.course_name}</option>`
                        );
                    });
                }
            });

        } else {
            $('#courseBox').addClass('d-none');
            $('#courseSelect').empty();
        }
    });

});
</script>
@endsection