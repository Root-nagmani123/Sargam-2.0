@extends('admin.layouts.master')

@section('title', 'Medical Exception Faculty View - Sargam | Lal Bahadur')

@section('setup_content')
<style>
.faculty-card {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    padding: 20px;
}

.faculty-header {
    border-bottom: 2px solid #af2910;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.course-card {
    background: #f8f9fa;
    border-left: 4px solid #004a93;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
}

.coordinator-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin: 5px 5px 5px 0;
}

.cc-badge {
    background: #004a93;
    color: white;
}

.acc-badge {
    background: #28a745;
    color: white;
}

.exemption-badge {
    background: #ffc107;
    color: #000;
    font-weight: 700;
    padding: 8px 15px;
    border-radius: 25px;
}

.student-list {
    max-height: 200px;
    overflow-y: auto;
    margin-top: 10px;
}

.student-item {
    padding: 8px 12px;
    background: white;
    border-radius: 5px;
    margin-bottom: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.student-card {
    background: #ffffff;
    border-left: 4px solid #004a93;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 15px;
    padding: 20px;
}

.student-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.exemption-count-badge {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #000;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 18px;
}

.exemption-item {
    background: #f8f9fa;
    border-left: 3px solid #b72a2a;
    border-radius: 5px;
    padding: 12px;
    margin-bottom: 10px;
}

.exemption-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.detail-item {
    font-size: 14px;
}

.detail-label {
    font-weight: 600;
    color: #666;
}

.detail-value {
    color: #000;
}

.table-responsive {
    overflow-x: auto;
}

.badge {
    font-weight: 500;
    padding: 6px 10px;
}

.bg-warning-subtle {
    background-color: #fff4d6;
}

.bg-danger-subtle {
    background-color: #fde8e8;
}

.card h6 {
    color: #003366;
}
</style>

<div class="container-fluid">
    <x-breadcrum title="Medical Exception Faculty View"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <h4>Medical Exception Faculty View</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-info d-flex align-items-center" onclick="window.print()">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-2">
                    <label for="" class="form-label"></label>
                    <div class="row">
                        <div class="col-12">
                            <label for="" class="form-label">Course Name</label>
                            <div class="mb-3">
                                <select name="" id="" class="form-control">
                                    <option value="0">Select</option>
                                    <option value="1">A01</option>
                                    <option value="2">A02</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <label for="" class="form-label">Date</label>
                    <div class="row">
                        <div class="col-6">
                            <label for="" class="form-label">from</label>
                            <div class="mb-3">
                                <input type="date" name="" id="" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="" class="form-label">To</label>
                            <div class="mb-3">
                                <input type="date" name="" id="" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <label for="" class="form-label">Time</label>
                    <div class="row">
                        <div class="col-6">
                            <label for="" class="form-label">from</label>
                            <div class="mb-3">
                                <input type="time" name="" id="" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="" class="form-label">To</label>
                            <div class="mb-3">
                                <input type="time" name="" id="" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <label for="" class="form-label"></label>
                    <div class="row">
                        <div class="col-12">
                            <label for="" class="form-label">OT Code</label>
                            <div class="mb-3">
                                <select name="" id="" class="form-control">
                                    <option value="0">Select</option>
                                    <option value="1">A01</option>
                                    <option value="2">A02</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            @php
            // Check if this is a faculty login view
            $isFacultyView = isset($isFacultyView) && $isFacultyView === true;
            @endphp

            @if($isFacultyView)

            @if(isset($hasData) && $hasData && count($studentData) > 0)

            @foreach($studentData as $course)

            <div class="card mb-4 shadow-sm border-0" style="border-left:4px solid #004a93;">
                <div class="card-body">

                    <!-- Course Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">
                                {{ $course['course_name'] }}
                            </h6>

                            <div class="text-muted small">
                                CC:
                                <span class="fw-semibold text-dark">
                                    {{ $course['cc_name'] ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <div class="text-end">
                            <span class="badge bg-warning-subtle text-warning border mb-1 d-block">
                                {{ $course['total_exemption_count'] }} Student(s) on Medical Exception
                            </span>
                            <div class="text-muted small">
                                Total Students: {{ $course['total_students'] }}
                            </div>
                        </div>
                    </div>

                    <!-- Students List -->
                    @if(count($course['students']) > 0)
                    <div class="mt-3">

                        @foreach($course['students'] as $student)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="fw-semibold">
                                    {{ $student['generated_OT_code'] }}
                                </span>
                                <span class="text-muted">
                                    – {{ $student['display_name'] }}
                                </span>
                            </div>

                            <span class="badge bg-danger-subtle text-danger border">
                                Medical Exception
                            </span>
                        </div>
                        @endforeach

                    </div>
                    @else
                    <div class="text-muted fst-italic">
                        No students under medical exception.
                    </div>
                    @endif

                </div>
            </div>

            @endforeach

            @else
            <div class="alert alert-info text-center">
                <i class="material-icons material-symbols-rounded fs-1">info</i>
                <div class="mt-2">No records found</div>
            </div>

            @endif

            @else
            <!-- Admin View -->
            <!-- Faculty Data -->
            @if(isset($facultyData) && count($facultyData) > 0)
            @foreach($facultyData as $faculty)
            <div class="faculty-card">
                <div class="faculty-header">
                    <div class="row">
                        <div class="col-6">
                            <h5 class="mb-0">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 24px; vertical-align: middle;">person</i>
                        {{ $faculty['faculty_name'] }}
                    </h5>
                        </div>
                        <div class="col-6 gap-2 text-end">
                             <a href=""><i class="material-icons menu-icon material-symbols-rounded text-dark"
                            style="font-size: 24px; vertical-align: middle;" tooltip="Eye Tracking">eye_tracking</i> </a>
                            <a href=""><i class="material-icons menu-icon material-symbols-rounded text-dark"
                            style="font-size: 24px; vertical-align: middle;" tooltip="Download">download</i></a>
                        </div>
                    </div>
                </div>

                @foreach($faculty['courses'] as $course)
                <div class="course-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold mb-2">{{ $course['course_name'] }}</h6>
                            <div>
                                <span class="coordinator-badge cc-badge">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 16px; vertical-align: middle;">person</i>
                                    CC: {{ $course['cc'] }}
                                </span>
                                <span class="coordinator-badge acc-badge">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 16px; vertical-align: middle;">person</i>
                                    ACC: {{ $course['acc'] }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="exemption-badge">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 18px; vertical-align: middle;">medical_services</i>
                                {{ $course['exemption_count'] }} Student(s) on Medical Exception
                            </div>
                            <div class="mt-2 text-muted">
                                Total Students: {{ $course['total_students'] }}
                            </div>
                        </div>
                    </div>

                    @if($course['students']->count() > 0)
                    <div class="student-list">
                        <h6 class="fw-semibold mb-2">Students:</h6>
                        @foreach($course['students'] as $student)
                        <div class="student-item">
                            <div>
                                <strong>{{ $student->generated_OT_code }}</strong> - {{ $student->display_name }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted mb-0">No students enrolled in this course.</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endforeach
            @else
            <div class="alert alert-info text-center">
                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;">info</i>
                <p class="mt-2">No faculty data found matching the selected filters.</p>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

@endsection