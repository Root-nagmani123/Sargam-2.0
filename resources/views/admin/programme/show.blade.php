<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - {{ $course->course_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root {
        --theme-color: #004a93;
        --theme-accent: #e7f3ff;
    }

    body {
        background-color: #f8fafc;
        font-family: "Segoe UI", system-ui, sans-serif;
        color: #212529;
        line-height: 1.6;
    }

    .course-header {
        background: linear-gradient(120deg, var(--theme-color), #0078d7);
        color: #fff;
        padding: 2rem;
        border-radius: 1rem 1rem 0 0;
        text-align: center;
    }

    .course-header h1 {
        font-size: 1.75rem;
        font-weight: 600;
    }

    .section-title {
        border-left: 4px solid var(--theme-color);
        padding-left: 0.75rem;
        font-weight: 600;
        color: var(--theme-color);
        margin-bottom: 1.25rem;
        font-size: 1.25rem;
    }

    .details-card {
        border: none;
        background-color: #fff;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-top: -1rem;
        z-index: 1;
        position: relative;
    }

    .detail-value {
        background-color: var(--theme-accent);
        border-left: 4px solid var(--theme-color);
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
    }

    .photo-frame img {
        border-radius: 0.5rem;
        width: 140px;
        height: 140px;
        object-fit: cover;
    }

    .role-badge {
        background-color: var(--theme-color);
        color: #fff;
        border-radius: 1rem;
        font-size: 0.9rem;
        padding: 0.25rem 0.75rem;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .btn-primary,
    .btn-danger,
    .btn-outline-secondary {
        border-radius: 0.5rem;
    }

    /* Accessibility for print */
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
            color: #000 !important;
        }

        a {
            color: #000 !important;
            text-decoration: none;
        }
    }
    </style>
</head>

<body>
    <main role="main" class="container-fluid py-4" aria-label="Course Details">

        <!-- Action Buttons -->
        <div class="no-print mb-4 d-flex justify-content-between align-items-center flex-wrap">
            <a href="{{ route('programme.index') }}" class="btn btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left"></i> <span class="visually-hidden">Back</span> Back to Course List
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-primary mb-2">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}"
                    class="btn btn-danger mb-2">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
            </div>
        </div>

        <!-- Course Header -->
        <header class="course-header">
            <h1>{{ $course->course_name }}</h1>
            <p class="mb-0">
                <span class="badge bg-light text-dark">{{ $course->couse_short_name }}</span>
                &nbsp; | &nbsp; <strong>Year:</strong> {{ $course->course_year }}
            </p>
        </header>

        <!-- Details Card -->
        <div class="card details-card p-4 mt-4">

            <!-- Course Information -->
            <section aria-labelledby="course-info">
                <h2 id="course-info" class="section-title"><i class="bi bi-info-circle me-2"></i>Course Information</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="fw-semibold mb-1 text-secondary">Course Name</p>
                        <div class="detail-value">{{ $course->course_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-semibold mb-1 text-secondary">Short Name</p>
                        <div class="detail-value">{{ $course->couse_short_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-semibold mb-1 text-secondary">Course Year</p>
                        <div class="detail-value">{{ $course->course_year }}</div>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-semibold mb-1 text-secondary">Course Duration</p>
                        <div class="detail-value">
                            @if($course->start_year && $course->end_date)
                            <i class="bi bi-calendar-event"></i>
                            {{ \Carbon\Carbon::parse($course->start_year)->format('M d, Y') }}
                            –
                            <i class="bi bi-calendar-check"></i>
                            {{ \Carbon\Carbon::parse($course->end_date)->format('M d, Y') }}
                            @else
                            <span class="text-muted">Not specified</span>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <hr class="my-4">

            <!-- Course Team -->
            <section aria-labelledby="course-team">
                <h2 id="course-team" class="section-title"><i class="bi bi-people me-2"></i>Course Team</h2>

                <!-- Coordinator -->
                <div class="text-center mb-4">
                    <span class="role-badge">Course Coordinator</span>
                    <div class="photo-frame mx-auto mb-2">
                        @if($coordinatorFaculty && $coordinatorFaculty->photo_uplode_path)
                        <img src="{{ asset('storage/' . $coordinatorFaculty->photo_uplode_path) }}"
                            alt="Photo of {{ $coordinatorName }}"
                            onerror="this.onerror=null; this.src='{{ asset('images/dummypic.jpeg') }}';">
                        @else
                        <img src="{{ asset('images/dummypic.jpeg') }}" alt="No photo available">
                        @endif
                    </div>
                    <p class="fw-semibold text-primary">{{ $coordinatorName }}</p>
                </div>

                <!-- Assistant Coordinators -->
                <div class="text-center">
                    <span class="role-badge mb-3">Assistant Coordinators</span>
                </div>
                <div class="row g-4 justify-content-center">
                    @forelse($assistantCoordinatorsData as $assistant)
                    <div class="col-6 col-md-3 text-center">
                        <div class="photo-frame mb-2">
                            @if($assistant['photo'])
                            <img src="{{ asset('storage/' . $assistant['photo']) }}"
                                alt="Photo of {{ $assistant['name'] }}"
                                onerror="this.onerror=null; this.src='{{ asset('images/dummypic.jpeg') }}';">
                            @else
                            <img src="{{ asset('images/dummypic.jpeg') }}" alt="No photo available">
                            @endif
                        </div>
                        <p class="fw-semibold text-primary">{{ $assistant['name'] }}</p>
                        <span class="role-badge">{{ $assistant['role'] }}</span>
                    </div>
                    @empty
                    <div class="text-muted text-center py-3">
                        <i class="bi bi-info-circle"></i> No Assistant Coordinators assigned
                    </div>
                    @endforelse
                </div>
            </section>

        </div>

        <!-- Footer -->
        <footer class="text-center text-secondary mt-4 no-print small">
            Generated on {{ \Carbon\Carbon::now()->format('F d, Y \a\t h:i A') }}
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>