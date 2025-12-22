<div class="card card-body py-3" style="border-left: 4px solid #004a93;">
    <div class="row align-items-center">
        <div class="col-12">
            <div class="d-sm-flex align-items-center justify-space-between">
                <div class="d-flex align-items-center gap-2">
                    <a onclick="window.history.back()" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1" title="Go back">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.295 19.716a1 1 0 0 0 1.404-1.425l-5.37-5.29h13.67a1 1 0 1 0 0-2H6.336l5.359-5.358a1 1 0 0 0-1.414-1.414l-7.071 7.07a1 1 0 0 0 0 1.415l7.085 7.002Z" fill="currentColor"/>
                        </svg>
</a>
                    <h4 class="mb-0 card-title">{{ $title }}</h4>
                </div>
                <nav aria-label="breadcrumb" class="ms-auto">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item d-flex align-items-center">
                            <a class="text-muted text-decoration-none d-flex" href="{{ route('admin.dashboard') }}">
                                <!-- <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon> -->
                                <svg width="24" height="24" fill="none" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M10.55 2.533a2.25 2.25 0 0 1 2.9 0l6.75 5.695c.508.427.8 1.056.8 1.72v9.802a1.75 1.75 0 0 1-1.75 1.75h-3a1.75 1.75 0 0 1-1.75-1.75v-5a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v5a1.75 1.75 0 0 1-1.75 1.75h-3A1.75 1.75 0 0 1 3 19.75V9.947c0-.663.292-1.292.8-1.72l6.75-5.694Z"
                                        fill="#C0382B" style="width: 1.26rem;"/>
                                </svg>
                            </a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                {{ $title }}
                            </span>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>