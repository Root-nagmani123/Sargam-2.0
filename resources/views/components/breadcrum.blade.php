<<<<<<< HEAD
<div class="card card-body py-3" style="border-left: 4px solid #004a93;">
    <div class="row align-items-center">
        <div class="col-12">
            <div class="d-sm-flex align-items-center justify-space-between">
                <div class="d-flex align-items-center gap-2">
                    <a onclick="window.history.back()" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1" title="Go back">
                        <i class="material-icons material-symbols-rounded fs-6" style="font-size: 30px;">keyboard_double_arrow_left</i>
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
=======
<div class="breadcrumb-wrapper mb-4">
    <div class="card card-body py-3 px-3 px-md-4 px-lg-5 border-0 mb-0 position-relative overflow-hidden">
        <!-- Decorative Background Elements -->
        <div class="breadcrumb-bg-decoration" aria-hidden="true"></div>
        <div class="breadcrumb-accent-line" aria-hidden="true"></div>
        
        <div class="row align-items-center g-2 g-md-3 position-relative">
            <div class="col-12">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <!-- Back Button & Title Section -->
                    <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap">
                        <button type="button" 
                                onclick="window.history.back()" 
                                class="btn-back btn d-flex align-items-center justify-content-center gap-2 rounded-3 px-3 px-sm-4 py-2 shadow-sm border-0" 
                                title="Go back"
                                aria-label="Go back to previous page">
                            <i class="material-icons material-symbols-rounded back-icon" aria-hidden="true">arrow_back_ios</i>
                            <span class="d-none d-sm-inline fw-semibold">Back</span>
                        </button>
                        
                        <div class="title-wrapper d-flex align-items-center gap-2">
                            <div class="title-indicator" aria-hidden="true"></div>
                            <h1 class="h4 mb-0 card-title fw-bold text-dark lh-sm title-text">
>>>>>>> c527b824 (user course resporitory)
                                {{ $title }}
                            </h1>
                        </div>
                    </div>
                    
                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb" class="ms-sm-auto breadcrumb-nav">
                        <ol class="breadcrumb mb-0 align-items-center flex-wrap">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="breadcrumb-link d-flex align-items-center gap-2 text-decoration-none" 
                                   href="{{ route('admin.dashboard') }}"
                                   aria-label="Navigate to dashboard">
                                    <div class="home-icon-wrapper d-flex align-items-center justify-content-center">
                                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="home-icon" aria-hidden="true">
                                            <path
                                                d="M10.55 2.533a2.25 2.25 0 0 1 2.9 0l6.75 5.695c.508.427.8 1.056.8 1.72v9.802a1.75 1.75 0 0 1-1.75 1.75h-3a1.75 1.75 0 0 1-1.75-1.75v-5a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v5a1.75 1.75 0 0 1-1.75 1.75h-3A1.75 1.75 0 0 1 3 19.75V9.947c0-.663.292-1.292.8-1.72l6.75-5.694Z"
                                                fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <span class="d-none d-md-inline breadcrumb-text">Home</span>
                                </a>
                            </li>
                            <li class="breadcrumb-item active d-flex align-items-center" aria-current="page">
                                <div class="breadcrumb-separator d-flex align-items-center" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span class="badge-current-page badge text-bg-primary-subtle">
                                    {{ $title }}
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>