@extends(hasRole('Officer Trainee') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', $pageTitle)

@section(hasRole('Officer Trainee') ? 'content' : 'setup_content')

@include('admin.ot_mdo_escrot_exemption._styles')

@php
    // Duty-type pill class from the duty_type text.
    $otmdoBadge = function ($type) {
        $t = strtolower((string) $type);
        if (str_contains($t, 'escort')) return 'otmdo-badge-escort';
        if (str_contains($t, 'mdo') || str_contains($t, 'moder')) return 'otmdo-badge-mdo';
        return 'otmdo-badge-neutral';
    };

    // Acknowledgement status pill.
    $otmdoStatus = function ($status) {
        $isCompleted = strtolower((string) $status) === 'completed';
        $cls = $isCompleted ? 'otmdo-status-completed' : 'otmdo-status-pending';
        $label = $isCompleted ? 'Completed' : 'Pending';
        return '<span class="otmdo-badge ' . $cls . '">' . $label . '</span>';
    };

    $dutyMaps = $studentData['duty_maps'] ?? [];
@endphp

<div class="container-fluid otmdo px-2 py-2">

    {{-- ===================== HEADER ===================== --}}
    <div class="card otmdo-card mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                {{-- Back + title --}}
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('ot.mdo.escrot.exemption.view') }}" class="otmdo-back" aria-label="Back">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </a>
                    <div>
                        <h4 class="otmdo-title mb-1">{{ $pageTitle }}</h4>
                        <p class="mb-0 small text-muted">Session Moderator / Escort Duty</p>
                    </div>
                </div>

                {{-- OT identity --}}
                <div class="text-md-end otmdo-header-actions">
                    <div class="mb-1">
                        <span class="otmdo-id-label">OT Code:</span>
                        <span class="otmdo-id-value ms-1">{{ $studentData['ot_code'] }}</span>
                    </div>
                    <div class="mb-1">
                        <span class="otmdo-id-label">OT Name:</span>
                        <span class="otmdo-id-value ms-1">{{ $studentData['student_name'] }}</span>
                    </div>
                    <div>
                        <span class="otmdo-id-label">Email:</span>
                        <span class="otmdo-id-value ms-1">{{ $studentData['email'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== TABLE ===================== --}}
    <div class="card otmdo-card">
        <div class="card-body p-0">
            @if(!empty($dutyMaps) && count($dutyMaps) > 0)
            <div class="table-responsive">
                <table class="table align-middle otmdo-table">
                    <thead>
                        <tr>
                            <th class="ps-3 ps-md-4">S. No.</th>
                            <th>Date &amp; Time</th>
                            <th>Course Name</th>
                            <th>Duty Type</th>
                            <th>Faculty</th>
                            <th class="text-center">Status</th>
                            @if($showAcknowledge)
                            <th class="text-center pe-3 pe-md-4">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dutyMaps as $i => $duty)
                        @php
                            $timeText = trim((string) ($duty['time'] ?? ''));
                            $hasTime = $timeText !== '' && $timeText !== 'N/A - N/A' && stripos($timeText, 'N/A') === false;
                            $remark = $duty['description'] ?? null;
                            $hasRemark = $remark && $remark !== 'N/A';
                            $isCompleted = ($duty['status'] ?? 'pending') === 'completed';
                        @endphp
                        <tr>
                            <td class="ps-3 ps-md-4 text-muted">{{ $i + 1 }}</td>
                            <td>
                                <div class="otmdo-date">
                                    {{ !empty($duty['date']) ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}
                                </div>
                                @if($hasTime)
                                <div class="otmdo-time">{{ $timeText }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $duty['course'] ?? 'N/A' }}</span>
                                @if($hasRemark)
                                <div class="small text-muted text-truncate" style="max-width: 22rem;" title="{{ $remark }}">
                                    <i class="bi bi-sticky align-middle me-1" aria-hidden="true"></i>{{ $remark }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="otmdo-badge {{ $otmdoBadge($duty['duty_type'] ?? '') }}">
                                    {{ $duty['duty_type'] ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $duty['faculty'] ?? 'N/A' }}</td>
                            <td class="text-center">{!! $otmdoStatus($duty['status'] ?? 'pending') !!}</td>
                            @if($showAcknowledge)
                            <td class="text-center pe-3 pe-md-4">
                                @if($isCompleted)
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 d-inline-flex align-items-center gap-1" disabled>
                                    <i class="bi bi-check2" aria-hidden="true"></i>
                                    <span>Acknowledged</span>
                                </button>
                                @else
                                <form method="POST" action="{{ route('ot.mdo.escrot.acknowledge') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="duty_pk" value="{{ $duty['id'] }}">
                                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-1 d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                        <span>Acknowledge</span>
                                    </button>
                                </form>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center px-3 px-md-4 py-3 border-top">
                <span class="small text-muted">
                    Showing <span class="fw-semibold text-dark">{{ count($dutyMaps) }}</span>
                    {{ Str::plural('duty', count($dutyMaps)) }}
                </span>
            </div>
            @else
            <div class="otmdo-empty text-center py-5 m-3 m-md-4">
                <i class="bi bi-calendar-x text-secondary" style="font-size:48px;" aria-hidden="true"></i>
                <p class="mt-2 mb-0 fw-semibold text-secondary">No records found</p>
                <p class="small text-muted mb-0">There are no duties in this list yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
