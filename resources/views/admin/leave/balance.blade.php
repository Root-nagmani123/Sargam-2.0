@extends(hasRole('Officer Trainee') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Leave Balance')

@section(hasRole('Officer Trainee') ? 'content' : 'setup_content')

@include('admin.leave.partials.styles')

<div class="container-fluid py-3 leave-module">
    <div class="row g-3">
        <div class="col-lg-3">
            @include('admin.leave.partials.sidebar', ['ptBalance' => $ptBalance])
        </div>

        <div class="col-lg-9">
            <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
                <div class="card-body p-3 p-md-4">
                    <h2 class="h5 fw-semibold mb-3">Leave Balance</h2>
                    <p class="text-muted small mb-4">Course: {{ $course->course_name }}</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="pt-balance-card p-4">
                                <div class="small text-muted">PT Balance Remaining</div>
                                <div class="pt-balance-value">{{ number_format($ptBalance['remaining'], 1) }} Days</div>
                                <div class="small text-muted mt-2">As on {{ $ptBalance['as_on'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-4 h-100">
                                <div class="d-flex justify-content-between mb-2"><span>Allocated</span><strong>{{ number_format($ptBalance['allocated'], 1) }}</strong></div>
                                <div class="d-flex justify-content-between mb-2"><span>Used (Approved)</span><strong>{{ number_format($ptBalance['used'], 1) }}</strong></div>
                                <div class="d-flex justify-content-between"><span>Pending</span><strong>{{ number_format($ptBalance['pending'], 1) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-alert p-3 mt-4 small">
                        PT balance is updated once leave is approved by the authority.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
