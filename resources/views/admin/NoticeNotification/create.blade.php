@extends('admin.layouts.master')

@section('title', 'Create Notice notification')

@push('styles')
@include('admin.NoticeNotification.partials.module-styles')
@endpush

@section('setup_content')

@php
    $todayMin = now()->format('Y-m-d');
@endphp

<div class="container-fluid">
    <x-breadcrum title="Notice List" />
    <x-session_message />

    <div class="card notice-card border-0 shadow-sm overflow-hidden">
        @if ($errors->any())
        <div class="card-body pb-0">
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        <div class="card-body p-4 p-lg-5">
            <div class="notice-form-header mb-4">
                <h4 class="card-title mb-0 fw-bold">
                    Create <span class="notice-title-highlight">Notice notification</span>
                </h4>
            </div>

            <form method="POST" action="{{ route('admin.notice.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    @include('admin.NoticeNotification.partials.notice-type-fields', ['notice' => null])

                    <div class="col-12">
                        <label class="form-label notice-form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="editor" name="description" class="form-control" placeholder="Write here...">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea id="editor" name="description" class="form-control">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Notice Type (Category) <span class="text-danger">*</span></label>
                            <select name="notice_category_master_pk" id="noticeCategory" class="form-control" required>
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->pk }}" {{ (string) old('notice_category_master_pk') === (string) $cat->pk ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Notice Sub Type (Subcategory)</label>
                            <select name="notice_subcategory_master_pk" id="noticeSubcategory" class="form-control">
                                <option value="">Select sub type</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Display Date <span class="text-danger">*</span></label>
                            <input type="date" name="display_date" id="noticeDisplayDate" class="form-control"
                                value="{{ old('display_date') }}" min="{{ $todayMin }}" required>
                            <div class="form-text">Must be today or a future date.</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" id="noticeExpiryDate" class="form-control"
                                value="{{ old('expiry_date') }}" min="{{ $todayMin }}" required>
                            <div class="form-text">Must be on or after the display date.</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Upload document <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="file" name="document" class="form-control" id="noticeDocument"
                                accept=".pdf,.png,.jpg,.jpeg,image/jpeg,image/png,application/pdf">
                            <div class="form-text">Types: <strong>PDF, JPG, PNG</strong>. Max <strong>5&nbsp;MB</strong> per file.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label notice-form-label">Target Audience <span class="text-danger">*</span></label>
                        <select name="target_audience" id="targetAudience" class="form-control">
                            <option value="">Select the target audience</option>
                            @foreach($target as $t)
                            <option value="{{ $t }}" {{ old('target_audience') == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3 d-none" id="courseBox">
                            <label class="form-label notice-form-label">Select Course</label>
                            <select name="course_master_pk" id="courseSelect" class="form-control">
                                <option value="">Select Course</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-4 border-top">
                    <a href="{{ route('admin.notice.index') }}" class="btn btn-notice-cancel btn-outline-primary rounded-3 px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-notice-save text-white rounded-3 px-4">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Save
                    </button>
                </div>
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
            placeholder: 'Write here...',
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
                pdfUpload: function(context) {
                    var ui = $.summernote.ui;

                    var button = ui.button({
                        contents: '<i class="note-icon-paperclip"></i> PDF',
                        tooltip: 'Upload PDF',
                        click: function() {

                            let fileInput = $('<input type="file" accept="application/pdf">');
                            fileInput.trigger('click');

                            fileInput.on('change', function() {

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
                                    success: function(data) {
                                        let url = data.location;
                                        context.invoke('editor.insertText', url);
                                    },
                                    error: function(xhr) {
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

        const oldCat = @json(old('notice_category_master_pk'));
        const oldSub = @json(old('notice_subcategory_master_pk'));
        if (oldCat) {
            loadNoticeSubcategories(oldCat, oldSub);
        }

        $('#noticeCategory').on('change', function() {
            loadNoticeSubcategories($(this).val(), null);
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

        var todayMin = @json($todayMin);
        function syncNoticeExpiryMin() {
            var disp = $('input[name="display_date"]').val();
            var $exp = $('input[name="expiry_date"]');
            var floor = disp && disp >= todayMin ? disp : todayMin;
            $exp.attr('min', floor);
            if ($exp.val() && $exp.val() < floor) {
                $exp.val(floor);
            }
        }
        $('input[name="display_date"]').on('change', syncNoticeExpiryMin);
        syncNoticeExpiryMin();

    });
</script>
@endsection
