@extends('admin.layouts.master')

@section('title', 'Medical Exception OT View - Sargam | Lal Bahadur')

@section('academics_content')
<style>
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
                    <h4>Medical Exception OT View</h4>
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
                // Check if this is a student login view (has student_name, ot_code, email keys)
                $isStudentView = isset($studentData) && isset($studentData['student_name']) && isset($studentData['ot_code']);
            @endphp
            
            @if($isStudentView)
                <!-- Student Login View -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light" style="border-left: 4px solid #004a93;">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted mb-1">Student Name</label>
                                            <div class="fs-5 fw-semibold">{{ $studentData['student_name'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted mb-1">OT Code</label>
                                            <div class="fs-5 fw-semibold">{{ $studentData['ot_code'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted mb-1">Email</label>
                                            <div class="fs-5 fw-semibold">{{ $studentData['email'] ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted mb-1">Total Medical Exemption Count</label>
                                            <div class="fs-5 fw-semibold text-primary">{{ $studentData['total_exemption_count'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exemption Details -->
                @if(isset($studentData['has_exemptions']) && $studentData['has_exemptions'])
                    @if(count($studentData['exemptions']) > 0)
                        <div class="row">
                            @foreach($studentData['exemptions'] as $exemption)
                                <div class="col-12 mb-3">
                                    <div class="card exemption-item">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3" style="color: #004a93; font-weight: 600;">
                                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">school</i>
                                                Course: {{ $exemption['course_name'] }}
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <span class="detail-label">From Date:</span>
                                                    <span class="detail-value">{{ $exemption['from_date'] ? \Carbon\Carbon::parse($exemption['from_date'])->format('d/m/Y') : 'N/A' }}</span>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <span class="detail-label">To Date:</span>
                                                    <span class="detail-value">{{ $exemption['to_date'] ? \Carbon\Carbon::parse($exemption['to_date'])->format('d/m/Y') : 'Ongoing' }}</span>
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <span class="detail-label">OPD Category:</span>
                                                    <span class="detail-value">{{ $exemption['opd_category'] ?? 'N/A' }}</span>
                                                </div>
                                                @if($exemption['doc_upload'])
                                                <div class="col-md-6 mb-2">
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
                                                <div class="col-12 mb-2">
                                                    <span class="detail-label">Description:</span>
                                                    <div class="detail-value mt-1">{{ $exemption['description'] }}</div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info text-center">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;">info</i>
                            <p class="mt-2 fs-5">No records found</p>
                        </div>
                    @endif
                @else
                    <div class="alert alert-info text-center">
                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;">info</i>
                        <p class="mt-2 fs-5">No records found</p>
                    </div>
                @endif
            @else
                <!-- Admin View -->
                <!-- Student Data -->
                @if(isset($studentData) && is_array($studentData) && count($studentData) > 0)
                    @foreach($studentData as $student)
                        <div class="student-card">
                            <div class="student-header">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px; vertical-align: middle;">person</i>
                                        <strong>{{ $student['ot_code'] }}</strong> - {{ $student['student_name'] }}
                                    </h5>
                                </div>
                                <div class="exemption-count-badge">
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">medical_services</i>
                                    Total Exemptions: {{ $student['exemption_count'] }}
                                </div>
                            </div>
                            
                            @if($student['exemptions']->count() > 0)
                                <div class="exemption-list">
                                    @foreach($student['exemptions'] as $exemption)
                                        <div class="exemption-item">
                                            <div class="exemption-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Course:</span>
                                                    <span class="detail-value">{{ $exemption->course->course_name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Category:</span>
                                                    <span class="detail-value">{{ $exemption->category->exemption_category_name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Speciality:</span>
                                                    <span class="detail-value">{{ $exemption->speciality->exemption_medical_speciality_name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">From Date:</span>
                                                    <span class="detail-value">{{ $exemption->from_date ? \Carbon\Carbon::parse($exemption->from_date)->format('d/m/Y') : 'N/A' }}</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">To Date:</span>
                                                    <span class="detail-value">{{ $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d/m/Y') : 'Ongoing' }}</span>
                                                </div>
                                                @if($exemption->Description)
                                                <div class="detail-item" style="grid-column: 1 / -1;">
                                                    <span class="detail-label">Description:</span>
                                                    <span class="detail-value">{{ $exemption->Description }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No medical exemptions found for this student.</p>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info text-center">
                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;">info</i>
                        <p class="mt-2">No student data found matching the selected filters.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection

