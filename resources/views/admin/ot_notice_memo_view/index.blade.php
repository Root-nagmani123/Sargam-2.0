@extends('admin.layouts.master')

@section('title', 'OT Notice / Memo View - Sargam | Lal Bahadur')

@section('content')
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
    
    .record-count-badge {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #000;
        font-weight: 700;
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 18px;
    }
    
    .record-item {
        background: #f8f9fa;
        border-left: 3px solid #b72a2a;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .record-details {
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
    
    .conversation-section {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .conversation-item {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 10px;
    }
    
    .conversation-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 12px;
        color: #666;
    }
    
    .conversation-message {
        color: #333;
        margin-bottom: 5px;
    }
    
    .status-badge {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-open {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-close {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .type-badge {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .type-notice {
        background-color: #cfe2ff;
        color: #084298;
    }
    
    .type-memo {
        background-color: #fff3cd;
        color: #856404;
    }
</style>

<div class="container-fluid">
    <x-session_message />
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <h4>OT Notice / Memo View</h4>
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
            
            <!-- Student Information -->
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
                                        <div class="fs-5 fw-semibold">{{ $studentData['email'] }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted mb-1">Total Notice/Memo Count</label>
                                        <div class="fs-5 fw-semibold text-primary">{{ $studentData['total_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Records List -->
            @if(isset($studentData['has_records']) && $studentData['has_records'])
                @if(count($studentData['records']) > 0)
                    <div class="row">
                        @foreach($studentData['records'] as $record)
                            <div class="col-12 mb-3">
                                <div class="card record-item">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title mb-0" style="color: #004a93; font-weight: 600;">
                                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">description</i>
                                                {{ $record->type }}: {{ $record->course_name ?? 'N/A' }}
                                            </h6>
                                            <div>
                                                <span class="type-badge type-{{ strtolower($record->type) }}">
                                                    {{ $record->type }}
                                                </span>
                                                <span class="status-badge {{ $record->status == 1 ? 'status-open' : 'status-close' }}">
                                                    {{ $record->status == 1 ? 'Open' : 'Close' }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="record-details">
                                            <div class="detail-item">
                                                <span class="detail-label">Course Name:</span>
                                                <span class="detail-value">{{ $record->course_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Participant Name:</span>
                                                <span class="detail-value">{{ $record->participant_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Type:</span>
                                                <span class="detail-value">{{ $record->type }}</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Session Date:</span>
                                                <span class="detail-value">{{ $record->session_date ? \Carbon\Carbon::parse($record->session_date)->format('d/m/Y') : 'N/A' }}</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Topic:</span>
                                                <span class="detail-value">{{ $record->topic ?? 'N/A' }}</span>
                                            </div>
                                            @if($record->type == 'Memo')
                                                @if(isset($record->response))
                                                <div class="detail-item">
                                                    <span class="detail-label">Response:</span>
                                                    <span class="detail-value">{{ $record->response }}</span>
                                                </div>
                                                @endif
                                                @if(isset($record->conclusion_type))
                                                <div class="detail-item">
                                                    <span class="detail-label">Conclusion Type:</span>
                                                    <span class="detail-value">{{ $record->conclusion_type }}</span>
                                                </div>
                                                @endif
                                                @if(isset($record->conclusion_remark))
                                                <div class="detail-item">
                                                    <span class="detail-label">Conclusion Remark:</span>
                                                    <span class="detail-value">{{ $record->conclusion_remark }}</span>
                                                </div>
                                                @endif
                                            @endif
                                            <div class="detail-item">
                                                <span class="detail-label">Status:</span>
                                                <span class="detail-value">
                                                    <span class="status-badge {{ $record->status == 1 ? 'status-open' : 'status-close' }}">
                                                        {{ $record->status == 1 ? 'Open' : 'Close' }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Conversation Section -->
                                        @if(isset($record->conversations) && $record->conversations->count() > 0)
                                            <div class="conversation-section">
                                                <h6 class="mb-3" style="color: #004a93; font-weight: 600;">
                                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">chat</i>
                                                    Conversation
                                                </h6>
                                                @foreach($record->conversations as $conversation)
                                                    <div class="conversation-item">
                                                        <div class="conversation-header">
                                                            <span>
                                                                @if(isset($conversation->role_type))
                                                                    @if($conversation->role_type == 'f')
                                                                        Faculty/Admin
                                                                    @elseif($conversation->role_type == 's')
                                                                        Student
                                                                    @else
                                                                        Unknown
                                                                    @endif
                                                                @endif
                                                            </span>
                                                            <span>{{ $conversation->created_date ? \Carbon\Carbon::parse($conversation->created_date)->format('d/m/Y H:i') : 'N/A' }}</span>
                                                        </div>
                                                        <div class="conversation-message">
                                                            {{ $conversation->student_decip_incharge_msg ?? 'N/A' }}
                                                        </div>
                                                        @if(isset($conversation->doc_upload) && $conversation->doc_upload)
                                                            <div class="mt-2">
                                                                <a href="{{ asset('storage/' . $conversation->doc_upload) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px;">file_download</i>
                                                                    View Document
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
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
        </div>
    </div>
</div>

@endsection

