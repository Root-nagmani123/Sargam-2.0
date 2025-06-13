@extends('admin.layouts.master')

@section('title', 'Front Page - Sargam | Lal Bahadur')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Front Page</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="#">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">Front Page</span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Front Page Form -->
        <div class="card mt-3">
            <div class="card-body">
                <h4 class="card-title mb-3">Create Front Page</h4>
                <hr>

                <form method="POST" action="{{ route('admin.frontpage.save') }}" id="cadreForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Course Duration -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Course Start Date</label>
                                <input type="date" name="course_start_date" class="form-control"
                                    value="{{ old('course_start_date', isset($data) ? $data->course_start_date : '') }}"
                                    required>

                                <label class="form-label mt-3 fw-semibold">Course End Date</label>
                                <input type="date" name="course_end_date" class="form-control"
                                    value="{{ old('course_end_date', isset($data) ? $data->course_end_date : '') }}"
                                    required>
                            </div>
                        </div>

                        <!-- Registration Duration -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Registration Start Date</label>
                                <input type="date" name="registration_start_date" class="form-control"
                                    value="{{ old('registration_start_date', isset($data) ? $data->registration_start_date : '') }}"
                                    required>

                                <label class="form-label mt-3 fw-semibold">Registration End Date</label>
                                <input type="date" name="registration_end_date" class="form-control"
                                    value="{{ old('registration_end_date', isset($data) ? $data->registration_end_date : '') }}"
                                    required>
                            </div>
                        </div>

                        <!-- Course Title -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Course Title</label>
                                <input type="text" name="course_title" class="form-control"
                                    value="{{ old('course_title', isset($data) ? $data->course_title : '') }}">
                            </div>
                        </div>

                        <!-- Coordinator Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Coordinator Name</label>
                                <input type="text" name="coordinator_name" class="form-control"
                                    value="{{ old('coordinator_name', isset($data) ? $data->coordinator_name : '') }}">
                            </div>
                        </div>

                        <!-- Coordinator Designation -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Coordinator Designation</label>
                                <input type="text" name="coordinator_designation" class="form-control"
                                    value="{{ old('coordinator_designation', isset($data) ? $data->coordinator_designation : '') }}">
                            </div>
                        </div>

                        <!-- Coordinator Info -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Coordinator Info</label>
                                <input type="text" name="coordinator_info" class="form-control"
                                    value="{{ old('coordinator_info', isset($data) ? $data->coordinator_info : '') }}">
                            </div>
                        </div>

                        <!-- Coordinator Signature -->
                        <div class="col-md-6 mt-3">
                            <label class="form-label fw-semibold">Coordinator Signature (Image)</label>
                            <input type="file" name="coordinator_signature" class="form-control">
                            @if (!empty($data) && !empty($data->coordinator_signature))
                                <img src="{{ asset($data->coordinator_signature) }}" alt="Signature" height="50"
                                    class="mt-2">
                            @endif
                        </div>

                        <!-- Important Updates -->
                        <div class="col-12 mt-4">
                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="important_updates">Important Updates</label>
                                <textarea class="form-control text-dark" rows="5" name="important_updates" id="important_updates">{!! old('important_updates', isset($data) ? $data->important_updates : '') !!}</textarea>
                            </div>
                        </div>


                        <!-- Submit Button -->
                        <div class="col-12 text-end">
                            <button class="btn btn-primary" type="submit">
                                <i class="material-icons menu-icon">send</i> Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Summernote Initialization -->
    <script>
        $.noConflict();
        jQuery(document).ready(function($) {
            $('#important_updates').summernote({
                tabsize: 2,
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript',
                        'subscript', 'clear'
                    ]],
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
                    pdf: function() {
                        var ui = $.summernote.ui;
                        return ui.button({
                            contents: '<i class="note-icon-file"></i> PDF',
                            tooltip: 'Upload PDF',
                            click: function() {
                                $('<input type="file" accept="application/pdf">')
                                    .on('change', function(event) {
                                        var file = event.target.files[0];
                                        if (file) {
                                            uploadPDF(file);
                                        }
                                    }).click();
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
                    success: function(response) {
                        $('#important_updates').summernote('insertText', response.url);
                    },
                    error: function() {
                        alert('Failed to upload PDF. Please try again.');
                    }
                });
            }
        });
    </script>
@endsection
