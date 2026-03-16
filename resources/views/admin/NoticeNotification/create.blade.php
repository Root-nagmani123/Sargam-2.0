@extends('admin.layouts.master')

@section('title', 'Create Notice notification')

@section('content')


<div class="container-fluid">
    <x-breadcrum title="Notice List" />
    <x-session_message />
    <div class="card">
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
            <h4 class="card-title mb-3">Create Notice notification</h4>
            <hr>
            <form method="POST" action="{{ route('admin.notice.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Notice Title <span class="text-danger">*</span></label>
                            <input type="text" name="notice_title" class="form-control" value="{{ old('notice_title') }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea id="editor" name="description" class="form-control">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Notice Type <span class="text-danger">*</span></label>
                            <select name="notice_type" class="form-control">
                                <option value="">Select Notice Type</option>
                                @foreach($types as $t)
                                <option value="{{ $t }}" {{ old('notice_type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Display Date <span class="text-danger">*</span></label>
                            <input type="date" name="display_date" class="form-control" value="{{ old('display_date') }}">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name="document" class="form-control">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Target Audience <span class="text-danger">*</span></label>
                            <select name="target_audience" id="targetAudience" class="form-control">
                                <option value="">Select Target Audience</option>
                                @foreach($target as $t)
                                <option value="{{ $t }}" {{ old('target_audience') == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3 d-none" id="courseBox">
                            <label class="form-label">Select Course</label>
                            <select name="course_master_pk" id="courseSelect" class="form-control">
                                <option value="">Select Course</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary">Save</button>
                        <a href="{{ route('admin.notice.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'bulletedList', 'numberedList', '|',
                    'link', 'blockQuote', 'insertTable', '|',
                    'undo', 'redo'
                ]
            })
            .catch(error => {
                console.error('CKEditor initialization error:', error);
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