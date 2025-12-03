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
            
            @php
                // Check if this is a faculty login view
                $isFacultyView = isset($isFacultyView) && $isFacultyView === true;
            @endphp
            
            @if($isFacultyView)
                <!-- Faculty Login View -->
                @if(isset($hasData) && $hasData && count($studentData) > 0)
                    <!-- Total Exceptions Summary -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light" style="border-left: 4px solid #004a93;">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold text-muted mb-1">Total Number of Exceptions</label>
                                                <div class="fs-4 fw-semibold text-primary">{{ $totalExceptions ?? 0 }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold text-muted mb-1">Total Students with Exceptions</label>
                                                <div class="fs-4 fw-semibold text-primary">{{ count($studentData) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Data -->
                    @foreach($studentData as $student)
                        <div class="student-card">
                            <div class="student-header">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px; vertical-align: middle;">person</i>
                                        <strong>{{ $student['ot_code'] }}</strong> - {{ $student['student_name'] }}
                                    </h5>
                                    <div class="mt-2">
                                        <span class="text-muted">
                                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">email</i>
                                            {{ $student['email'] ?? 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="exemption-count-badge">
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">medical_services</i>
                                    Total Exceptions: {{ $student['total_exemption_count'] }}
                                </div>
                            </div>
                            
                            @if(count($student['exemptions']) > 0)
                                <div class="exemption-list">
                                    @foreach($student['exemptions'] as $exemption)
                                        <div class="exemption-item">
                                            <div class="exemption-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Course:</span>
                                                    <span class="detail-value">{{ $exemption['course_name'] ?? 'N/A' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">From Date:</span>
                                                    <span class="detail-value">{{ $exemption['from_date'] ? \Carbon\Carbon::parse($exemption['from_date'])->format('d/m/Y') : 'N/A' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">To Date:</span>
                                                    <span class="detail-value">{{ $exemption['to_date'] ? \Carbon\Carbon::parse($exemption['to_date'])->format('d/m/Y') : 'Ongoing' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">OPD Category:</span>
                                                    <span class="detail-value">{{ $exemption['opd_category'] ?? 'N/A' }}</span>
                                                </div>
                                                @if($exemption['doc_upload'])
                                                <div class="detail-item">
                                                    <span class="detail-label">Document:</span>
                                                    <span class="detail-value">
                                                        <a href="{{ asset('storage/' . $exemption['doc_upload']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px;">file_download</i>
                                                            View Document
                                                        </a>
                                                    </span>
                                                </div>
                                                @endif
                                                @if($exemption['description'])
                                                <div class="detail-item" style="grid-column: 1 / -1;">
                                                    <span class="detail-label">Description:</span>
                                                    <div class="detail-value mt-1">{{ $exemption['description'] }}</div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <!-- No records found -->
                    <div class="alert alert-info text-center">
                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;">info</i>
                        <p class="mt-2 fs-5">No records found</p>
                    </div>
                @endif
            @else
                <!-- Admin View -->
                <!-- Faculty Data -->
                @if(isset($facultyData) && count($facultyData) > 0)
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
            @endif
        </div>
    </div>
</div>

@endsection

