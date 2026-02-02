{{--
    Modern Breadcrumb Component
    - Bootstrap 5.3 native structure
    - WCAG 2.1 compliant (ARIA, focus-visible, semantic HTML)
    - Supports: title (required)
--}}
<div class="breadcrumb-wrapper mb-4">
    <div class="card card-body py-3 px-3 px-md-4 px-lg-5 border-0 mb-0 position-relative overflow-hidden shadow-sm">
        {{-- Decorative elements: hidden from assistive tech per WCAG --}}
        <div class="breadcrumb-bg-decoration" aria-hidden="true"></div>
        <div class="breadcrumb-accent-line" aria-hidden="true"></div>

        <div class="row align-items-center g-2 g-md-3 position-relative">
            <div class="col-12">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    {{-- Back Button & Page Title --}}
                    <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap">
                        <button type="button"
                                onclick="window.history.back()"
                                class="btn-back btn d-flex align-items-center justify-content-center gap-2 rounded-pill px-3 px-sm-4 py-2 shadow-sm border-0"
                                title="Go back to previous page"
                                aria-label="Go back to previous page">
                            <i class="material-icons material-symbols-rounded back-icon" aria-hidden="true">arrow_back_ios</i>
                            <span class="d-none d-sm-inline fw-semibold">Back</span>
                        </button>

                        <div class="title-wrapper d-flex align-items-center gap-2">
                            <div class="title-indicator" aria-hidden="true"></div>
                            <h1 class="h4 mb-0 card-title fw-bold text-dark lh-sm title-text">
                                {{ $title }}
                            </h1>
                        </div>
                    </div>

                    {{-- Breadcrumb: W3C ARIA pattern - nav landmark with aria-label --}}
                    <nav class="ms-sm-auto breadcrumb-nav" aria-label="Breadcrumb">
                        <ol class="breadcrumb mb-0 align-items-center flex-wrap"
                            style="--bs-breadcrumb-divider: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%278%27 height=%278%27%3E%3Cpath d=%27M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z%27 fill=%27%236b7280%27/%3E%3C/svg%3E');">
                            <li class="breadcrumb-item">
                                <a class="breadcrumb-link d-inline-flex align-items-center gap-2 text-decoration-none rounded-2 px-2 py-1"
                                   href="{{ route('admin.dashboard') }}"
                                   aria-label="Home, navigate to dashboard">
                                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="breadcrumb-home-icon" aria-hidden="true">
                                        <path d="M10.55 2.533a2.25 2.25 0 0 1 2.9 0l6.75 5.695c.508.427.8 1.056.8 1.72v9.802a1.75 1.75 0 0 1-1.75 1.75h-3a1.75 1.75 0 0 1-1.75-1.75v-5a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v5a1.75 1.75 0 0 1-1.75 1.75h-3A1.75 1.75 0 0 1 3 19.75V9.947c0-.663.292-1.292.8-1.72l6.75-5.694Z" fill="currentColor"/>
                                    </svg>
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
