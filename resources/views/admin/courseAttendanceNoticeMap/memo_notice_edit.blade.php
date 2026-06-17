@extends('admin.layouts.master')

@section('title', 'Edit Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Edit Memo / Notice" />
        <x-session_message />
        <div class="card" >
            <div class="card-body">

                <h4 class="card-title mb-3">Edit Memo / Notice</h4>
                <hr>

                <form action="{{ route('admin.memo-notice.update', $template->pk) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">

                        <!-- Select Course -->
                        <div class="col-6">
                            <label class="form-label">Select Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" class="form-select" required>
                                <option value="">All Courses</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}"
                                        {{ old('course_master_pk', $template->course_master_pk) == $course->pk ? 'selected' : '' }}>
                                        {{ $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select an active course if this memo is course-specific</small>
                        </div>

                        <!-- Title -->
                        <div class="col-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title"
                                value="{{ old('title', $template->title) }}" required>
                        </div>

                        <!-- Director Name -->
                        <div class="col-6">
                            <label class="form-label">Director's Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="director"
                                value="{{ old('director', $template->director_name) }}" required>
                        </div>

                        <!-- Designation -->
                        <div class="col-3">
                            <label class="form-label">Director's Designation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="designation"
                                value="{{ old('designation', $template->director_designation) }}" required>
                        </div>
                         <div class="col-3">
                            <label class="form-label">Memo / Notice Type <span class="text-danger">*</span></label>
                            <select name="memo_notice_type" class="form-select" required>
                                <option value="" disabled selected>Select Type</option>
                                <option value="Memo" {{ old('memo_notice_type', $template->memo_notice_type) == 'Memo' ? 'selected' : '' }}>Memo</option>
                                <option value="Notice" {{ old('memo_notice_type', $template->memo_notice_type) == 'Notice' ? 'selected' : '' }}>Notice</option>
                                <option value="Discipline Memo" {{ old('memo_notice_type', $template->memo_notice_type) == 'Discipline Memo' ? 'selected' : '' }}>Discipline Memo</option>
                            </select>
                        </div>

                        <!-- Content -->
                        <div class="col-12">
                            <label class="form-label">Memo / Notice Content <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" class="form-control" required>
                            {{ old('content', $template->content) }}
                        </textarea>
                        </div>

                        <!-- Signature Image -->
                        <div class="col-md-6">
                            <label class="form-label">Signature Image <small class="text-muted">(jpeg/png/gif, max 2MB)</small></label>
                            @if($template->signature_image)
                                <div class="mb-2 d-flex align-items-center gap-3">
                                    <img src="{{ Storage::url($template->signature_image) }}" alt="Current Signature" style="max-height:60px;border:1px solid #dee2e6;padding:4px;border-radius:4px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_signature" value="1" id="removeSignature">
                                        <label class="form-check-label text-danger" for="removeSignature">Remove signature</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" name="signature_image" class="form-control" accept="image/jpeg,image/png,image/gif">
                            <small class="text-muted">Upload a new image to replace the existing signature.</small>
                        </div>

                    </div>

                    <hr>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('admin.memo-notice.index') }}" class="btn btn-secondary">Back</a>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- Summernote CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
@endsection



@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        $(document).ready(function() {

            /** Initialize Summernote **/
            $('#content').summernote({
                tabsize: 2,
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph', 'align']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video', 'pdf']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                ],
                buttons: {
                    pdf: function() {
                        var ui = $.summernote.ui;

                        return ui.button({
                            contents: '<i class="note-icon-file"></i> PDF',
                            tooltip: 'Upload PDF',
                            click: function() {
                                $('<input type="file" accept="application/pdf">')
                                    .on('change', function(e) {
                                        var file = e.target.files[0];
                                        if (file) uploadPDF(file);
                                    })
                                    .click();
                            }
                        }).render();
                    }
                }
            });

            /** PDF Upload Function **/
            function uploadPDF(file) {
                var formData = new FormData();
                formData.append('file', file);

                $.ajax({
                    url: '/admin/upload-pdf',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#content').summernote('insertText', response.url);
                    },
                    error: function() {
                        alert('PDF upload failed. Try again.');
                    }
                });
            }

        });
    </script>
@endsection
