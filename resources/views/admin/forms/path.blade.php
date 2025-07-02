@extends('admin.layouts.master')

@section('title', 'Path Page - Sargam | Lal Bahadur')

@section('head')
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet"> --}}
@endsection

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
        <div class="container-fluid">
            <!-- Page Header -->
            <x-breadcrum title="Path Page" />
        <x-session_message />

            <!-- Form Card -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <h4 class="card-title mb-3">Create Path Page</h4>
                    <hr>
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.path.page.save') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Registration Section -->

                        @php
                            $today = date('Y-m-d');
                        @endphp

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="registration_start_date" class="form-label fw-semibold">Registration Start
                                    Date</label>
                                <input type="date" name="registration_start_date" id="registration_start_date"
                                    class="form-control" min="{{ $today }}"
                                    value="{{ old('registration_start_date', $pathPage->registration_start_date ?? '') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="registration_end_date" class="form-label fw-semibold">Registration End
                                    Date</label>
                                <input type="date" name="registration_end_date" id="registration_end_date"
                                    class="form-control"
                                    min="{{ old('registration_start_date', $pathPage->registration_start_date ?? $today) }}"
                                    value="{{ old('registration_end_date', $pathPage->registration_end_date ?? '') }}">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="exemption_start_date" class="form-label fw-semibold">Exemption Start
                                    Date</label>
                                <input type="date" name="exemption_start_date" id="exemption_start_date"
                                    class="form-control" min="{{ $today }}"
                                    value="{{ old('exemption_start_date', $pathPage->exemption_start_date ?? '') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="exemption_end_date" class="form-label fw-semibold">Exemption End Date</label>
                                <input type="date" name="exemption_end_date" id="exemption_end_date" class="form-control"
                                    min="{{ old('exemption_start_date', $pathPage->exemption_start_date ?? $today) }}"
                                    value="{{ old('exemption_end_date', $pathPage->exemption_end_date ?? '') }}">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Register for Foundation Course <span
                                    class="text-danger">*</span></label>
                            <textarea name="register_course" class="form-control summernote" required>{{ old('register_course', $pathPage->register_course ?? '') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Apply for Exemption <span
                                    class="text-danger">*</span></label>
                            <textarea name="apply_exemption" class="form-control summernote" required>{{ old('apply_exemption', $pathPage->apply_exemption ?? '') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Already Registered <span
                                    class="text-danger">*</span></label>
                            <textarea name="already_registered" class="form-control summernote" required>{{ old('already_registered', $pathPage->already_registered ?? '') }}</textarea>
                        </div>

                        <!-- FAQs Section -->
                        <hr>
                        <h5 class="fw-bold mt-4 mb-3">FAQs Section</h5>

                        <div id="faq-wrapper">
                            @if (!empty($pathPage) && $pathPage->faqs->count())
                                @foreach ($pathPage->faqs as $faq)
                                    <div class="row mb-3" data-id="{{ $faq->id }}">
                                        <div class="col-5">
                                            <label for="" class="form-label">Accordian Header</label>
                                            <input type="text" name="faq_header[]" class="form-control"
                                                value="{{ $faq->header }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="" class="form-label">Accordian Content</label>
                                            <textarea name="faq_content[]" class="form-control" rows="2">{{ $faq->content }}</textarea>
                                        </div>
                                        <div class="col-1 text-end align-self-end">
                                            <button type="button" class="btn btn-sm btn-danger delete-faq-btn"
                                                data-id="{{ $faq->id }}">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <!-- <div class="row faq-item align-items-end">
                                            <div class="col-md-5 mb-3">
                                                <label class="form-label fw-semibold">Accordion Header</label>
                                                <input type="text" name="faq_header[]" class="form-control">
                                            </div>
                                            <div class="col-md-5 mb-3">
                                                <label class="form-label fw-semibold">Accordion Content</label>
                                                <textarea name="faq_content[]" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div> -->
                                <div class="row mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Accordion Header</label>
                                        <input type="text" name="faq_header[]" class="form-control">
                                    </div>
                                    <div class="col-md-5 ">
                                        <label class="form-label">Accordion Content</label>
                                        <textarea name="faq_content[]" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-faq">+ Add
                                FAQ</button>
                        </div>
                        <hr>
                        <!-- Submit -->
                        <div class="mb-3 gap-2 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary d-flex align-items-center px-4 gap-2">
                                <i class="material-icons menu-icon">send</i> Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection

    @section('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

        <script>
            $(document).ready(function() {
                $('.summernote').summernote({
                    height: 250,
                    tabsize: 2,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview']],
                        ['misc', ['undo', 'redo']]
                    ]
                });

                $('#add-faq').on('click', function() {
                    const newFaq = `
                    <div class="row faq-item mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Accordion Header</label>
                            <input type="text" name="faq_header[]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Accordion Content</label>
                            <textarea name="faq_content[]" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-1 text-end align-self-end">
                            <button type="button" class="btn btn-sm btn-danger remove-faq">Remove</button>
                        </div>
                    </div>`;
                    $('#faq-wrapper').append(newFaq);
                });

                // Remove unsaved FAQ
                $(document).on('click', '.remove-faq', function() {
                    $(this).closest('.faq-item').remove();
                });

                // Delete saved FAQ via form submission
                $(document).on('click', '.delete-faq-btn', function() {
                    const id = $(this).data('id');
                    if (confirm('Delete this FAQ?')) {
                        const form = $('<form>', {
                            method: 'POST',
                            action: "/fc/faqs/" + id
                        });

                        form.append(`@csrf`);
                        form.append(`<input type="hidden" name="_method" value="DELETE">`);
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        </script>
    @endsection
