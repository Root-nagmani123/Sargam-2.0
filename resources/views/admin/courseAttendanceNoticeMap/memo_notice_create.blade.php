@extends('admin.layouts.master') {{-- this layout MUST contain the tabs --}}

@section('title', 'Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Notice /Memo Admin Management" />
        <x-session_message />
        <div class="card" >
            <div class="card-body">
                <h4 class="card-title mb-3">Create Memo / Notice</h4>
                <hr>

                <form action="{{ route('admin.memo-notice.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">

                        <div class="col-6">
                            <label class="form-label">Select Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" id="mtCourse" class="form-select" required>
                                <option value="" disabled selected>Select Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}" {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
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
                            <select name="memo_notice_type" id="mtType" class="form-select" required>
                                <option value="" disabled {{ old('memo_notice_type') ? '' : 'selected' }}>Select Type</option>
                                <option value="Memo" {{ old('memo_notice_type') === 'Memo' ? 'selected' : '' }}>Memo</option>
                                <option value="Notice" {{ old('memo_notice_type') === 'Notice' ? 'selected' : '' }}>Notice</option>
                                <option value="Discipline Memo" {{ old('memo_notice_type') === 'Discipline Memo' ? 'selected' : '' }}>Discipline Memo</option>
                            </select>
                        </div>

                        {{-- Discipline: only relevant for Discipline Memo templates (per-discipline template). --}}
                        <div class="col-6 d-none" id="mtDisciplineWrap">
                            <label class="form-label">Discipline <span class="text-danger">*</span></label>
                            <select name="discipline_master_pk" id="mtDiscipline" class="form-select" data-old="{{ old('discipline_master_pk') }}">
                                <option value="">Select Discipline</option>
                            </select>
                            <small class="text-muted">This template will be offered when generating a memo for this discipline.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Memo / Notice Content <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control" id="content" rows="3" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Signature Image <small class="text-muted">(jpeg/png/gif, max 2MB)</small></label>
                            <input type="file" name="signature_image" class="form-control" accept="image/jpeg,image/png,image/gif">
                            <small class="text-muted">Upload the authorised signatory's signature. It will appear on the printed memo/notice.</small>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        $(document).ready(function() {

            // Initialize Summernote
            $('#content').summernote({
                height: 300
            });

            // ── Discipline field: only for "Discipline Memo" type, filtered by course ──
            var mtDisciplines = @json($disciplines);

            function populateDisciplines() {
                var courseId = String($('#mtCourse').val() || '');
                var $sel = $('#mtDiscipline');
                var previous = $sel.attr('data-old') || $sel.val() || '';
                $sel.empty().append('<option value="">Select Discipline</option>');
                mtDisciplines
                    .filter(function (d) { return String(d.course_master_pk) === courseId; })
                    .forEach(function (d) {
                        $sel.append($('<option>').val(d.pk).text(d.discipline_name));
                    });
                if (previous) { $sel.val(String(previous)); }
                $sel.removeAttr('data-old');
            }

            function toggleDisciplineField() {
                var isDiscipline = $('#mtType').val() === 'Discipline Memo';
                $('#mtDisciplineWrap').toggleClass('d-none', !isDiscipline);
                $('#mtDiscipline').prop('required', isDiscipline);
                if (isDiscipline) { populateDisciplines(); }
            }

            $('#mtType').on('change', toggleDisciplineField);
            $('#mtCourse').on('change', function () {
                if ($('#mtType').val() === 'Discipline Memo') { populateDisciplines(); }
            });
            toggleDisciplineField(); // reflect old() state on validation errors
        });
    </script>
@endsection
