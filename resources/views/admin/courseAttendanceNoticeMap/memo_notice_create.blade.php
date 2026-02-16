@extends('admin.layouts.master') {{-- this layout MUST contain the tabs --}}

@section('title', 'Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
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
                            <label class="form-label">Select Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" class="form-select" required>
                                <option value="" disabled selected>Select Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select an active course if this memo/notice is course-specific</small>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                placeholder="Enter Memo / Notice Title" required>
                        </div>

                        <div class="col-5">
                            <label class="form-label">Director's Name <span class="text-danger">*</span></label>
                            <input type="text" name="director" class="form-control" placeholder="Enter Director's Name"
                                required>
                        </div>

                        <div class="col-5">
                            <label class="form-label">Director's Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" placeholder="Enter designation"
                                required>
                        </div>

                        <div class="col-2">
                            <label class="form-label">Memo / Notice Type <span class="text-danger">*</span></label>
                            <select name="memo_notice_type" class="form-select" required>
                                <option value="" disabled selected>Select Type</option>
                                <option value="Memo">Memo</option>
                                <option value="Notice">Notice</option>
                                <option value="Discipline Memo">Discipline Memo</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control" id="content" rows="3" required></textarea>
                        </div>
                    </div>

                    <hr>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Submit</button>
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
    @include('components.jquery-3-6')
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
