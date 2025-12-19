<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - {{ $course->course_name }}</title>
    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/ico" href="{{asset('admin_assets/images/logos/favicon.ico')}}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    /* =====================================================
   GIGW + MODERN GOVERNMENT UI VARIABLES
===================================================== */
    :root {
        --primary: #004a93;
        --primary-dark: #003366;
        --accent: #eef5ff;
        --bg-light: #f4f6f9;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border: #e5e7eb;
    }

    /* =====================================================
   BASE
===================================================== */
    body {
        background: var(--bg-light);
        font-family: "Segoe UI", system-ui, sans-serif;
        color: var(--text-main);
        line-height: 1.65;
    }

    /* =====================================================
   GOI HEADER
===================================================== */
    .goi-header {
        background: #ffffff;
        border-bottom: 4px solid var(--primary);
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .goi-header img {
        height: 48px;
    }

    .goi-header span {
        font-weight: 600;
        color: var(--primary);
    }

    /* =====================================================
   COURSE HEADER
===================================================== */
    .course-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 2.75rem 1.5rem;
        border-radius: 1rem 1rem 0 0;
        text-align: center;
    }

    .course-header h1 {
        font-size: 1.85rem;
        font-weight: 600;
    }

    .course-header .badge {
        background: #ffffff;
        color: #000;
    }

    /* =====================================================
   CARD
===================================================== */
    .details-card {
        background: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        margin-top: -1.5rem;
        padding: 2rem;
    }

    /* =====================================================
   SECTION TITLE
===================================================== */
    .section-title {
        border-left: 4px solid var(--primary);
        padding-left: 0.75rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 1.25rem;
        font-size: 1.2rem;
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    /* =====================================================
   DETAIL BOX
===================================================== */
    .detail-value {
        background: var(--accent);
        border: 1px solid var(--border);
        border-left: 4px solid var(--primary);
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
    }

    /* =====================================================
   FACULTY CARD
===================================================== */
    .faculty-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1rem;
        text-align: center;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .faculty-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
    }

    .faculty-card img {
        width: 160px;
        height: 160px;
        border-radius: 0.75rem;
        object-fit: cover;
        border: 1px solid var(--border);
    }

    .role-badge {
        background: var(--primary);
        color: #fff;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
    }

    /* =====================================================
   SKELETON LOADER
===================================================== */
    .skeleton {
        background: linear-gradient(90deg, #e5e7eb, #f3f4f6, #e5e7eb);
        background-size: 200% 100%;
        animation: shimmer 1.2s infinite;
        border-radius: 0.5rem;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    /* =====================================================
   DARK MODE (USER OPTIONAL)
===================================================== */
    @media (prefers-color-scheme: dark) {
        body {
            background: #0f172a;
            color: #e5e7eb;
        }

        .details-card,
        .faculty-card,
        .goi-header {
            background: #020617;
            border-color: #1e293b;
        }

        .detail-value {
            background: #020617;
            color: #e5e7eb;
        }
    }

    /* =====================================================
   PRINT
===================================================== */
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff;
            color: #000;
        }

        .details-card {
            box-shadow: none;
            border: 1px solid #000;
        }
    }
    </style>
</head>

<body>

    <!-- GOI HEADER -->
    <div class="goi-header">
        <span>Lal Bahadur Shastri National Academy of Administration</span>
        <span>Government of India</span>
    </div>

    <main class="container-fluid py-4">

        <!-- ACTIONS -->
        <div class="no-print mb-4 d-flex justify-content-between flex-wrap gap-2">
            <a href="{{ route('programme.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
            </div>
        </div>

        <!-- HEADER -->
        <header class="course-header">
            <h1>{{ $course->course_name }}</h1>
            <p>
                <span class="badge">{{ $course->couse_short_name }}</span>
                | <strong>Year:</strong> {{ $course->course_year }}
            </p>
        </header>

        <!-- CARD -->
        <div class="details-card mt-4">

            <section>
                <h2 class="section-title"><i class="bi bi-info-circle"></i> Course Information</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-value">{{ $course->course_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-value">{{ $course->couse_short_name }}</div>
                    </div>
                </div>
            </section>

            <hr class="my-4">

            <section>
                <h2 class="section-title"><i class="bi bi-people"></i> Course Team</h2>

                <div class="text-center mb-4">
                    <span class="role-badge">Course Coordinator</span>
                    <div class="faculty-card mx-auto mt-3" style="max-width:240px">
                        <img src="{{ asset('storage/' . ($coordinatorFaculty->photo_uplode_path ?? '')) }}"
                            onerror="this.src='{{ asset('images/user-placeholder.png') }}'">
                        <p class="fw-semibold mt-2">{{ $coordinatorName }}</p>
                    </div>
                </div>

                <div class="row g-4 justify-content-center">
                    @forelse($assistantCoordinatorsData as $assistant)
                    <div class="col-6 col-md-3">
                        <div class="faculty-card">
                            <img src="{{ asset('storage/' . $assistant['photo']) }}"
                                onerror="this.src='{{ asset('images/user-placeholder.png') }}'">
                            <p class="fw-semibold mt-2">{{ $assistant['name'] }}</p>
                            <span class="role-badge">{{ $assistant['role'] }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-muted text-center">No Assistant Coordinators</div>
                    @endforelse
                </div>
            </section>

        </div>

        <footer class="text-center mt-4 text-muted small no-print">
            Generated on {{ \Carbon\Carbon::now()->format('d F Y, h:i A') }}
        </footer>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>