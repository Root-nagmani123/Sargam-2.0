{{--
    Premium Breadcrumb Component - Bootstrap 5.3+
    - Glassmorphism, elevated design, refined interactions
    - WCAG 2.1 compliant (ARIA, focus-visible, semantic HTML)
    - Supports: title (required)
--}}
<div class="breadcrumb-wrapper mb-4">
    <div class="breadcrumb-card card border-0 mb-0 position-relative overflow-hidden">
        {{-- Decorative layers --}}
        <div class="breadcrumb-bg-pattern" aria-hidden="true"></div>
        <div class="breadcrumb-bg-gradient" aria-hidden="true"></div>
        <div class="breadcrumb-accent-bar" aria-hidden="true"></div>
        <div class="breadcrumb-corner-glow" aria-hidden="true"></div>

        <div class="card-body breadcrumb-card-body position-relative">
            <div class="row align-items-center g-3">
                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                        {{-- Left: Back + Title --}}
                        <div class="d-flex align-items-center gap-3 flex-wrap min-w-0 flex-grow-1">
                            <button type="button"
                                    onclick="window.history.back()"
                                    class="btn-back btn d-flex align-items-center justify-content-center gap-2 rounded-3 px-3 px-sm-4 py-2 border-0 focus-ring focus-ring-primary"
                                    title="Go back to previous page"
                                    aria-label="Go back to previous page">
                                <i class="material-icons material-symbols-rounded back-icon" aria-hidden="true">arrow_back_ios</i>
                                <span class="d-none d-sm-inline fw-semibold">Back</span>
                            </button>

                            <div class="breadcrumb-title-block d-flex align-items-center gap-3 min-w-0">
                                <div class="breadcrumb-title-accent" aria-hidden="true"></div>
                                <h1 class="h4 mb-0 fw-bold title-text text-truncate">
                                    {{ $title }}
                                </h1>
                            </div>
                        </div>

                        {{-- Right: Breadcrumb trail --}}
                        <nav class="ms-sm-auto breadcrumb-nav flex-shrink-0" aria-label="Breadcrumb">
                            <ol class="breadcrumb mb-0 align-items-center flex-wrap breadcrumb-trail">
                                <li class="breadcrumb-item">
                                    <a class="breadcrumb-link d-inline-flex align-items-center gap-2 text-decoration-none rounded-3 px-3 py-2 focus-ring focus-ring-primary"
                                       href="{{ route('admin.dashboard') }}"
                                       aria-label="Home, navigate to dashboard">
                                        <span class="breadcrumb-home-wrap">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="breadcrumb-home-icon" aria-hidden="true">
                                                <path d="M10.55 2.533a2.25 2.25 0 0 1 2.9 0l6.75 5.695c.508.427.8 1.056.8 1.72v9.802a1.75 1.75 0 0 1-1.75 1.75h-3a1.75 1.75 0 0 1-1.75-1.75v-5a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v5a1.75 1.75 0 0 1-1.75 1.75h-3A1.75 1.75 0 0 1 3 19.75V9.947c0-.663.292-1.292.8-1.72l6.75-5.694Z" fill="currentColor"/>
                                            </svg>
                                        </span>
                                        <span class="d-none d-md-inline">Home</span>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <span class="breadcrumb-current">{{ $title }}</span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
