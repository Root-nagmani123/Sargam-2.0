@extends('admin.layouts.master')

@section('title', 'Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Notice /Memo Admin Management" />
    <x-session_message />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Memo / Notice</h4>
            <hr>
            <form action="{{ route('admin.memo-notice.store') }}" method="POST">
                @csrf
                <div class="row">
                      <div class="col-6">
                        <label for="course_id" class="form-label">Select Course (Optional)</label>
                        <div class="mb-3">
                            <select name="course_id" class="form-select">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ request('course_id') == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                            <small class="text-muted">Select an active course if this memo/notice is course-specific</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="director" class="form-label">Director's Name</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="director" name="director"
                                placeholder="Enter Director's Name" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="designation" class="form-label">Director's Designation</label>
                       <div class="mb-3">
                         <input type="text" class="form-control" id="designation" name="designation"
                            placeholder="Enter designation" required>
                       </div>
                    </div>
                    <div class="col-12">
                        <label for="content" class="form-label">Memo / Notice Content</label>
                        <textarea name="content" class="form-control" id="content" rows="3"></textarea>
                    </div>
                </div>
                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Summernote
    $('#content').summernote({
        tabsize: 2,
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph', 'align']],
            ['height', ['height']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'pdf']],
            ['view', ['fullscreen', 'codeview', 'help']],
            ['misc', ['undo', 'redo']]
        ],
        buttons: {
            pdf: function () {
                var ui = $.summernote.ui;

                return ui.button({
                    contents: '<i class="note-icon-file"></i> PDF',
                    tooltip: 'Upload PDF',
                    click: function () {
                        $('<input type="file" accept="application/pdf">')
                            .on('change', function (event) {
                                var file = event.target.files[0];
                                if (file) {
                                    uploadPDF(file);
                                }
                            })
                            .click();
                    }
                }).render();
            }
        }
    });

    function uploadPDF(file) {
        var formData = new FormData();
        formData.append('file', file);

        $.ajax({
            url: '/admin/upload-pdf',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $('#content').summernote('insertText', response.url);
            },
            error: function (xhr) {
                alert('Failed to upload PDF. Please try again.');
            }
        });
    }

    // Hide Summernote editors from tabs that shouldn't be active
    $('#tab-setup .note-editor').hide();
    $('#tab-communications .note-editor').hide();
    $('#tab-academics .note-editor').hide();
    $('#tab-material-management .note-editor').hide();
    
    // Only show the editor in active tab
    $('#home .note-editor').show();
});

// Additional scripts from original file
document.getElementById('texttype').addEventListener('change', function() {
    const value = this.value;
    document.getElementById('content-field').style.display = value === '1' ? 'block' : 'none';
    document.getElementById('pdf-upload-field').style.display = value === '2' ? 'block' : 'none';
    document.getElementById('website-url-field').style.display = value === '3' ? 'block' : 'none';
});

document.getElementById('txtpostion').addEventListener('change', function() {
    const value = this.value;
    if (value == 3 || value == 4 || value == 5 || value == 6 || value == 7) {
        var selectElement = document.getElementById('menucategory');

        // Loop through all options and disable them except for the one with value '0'
        for (var i = 0; i < selectElement.options.length; i++) {
            if (selectElement.options[i].value !== '0') {
                selectElement.options[i].disabled = true;
            } else {
                selectElement.options[i].disabled = false; // Ensure '0' is enabled
                selectElement.options[i].selected = true; // Select the '0' option
            }
        }
    }
});

function displayFileName() {
    const fileInput = document.getElementById('file-upload');
    const fileNameDiv = document.getElementById('file-name');

    if (fileInput.files && fileInput.files[0]) {
        const fileName = fileInput.files[0].name;
        fileNameDiv.textContent = 'Selected file: ' + fileName;
        fileNameDiv.style.display = 'block'; // Show the file name
    } else {
        fileNameDiv.style.display = 'none'; // Hide if no file is selected
    }
}
</script>
@endsection