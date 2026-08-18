@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Upload') . ' Document - Sargam')

@push('styles')
{{-- select2 ka JS footer me globally load hota hai, CSS nahi — isliye har page apni
     stylesheet khud deta hai (Timetable Session Report bhi yahi karta hai). Bina iske
     dropdown bilkul unstyled plain list ban kar page par gir jata hai. Local copy use
     ki hai, CDN nahi, taaki intranet par bhi chale. --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<style>
    /* Select2 ko form-select ki height/style se match karao */
    .select2-container { width: 100% !important; display: block !important; }
    .select2-container--open { z-index: 9999 !important; }
    .select2-dropdown { z-index: 9999 !important; }
    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        background-color: #fff;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        color: #212529;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum :title="$item ? 'Edit Document' : 'Upload Document'" />

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Document' : 'Upload Document' }}</h1>
            <p class="text-muted small mb-4">
                {{ $item
                    ? 'Update the document details, or replace the PDF with a new one.'
                    : 'Select the course and week, then upload the PDF (max ' . $maxLabel . ').' }}
            </p>
            <hr class="my-4">

            <form action="{{ $item ? route('timetable-repository.update', $item->pk) : route('timetable-repository.store') }}"
                  method="POST" enctype="multipart/form-data" id="ttrForm">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="document_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('document_name') is-invalid @enderror"
                               id="document_name" name="document_name"
                               value="{{ old('document_name', $item->document_name ?? '') }}"
                               required maxlength="255" placeholder="Enter document name">
                        @error('document_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="course_master_pk" class="form-label">Course Name <span class="text-danger">*</span></label>
                        <select class="form-select @error('course_master_pk') is-invalid @enderror"
                                id="course_master_pk" name="course_master_pk" required>
                            <option value="">Select course</option>
                            @if($activeCourses->count())
                            <optgroup label="Active Courses">
                                @foreach($activeCourses as $course)
                                    <option value="{{ $course->pk }}"
                                        @selected((int) old('course_master_pk', $item->course_master_pk ?? 0) === (int) $course->pk)>
                                        {{ $course->course_name }}@if($course->course_year) ({{ $course->course_year }})@endif
                                    </option>
                                @endforeach
                            </optgroup>
                            @endif
                            @if($archiveCourses->count())
                            <optgroup label="Archived Courses">
                                @foreach($archiveCourses as $course)
                                    <option value="{{ $course->pk }}"
                                        @selected((int) old('course_master_pk', $item->course_master_pk ?? 0) === (int) $course->pk)>
                                        {{ $course->course_name }}@if($course->course_year) ({{ $course->course_year }})@endif
                                    </option>
                                @endforeach
                            </optgroup>
                            @endif
                        </select>
                        @error('course_master_pk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="week_start" class="form-label">Week <span class="text-danger">*</span></label>
                        <select class="form-select @error('week_start') is-invalid @enderror"
                                id="week_start" name="week_start" required
                                data-selected="{{ old('week_start', optional($item?->week_start)->toDateString() ?? '') }}">
                            @php $selectedWeek = old('week_start', optional($item?->week_start)->toDateString() ?? ''); @endphp
                            <option value="">{{ count($weeks) ? 'Select week' : 'Select a course first' }}</option>
                            @foreach($weeks as $week)
                                <option value="{{ $week['value'] }}" @selected($selectedWeek === $week['value'])>{{ $week['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Weeks are counted from the selected course's start date.</div>
                        @error('week_start')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="document_file" class="form-label">
                            PDF Upload @unless($item)<span class="text-danger">*</span>@endunless
                        </label>
                        <input type="file"
                               class="form-control @error('document_file') is-invalid @enderror"
                               id="document_file" name="document_file"
                               accept="application/pdf,.pdf" @unless($item) required @endunless>
                        {{-- 5 MB is the requirement's ceiling; $maxLabel drops to whatever
                             php.ini really accepts, so the hint never promises more than
                             the server will take. --}}
                        <div class="form-text">Only PDF files, maximum size {{ $maxLabel }}.</div>
                        <div class="invalid-feedback d-block d-none" id="fileSizeError">
                            The PDF may not be larger than {{ $maxLabel }}.
                        </div>
                        @if($maxKb < 5120)
                            <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small" role="alert">
                                This server currently accepts uploads only up to {{ $maxLabel }}.
                                Raise <code>upload_max_filesize</code> and <code>post_max_size</code> to at least 5M in php.ini for the full 5 MB limit.
                            </div>
                        @endif
                        @error('document_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                        @if($item && $item->fileExists())
                            <div class="mt-2 small">
                                Current file:
                                <a href="{{ route('timetable-repository.download', $item->pk) }}" class="text-danger text-decoration-none">
                                    <i class="material-icons material-symbols-rounded align-middle" style="font-size:18px">picture_as_pdf</i>
                                    {{ $item->file_name }}
                                </a>
                                <span class="text-muted">— leave the field empty to keep it.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                    <a href="{{ route('timetable-repository.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var MAX_BYTES = {{ (int) $maxKb }} * 1024;   // server side ki effective limit ka mirror
    var MAX_LABEL = @json($maxLabel);
    var weeksUrl  = "{{ route('timetable-repository.weeks') }}";
    var $course   = $('#course_master_pk');
    var $week     = $('#week_start');
    var $file     = $('#document_file');
    var $fileErr  = $('#fileSizeError');

    // Default theme hi — 'bootstrap-5' theme ki CSS is project me nahi hai.
    if ($.fn.select2) {
        $course.select2({ width: '100%', placeholder: 'Select course' });
    }

    // Course badalte hi uske weeks laao. Purana selection tabhi rakho jab
    // wahi week nayi list me bhi mile.
    $course.on('change', function () {
        var coursePk = $(this).val();
        var keep = $week.data('selected') || '';

        $week.prop('disabled', true).html('<option value="">Loading weeks…</option>');

        if (!coursePk) {
            $week.prop('disabled', false).html('<option value="">Select a course first</option>');
            return;
        }

        $.getJSON(weeksUrl, { course_master_pk: coursePk })
            .done(function (res) {
                var options = ['<option value="">Select week</option>'];
                (res.weeks || []).forEach(function (week) {
                    options.push('<option value="' + week.value + '"' +
                        (week.value === keep ? ' selected' : '') + '>' + week.label + '</option>');
                });

                if (!(res.weeks || []).length) {
                    options = ['<option value="">No weeks available for this course</option>'];
                }

                $week.html(options.join('')).prop('disabled', false);
            })
            .fail(function () {
                $week.html('<option value="">Could not load weeks</option>').prop('disabled', false);
            });
    });

    // Server bhi check karta hai; yahan turant feedback dene ke liye.
    $file.on('change', function () {
        var file = this.files && this.files[0];
        var tooBig = !!file && file.size > MAX_BYTES;
        var notPdf = !!file && !/\.pdf$/i.test(file.name);

        $fileErr.toggleClass('d-none', !(tooBig || notPdf))
                .text(tooBig ? 'The PDF may not be larger than ' + MAX_LABEL + '.' : 'Only PDF files can be uploaded.');

        if (tooBig || notPdf) {
            $(this).val('');
        }
    });

    $('#ttrForm').on('submit', function (e) {
        var file = $file[0].files && $file[0].files[0];
        if (file && file.size > MAX_BYTES) {
            e.preventDefault();
            $fileErr.removeClass('d-none').text('The PDF may not be larger than ' + MAX_LABEL + '.');
        }
    });
});
</script>
@endpush
