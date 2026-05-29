@extends('admin.layouts.master') {{-- this layout MUST contain the tabs --}}

@section('title', 'Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css">
<link rel="stylesheet" href="{{ asset('css/memo-notice-management-admin.css') }}?v={{ @filemtime(public_path('css/memo-notice-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
    <div class="container-fluid mnm-master-page py-3 px-3 px-lg-4">
        <x-breadcrum title="Notice /Memo Admin Management" />
        <x-session_message />
        <div class="card mnm-form-card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 p-md-4">
                <h2 class="mnm-page-title mb-4">Create Memo / Notice</h2>

                <form action="{{ route('admin.memo-notice.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Select Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" class="form-select" required>
                                <option value="" disabled selected>Select Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select an active course if this memo/notice is course-specific</small>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                placeholder="Enter Memo / Notice Title" required>
                        </div>

                        <div class="col-12 col-md-6 col-xl-5">
                            <label class="form-label">Director's Name <span class="text-danger">*</span></label>
                            <input type="text" name="director" class="form-control" placeholder="Enter Director's Name"
                                required>
                        </div>

                        <div class="col-12 col-md-6 col-xl-5">
                            <label class="form-label">Director's Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" placeholder="Enter designation"
                                required>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="form-label">Memo / Notice Type <span class="text-danger">*</span></label>
                            <select name="memo_notice_type" class="form-select" required>
                                <option value="" disabled selected>Select Type</option>
                                <option value="Memo">Memo</option>
                                <option value="Notice">Notice</option>
                                <option value="Discipline Memo">Discipline Memo</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Memo / Notice Content <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control" id="content" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 pt-3 mt-2 border-top">
                        <a href="{{ route('admin.memo-notice.index') }}" class="btn btn-outline-secondary px-4 rounded-2">Back</a>
                        <button type="submit" class="btn btn-primary px-4 rounded-2 fw-semibold">Submit</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection


@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        $(document).ready(function() {

            // Initialize Summernote
            $('#content').summernote({
                height: 300
            });

        });
    </script>
@endsection
