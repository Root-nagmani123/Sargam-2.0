@extends('admin.layouts.master')

@section('title', 'Class Material (Week Wise) | Course Repository Admin')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 pt-3 pb-0">
        <x-breadcrum title="Class Material (Week Wise)"></x-breadcrum>
    </div>

    <div class="container-fluid px-3 px-md-4 py-4" id="main-content">
        <div class="cru-panel bg-white border rounded-3 shadow-sm p-3 p-md-4">
            @include('admin.course-repository.user.partials.filter-card', [
                'route' => route('admin.course-repository.user.class-material-week-wise', $courseCode),
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => $filters,
            ])

            <div class="material-cards-grid mb-4">
                <div class="row g-3 g-md-4">
                    @php
                        $cardColors = ['bg-success-subtle', 'bg-warning-subtle', 'bg-info-subtle'];
                        $colorIndex = 0;
                    @endphp
                    @foreach(['Class Material (Subject Wise)', 'Class Material (Week Wise)', 'Hindi Reading Material'] as $cardTitle)
                        <div class="col-sm-6 col-lg-4">
                            <div class="card material-card border h-100 rounded-3 shadow-sm {{ $cardColors[$colorIndex % count($cardColors)] }}">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="material-icon-wrapper">
                                            <i class="bi bi-folder2-open fs-4" aria-hidden="true"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title fw-bold mb-2">{{ $cardTitle }}</h5>
                                            <p class="text-muted small mb-0">12 Files • 2 Resources</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php $colorIndex++; @endphp
                    @endforeach
                </div>
            </div>

            <div class="weeks-list">
                <div class="row g-3">
                    @foreach($weeks as $week)
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ route('admin.course-repository.user.week-detail', [$courseCode, $week['number']]) }}"
                               class="text-decoration-none text-dark">
                                <div class="card week-item border h-100 rounded-3 shadow-sm">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="week-icon-wrapper">
                                                    <i class="bi bi-folder2 fs-5" aria-hidden="true"></i>
                                                </div>
                                                <span class="fw-semibold">{{ $week['label'] }}</span>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
