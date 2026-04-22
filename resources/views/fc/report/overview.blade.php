@extends('admin.layouts.master')
@section('title', 'FC Registration Overview Report')

@section('setup_content')
<div class="container-fluid px-3">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-bar-chart-line me-2"></i>FC Registration — Overview Report
            </h4>
            <small class="text-muted">All registered students with completion status</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.reports.service') }}"  class="btn btn-sm btn-outline-primary"><i class="bi bi-briefcase me-1"></i>By Service</a>
            <a href="{{ route('admin.reports.state') }}"    class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>By State</a>
            <a href="{{ route('admin.reports.documents') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-check me-1"></i>Documents</a>
            <a href="{{ route('admin.reports.bank') }}"     class="btn btn-sm btn-outline-primary"><i class="bi bi-bank me-1"></i>Bank Details</a>
            <a href="{{ route('admin.travel.index') }}"     class="btn btn-sm btn-outline-primary"><i class="bi bi-train-front me-1"></i>Travel Plans</a>
            <a href="{{ route('admin.reports.export','overview') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        @php
        $cards = [
            ['Total Registered',   $summary['total'],      'bi-people-fill',         '#1a3c6e', '#e8f0fb'],
            ['Submitted',          $summary['submitted'],  'bi-send-check-fill',      '#16a34a', '#dcfce7'],
            ['Declaration Done',   $summary['confirmed'],  'bi-patch-check-fill',     '#0891b2', '#e0f2fe'],
            ['Incomplete',         $summary['incomplete'], 'bi-hourglass-split',      '#d97706', '#fef9c3'],
            ['Step 1 Done',        $summary['step1_done'], 'bi-1-circle-fill',        '#7c3aed', '#ede9fe'],
            ['Step 2 Done',        $summary['step2_done'], 'bi-2-circle-fill',        '#7c3aed', '#ede9fe'],
            ['Step 3 Done',        $summary['step3_done'], 'bi-3-circle-fill',        '#7c3aed', '#ede9fe'],
            ['Bank Done',          $summary['bank_done'],  'bi-bank',                 '#0e7490', '#e0f2fe'],
            ['Travel Done',        $summary['travel_done'],'bi-train-front',          '#7c2d12', '#ffedd5'],
            ['Docs Done',          $summary['docs_done'],  'bi-file-earmark-check',   '#065f46', '#dcfce7'],
        ];
        @endphp
        @foreach($cards as [$label,$val,$icon,$color,$bg])
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi {{ $icon }} fs-4" style="color:{{ $color }};"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:{{ $color }};">{{ number_format($val) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">{{ $label }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:8px;">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.reports.overview') }}" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Session</label>
                    <select name="session_id" class="form-select form-select-sm">
                        <option value="">All Sessions</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ request('session_id')==$s->id?'selected':'' }}>{{ $s->session_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="SUBMITTED"  {{ request('status')=='SUBMITTED'?'selected':'' }}>Submitted</option>
                        <option value="INCOMPLETE" {{ request('status')=='INCOMPLETE'?'selected':'' }}>Incomplete</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Service</label>
                    <select name="service_id" class="form-select form-select-sm">
                        <option value="">All Services</option>
                        @foreach($services as $sv)
                            <option value="{{ $sv->id }}" {{ request('service_id')==$sv->id?'selected':'' }}>{{ $sv->service_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">State</label>
                    <select name="state_id" class="form-select form-select-sm">
                        <option value="">All States</option>
                        @foreach($states as $st)
                            <option value="{{ $st->id }}" {{ request('state_id')==$st->id?'selected':'' }}>{{ $st->state_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Gender</label>
                    <select name="gender" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="Male"   {{ request('gender')=='Male'?'selected':'' }}>Male</option>
                        <option value="Female" {{ request('gender')=='Female'?'selected':'' }}>Female</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Name / Username / Mobile"
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary btn-sm px-2"><i class="bi bi-search"></i></button>
                        <a href="{{ route('admin.reports.overview') }}" class="btn btn-outline-secondary btn-sm px-2"><i class="bi bi-x"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="small fw-semibold">Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} students</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Service</th>
                        <th>Cadre</th>
                        <th>State</th>
                        <th>Mobile</th>
                        <th class="text-center">S1</th>
                        <th class="text-center">S2</th>
                        <th class="text-center">S3</th>
                        <th class="text-center">Bank</th>
                        <th class="text-center">Trv</th>
                        <th class="text-center">Docs</th>
                        <th class="text-center">Progress</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $idx => $s)
                    <tr>
                        <td class="px-3">{{ $students->firstItem() + $idx }}</td>
                        <td><code style="font-size:11px">{{ $s->username }}</code></td>
                        <td>{{ $s->full_name ?? '—' }}</td>
                        <td>{{ $s->gender ?? '—' }}</td>
                        <td><span class="badge bg-primary-subtle text-primary" style="font-size:10px;">{{ $s->service_code ?? '—' }}</span></td>
                        <td>{{ $s->cadre ?? '—' }}</td>
                        <td>{{ $s->allotted_state ?? '—' }}</td>
                        <td>{{ $s->mobile_no ?? '—' }}</td>
                        @foreach(['step1_done','step2_done','step3_done','bank_done','travel_done','docs_done'] as $step)
                            <td class="text-center">
                                @if($s->{$step})
                                    <i class="bi bi-check-circle-fill text-success" style="font-size:13px;"></i>
                                @else
                                    <i class="bi bi-circle text-secondary" style="font-size:13px;opacity:.4;"></i>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center">
                            <div class="progress" style="height:6px;width:60px;margin:auto;">
                                <div class="progress-bar bg-success" style="width:{{ ($s->steps_done/6)*100 }}%"></div>
                            </div>
                            <span style="font-size:10px;color:#666;">{{ $s->steps_done }}/6</span>
                        </td>
                        <td class="text-center">
                            @if($s->status === 'SUBMITTED')
                                <span class="badge bg-success" style="font-size:10px;">Submitted</span>
                            @else
                                <span class="badge bg-warning text-dark" style="font-size:10px;">Incomplete</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.reports.student', $s->username) }}"
                               class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px;">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="17" class="text-center py-4 text-muted">No students found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2 px-3">
            {{ $students->links() }}
        </div>
    </div>

</div>
@endsection

