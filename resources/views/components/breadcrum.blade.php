
<div class="breadcrumb-wrapper mb-4">
    <div class="breadcrumb-card card border-0 position-relative overflow-hidden">
        {{-- Decorative layers --}}
        <div class="breadcrumb-bg-pattern" aria-hidden="true"></div>
        <div class="breadcrumb-bg-gradient" aria-hidden="true"></div>
        <div class="breadcrumb-accent-bar" aria-hidden="true"></div>
        <div class="breadcrumb-corner-glow" aria-hidden="true"></div>

        <div class="card-body breadcrumb-card-body position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                        <button type="button"
                            class="btn btn-back d-inline-flex align-items-center justify-content-center gap-2 px-3 px-sm-4 py-2 shadow-sm focus-ring border-0 bg-transparent text-body"
                            onclick="if(window.history.length > 1){window.history.back();}else{window.location.href=this.dataset.homeUrl;}"
                            title="Go back to previous page"
                            aria-label="Go back to previous page">
                            <i class="material-icons material-symbols-rounded back-icon" aria-hidden="true">arrow_back_ios_new</i>
                        </button>

                    <div class="breadcrumb-title-block d-flex align-items-center gap-3 min-w-0">
                        <div class="breadcrumb-title-accent" aria-hidden="true"></div>
                        <h1 class="h4 mb-0 fw-bold title-text text-truncate">{{ $title }}</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
