@extends('admin.layouts.master')

@section('title', 'Medical Exception Faculty View - Sargam | Lal Bahadur')

@section('content')
<style>
    .faculty-card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        padding: 20px;
    }
    
    .faculty-header {
        background: linear-gradient(135deg, #b72a2a 0%, #8b1f1f 100%);
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
    
    .table-responsive {
        overflow-x: auto;
    }
</style>

<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <h4>Medical Exception Faculty View</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-info d-flex align-items-center" onclick="window.print()">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <!-- Filters Section -->
            <form method="GET" action="{{ route('medical.exception.faculty.view') }}" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="faculty_filter" class="form-label fw-semibold">Faculty</label>
                        <select name="faculty_filter" id="faculty_filter" class="form-select">
                            <option value="">-- All Faculty --</option>
                            @foreach($allFaculties as $faculty)
                                <option value="{{ $faculty->pk }}" {{ $facultyFilter == $faculty->pk ? 'selected' : '' }}>
                                    {{ $faculty->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="course_filter" class="form-label fw-semibold">Course</label>
                        <select name="course_filter" id="course_filter" class="form-select">
                            <option value="">-- All Courses --</option>
                            @foreach($allCourses as $course)
                                <option value="{{ $course->pk }}" {{ $courseFilter == $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="{{ route('medical.exception.faculty.view') }}" class="btn btn-outline-danger w-100 mt-2">Reset</a>
                    </div>
                </div>
            </form>
            
            <!-- Faculty Data -->
            @if(count($facultyData) > 0)
                @foreach($facultyData as $faculty)
                    <div class="faculty-card">
                        <div class="faculty-header">
                            <h5 class="mb-0">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px; vertical-align: middle;">person</i>
                                {{ $faculty['faculty_name'] }}
                            </h5>
                        </div>
                        
                        @foreach($faculty['courses'] as $course)
                            <div class="course-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-2">{{ $course['course_name'] }}</h6>
                                        <div>
                                            <span class="coordinator-badge cc-badge">
                                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">person</i>
                                                CC: {{ $course['cc'] }}
                                            </span>
                                            <span class="coordinator-badge acc-badge">
                                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">person</i>
                                                ACC: {{ $course['acc'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="exemption-badge">
                                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">medical_services</i>
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
        </div>
    </div>
</div>

@endsection

