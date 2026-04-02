@extends('admin.layouts.master')

@section('title', 'Direct Notice - Sargam | LBSNAA')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Direct Notice" />
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-6">
                    <h4>Direct Notice</h4>
                </div>
            </div>
            <hr>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="programme" class="form-label fw-semibold mb-1">Course Name :</label>
                    <div class="mb-0">
                        <select name="course_master_pk" id="programme" class="form-select js-choice" required>
                            <option value="">Select Course</option>
                            @foreach($courseMasters as $course)
                            <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                            @endforeach
                        </select>
                        <!-- <small class="form-text text-muted">Select Course</small> -->
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="from_date" class="form-label fw-semibold mb-1">From Date :</label>
                    <div class="mb-0">
                        <input type="date" class="form-control" id="from_date" name="from_date" placeholder="From Date">
                        <!-- <small class="form-text text-muted">Select From Date</small> -->
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label for="to_date" class="form-label fw-semibold mb-1">To Date :</label>
                    <div class="mb-0">
                        <input type="date" class="form-control" id="to_date" name="to_date" placeholder="To Date">
                        <!-- <small class="form-text text-muted">Select To Date</small> -->
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label fw-semibold mb-1 d-block">Attendance Type :</label>
                    <div class="d-flex flex-wrap gap-3 mb-0 pt-1">
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="radio" name="attendance_type" id="full_day"
                                value="full_day" checked>
                            <label class="form-check-label" for="full_day">Full Day</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="radio" name="attendance_type" id="manual"
                                value="manual">
                            <label class="form-check-label" for="manual">Manual</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="radio" name="attendance_type" id="normal"
                                value="normal">
                            <label class="form-check-label" for="normal">Normal</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3" id="normal_session_container" style="display: none;">
                    <label for="session" class="form-label fw-semibold mb-1">Normal Session :</label>
                    <div class="mb-0">
                        <select name="session" id="session" class="form-select js-choice">
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                            @endforeach
                        </select>
                        <!-- <small class="form-text text-muted">Select Session</small> -->
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3" id="manual_session_container" style="display: none;">
                    <label for="manual_session" class="form-label fw-semibold mb-1">Manual Session:</label>
                    <div class="mb-0">
                        <select name="manual_session" id="manual_session" class="form-select js-choice">
                            <option value="">Select Session</option>
                            @foreach($maunalSessions as $maunalSession)
                            <option value="{{ $maunalSession['class_session'] }}">
                                {{ $maunalSession['class_session'] }}</option>
                            @endforeach
                        </select>
                        <!-- <small class="form-text text-muted">Select Session</small> -->
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-end">
                <button class="btn btn-primary hstack gap-6 float-end" id="searchAttendance" type="button">
                    <span class="material-symbols-rounded text-white fs-6">search</span>
                    Search
                </button>
            </div>
        </div>
        <div class="card-body d-none" id="attendanceTableCard">
            <div class="table-responsive" id="attendanceTableDiv">
                    <table id="attendanceTable" class="table">
                        <thead>
                            <tr>
                                <th class="col">#</th>
                                <th class="col">Course Name</th>
                                <th class="col">Date</th>
                                <th class="col">Session</th>
                                <th class="col">Venue</th>
                                <th class="col">Group</th>
                                <th class="col">Topic</th>
                                <th class="col">Faculty</th>
                                <th class="col">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
        </div>
    </div>
</div>


@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    (function() {
        function initChoicesDropdowns() {
            var selects = document.querySelectorAll('.js-choice');
            selects.forEach(function(select) {
                if (select.dataset.choicesInitialized === '1') return;
                select.dataset.choicesInitialized = '1';

                new Choices(select, {
                    searchEnabled: true,
                    searchChoices: true,
                    shouldSort: false,
                    allowHTML: false,
                    itemSelectText: ''
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChoicesDropdowns);
        } else {
            initChoicesDropdowns();
        }
    })();
</script>
@endsection