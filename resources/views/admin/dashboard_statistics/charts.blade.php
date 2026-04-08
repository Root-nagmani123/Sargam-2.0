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
            <form method="get" action="{{ route('admin.dashboard-statistics.charts') }}"
                class="row g-2 align-items-end">
                <div class="col-12 col-md-auto">
                    <label for="course_master_pk" class="form-label mb-0 small fw-medium">Course</label>
                    <select name="course_master_pk" id="course_master_pk" class="form-select form-select-sm"
                        style="min-width: 220px;">
                        <option value="">Select course to view batch profile</option>
                        @foreach($courses ?? [] as $c)
                        <option value="{{ $c->pk }}"
                            {{ (isset($course) && $course && $course->pk == $c->pk) ? 'selected' : '' }}>
                            {{ $c->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm">View statistics</button>
                </div>
                <div class="col-12 col-md-auto ms-md-auto">
                    <a href="{{ route('admin.dashboard-statistics.index') }}"
                        class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear me-1"></i> Manage snapshots</a>
                </div>
            </form>
            <p class="mb-0 mt-2 text-muted small">Data is based on <strong>enrolled students</strong> for the selected
                course.</p>
        </div>
    </div>

    @if(empty($chartData))
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body text-center py-5 px-4">
            <i class="bi bi-bar-chart-line text-muted opacity-50" style="font-size: 4rem;"></i>
            <h4 class="h6 mt-3 text-muted">No data to display</h4>
            <p class="text-muted small mb-4">Select a course above to see the batch profile from enrolled students.</p>
            <a href="{{ route('admin.dashboard-statistics.index') }}" class="btn btn-outline-primary btn-sm"><i
                    class="bi bi-collection me-1"></i> Manage snapshots</a>
        </div>
    </div>
    @else
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        @if(isset($course) && $course)
        <p class="mb-0 text-muted small">Data from enrolled students: <strong>{{ $course->course_name }}</strong></p>
        @elseif(isset($snapshot) && $snapshot)
        <p class="mb-0 text-muted small">Snapshot: <strong>{{ $snapshot->snapshot_date->format('d M Y') }}</strong>
            @if($snapshot->title)({{ $snapshot->title }})@endif</p>
        @else
        <p class="mb-0 text-muted small">Participant statistics</p>
        @endif
    </div>

    @php
        $totalParticipants = $chartData['summary']['total_participants'] ?? 0;
        $femaleCount = $chartData['summary']['female_count'] ?? 0;
        $maleCount = $chartData['summary']['male_count'] ?? 0;
        $femalePct = $totalParticipants > 0 ? round(($femaleCount / $totalParticipants) * 100) : 0;
        $malePct = $totalParticipants > 0 ? round(($maleCount / $totalParticipants) * 100) : 0;
        $courseName = (isset($course) && $course) ? $course->course_name : 'Batch Profile';
        $courseYear = (isset($course) && $course && !empty($course->start_date)) ? \Carbon\Carbon::parse($course->start_date)->format('Y') : date('Y');
    @endphp

    {{-- ═══════════ MAIN BATCH PROFILE CARD (teal border like image) ═══════════ --}}
    <div class="card border-0 shadow rounded-3 overflow-hidden mb-4" style="border: 3px solid #1a8a7d;">
        {{-- Header bar --}}
        <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #e8f4f2 0%, #f0faf8 100%);">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA" style="height: 50px;" onerror="this.style.display='none'">
                <div>
                    <h4 class="mb-0 fw-bold" style="color: #d32f2f; font-family: 'Georgia', serif;">Batch Profile {{ $courseYear }}</h4>
                    <small class="text-muted">{{ $courseName }}</small>
                </div>
            </div>
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png"
                 alt="Emblem of India" style="height: 50px;" onerror="this.style.display='none'">
        </div>

        <div class="card-body px-4 py-3">
            <div class="row g-3">
                {{-- LEFT: Summary stats + Gender visual + Stream chart --}}
                <div class="col-12 col-lg-5">
                    {{-- Summary badges --}}
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge rounded-pill px-3 py-2 fs-6" style="background: #1a8a7d;">
                            {{ $totalParticipants }} Officer Trainees
                        </span>
                        <span class="badge rounded-pill bg-primary px-3 py-2 fs-6">{{ $maleCount }}</span>
                        <span class="badge rounded-pill px-3 py-2 fs-6" style="background: #e91e90;">{{ $femaleCount }}</span>
                        <span class="badge rounded-pill bg-secondary px-3 py-2 fs-6">Total: {{ $totalParticipants }}</span>
                    </div>

                    {{-- Gender icons --}}
                    <div class="d-flex align-items-end justify-content-center gap-4 mb-3 py-3 rounded-3" style="background: #f8fffe;">
                        {{-- Female --}}
                        <div class="text-center">
                            <div style="font-size: 3.5rem; color: #e91e90;">
                                <i class="bi bi-person-standing-dress"></i>
                            </div>
                            <div class="fw-bold fs-5" style="color: #e91e90;">{{ $femaleCount }}</div>
                            <div class="text-muted small fw-semibold">{{ $femalePct }}%</div>
                        </div>
                        {{-- Male --}}
                        <div class="text-center">
                            <div style="font-size: 3.5rem; color: #1565c0;">
                                <i class="bi bi-person-standing"></i>
                            </div>
                            <div class="fw-bold fs-5" style="color: #1565c0;">{{ $maleCount }}</div>
                            <div class="text-muted small fw-semibold">{{ $malePct }}%</div>
                        </div>
                    </div>

                    {{-- Gender donut (Average Age) --}}
                    <div class="card border rounded-3 mb-3">
                        <div class="card-header bg-transparent border-0 pb-0 text-center">
                            <h6 class="mb-0 fw-bold">Average Age</h6>
                        </div>
                        <div class="card-body pt-1 pb-2">
                            <div id="chart-age-donut" style="min-height: 200px;"></div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Stream distribution --}}
                <div class="col-12 col-lg-7">
                    <div class="card border rounded-3 h-100">
                        <div class="card-header bg-transparent border-0 pb-0 text-center">
                            <h6 class="mb-0 fw-bold">Highest Stream Wise Distribution</h6>
                        </div>
                        <div class="card-body pt-1">
                            <div id="chart-stream-bar" style="min-height: 280px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Social Group wise Distribution --}}
            <div class="card border rounded-3 mt-3">
                <div class="card-header bg-transparent border-0 pb-0 text-center">
                    <h6 class="mb-0 fw-bold">Social Group wise Distribution</h6>
                </div>
                <div class="card-body pt-1">
                    <div id="chart-social-by-age" style="min-height: 280px;"></div>
                </div>
            </div>

            {{-- Age Distribution --}}
            <div class="card border rounded-3 mt-3">
                <div class="card-header bg-transparent border-0 pb-0 text-center">
                    <h6 class="mb-0 fw-bold">Age Distribution</h6>
                </div>
                <div class="card-body pt-1">
                    <div id="chart-age-distribution" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ CADRE WISE DISTRIBUTION ═══════════ --}}
    <div class="card border-0 shadow rounded-3 overflow-hidden mb-4" style="border: 3px solid #1a8a7d;">
        <div class="card-body px-4 py-3">
            <div class="card border rounded-3">
                <div class="card-header bg-transparent border-0 pb-0 text-center">
                    <h6 class="mb-0 fw-bold">Cadre Wise Distribution</h6>
                </div>
                <div class="card-body pt-1">
                    <div id="chart-cadre" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ DOMICILE STATE DISTRIBUTION ═══════════ --}}
    <div class="card border-0 shadow rounded-3 overflow-hidden mb-4" style="border: 3px solid #1a8a7d;">
        <div class="card-body px-4 py-3">
            <div class="card border rounded-3">
                <div class="card-header bg-transparent border-0 pb-0 text-center">
                    <h6 class="mb-0 fw-bold">Domicile State Distribution</h6>
                </div>
                <div class="card-body pt-1">
                    <div id="chart-domicile" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($course) && $course)
    {{-- Save batch profile as snapshot --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4" style="border-left: 4px solid #198754;">
        <div class="card-body py-3">
            <h6 class="mb-1 fw-bold"><i class="bi bi-save2 me-1 text-success"></i> Save batch profile</h6>
            <p class="text-muted small mb-3">Save current enrolled-student data for this course as a snapshot for later
                comparison or as default view.</p>
            <form action="{{ route('admin.dashboard-statistics.save-from-course') }}" method="POST"
                class="row g-3 align-items-end">
                @csrf
                <input type="hidden" name="course_master_pk" value="{{ $course->pk }}">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="save_snapshot_date" class="form-label small mb-0 fw-medium">Snapshot date <span
                            class="text-danger">*</span></label>
                    <input type="date" name="snapshot_date" id="save_snapshot_date"
                        value="{{ old('snapshot_date', date('Y-m-d')) }}"
                        class="form-control form-control-sm @error('snapshot_date') is-invalid @enderror" required>
                    @error('snapshot_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="save_title" class="form-label small mb-0 fw-medium">Title</label>
                    <input type="text" name="title" id="save_title" class="form-control form-control-sm"
                        placeholder="e.g. {{ $course->couse_short_name ?? $course->course_name }} batch"
                        value="{{ old('title') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-flex align-items-center pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="save_is_default"
                            {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="save_is_default">Set as default</label>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-save2 me-1"></i> Save
                        snapshot</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin_assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartData = @json($chartData ?? []);
    var chartInstances = [];

    if (!chartData || typeof chartData !== 'object' || Object.keys(chartData).length === 0) {
        return;
    }

    function toNumberArray(arr) {
        if (Array.isArray(arr)) {
            return arr.map(function(v) { var n = Number(v); return Number.isFinite(n) ? n : 0; });
        }
        if (arr && typeof arr === 'object') {
            return Object.values(arr).map(function(v) { var n = Number(v); return Number.isFinite(n) ? n : 0; });
        }
        return [];
    }

    function toStringArray(arr) {
        if (Array.isArray(arr)) return arr.map(String);
        if (arr && typeof arr === 'object') return Object.values(arr).map(String);
        return [];
    }

    function sumArrays(a, b) {
        var len = Math.max(a.length, b.length);
        var out = [];
        for (var i = 0; i < len; i++) out.push((a[i] || 0) + (b[i] || 0));
        return out;
    }

    function limitTop(labels, values, maxItems) {
        var pairs = labels.map(function(label, idx) { return { label: label, value: values[idx] || 0 }; });
        pairs.sort(function(a, b) { return b.value - a.value; });
        pairs = pairs.slice(0, maxItems);
        return {
            labels: pairs.map(function(p) { return p.label; }),
            values: pairs.map(function(p) { return p.value; })
        };
    }

    function safeRender(selector, options, label) {
        var node = document.querySelector(selector);
        if (!node) return;
        try {
            var chart = new ApexCharts(node, options);
            chart.render();
            chartInstances.push(chart);
        } catch (e) {
            console.error(label + ' render failed:', e);
        }
    }

    // ── Colors matching the reference image ──
    var colFemale = '#e91e90';
    var colMale = '#1565c0';
    var colGreen = '#8bc34a';
    var colOrange = '#ff9800';
    var streamColors = ['#d32f2f', '#1565c0', '#2e7d32', '#e65100', '#6a1b9a', '#00838f', '#ff6f00', '#4527a0', '#1b5e20', '#b71c1c'];

    // ── Age data ──
    var ageCategories = toStringArray(chartData.age && chartData.age.categories ? chartData.age.categories : []);
    var ageFemale = toNumberArray(chartData.age && chartData.age.female ? chartData.age.female : []);
    var ageMale = toNumberArray(chartData.age && chartData.age.male ? chartData.age.male : []);
    var ageTotals = sumArrays(ageFemale, ageMale);
    var hasAgeData = ageTotals.some(function(v) { return v > 0; });

    // ═══ 1. Average Age — Pie chart ═══
    safeRender("#chart-age-donut", {
        series: hasAgeData ? ageTotals : [1],
        chart: { type: "pie", height: 200, toolbar: { show: false } },
        labels: hasAgeData ? ageCategories : ['No data'],
        colors: ['#1565c0', '#e91e90', '#ff9800', '#4caf50', '#9c27b0', '#607d8b'],
        dataLabels: { enabled: true, style: { fontSize: '11px' } },
        legend: { position: "right", fontSize: "11px" }
    }, "Average Age");

    // ═══ 2. Highest Stream Wise Distribution — Vertical bar ═══
    var streamLabels = toStringArray(chartData.stream && chartData.stream.categories ? chartData.stream.categories : []);
    var streamValues = toNumberArray(chartData.stream && chartData.stream.values ? chartData.stream.values : []);
    var streamTop = limitTop(streamLabels, streamValues, 12);
    safeRender("#chart-stream-bar", {
        series: [{ name: "Count", data: streamTop.values.length ? streamTop.values : [0] }],
        chart: { type: "bar", height: 280, toolbar: { show: false } },
        colors: streamColors,
        plotOptions: {
            bar: { distributed: true, columnWidth: "55%", borderRadius: 2 }
        },
        xaxis: {
            categories: streamTop.labels.length ? streamTop.labels : ['No data'],
            labels: { rotate: -35, style: { fontSize: '10px' }, trim: true, maxHeight: 80 }
        },
        dataLabels: { enabled: true, offsetY: -15, style: { fontSize: '10px', colors: ['#333'] } },
        legend: { show: false },
        grid: { borderColor: "#eef1f5" }
    }, "Highest Stream Wise Distribution");

    // ═══ 3. Social Group wise Distribution — Grouped bar (Female / Male) ═══
    var socialLabels = toStringArray(chartData.social_groups && chartData.social_groups.categories ? chartData.social_groups.categories : []);
    var socialFemale = toNumberArray(chartData.social_groups && chartData.social_groups.female ? chartData.social_groups.female : []);
    var socialMale = toNumberArray(chartData.social_groups && chartData.social_groups.male ? chartData.social_groups.male : []);
    safeRender("#chart-social-by-age", {
        series: [
            { name: "Female", data: socialFemale.length ? socialFemale : [0] },
            { name: "Male", data: socialMale.length ? socialMale : [0] }
        ],
        chart: { type: "bar", height: 280, toolbar: { show: false } },
        colors: [colFemale, colMale],
        plotOptions: { bar: { columnWidth: "50%", borderRadius: 2 } },
        xaxis: {
            categories: socialLabels.length ? socialLabels : ['No data'],
            labels: { style: { fontSize: '11px' } }
        },
        dataLabels: { enabled: true, style: { fontSize: '10px' } },
        legend: { position: "bottom", fontSize: "12px" },
        grid: { borderColor: "#eef1f5" }
    }, "Social Group wise Distribution");

    // ═══ 4. Age Distribution — Grouped bar (Female / Male) ═══
    safeRender("#chart-age-distribution", {
        series: [
            { name: "Female", data: ageFemale.length ? ageFemale : [0] },
            { name: "Male", data: ageMale.length ? ageMale : [0] }
        ],
        chart: { type: "bar", height: 300, toolbar: { show: false } },
        colors: [colFemale, colMale],
        plotOptions: { bar: { columnWidth: "45%", borderRadius: 3 } },
        xaxis: {
            categories: ageCategories.length ? ageCategories : ['No data'],
            labels: { style: { fontSize: '12px', fontWeight: 600 } }
        },
        dataLabels: { enabled: true, style: { fontSize: '11px' } },
        legend: { position: "bottom", fontSize: "12px" },
        grid: { borderColor: "#eef1f5" }
    }, "Age Distribution");

    // ═══ 5. Cadre Wise Distribution — Vertical bar ═══
    var cadreLabels = toStringArray(chartData.cadre && chartData.cadre.categories ? chartData.cadre.categories : []);
    var cadreFemale = toNumberArray(chartData.cadre && chartData.cadre.female ? chartData.cadre.female : []);
    var cadreMale = toNumberArray(chartData.cadre && chartData.cadre.male ? chartData.cadre.male : []);
    var cadreTotals = sumArrays(cadreFemale, cadreMale);
    var cadreTop = limitTop(cadreLabels, cadreTotals, 20);
    safeRender("#chart-cadre", {
        series: [{ name: "Count", data: cadreTop.values.length ? cadreTop.values : [0] }],
        chart: { type: "bar", height: 320, toolbar: { show: false } },
        colors: [colGreen],
        plotOptions: { bar: { columnWidth: "50%", borderRadius: 2 } },
        xaxis: {
            categories: cadreTop.labels.length ? cadreTop.labels : ['No data'],
            labels: { rotate: -40, style: { fontSize: '10px' }, trim: true, maxHeight: 90 }
        },
        dataLabels: { enabled: true, offsetY: -15, style: { fontSize: '10px', colors: ['#333'] } },
        grid: { borderColor: "#eef1f5" }
    }, "Cadre Wise Distribution");

    // ═══ 6. Domicile State Distribution — Vertical bar ═══
    var domicileLabels = toStringArray(chartData.domicile && chartData.domicile.categories ? chartData.domicile.categories : []);
    var domicileValues = toNumberArray(chartData.domicile && chartData.domicile.values ? chartData.domicile.values : []);
    var domicileTop = limitTop(domicileLabels, domicileValues, 20);
    safeRender("#chart-domicile", {
        series: [{ name: "Count", data: domicileTop.values.length ? domicileTop.values : [0] }],
        chart: { type: "bar", height: 320, toolbar: { show: false } },
        colors: [colOrange],
        plotOptions: { bar: { columnWidth: "50%", borderRadius: 2 } },
        xaxis: {
            categories: domicileTop.labels.length ? domicileTop.labels : ['No data'],
            labels: { rotate: -40, style: { fontSize: '10px' }, trim: true, maxHeight: 90 }
        },
        dataLabels: { enabled: true, offsetY: -15, style: { fontSize: '10px', colors: ['#333'] } },
        grid: { borderColor: "#eef1f5" }
    }, "Domicile State Distribution");

    // ── Resize / tab switch handling ──
    document.addEventListener('shown.bs.tab', function() {
        setTimeout(function() {
            chartInstances.forEach(function(chart) { try { chart.updateOptions({}, false, false); } catch (e) {} });
        }, 100);
    });
    var resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            chartInstances.forEach(function(chart) { try { chart.updateOptions({}, false, false); } catch (e) {} });
        }, 250);
    });
});
</script>
@endpush