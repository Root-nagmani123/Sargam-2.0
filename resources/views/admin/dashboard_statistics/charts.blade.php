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

    @if(!empty($chartData['summary']) && ($chartData['summary']['total_participants'] > 0 ||
    $chartData['summary']['states_count'] > 0))
    {{-- Summary strip --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden bg-primary bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <div class="fw-bold text-primary fs-4 lh-1">
                        {{ number_format($chartData['summary']['total_participants']) }}</div>
                    <small class="text-muted d-block mt-1">Total Participants</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body py-3 text-center">
                    <div class="fw-bold text-danger lh-1">
                        {{ number_format($chartData['summary']['female_count'] ?? 0) }}</div>
                    <small class="text-muted d-block mt-1">Female</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body py-3 text-center">
                    <div class="fw-bold text-primary lh-1">{{ number_format($chartData['summary']['male_count'] ?? 0) }}
                    </div>
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

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-bold">Total Age Distribution</h6>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-age-donut" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-bold">Social Groups by Age</h6>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-social-by-age" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-bold">Highest Stream by Age</h6>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-stream-radar" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-bold">Age Shift Across Selected Courses</h6>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-age-shift" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-bold">Cadre by Age Group</h6>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-cadre-age" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0 fw-bold">Domicile State by Age Group</h6>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-domicile-age" style="min-height: 220px;"></div>
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
    var chartInstances = [];
    var agePalette = ["#8ec5ff", "#4f91ff", "#245ecf", "#193f9f", "#102a70", "#7a8ba8"];

    console.log('Chart Data:', chartData);

    if (!chartData || typeof chartData !== 'object' || Object.keys(chartData).length === 0) {
        console.log('No chart data available');
        return;
    }

    function toNumberArray(arr) {
        return Array.isArray(arr) ? arr.map(function(v) {
            var n = Number(v);
            return Number.isFinite(n) ? n : 0;
        }) : [];
    }

    function sumArrays(a, b) {
        var len = Math.max(a.length, b.length);
        var out = [];
        for (var i = 0; i < len; i++) {
            out.push((a[i] || 0) + (b[i] || 0));
        }
        return out;
    }

    function limitTop(labels, values, maxItems) {
        var pairs = labels.map(function(label, idx) {
            return {
                label: label,
                value: values[idx] || 0
            };
        });
        pairs.sort(function(a, b) {
            return b.value - a.value;
        });
        pairs = pairs.slice(0, maxItems);
        return {
            labels: pairs.map(function(p) {
                return p.label;
            }),
            values: pairs.map(function(p) {
                return p.value;
            })
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

    var ageCategories = (chartData.age && chartData.age.categories) ? chartData.age.categories : [];
    var ageFemale = toNumberArray(chartData.age && chartData.age.female ? chartData.age.female : []);
    var ageMale = toNumberArray(chartData.age && chartData.age.male ? chartData.age.male : []);
    var ageTotals = sumArrays(ageFemale, ageMale);
    var hasAgeData = ageTotals.some(function(v) {
        return v > 0;
    });

    safeRender("#chart-age-donut", {
        series: hasAgeData ? ageTotals : [1],
        chart: {
            type: "donut",
            height: 220,
            toolbar: {
                show: false
            }
        },
        labels: hasAgeData ? ageCategories : ['No data'],
        colors: agePalette.slice(0, hasAgeData ? ageTotals.length : 1),
        plotOptions: {
            pie: {
                donut: {
                    size: "65%"
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            position: "bottom",
            fontSize: "11px"
        }
    }, "Total Age Distribution");

    var socialLabels = (chartData.social_groups && chartData.social_groups.categories) ? chartData.social_groups.categories : [];
    var socialFemale = toNumberArray(chartData.social_groups && chartData.social_groups.female ? chartData.social_groups.female : []);
    var socialMale = toNumberArray(chartData.social_groups && chartData.social_groups.male ? chartData.social_groups.male : []);
    var socialTop = limitTop(socialLabels, sumArrays(socialFemale, socialMale), 8);
    safeRender("#chart-social-by-age", {
        series: [{
            name: "Count",
            data: socialTop.values.length ? socialTop.values : [0]
        }],
        chart: {
            type: "bar",
            height: 220,
            toolbar: {
                show: false
            }
        },
        colors: ["#245ecf"],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: "40%",
                borderRadius: 3
            }
        },
        xaxis: {
            categories: socialTop.labels.length ? socialTop.labels : ['No data'],
            labels: {
                style: {
                    fontSize: '11px'
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: "#eef1f5"
        }
    }, "Social Groups by Age");

    var streamLabels = (chartData.stream && chartData.stream.categories) ? chartData.stream.categories : [];
    var streamValues = toNumberArray(chartData.stream && chartData.stream.values ? chartData.stream.values : []);
    var streamTop = limitTop(streamLabels, streamValues, 6);
    safeRender("#chart-stream-radar", {
        series: [{
            name: "Count",
            data: streamTop.values.length ? streamTop.values : [0]
        }],
        chart: {
            type: "radar",
            height: 220,
            toolbar: {
                show: false
            }
        },
        labels: streamTop.labels.length ? streamTop.labels : ['No data'],
        colors: ["#245ecf"],
        stroke: {
            width: 2
        },
        fill: {
            opacity: 0.25
        },
        markers: {
            size: 3
        }
    }, "Highest Stream by Age");

    safeRender("#chart-age-shift", {
        series: [{
            name: "Participants",
            data: hasAgeData ? ageTotals : [0]
        }],
        chart: {
            type: "line",
            height: 220,
            toolbar: {
                show: false
            }
        },
        stroke: {
            curve: "smooth",
            width: 2
        },
        colors: ["#245ecf"],
        markers: {
            size: 3
        },
        xaxis: {
            categories: hasAgeData ? ageCategories : ['No data']
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: "#eef1f5"
        }
    }, "Age Shift Across Selected Courses");

    var cadreLabels = (chartData.cadre && chartData.cadre.categories) ? chartData.cadre.categories : [];
    var cadreFemale = toNumberArray(chartData.cadre && chartData.cadre.female ? chartData.cadre.female : []);
    var cadreMale = toNumberArray(chartData.cadre && chartData.cadre.male ? chartData.cadre.male : []);
    var cadreTop = limitTop(cadreLabels, sumArrays(cadreFemale, cadreMale), 8);
    safeRender("#chart-cadre-age", {
        series: [{
            name: "Count",
            data: cadreTop.values.length ? cadreTop.values : [0]
        }],
        chart: {
            type: "bar",
            height: 220,
            toolbar: {
                show: false
            }
        },
        colors: ["#245ecf"],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: "45%",
                borderRadius: 3
            }
        },
        xaxis: {
            categories: cadreTop.labels.length ? cadreTop.labels : ['No data'],
            labels: {
                rotate: -25,
                style: {
                    fontSize: '11px'
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: "#eef1f5"
        }
    }, "Cadre by Age Group");

    var domicileLabels = (chartData.domicile && chartData.domicile.categories) ? chartData.domicile.categories : [];
    var domicileValues = toNumberArray(chartData.domicile && chartData.domicile.values ? chartData.domicile.values : []);
    var domicileTop = limitTop(domicileLabels, domicileValues, 8);
    safeRender("#chart-domicile-age", {
        series: [{
            name: "Count",
            data: domicileTop.values.length ? domicileTop.values : [0]
        }],
        chart: {
            type: "bar",
            height: 220,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: "45%",
                borderRadius: 2
            }
        },
        colors: ["#245ecf"],
        xaxis: {
            categories: domicileTop.labels.length ? domicileTop.labels : ['No data']
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: "#eef1f5"
        }
    }, "Domicile State by Age Group");

    // Handle Bootstrap tab shown event - resize charts when tab becomes visible
    document.addEventListener('shown.bs.tab', function() {
        console.log('Tab switched, resizing charts');
        setTimeout(function() {
            chartInstances.forEach(function(chart) {
                if (chart && chart.windowResizeHandler) {
                    chart.windowResizeHandler();
                }
            });
        }, 100);
    });

    // Handle window resize
    var resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            chartInstances.forEach(function(chart) {
                if (chart && chart.windowResizeHandler) {
                    chart.windowResizeHandler();
                }
            });
        }, 250);
    });
});
</script>
@endpush