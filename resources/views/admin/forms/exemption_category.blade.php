@extends('admin.layouts.master')

@section('title', 'Exemption Category')


@section('content')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> {{-- jQuery before Summernote --}}
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <div class="container-fluid">
        <form method="POST" action="{{ route('admin.exemption-category.save') }}">
            @csrf

            <div class="card card-body">
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
                <h5 class="mb-4 fw-bold">Exemption Category Form</h5>

                <div class="row">
                    <!-- Exemption for CSE -->
                    <div class="col-md-6 mb-3">
                        <label for="cse_heading" class="form-label">Exemption for CSE - Heading</label>
                        <input type="text" name="cse_heading" id="cse_heading"
                            value="{{ old('cse_heading', $data->cse_heading ?? '') }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cse_subheading" class="form-label">Exemption for CSE - Subheading</label>
                        <textarea name="cse_subheading" id="cse_subheading" class="form-control">{{ old('cse_subheading', $data->cse_subheading ?? '') }}</textarea>
                    </div>

                    <!-- Already Attended -->
                    <div class="col-md-6 mb-3">
                        <label for="attended_heading" class="form-label">Already Attended - Heading</label>
                        <input type="text" name="attended_heading" id="attended_heading"
                            value="{{ old('attended_heading', $data->attended_heading ?? '') }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="attended_subheading" class="form-label">Already Attended - Subheading</label>
                        <textarea name="attended_subheading" id="attended_subheading" class="form-control">{{ old('attended_subheading', $data->attended_subheading ?? '') }}</textarea>
                    </div>

                    <!-- Medical Grounds -->
                    <div class="col-md-6 mb-3">
                        <label for="medical_heading" class="form-label">Medical Grounds - Heading</label>
                        <input type="text" name="medical_heading" id="medical_heading"
                            value="{{ old('medical_heading', $data->medical_heading ?? '') }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="medical_subheading" class="form-label">Medical Grounds - Subheading</label>
                        <textarea name="medical_subheading" id="medical_subheading" class="form-control">{{ old('medical_subheading', $data->medical_subheading ?? '') }}</textarea>
                    </div>

                    <!-- Opting Out -->
                    <div class="col-md-6 mb-3">
                        <label for="optout_heading" class="form-label">Opting Out - Heading</label>
                        <input type="text" name="optout_heading" id="optout_heading"
                            value="{{ old('optout_heading', $data->optout_heading ?? '') }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="optout_subheading" class="form-label">Opting Out - Subheading</label>
                        <textarea name="optout_subheading" id="optout_subheading" class="form-control">{{ old('optout_subheading', $data->optout_subheading ?? '') }}</textarea>
                    </div>

                    <!-- Description -->
                    <!-- Important Notice -->
                    <div class="col-12 mb-3">
                        <label for="important_notice" class="form-label">Important Notice</label>
                        <textarea name="important_notice" id="important_notice" class="form-control summernote">
                          {{ old('important_notice', $data->important_notice ?? '') }}
                          </textarea>
                    </div>


                    <!-- Submit -->
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4">Save</button>
                    </div>
                </div>
            </div>
        </form>
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
        });
    </script>
@endsection
