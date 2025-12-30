@extends('admin.layouts.master')

@section('title', 'OT MDO/Escort Exception View - Sargam | Lal Bahadur')

@section('setup_content')
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

.duty-count-badge {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #000;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 18px;
}

.duty-item {
    background: #f8f9fa;
    border-left: 3px solid #b72a2a;
    border-radius: 5px;
    padding: 12px;
    margin-bottom: 10px;
}

.duty-details {
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
                    <h4>OT MDO/Escort Exception View</h4>
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

            <hr>

            <!-- Duty Type Filter -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="duty_type_filter" class="form-label fw-bold">Filter by Duty Type</label>
                    <select name="duty_type_filter" id="duty_type_filter" class="form-select">
                        <option value="">All Duty Types</option>
                        @if(isset($allDutyTypes) && is_array($allDutyTypes))
                            @foreach($allDutyTypes as $pk => $name)
                                <option value="{{ $pk }}" {{ isset($dutyTypeFilter) && $dutyTypeFilter == $pk ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="clearFilterBtn" class="btn btn-outline-secondary px-4 py-2 shadow-sm">
                        <i class="material-icons menu-icon material-symbols-rounded me-1" style="font-size: 18px; vertical-align: middle;">refresh</i> Clear Filter
                    </button>
                </div>
            </div>

            @php
            // Check if this is a student login view (has student_name, ot_code, email keys)
            $isStudentView = isset($studentData) && isset($studentData['student_name']) &&
            isset($studentData['ot_code']);
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
                                        <label class="form-label fw-bold text-muted mb-1">Total Duty Count</label>
                                        <div class="fs-5 fw-semibold text-primary">
                                            {{ $studentData['total_duty_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Duty Details -->
            @if(isset($studentData['has_duties']) && $studentData['has_duties'])
            @if(count($studentData['duty_maps']) > 0)
            <div class="row">
                @foreach($studentData['duty_maps'] as $duty)
                <div class="col-12 mb-3">
                    <div class="card duty-item">
                        <div class="card-body">
                            <div class="row g-2 align-items-center">

                                <!-- Left: Date Block -->
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center" aria-label="Duty Date">
                                        <i class="material-icons material-symbols-rounded me-2"
                                            style="font-size: 22px; color: #004a93;" aria-hidden="true">event</i>

                                        <div>
                                            <span class="fw-semibold text-dark">Date:</span>
                                            <span class="text-body">
                                                {{ $duty['date'] ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Accessible Edit Button -->
                                @if(strtolower($duty['duty_type']) == 'escort' && !empty($duty['faculty_master_pk']))
                                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                    <a href="{{ route('faculty.edit', ['id' => encrypt($duty['faculty_master_pk'])]) }}"
                                        class="btn btn-outline-primary btn-sm px-3 py-2"
                                        style="border-radius: 8px; border-color:#004a93; color:#004a93;"
                                        aria-label="Edit Faculty Details">

                                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;"
                                            aria-hidden="true">edit</i>

                                        <span class="fw-semibold">Edit Faculty Details</span>
                                    </a>
                                </div>
                                @endif

                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <span class="detail-label">User Name:</span>
                                    <span class="detail-value">{{ $duty['user_name'] }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <span class="detail-label">Course:</span>
                                    <span class="detail-value">{{ $duty['course'] }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <span class="detail-label">Duty Type:</span>
                                    <span class="detail-value">{{ $duty['duty_type'] }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <span class="detail-label">Faculty:</span>
                                    <span class="detail-value">{{ $duty['faculty'] }}</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <span class="detail-label">Time:</span>
                                    <span class="detail-value">{{ $duty['time'] }}</span>
                                </div>
                                @if($duty['description'] && $duty['description'] != 'N/A')
                                <div class="col-12 mb-2">
                                    <span class="detail-label">Description:</span>
                                    <div class="detail-value mt-1">{{ $duty['description'] }}</div>
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
            @if(isset($studentData) && is_array($studentData) && count($studentData) > 0)
            @foreach($studentData as $student)
            <div class="student-card">
                <div class="student-header">
                    <div>
                        <h5 class="mb-1">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px; vertical-align: middle;">person</i>
                            <strong>{{ $student['ot_code'] }}</strong> - {{ $student['student_name'] }}
                        </h5>
                    </div>
                    <div class="duty-count-badge">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">assignment</i>
                        Total Duties: {{ $student['duty_count'] }}
                    </div>
                </div>

                @if(count($student['duty_maps']) > 0)
                <div class="duty-list">
                    @foreach($student['duty_maps'] as $duty)
                    <div class="duty-item">
                        <div class="duty-details">
                            <div class="detail-item">
                                <span class="detail-label">Date:</span>
                                <span
                                    class="detail-value">{{ $duty['date'] ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Course:</span>
                                <span class="detail-value">{{ $duty['course'] }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Duty Type:</span>
                                <span class="detail-value">{{ $duty['duty_type'] }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Faculty:</span>
                                <span class="detail-value">{{ $duty['faculty'] }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time:</span>
                                <span class="detail-value">{{ $duty['time'] }}</span>
                            </div>
                            @if($duty['description'] && $duty['description'] != 'N/A')
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <span class="detail-label">Description:</span>
                                <span class="detail-value">{{ $duty['description'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted mb-0">No MDO/Escort duties found for this student.</p>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dutyTypeFilter = document.getElementById('duty_type_filter');
        const clearFilterBtn = document.getElementById('clearFilterBtn');
        
        if (dutyTypeFilter) {
            dutyTypeFilter.addEventListener('change', function() {
                const selectedValue = this.value;
                const url = new URL(window.location.href);
                
                if (selectedValue) {
                    url.searchParams.set('duty_type_filter', selectedValue);
                } else {
                    url.searchParams.delete('duty_type_filter');
                }
                
                window.location.href = url.toString();
            });
        }
        
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', function() {
                const url = new URL(window.location.href);
                url.searchParams.delete('duty_type_filter');
                window.location.href = url.toString();
            });
        }
    });
</script>

@endsection