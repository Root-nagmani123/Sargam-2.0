{{-- Breadcrumb: variant = glass | minimal | pill | stepper | underline | compact --}}
@props(['title' => 'Page', 'variant' => 'glass'])
@php $variant = $variant ?? 'glass'; @endphp
<div class="breadcrumb-wrapper breadcrumb-variant-{{ $variant }} mb-4" data-variant="{{ $variant }}">
    <div class="breadcrumb-card card border-0 shadow-sm rounded-4 position-relative overflow-hidden bg-body">
        {{-- Decorative layers --}}
        <div class="breadcrumb-bg-pattern" aria-hidden="true"></div>
        <div class="breadcrumb-bg-gradient" aria-hidden="true"></div>
        <div class="breadcrumb-accent-bar" aria-hidden="true"></div>
        <div class="breadcrumb-corner-glow" aria-hidden="true"></div>

        <div class="card-body breadcrumb-card-body position-relative p-3 p-md-4">
            <div class="row align-items-center g-3">
                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                        {{-- Left: Back + Title --}}
                        <div class="d-flex align-items-center gap-3 flex-wrap min-w-0 flex-grow-1">
                            <a href="javascript:void(0)"
                                    onclick="window.history.back()"
                                    class="btn btn-light border border-secondary-subtle shadow-sm d-flex align-items-center justify-content-center gap-2 rounded-pill px-3 px-sm-4 py-2 focus-ring focus-ring-primary"
                                    title="Go back to previous page"
                                    aria-label="Go back to previous page">
                                <i class="material-icons material-symbols-rounded back-icon" aria-hidden="true">arrow_back_ios</i>
                                <span class="d-none d-sm-inline fw-semibold small">Back</span>
                            </a>
                            <div class="breadcrumb-title-block d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                                <div class="breadcrumb-title-accent" aria-hidden="true"></div>
                                <h1 class="h4 mb-0 fw-semibold title-text text-truncate">
                                    {{ $title }}
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
