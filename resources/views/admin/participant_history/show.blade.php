@extends('admin.layouts.master')

@section('title', 'Participant History - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
    .participant-info-table th { color: #1a1a1a !important; font-weight: 600; }
    .history-nav-tabs .nav-link { font-weight: 500; }
    .history-nav-tabs .nav-link.active { border-bottom: 3px solid #004a93; background: #f8f9fa; }
    .summary-mini-card { border-radius: 8px; padding: 0.75rem; }
</style>
<div class="container-fluid">
    <x-breadcrum title="Participant History"></x-breadcrum>
    <x-session_message />

    {{-- Participant Header --}}
    <div class="card mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Participant Information</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.dashboard.students.detail', encrypt($student->pk)) }}" class="btn btn-sm btn-light">
                    <i class="fas fa-eye me-1"></i> View Details
                </a>
                <a href="{{ route('admin.dashboard.students') }}" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-borderless participant-info-table mb-0">
                        <tr>
                            <th width="12%" class="py-1">Name:</th>
                            <td class="py-1">{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</td>
                            <th width="12%" class="py-1">OT Code:</th>
                            <td class="py-1">{{ $student->generated_OT_code ?? 'N/A' }}</td>
                            <th width="12%" class="py-1">Service:</th>
                            <td class="py-1">{{ optional($student->service)->service_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="py-1">Email:</th>
                            <td class="py-1">{{ $student->email ?? 'N/A' }}</td>
                            <th class="py-1">Courses:</th>
                            <td class="py-1" colspan="3">{{ $courseMaps->count() }} ({{ $courseMaps->where('active_inactive', 1)->count() }} active)</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Overall Summary Cards --}}
    <div class="row mb-4">
        <div class="col-auto">
            <div class="card text-center summary-mini-card" style="border-left: 4px solid #dc3545;">
                <div class="card-body py-2 px-3">
                    <h5 class="text-danger mb-0">{{ $overallSummary['medical_count'] }}</h5>
                    <small class="text-muted">Medical Exceptions</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card text-center summary-mini-card" style="border-left: 4px solid #ffc107;">
                <div class="card-body py-2 px-3">
                    <h5 class="text-warning mb-0">{{ $overallSummary['duty_count'] }}</h5>
                    <small class="text-muted">Duties</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card text-center summary-mini-card" style="border-left: 4px solid #17a2b8;">
                <div class="card-body py-2 px-3">
                    <h5 class="text-info mb-0">{{ $overallSummary['notice_count'] }}</h5>
                    <small class="text-muted">Notices</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card text-center summary-mini-card" style="border-left: 4px solid #6c757d;">
                <div class="card-body py-2 px-3">
                    <h5 class="text-secondary mb-0">{{ $overallSummary['memo_count'] }}</h5>
                    <small class="text-muted">Memos</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card text-center summary-mini-card" style="border-left: 4px solid #28a745;">
                <div class="card-body py-2 px-3">
                    <h5 class="text-success mb-0">{{ $overallSummary['total_present'] }}</h5>
                    <small class="text-muted">Present</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card text-center summary-mini-card" style="border-left: 4px solid #fd7e14;">
                <div class="card-body py-2 px-3">
                    <h5 class="mb-0" style="color:#fd7e14;">{{ $overallSummary['total_absent'] }}</h5>
                    <small class="text-muted">Absent</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Course Tabs --}}
    <div class="card">
        <div class="card-header border-bottom">
            <div class="nav-tabs-wrapper overflow-auto" style="max-height: 120px;">
            <ul class="nav nav-tabs card-header-tabs history-nav-tabs flex-nowrap" id="courseTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                        <i class="fas fa-th-large me-1"></i> Overview
                    </button>
                </li>
                @foreach($courseMaps as $index => $map)
                    @php $course = $map->course; $cpk = $course->pk ?? $map->course_master_pk; @endphp
                    @if($course)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="course-{{ $cpk }}-tab" data-bs-toggle="tab" data-bs-target="#course-{{ $cpk }}" type="button" role="tab">
                            {{ Str::limit($course->course_name ?? 'Course', 25) }}
                            @if($map->active_inactive != 1)
                                <span class="badge bg-secondary ms-1">Archived</span>
                            @endif
                        </button>
                    </li>
                    @endif
                @endforeach
            </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="tab-content" id="courseTabsContent">
                {{-- Overview Tab --}}
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <h6 class="text-muted mb-3"><i class="fas fa-list me-2"></i>All Courses Summary</h6>
                    @if($courseMaps->isEmpty())
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>No course enrollment found for this participant.
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Medical</th>
                                    <th>Duties</th>
                                    <th>Notices</th>
                                    <th>Memos</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($courseMaps as $map)
                                    @php
                                        $c = $map->course;
                                        $cpk = $c->pk ?? $map->course_master_pk;
                                        $med = $medicalExemptions->get($cpk) ?? collect();
                                        $dut = $duties->get($cpk) ?? collect();
                                        $not = $notices->get($cpk) ?? collect();
                                        $mem = $memos->get($cpk) ?? collect();
                                        $att = $attendanceByCourse[$cpk] ?? ['summary' => (object)['present_count'=>0,'absent_count'=>0,'late_count'=>0], 'sessions' => collect()];
                                    @endphp
                                    @if($c)
                                    <tr>
                                        <td>{{ $c->course_name ?? 'N/A' }}</td>
                                        <td><span class="badge {{ $map->active_inactive == 1 ? 'bg-success' : 'bg-secondary' }}">{{ $map->active_inactive == 1 ? 'Active' : 'Archived' }}</span></td>
                                        <td>{{ $med->count() }}</td>
                                        <td>{{ $dut->count() }}</td>
                                        <td>{{ $not->count() }}</td>
                                        <td>{{ $mem->count() }}</td>
                                        <td>{{ $att['summary']->present_count ?? 0 }}</td>
                                        <td>{{ $att['summary']->absent_count ?? 0 }}</td>
                                        <td>{{ $att['summary']->late_count ?? 0 }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    {{-- Quick Links --}}
                    <div class="mt-4">
                        <h6 class="text-muted mb-2"><i class="fas fa-link me-2"></i>Quick Links</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="#" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-pdf me-1"></i> Participant Report</a>
                            <a href="#" class="btn btn-outline-secondary btn-sm"><i class="fas fa-book me-1"></i> OM & Circular of DOPT</a>
                        </div>
                    </div>
                </div>

                {{-- Per-Course Tabs --}}
                @foreach($courseMaps as $map)
                    @php
                        $course = $map->course;
                        $cpk = $course->pk ?? $map->course_master_pk;
                        $medList = $medicalExemptions->get($cpk) ?? collect();
                        $dutyList = $duties->get($cpk) ?? collect();
                        $noticeList = $notices->get($cpk) ?? collect();
                        $memoList = $memos->get($cpk) ?? collect();
                        $attData = $attendanceByCourse[$cpk] ?? ['summary' => (object)['present_count'=>0,'absent_count'=>0,'late_count'=>0,'total_sessions'=>0], 'sessions' => collect()];
                    @endphp
                    @if($course)
                    <div class="tab-pane fade" id="course-{{ $cpk }}" role="tabpanel">
                        <h6 class="text-primary mb-3">{{ $course->course_name }}</h6>

                        {{-- Academic Summary (Attendance) --}}
                        <div class="card mb-3">
                            <div class="card-header py-2"><i class="fas fa-chart-pie me-2"></i>Academic Report (Attendance)</div>
                            <div class="card-body py-2">
                                <div class="row g-2">
                                    <div class="col"><span class="badge bg-success">Present: {{ $attData['summary']->present_count ?? 0 }}</span></div>
                                    <div class="col"><span class="badge bg-warning">Late: {{ $attData['summary']->late_count ?? 0 }}</span></div>
                                    <div class="col"><span class="badge bg-danger">Absent: {{ $attData['summary']->absent_count ?? 0 }}</span></div>
                                    <div class="col"><span class="badge bg-info">MDO/Escort: {{ (($attData['summary']->mdo_count ?? 0) + ($attData['summary']->escort_count ?? 0)) }}</span></div>
                                    <div class="col"><span class="badge bg-secondary">Medical/Other Exempt: {{ (($attData['summary']->medical_exempt_count ?? 0) + ($attData['summary']->other_exempt_count ?? 0)) }}</span></div>
                                    @php $total = $attData['summary']->total_sessions ?? 0; $pres = $attData['summary']->present_count ?? 0; @endphp
                                    @if($total > 0)
                                    <div class="col-12"><small class="text-muted">Attendance %: {{ number_format(($pres / $total) * 100, 1) }}%</small></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Medical Exceptions --}}
                        <div class="card mb-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-heartbeat me-2"></i>Medical Exceptions ({{ $medList->count() }})</span>
                            </div>
                            <div class="card-body py-2">
                                @forelse($medList as $ex)
                                    <div class="border-bottom pb-2 mb-2">
                                        <small><strong>{{ $ex->from_date ? \Carbon\Carbon::parse($ex->from_date)->format('d M Y') : 'N/A' }} – {{ $ex->to_date ? \Carbon\Carbon::parse($ex->to_date)->format('d M Y') : 'Ongoing' }}</strong> | {{ optional($ex->category)->exemp_category_name ?? 'N/A' }} | {{ optional($ex->speciality)->speciality_name ?? 'N/A' }}</small>
                                        @if($ex->Description)<br><small class="text-muted">{{ Str::limit($ex->Description, 100) }}</small>@endif
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No medical exceptions.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Duties --}}
                        <div class="card mb-3">
                            <div class="card-header py-2"><i class="fas fa-tasks me-2"></i>Duties Assigned ({{ $dutyList->count() }})</div>
                            <div class="card-body py-2">
                                @forelse($dutyList as $d)
                                    <div class="border-bottom pb-2 mb-2">
                                        <span class="badge bg-info">{{ optional($d->mdoDutyTypeMaster)->mdo_duty_type_name ?? 'N/A' }}</span>
                                        <small>{{ $d->mdo_date ? \Carbon\Carbon::parse($d->mdo_date)->format('d M Y') : 'N/A' }} | {{ $d->Time_from ?? '' }} - {{ $d->Time_to ?? '' }} | {{ optional($d->facultyMaster)->full_name ?? 'N/A' }}</small>
                                        @if($d->Remark)<br><small class="text-muted">{{ Str::limit($d->Remark, 80) }}</small>@endif
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No duties assigned.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Notices --}}
                        <div class="card mb-3">
                            <div class="card-header py-2"><i class="fas fa-bell me-2"></i>Notices Received ({{ $noticeList->count() }})</div>
                            <div class="card-body py-2">
                                @forelse($noticeList as $n)
                                    <div class="border-bottom pb-2 mb-2">
                                        <small>{{ $n->session_date ? \Carbon\Carbon::parse($n->session_date)->format('d M Y') : 'N/A' }} | {{ $n->topic ?? 'N/A' }}</small>
                                        <span class="badge {{ ($n->status ?? 0) == 2 ? 'bg-danger' : 'bg-warning' }} ms-1">{{ ($n->status ?? 0) == 2 ? 'Escalated' : 'Pending' }}</span>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No notices.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Memos --}}
                        <div class="card mb-3">
                            <div class="card-header py-2"><i class="fas fa-file-alt me-2"></i>Memos Issued ({{ $memoList->count() }})</div>
                            <div class="card-body py-2">
                                @forelse($memoList as $m)
                                    <div class="border-bottom pb-2 mb-2">
                                        <small>{{ $m->session_date ? \Carbon\Carbon::parse($m->session_date)->format('d M Y') : 'N/A' }} | {{ $m->topic ?? 'N/A' }} | {{ $m->conclusion_type ?? 'N/A' }}</small>
                                        <span class="badge {{ ($m->status ?? 0) == 2 ? 'bg-success' : 'bg-warning' }} ms-1">{{ ($m->status ?? 0) == 2 ? 'Resolved' : 'Pending' }}</span>
                                        @if(!empty($m->response))<br><small class="text-muted">{{ Str::limit($m->response, 80) }}</small>@endif
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No memos issued.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Session Attendance --}}
                        <div class="card mb-3">
                            <div class="card-header py-2"><i class="fas fa-calendar-check me-2"></i>Session Attendance ({{ count($attData['sessions']) }})</div>
                            <div class="card-body py-2">
                                @if(count($attData['sessions']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Session</th>
                                                    <th>Topic</th>
                                                    <th>Venue</th>
                                                    <th>Faculty</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($attData['sessions'] as $sess)
                                                    @php
                                                        $statusLabel = match($sess->status ?? 0) {
                                                            1 => ['Present', 'success'],
                                                            2 => ['Late', 'warning'],
                                                            3 => ['Absent', 'danger'],
                                                            4 => ['MDO', 'info'],
                                                            5 => ['Escort', 'info'],
                                                            6 => ['Medical Exempt', 'secondary'],
                                                            7 => ['Other Exempt', 'secondary'],
                                                            default => ['Not Marked', 'secondary'],
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $sess->session_date ? \Carbon\Carbon::parse($sess->session_date)->format('d M Y') : '-' }}</td>
                                                        <td>{{ ($sess->start_time ?? '-') . ' - ' . ($sess->end_time ?? '-') }}</td>
                                                        <td>{{ Str::limit($sess->subject_topic ?? '-', 30) }}</td>
                                                        <td>{{ $sess->venue_name ?? '-' }}</td>
                                                        <td>{{ Str::limit($sess->faculty_name ?? '-', 20) }}</td>
                                                        <td><span class="badge bg-{{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">No session attendance records.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
