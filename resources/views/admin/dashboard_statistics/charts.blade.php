@extends('admin.layouts.master')

@section('title', 'Participant Statistics Charts - Sargam | LBSNAA')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Batch Profile" />

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>{{ $errors->first() }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-body py-3">
            <form method="get" action="{{ route('admin.dashboard-statistics.charts') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-auto">
                    <label for="course_master_pk" class="form-label mb-0 small fw-medium">Course</label>
                    <select name="course_master_pk" id="course_master_pk" class="form-select form-select-sm" style="min-width: 220px;">
                        <option value="">Select course to view batch profile</option>
                        @foreach($courses ?? [] as $c)
                        <option value="{{ $c->pk }}" {{ (isset($course) && $course && $course->pk == $c->pk) ? 'selected' : '' }}>{{ $c->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm">View statistics</button>
                </div>
                <div class="col-12 col-md-auto ms-md-auto">
                    <a href="{{ route('admin.dashboard-statistics.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear me-1"></i> Manage snapshots</a>
                </div>
            </form>
            <p class="mb-0 mt-2 text-muted small">Data is based on <strong>enrolled students</strong> for the selected course.</p>
        </div>
    </div>

    @if(empty($chartData))
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body text-center py-5 px-4">
                <i class="bi bi-bar-chart-line text-muted opacity-50" style="font-size: 4rem;"></i>
                <h4 class="h6 mt-3 text-muted">No data to display</h4>
                <p class="text-muted small mb-4">Select a course above to see the batch profile from enrolled students.</p>
                <a href="{{ route('admin.dashboard-statistics.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-collection me-1"></i> Manage snapshots</a>
            </div>
        </div>
    @else
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            @if(isset($course) && $course)
            <p class="mb-0 text-muted small">Data from enrolled students: <strong>{{ $course->course_name }}</strong></p>
            @elseif(isset($snapshot) && $snapshot)
            <p class="mb-0 text-muted small">Snapshot: <strong>{{ $snapshot->snapshot_date->format('d M Y') }}</strong> @if($snapshot->title)({{ $snapshot->title }})@endif</p>
            @else
            <p class="mb-0 text-muted small">Participant statistics</p>
            @endif
        </div>

        @if(!empty($chartData['summary']) && ($chartData['summary']['total_participants'] > 0 || $chartData['summary']['states_count'] > 0))
        {{-- Summary strip --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden bg-primary bg-opacity-10">
                    <div class="card-body py-3 text-center">
                        <div class="fw-bold text-primary fs-4 lh-1">{{ number_format($chartData['summary']['total_participants']) }}</div>
                        <small class="text-muted d-block mt-1">Total Participants</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                    <div class="card-body py-3 text-center">
                        <div class="fw-bold text-danger lh-1">{{ number_format($chartData['summary']['female_count'] ?? 0) }}</div>
                        <small class="text-muted d-block mt-1">Female</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                    <div class="card-body py-3 text-center">
                        <div class="fw-bold text-primary lh-1">{{ number_format($chartData['summary']['male_count'] ?? 0) }}</div>
                        <small class="text-muted d-block mt-1">Male</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                    <div class="card-body py-3 text-center">
                        <div class="fw-bold text-dark lh-1">{{ $chartData['summary']['states_count'] ?? 0 }}</div>
                        <small class="text-muted d-block mt-1">States / UTs</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                    <div class="card-body py-3 text-center">
                        <div class="fw-bold text-dark lh-1">{{ $chartData['summary']['cadres_count'] ?? 0 }}</div>
                        <small class="text-muted d-block mt-1">Cadres</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                    <div class="card-body py-3 text-center">
                        <div class="fw-bold text-dark lh-1">{{ $chartData['summary']['streams_count'] ?? 0 }}</div>
                        <small class="text-muted d-block mt-1">Streams</small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(isset($course) && $course)
        {{-- Save batch profile as snapshot --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4" style="border-left: 4px solid #198754;">
            <div class="card-body py-3">
                <h6 class="mb-1 fw-bold"><i class="bi bi-save2 me-1 text-success"></i> Save batch profile</h6>
                <p class="text-muted small mb-3">Save current enrolled-student data for this course as a snapshot for later comparison or as default view.</p>
                <form action="{{ route('admin.dashboard-statistics.save-from-course') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="course_master_pk" value="{{ $course->pk }}">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="save_snapshot_date" class="form-label small mb-0 fw-medium">Snapshot date <span class="text-danger">*</span></label>
                        <input type="date" name="snapshot_date" id="save_snapshot_date" value="{{ old('snapshot_date', date('Y-m-d')) }}" class="form-control form-control-sm @error('snapshot_date') is-invalid @enderror" required>
                        @error('snapshot_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="save_title" class="form-label small mb-0 fw-medium">Title</label>
                        <input type="text" name="title" id="save_title" class="form-control form-control-sm" placeholder="e.g. {{ $course->couse_short_name ?? $course->course_name }} batch" value="{{ old('title') }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2 d-flex align-items-center pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="save_is_default" {{ old('is_default') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="save_is_default">Set as default</label>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-save2 me-1"></i> Save snapshot</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="row g-4">
            {{-- Social Groups Distribution --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 fw-bold">Social Groups Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div id="chart-social-groups" style="min-height: 280px;"></div>
                    </div>
                </div>
            </div>
            {{-- Gender Distribution (Donut) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 fw-bold">Gender Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div id="chart-gender" style="min-height: 280px;"></div>
                    </div>
                </div>
            </div>
            {{-- Age Distribution --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 fw-bold">Age Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div id="chart-age" style="min-height: 280px;"></div>
                    </div>
                </div>
            </div>

            {{-- Highest Stream Wise --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 fw-bold">Highest Stream Wise Distribution</h6>
                        <small class="text-muted">Educational background of participants</small>
                    </div>
                    <div class="card-body">
                        <div id="chart-stream" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>

            {{-- Cadre Wise --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 fw-bold">Cadre Wise Distribution</h6>
                        <small class="text-muted">Service category breakdown by gender</small>
                    </div>
                    <div class="card-body">
                        <div id="chart-cadre" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>

            {{-- Domicile State --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 fw-bold">Domicile State Distribution</h6>
                        <small class="text-muted">Geographic distribution across states and UTs</small>
                    </div>
                    <div class="card-body">
                        <div id="chart-domicile" style="min-height: 360px;"></div>
                        @if(!empty($chartData['domicile']['categories']))
                        <p class="text-muted small mt-2 mb-0">Covering {{ count($chartData['domicile']['categories']) }} States and Union Territories</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin_assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartData = @json($chartData ?? []);

    if (Object.keys(chartData).length === 0) return;

    // Social Groups - stacked bar
    if (chartData.social_groups && chartData.social_groups.categories && chartData.social_groups.categories.length) {
        new ApexCharts(document.querySelector("#chart-social-groups"), {
            series: [
                { name: "Female", data: chartData.social_groups.female || [] },
                { name: "Male", data: chartData.social_groups.male || [] }
            ],
            chart: { type: "bar", height: 280, stacked: true, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: "60%" } },
            colors: ["#dc3545", "#0d6efd"],
            xaxis: { categories: chartData.social_groups.categories },
            legend: { position: "bottom" },
            dataLabels: { enabled: false }
        }).render();
    }

    // Gender - donut
    if (chartData.gender && chartData.gender.labels && chartData.gender.labels.length) {
        new ApexCharts(document.querySelector("#chart-gender"), {
            series: chartData.gender.values || [],
            chart: { type: "donut", height: 280 },
            labels: chartData.gender.labels || [],
            colors: ["#dc3545", "#0d6efd"],
            legend: { position: "bottom" },
            plotOptions: { pie: { donut: { size: "65%" } } }
        }).render();
    }

    // Age - stacked bar
    if (chartData.age && chartData.age.categories && chartData.age.categories.length) {
        new ApexCharts(document.querySelector("#chart-age"), {
            series: [
                { name: "Female", data: chartData.age.female || [] },
                { name: "Male", data: chartData.age.male || [] }
            ],
            chart: { type: "bar", height: 280, stacked: true, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: "60%" } },
            colors: ["#dc3545", "#0d6efd"],
            xaxis: { categories: chartData.age.categories },
            legend: { position: "bottom" },
            dataLabels: { enabled: false }
        }).render();
    }

    // Stream - bar
    if (chartData.stream && chartData.stream.categories && chartData.stream.categories.length) {
        new ApexCharts(document.querySelector("#chart-stream"), {
            series: [{ name: "Count", data: chartData.stream.values || [] }],
            chart: { type: "bar", height: 320, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: "55%", borderRadius: 4 } },
            colors: ["#004a93"],
            xaxis: { categories: chartData.stream.categories },
            dataLabels: { enabled: false }
        }).render();
    }

    // Cadre - stacked bar
    if (chartData.cadre && chartData.cadre.categories && chartData.cadre.categories.length) {
        new ApexCharts(document.querySelector("#chart-cadre"), {
            series: [
                { name: "Female", data: chartData.cadre.female || [] },
                { name: "Male", data: chartData.cadre.male || [] }
            ],
            chart: { type: "bar", height: 320, stacked: true, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: "60%" } },
            colors: ["#198754", "#fd7e14"],
            xaxis: { categories: chartData.cadre.categories, labels: { rotate: -45 } },
            legend: { position: "bottom" },
            dataLabels: { enabled: false }
        }).render();
    }

    // Domicile - bar
    if (chartData.domicile && chartData.domicile.categories && chartData.domicile.categories.length) {
        new ApexCharts(document.querySelector("#chart-domicile"), {
            series: [{ name: "Count", data: chartData.domicile.values || [] }],
            chart: { type: "bar", height: 360, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: "70%", borderRadius: 4 } },
            colors: ["#0dcaf0"],
            xaxis: { categories: chartData.domicile.categories, labels: { rotate: -45 } },
            dataLabels: { enabled: false }
        }).render();
    }
});
</script>
@endpush
