@extends('admin.layouts.master')

@section('title', 'Participant History - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
    .participant-info-table th { color: #1a1a1a !important; font-weight: 600; }
    .history-nav-tabs .nav-link { font-weight: 500; border-radius: 0.375rem 0.375rem 0 0; }
    .history-nav-tabs .nav-link.active { border-bottom: 3px solid #004a93; background: #f8f9fa; color: #004a93; }
    .participant-hero { background:  #004a93; }
    .metric-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .metric-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
    .cursor-pointer { cursor: pointer; }
    .academic-timeline { position: relative; padding-left: 0; }
    .academic-timeline::before { content: ''; position: absolute; left: 15px; top: 24px; bottom: 24px; width: 2px; background: #dee2e6; border-radius: 1px; }
    .academic-timeline-item { position: relative; padding-left: 44px; padding-bottom: 1rem; }
    .academic-timeline-item:last-child { padding-bottom: 0; }
    .academic-timeline-marker { position: absolute; left: 0; top: 20px; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #fff; z-index: 1; }
    .academic-timeline-marker.completed { background: #198754; }
    .academic-timeline-marker.in-progress { background: #0d6efd; }
    .academic-timeline-marker.not-started { background: #6c757d; color: #fff; }
    .academic-timeline-card { border-radius: 0.5rem; padding: 1rem 1.25rem; text-align: left; transition: box-shadow 0.2s ease, border-color 0.2s ease; cursor: pointer; border: 1px solid #e9ecef; background: #fff; }
    .academic-timeline-card:hover { box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08); border-color: #dee2e6; }
    .academic-timeline-card.active { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,0.25); }
    .academic-timeline-card.bg-primary-subtle { background: rgba(13,110,253,0.08); border-color: rgba(13,110,253,0.3); }
    .memo-issue-card { border-left: 4px solid #fd7e14 !important; }
</style>
<div class="container-fluid">
    <x-breadcrum title="Participant History"></x-breadcrum>

    {{-- Participant Hero Banner --}}
    <div class="participant-hero rounded-3 shadow-sm mb-4 p-4 text-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <small class="text-white d-block mb-1">Participant History</small>
                <h1 class="h3 mb-2 fw-bold text-white">{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</h1>
                <p class="mb-0 small text-white">
                    {{ optional($student->service)->service_name ?? 'N/A' }}
                    @if(isset($student->cadre) && $student->cadre)
                        • {{ $student->cadre->cadre_name ?? 'N/A' }}
                    @endif
                    • {{ $student->generated_OT_code ?? 'N/A' }}
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="bg-dark bg-opacity-25 rounded-3 px-3 py-2 text-nowrap me-2">
                    <small class="d-block text-white-50">Courses</small>
                    <span class="fw-bold">{{ $courseMaps->count() }} ({{ $courseMaps->where('active_inactive', 1)->count() }} active)</span>
                </div>
                <a href="{{ route('admin.dashboard.students.detail', encrypt($student->pk)) }}" class="btn btn-sm btn-light">
                    <i class="fas fa-eye me-1"></i> View Details
                </a>
                <a href="{{ route('admin.dashboard.students') }}" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-danger fw-bold">{{ $overallSummary['medical_count'] }}</h4>
                    <small class="text-body-secondary">MEDICAL</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-warning fw-bold">{{ $overallSummary['duty_count'] }}</h4>
                    <small class="text-body-secondary">DUTIES</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-info fw-bold">{{ $overallSummary['notice_count'] }}</h4>
                    <small class="text-body-secondary">NOTICES</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-secondary fw-bold">{{ $overallSummary['memo_count'] }}</h4>
                    <small class="text-body-secondary">MEMOS</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-success fw-bold">{{ $overallSummary['total_present'] }}</h4>
                    <small class="text-body-secondary">PRESENT</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer">
                <div class="card-body py-3">
                    <h4 class="mb-0 fw-bold" style="color:#fd7e14;">{{ $overallSummary['total_absent'] }}</h4>
                    <small class="text-body-secondary">ABSENT</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
    {{-- Course Tabs --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
            <h5 class="mb-0 fw-bold text-body-emphasis">Academic Progression</h5>
        </div>
        <div class="card-body pt-0 px-4 pb-4">
            <div class="academic-timeline" id="courseTabs" role="tablist">
                {{-- Overview --}}
                <div class="academic-timeline-item">
                    <div class="academic-timeline-marker completed" aria-hidden="true"><i class="fas fa-check fa-xs"></i></div>
                    <button type="button" class="academic-timeline-card w-100 active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" role="tab" aria-selected="true">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="fw-bold text-body-emphasis">Overview</span>
                            <span class="badge bg-success bg-opacity-25 text-success">Completed</span>
                        </div>
                        <div class="small text-body-secondary mb-2"><i class="fas fa-calendar-alt me-1"></i>All courses summary</div>
                        <div class="d-flex gap-4 small">
                            <div><span class="text-body-secondary d-block">ATTENDANCE</span><span class="fw-semibold text-success">—</span></div>
                            <div><span class="text-body-secondary d-block">GPA</span><span class="fw-semibold">—</span></div>
                            <div><span class="text-body-secondary d-block">COURSES</span><span class="fw-semibold">{{ $courseMaps->count() }}/{{ $courseMaps->count() }}</span></div>
                        </div>
                    </button>
                </div>
                @foreach($courseMaps as $index => $map)
                    @php
                        $course = $map->course;
                        $cpk = $course->pk ?? $map->course_master_pk;
                        $att = $attendanceByCourse[$cpk] ?? ['summary' => (object)['present_count'=>0,'absent_count'=>0,'late_count'=>0,'total_sessions'=>0], 'sessions' => collect()];
                        $totalSess = $att['summary']->total_sessions ?? 0;
                        $presentCnt = $att['summary']->present_count ?? 0;
                        $attPct = $totalSess > 0 ? round(($presentCnt / $totalSess) * 100) : 0;
                        $isActive = $map->active_inactive == 1;
                    @endphp
                    @if($course)
                <div class="academic-timeline-item">
                    <div class="academic-timeline-marker {{ $isActive ? 'in-progress' : 'completed' }}" aria-hidden="true">
                        @if($isActive)<span>{{ $index + 2 }}</span>@else<i class="fas fa-check fa-xs"></i>@endif
                    </div>
                    <button type="button" class="academic-timeline-card w-100 {{ $isActive ? 'bg-primary-subtle' : '' }}" id="course-{{ $cpk }}-tab" data-bs-toggle="tab" data-bs-target="#course-{{ $cpk }}" role="tab" aria-selected="false">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="fw-bold {{ $isActive ? 'text-primary' : 'text-body-emphasis' }}">{{ Str::limit($course->course_name ?? 'Course', 40) }}</span>
                            @if($isActive)
                                <span class="badge bg-primary bg-opacity-25 text-primary">In Progress</span>
                            @else
                                <span class="badge bg-success bg-opacity-25 text-success">Completed</span>
                            @endif
                        </div>
                        <div class="small text-body-secondary mb-2"><i class="fas fa-calendar-alt me-1"></i>N/A – N/A</div>
                        <div class="d-flex gap-4 small">
                            <div><span class="text-body-secondary d-block">ATTENDANCE</span><span class="fw-semibold {{ $attPct >= 75 ? 'text-success' : ($isActive ? 'text-primary' : 'text-body') }}">{{ $totalSess > 0 ? $attPct . '%' : '0%' }}</span></div>
                            <div><span class="text-body-secondary d-block">GPA</span><span class="fw-semibold">—</span></div>
                            <div><span class="text-body-secondary d-block">COURSES</span><span class="fw-semibold">{{ $presentCnt }}/{{ $totalSess > 0 ? $totalSess : '—' }}</span></div>
                        </div>
                    </button>
                </div>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="card-body px-4 pb-4 pt-0 border-top">
            <div class="tab-content" id="courseTabsContent">
                {{-- Overview Tab --}}
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <h6 class="text-body-secondary mb-3 fw-semibold"><i class="fas fa-list me-2"></i>All Courses Summary</h6>
                    @if($courseMaps->isEmpty())
                        <div class="alert alert-info mb-0 rounded-3">
                            <i class="fas fa-info-circle me-2"></i>No course enrollment found for this participant.
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap mb-0">
                            <thead class="table-light">
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

                    @php $allMemos = $memos->flatten(1); $allMedical = $medicalExemptions->flatten(1); @endphp
                    <div class="row g-4 mt-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white border-0 pt-3 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h5 class="mb-0 fw-bold text-body-emphasis">Memos Issued</h5>
                                    <span class="badge rounded-pill bg-warning text-dark">{{ $allMemos->count() }}</span>
                                </div>
                                <div class="card-body px-4 pb-4">
                                    @forelse($allMemos as $m)
                                    <div class="memo-issue-card rounded-3 p-3 bg-light bg-opacity-50 mb-3 border border-0 border-start">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <span class="fw-bold text-body-emphasis">{{ Str::limit($m->topic ?? 'Memo', 40) }}</span>
                                            <small class="text-body-secondary text-nowrap">{{ $m->session_date ? \Carbon\Carbon::parse($m->session_date)->format('Y-m-d') : 'N/A' }}</small>
                                        </div>
                                        <p class="mb-1 small text-body">{{ Str::limit($m->conclusion_remark ?? $m->conclusion_type ?? $m->topic ?? '—', 120) }}</p>
                                        @if(!empty($m->response))<small class="text-body-secondary">Response: {{ Str::limit($m->response, 80) }}</small>@endif
                                        @if(isset($m->course_name) && $m->course_name)<small class="d-block text-body-secondary mt-1">Course: {{ $m->course_name }}</small>@endif
                                    </div>
                                    @empty
                                    <p class="text-muted mb-0">No memos issued.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white border-0 pt-3 pb-2 px-4">
                                    <h5 class="mb-0 fw-bold text-body-emphasis">Medical Exemptions</h5>
                                </div>
                                <div class="card-body px-4 pb-4">
                                    @forelse($allMedical as $ex)
                                    <div class="rounded-3 p-3 bg-light bg-opacity-50 mb-3 border">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <span class="fw-bold text-body-emphasis">{{ optional($ex->category)->exemp_category_name ?? 'Medical Exemption' }}</span>
                                            <span class="badge bg-success rounded-pill">APPROVED</span>
                                        </div>
                                        <p class="mb-2 small text-body">{{ Str::limit($ex->Description ?? '—', 120) }}</p>
                                        <small class="text-body-secondary"><i class="fas fa-calendar-alt me-1"></i>{{ $ex->from_date ? \Carbon\Carbon::parse($ex->from_date)->format('Y-m-d') : 'N/A' }} – {{ $ex->to_date ? \Carbon\Carbon::parse($ex->to_date)->format('Y-m-d') : 'Ongoing' }}</small>
                                    </div>
                                    @empty
                                    <p class="text-muted mb-0">No medical exemptions.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Links --}}
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-body-secondary mb-2 fw-semibold"><i class="fas fa-link me-2"></i>Quick Links</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="#" class="btn btn-outline-primary btn-sm rounded-3"><i class="fas fa-file-pdf me-1"></i> Participant Report</a>
                            <a href="#" class="btn btn-outline-secondary btn-sm rounded-3"><i class="fas fa-book me-1"></i> OM & Circular of DOPT</a>
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
                        <h6 class="text-primary mb-3 fw-semibold">{{ $course->course_name }}</h6>

                        {{-- Academic Summary (Attendance) --}}
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white border-0 py-2 px-3 fw-semibold"><i class="fas fa-chart-pie me-2 text-primary"></i>Academic Report (Attendance)</div>
                            <div class="card-body py-3 px-3">
                                <div class="row g-2">
                                    <div class="col"><span class="badge bg-success rounded-pill">Present: {{ $attData['summary']->present_count ?? 0 }}</span></div>
                                    <div class="col"><span class="badge bg-warning text-dark rounded-pill">Late: {{ $attData['summary']->late_count ?? 0 }}</span></div>
                                    <div class="col"><span class="badge bg-danger rounded-pill">Absent: {{ $attData['summary']->absent_count ?? 0 }}</span></div>
                                    <div class="col"><span class="badge bg-info rounded-pill">MDO/Escort: {{ (($attData['summary']->mdo_count ?? 0) + ($attData['summary']->escort_count ?? 0)) }}</span></div>
                                    <div class="col"><span class="badge bg-secondary rounded-pill">Medical/Other Exempt: {{ (($attData['summary']->medical_exempt_count ?? 0) + ($attData['summary']->other_exempt_count ?? 0)) }}</span></div>
                                    @php $total = $attData['summary']->total_sessions ?? 0; $pres = $attData['summary']->present_count ?? 0; @endphp
                                    @if($total > 0)
                                    <div class="col-12"><small class="text-body-secondary">Attendance %: {{ number_format(($pres / $total) * 100, 1) }}%</small></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Medical Exceptions --}}
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white border-0 py-2 px-3 fw-semibold"><i class="fas fa-heartbeat me-2 text-danger"></i>Medical Exceptions ({{ $medList->count() }})</div>
                            <div class="card-body py-3 px-3">
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
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white border-0 py-2 px-3 fw-semibold"><i class="fas fa-tasks me-2 text-primary"></i>Duties Assigned ({{ $dutyList->count() }})</div>
                            <div class="card-body py-3 px-3">
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
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white border-0 py-2 px-3 fw-semibold"><i class="fas fa-bell me-2 text-warning"></i>Notices Received ({{ $noticeList->count() }})</div>
                            <div class="card-body py-3 px-3">
                                @forelse($noticeList as $n)
                                    <div class="border-bottom pb-2 mb-2">
                                        <small>{{ $n->session_date ? \Carbon\Carbon::parse($n->session_date)->format('d M Y') : 'N/A' }} | {{ $n->topic ?? 'N/A' }}</small>
                                        <span class="badge {{ ($n->status ?? 0) == 2 ? 'bg-danger' : 'bg-warning text-dark' }} ms-1">{{ ($n->status ?? 0) == 2 ? 'Escalated' : 'Pending' }}</span>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No notices.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Memos --}}
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white border-0 py-2 px-3 fw-semibold"><i class="fas fa-file-alt me-2 text-secondary"></i>Memos Issued ({{ $memoList->count() }})</div>
                            <div class="card-body py-3 px-3">
                                @forelse($memoList as $m)
                                    <div class="border-bottom pb-2 mb-2">
                                        <small>{{ $m->session_date ? \Carbon\Carbon::parse($m->session_date)->format('d M Y') : 'N/A' }} | {{ $m->topic ?? 'N/A' }} | {{ $m->conclusion_type ?? 'N/A' }}</small>
                                        <span class="badge {{ ($m->status ?? 0) == 2 ? 'bg-success' : 'bg-warning text-dark' }} ms-1">{{ ($m->status ?? 0) == 2 ? 'Resolved' : 'Pending' }}</span>
                                        @if(!empty($m->response))<br><small class="text-muted">{{ Str::limit($m->response, 80) }}</small>@endif
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No memos issued.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Session Attendance --}}
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white border-0 py-2 px-3 fw-semibold"><i class="fas fa-calendar-check me-2 text-success"></i>Session Attendance ({{ count($attData['sessions']) }})</div>
                            <div class="card-body py-3 px-3">
                                @if(count($attData['sessions']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-hover align-middle mb-0">
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

        {{-- Right Sidebar: Participant Profile & Actions --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-user-graduate me-2 text-primary"></i>Participant Profile</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <small class="text-body-secondary text-uppercase small">Full Name</small>
                            <p class="mb-0 fw-medium">{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</p>
                        </div>
                        <div>
                            <small class="text-body-secondary text-uppercase small">Email Address</small>
                            <p class="mb-0 fw-medium">{{ $student->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <small class="text-body-secondary text-uppercase small">OT Code</small>
                            <p class="mb-0 fw-medium">{{ $student->generated_OT_code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <small class="text-body-secondary text-uppercase small">Service</small>
                            <p class="mb-0 fw-medium">{{ optional($student->service)->service_name ?? 'N/A' }}</p>
                        </div>
                        @if(isset($student->cadre) && $student->cadre)
                        <div>
                            <small class="text-body-secondary text-uppercase small">Cadre</small>
                            <p class="mb-0 fw-medium">{{ $student->cadre->cadre_name ?? 'N/A' }}</p>
                        </div>
                        @endif
                        <div>
                            <small class="text-body-secondary text-uppercase small">Courses</small>
                            <p class="mb-0 fw-medium">{{ $courseMaps->count() }} ({{ $courseMaps->where('active_inactive', 1)->count() }} active)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h5 class="mb-0 fw-semibold">Reports & Actions</h5>
                </div>
                <div class="card-body px-4 pb-4 d-flex flex-column gap-2">
                    <a href="{{ route('admin.dashboard.students.detail', encrypt($student->pk)) }}" class="btn btn-outline-secondary w-100 justify-content-start rounded-3">
                        <i class="fas fa-eye me-2"></i>View Student Details
                    </a>
                    <a href="#" class="btn btn-outline-secondary w-100 justify-content-start rounded-3">
                        <i class="fas fa-file-pdf me-2"></i>Participant Report
                    </a>
                    <a href="mailto:{{ $student->email ?? '#' }}" class="btn btn-outline-secondary w-100 justify-content-start rounded-3">
                        <i class="fas fa-envelope me-2"></i>Contact Participant
                    </a>
                    <a href="{{ route('admin.dashboard.students') }}" class="btn btn-outline-secondary w-100 justify-content-start rounded-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Student List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tabContent = document.querySelector('#courseTabsContent');
    var tabList = document.querySelector('#courseTabs');
    if (!tabContent || !tabList) return;
    var cards = tabList.querySelectorAll('.academic-timeline-card');
    function updateActiveCard() {
        var activePane = tabContent.querySelector('.tab-pane.active');
        var targetId = activePane ? activePane.id : null;
        cards.forEach(function(btn) {
            var isActive = btn.getAttribute('data-bs-target') === '#' + targetId;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }
    tabContent.addEventListener('shown.bs.tab', updateActiveCard);
    updateActiveCard();
});
</script>
@endpush
@endsection
