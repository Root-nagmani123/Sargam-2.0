<div class="breadcrumb-wrapper mb-4">
    <div class="card card-body py-3 px-3 px-md-4 px-lg-5 border-0 mb-0 position-relative overflow-hidden">
        <!-- Decorative Background Elements -->
        <div class="breadcrumb-bg-decoration"></div>
        <div class="breadcrumb-accent-line"></div>
        
        <div class="row align-items-center g-2 position-relative">
            <div class="col-12">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <!-- Back Button & Title Section -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <a onclick="window.history.back()" 
                           class="btn-back d-flex align-items-center justify-content-center gap-2 rounded-3 px-3 px-sm-4 py-2 shadow-sm border-0" 
                           title="Go back"
                           role="button"
                           aria-label="Go back to previous page">
                            <i class="material-icons material-symbols-rounded back-icon">arrow_back_ios</i>
                            <span class="d-none d-sm-inline fw-semibold">Back</span>
                        </a>
                        
                        <div class="title-wrapper d-flex align-items-center gap-2">
                            <div class="title-indicator"></div>
                            <h4 class="mb-0 card-title fw-bold text-dark lh-sm title-text">
                                {{ $title }}
                            </h4>
                        </div>
                    </div>
                    
                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb" class="ms-sm-auto breadcrumb-nav">
                        <ol class="breadcrumb mb-0 align-items-center flex-wrap">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="breadcrumb-link d-flex align-items-center gap-2" 
                                   href="{{ route('admin.dashboard') }}">
                                    <div class="home-icon-wrapper">
                                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="home-icon">
                                            <path
                                                d="M10.55 2.533a2.25 2.25 0 0 1 2.9 0l6.75 5.695c.508.427.8 1.056.8 1.72v9.802a1.75 1.75 0 0 1-1.75 1.75h-3a1.75 1.75 0 0 1-1.75-1.75v-5a.75.75 0 0 0-.75-.75h-3.5a.75.75 0 0 0-.75.75v5a1.75 1.75 0 0 1-1.75 1.75h-3A1.75 1.75 0 0 1 3 19.75V9.947c0-.663.292-1.292.8-1.72l6.75-5.694Z"
                                                fill="currentColor"/>
                                        </svg>
                                    </div>
                                    <span class="d-none d-md-inline breadcrumb-text">Home</span>
                                </a>
                            </li>
                            <li class="breadcrumb-item active d-flex align-items-center" aria-current="page">
                                <div class="breadcrumb-separator">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span class="badge-current-page">
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

<style>
    /* Main Wrapper */
    .breadcrumb-wrapper {
        position: relative;
    }

    /* Card Container with Modern Design */
    .breadcrumb-wrapper .card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 20px rgba(0, 74, 147, 0.08), 
                    0 1px 3px rgba(0, 0, 0, 0.05),
                    inset 0 1px 0 rgba(255, 255, 255, 0.9);
        border-radius: 16px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .breadcrumb-wrapper .card:hover {
        box-shadow: 0 8px 30px rgba(0, 74, 147, 0.12), 
                    0 2px 6px rgba(0, 0, 0, 0.08),
                    inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transform: translateY(-2px);
    }

    /* Decorative Background */
    .breadcrumb-bg-decoration {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, 
            rgba(0, 74, 147, 0.03) 0%, 
            rgba(0, 74, 147, 0.01) 50%, 
            transparent 100%);
        pointer-events: none;
        border-radius: 16px;
    }

    /* Accent Line */
    .breadcrumb-accent-line {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, 
            #004a93 0%, 
            rgba(0, 74, 147, 0.8) 50%, 
            #004a93 100%);
        border-radius: 16px 0 0 16px;
        box-shadow: 2px 0 8px rgba(0, 74, 147, 0.3);
    }

    /* Back Button - Enhanced */
    .btn-back {
        background: linear-gradient(135deg, rgba(0, 74, 147, 0.08) 0%, rgba(0, 74, 147, 0.05) 100%);
        color: #004a93;
        min-width: 44px;
        height: 44px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 74, 147, 0.1);
        font-size: 0.875rem;
    }

    .btn-back:hover {
        background: linear-gradient(135deg, rgba(0, 74, 147, 0.15) 0%, rgba(0, 74, 147, 0.1) 100%);
        color: #003d7a;
        transform: translateX(-4px) scale(1.02);
        box-shadow: 0 6px 20px rgba(0, 74, 147, 0.2), 
                    0 2px 6px rgba(0, 74, 147, 0.15);
        border-color: rgba(0, 74, 147, 0.2);
    }

    .btn-back:active {
        transform: translateX(-2px) scale(0.98);
    }

    .btn-back .back-icon {
        font-size: 18px;
        transition: transform 0.3s ease;
    }

    .btn-back:hover .back-icon {
        transform: translateX(-2px);
    }

    /* Title Section */
    .title-wrapper {
        position: relative;
        padding-left: 12px;
    }

    .title-indicator {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: linear-gradient(180deg, #004a93, rgba(0, 74, 147, 0.5));
        border-radius: 2px;
    }

    .title-text {
        font-size: clamp(1.15rem, 2.5vw, 1.5rem);
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.02em;
        line-height: 1.3;
    }

    /* Breadcrumb Navigation */
    .breadcrumb-nav {
        position: relative;
    }

    .breadcrumb {
        --bs-breadcrumb-divider: '';
        font-size: 0.875rem;
        gap: 0.5rem;
    }

    .breadcrumb-link {
        color: #6b7280;
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
    }

    .breadcrumb-link:hover {
        color: #004a93;
        background: rgba(0, 74, 147, 0.08);
        transform: translateY(-1px);
    }

    .home-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(192, 56, 43, 0.1);
        transition: all 0.3s ease;
    }

    .breadcrumb-link:hover .home-icon-wrapper {
        background: rgba(192, 56, 43, 0.2);
        transform: scale(1.1) rotate(-5deg);
    }

    .home-icon {
        color: #C0382B;
        transition: all 0.3s ease;
    }

    .breadcrumb-text {
        font-weight: 500;
    }

    /* Breadcrumb Separator */
    .breadcrumb-separator {
        display: flex;
        align-items: center;
        color: rgba(0, 0, 0, 0.25);
        margin: 0 0.5rem;
        transition: all 0.3s ease;
    }

    /* Current Page Badge */
    .badge-current-page {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #004a93;
        background: linear-gradient(135deg, rgba(0, 74, 147, 0.12) 0%, rgba(0, 74, 147, 0.08) 100%);
        border-radius: 10px;
        white-space: nowrap;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        border: 1px solid rgba(0, 74, 147, 0.15);
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.1);
        transition: all 0.3s ease;
    }

    .breadcrumb-item.active:hover .badge-current-page {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.15);
        background: linear-gradient(135deg, rgba(0, 74, 147, 0.15) 0%, rgba(0, 74, 147, 0.12) 100%);
    }

    /* Enhanced Mobile Responsiveness */
    @media (max-width: 575.98px) {
        .breadcrumb-wrapper .card {
            border-radius: 12px;
            padding: 0.875rem !important;
        }

        .breadcrumb-accent-line {
            width: 3px;
            border-radius: 12px 0 0 12px;
        }

        .btn-back {
            min-width: 40px;
            height: 40px;
            padding: 0.5rem 0.75rem !important;
        }

        .btn-back .back-icon {
            font-size: 16px;
        }

        .title-text {
            font-size: 1rem !important;
            line-height: 1.4;
        }

        .title-indicator {
            height: 50%;
        }

        .breadcrumb {
            font-size: 0.75rem;
            gap: 0.25rem;
        }

        .breadcrumb-link {
            padding: 0.375rem 0.5rem;
        }

        .home-icon-wrapper {
            width: 28px;
            height: 28px;
        }

        .home-icon {
            width: 16px;
            height: 16px;
        }

        .badge-current-page {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            max-width: 140px;
        }

        .breadcrumb-separator {
            margin: 0 0.25rem;
        }

        .breadcrumb-separator svg {
            width: 12px;
            height: 12px;
        }
    }

    @media (min-width: 576px) and (max-width: 767.98px) {
        .badge-current-page {
            max-width: 180px;
        }

        .breadcrumb-wrapper .card {
            padding: 1rem 1.25rem !important;
        }
    }

    @media (min-width: 768px) {
        .breadcrumb-wrapper .card {
            padding: 1rem 1.5rem !important;
        }
    }

    /* Focus States for Accessibility */
    .btn-back:focus-visible,
    .breadcrumb-link:focus-visible {
        outline: 3px solid rgba(0, 74, 147, 0.5);
        outline-offset: 2px;
        border-radius: 8px;
    }

    /* Smooth Animations */
    * {
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Loading State (if needed) */
    .breadcrumb-wrapper.loading {
        opacity: 0.7;
        pointer-events: none;
    }

    /* Print Styles */
    @media print {
        .breadcrumb-wrapper .card {
            box-shadow: none;
            border: 1px solid #ddd;
        }

        .btn-back {
            display: none;
        }
    }
</style>