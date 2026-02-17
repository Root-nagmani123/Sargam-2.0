@extends('admin.layouts.master') {{-- this layout MUST contain the tabs --}}

@section('title', 'Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
    <div class="container-fluid py-4">
        <x-breadcrum title="Notice /Memo Admin Management" />
        <x-session_message />
        
        <div class="card shadow-sm border-0" style="border-left: 4px solid #004a93;">
            <div class="card-header bg-white border-0 pb-0 pt-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 fw-semibold text-dark">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>Create Memo / Notice
                    </h4>
                </div>
            </div>
            <div class="card-body px-4 py-4">
                <form action="{{ route('admin.memo-notice.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label for="course_master_pk" class="form-label fw-semibold">
                                Select Course <span class="text-danger">*</span>
                            </label>
                            <select name="course_master_pk" id="course_master_pk" class="form-select form-select-lg" required>
                                <option value="" disabled selected>Choose a course...</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                Please select a course.
                            </div>
                            <small class="form-text text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>Select an active course if this memo/notice is course-specific
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label for="title" class="form-label fw-semibold">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="title" class="form-control form-control-lg" 
                                placeholder="Enter Memo / Notice Title" required>
                            <div class="invalid-feedback">
                                Please provide a title.
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label for="director" class="form-label fw-semibold">
                                Director's Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="director" id="director" class="form-control form-control-lg" 
                                placeholder="Enter Director's Name" required>
                            <div class="invalid-feedback">
                                Please provide the director's name.
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label for="designation" class="form-label fw-semibold">
                                Director's Designation <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="designation" id="designation" class="form-control form-control-lg" 
                                placeholder="Enter designation" required>
                            <div class="invalid-feedback">
                                Please provide the director's designation.
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label for="memo_notice_type" class="form-label fw-semibold">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select name="memo_notice_type" id="memo_notice_type" class="form-select form-select-lg" required>
                                <option value="" disabled selected>Select...</option>
                                <option value="Memo">Memo</option>
                                <option value="Notice">Notice</option>
                                <option value="Discipline Memo">Discipline Memo</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a type.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="content" class="form-label fw-semibold">
                                Message <span class="text-danger">*</span>
                            </label>
                            <textarea name="content" class="form-control" id="content" rows="5" required></textarea>
                            <div class="invalid-feedback">
                                Please provide the message content.
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="{{ route('admin.memo-notice.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-check-circle me-2"></i>Submit
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- Summernote CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
@endsection


@section('scripts')
    @include('components.jquery-3-6')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        $(document).ready(function() {
            // Bootstrap form validation
            (function() {
                'use strict';
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();

            // Initialize Summernote with enhanced options
            $('#content').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                placeholder: 'Enter your message content here...',
                callbacks: {
                    onInit: function() {
                        // Ensure textarea is marked as valid when Summernote content is added
                        var $content = $('#content');
                        $content.on('summernote.change', function() {
                            var content = $(this).summernote('code');
                            if (content && content.trim() !== '' && content !== '<p><br></p>') {
                                $content[0].setCustomValidity('');
                            } else {
                                $content[0].setCustomValidity('Please provide the message content.');
                            }
                        });
                    }
                }
            });

            // Add focus effects to form controls
            $('.form-control, .form-select').on('focus', function() {
                $(this).addClass('border-primary shadow-sm');
            }).on('blur', function() {
                $(this).removeClass('border-primary shadow-sm');
            });

        });
    </script>
@endsection
