{{-- Modern & Elegant Breadcrumb Component --}}
@props(['title' => 'Page', 'variant' => 'glass'])
@php $variant = $variant ?? 'glass'; @endphp

<div class="modern-breadcrumb-wrapper mb-4" data-variant="{{ $variant }}">
    
    {{-- Premium Card Design --}}
    <div class="modern-breadcrumb-card">
        
        {{-- Subtle Background Decoration --}}
        <div class="breadcrumb-decoration" aria-hidden="true">
            <div class="decoration-gradient"></div>
            <div class="decoration-pattern"></div>
        </div>
        
        {{-- Top Accent Line --}}
        <div class="breadcrumb-accent-line" aria-hidden="true"></div>
        
        {{-- Main Content Area --}}
        <div class="breadcrumb-content">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                
                {{-- Left Section: Navigation & Title --}}
                <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                    
                    {{-- Elegant Back Button --}}
                    <a href="#"
                       onclick="event.preventDefault(); handleBackNavigation();"
                       class="modern-back-button"
                       title="Go back to previous page"
                       aria-label="Go back to previous page">
                        <span class="back-icon-wrapper">
                            <i class="material-icons material-symbols-rounded">arrow_back</i>
                        </span>
                        <span class="back-text">Back</span>
                        <span class="button-ripple"></span>
                    </a>
                    
                    {{-- Refined Separator --}}
                    <div class="breadcrumb-separator"></div>
                    
                    {{-- Title Section --}}
                    <div class="breadcrumb-title-wrapper">
                        <div class="title-indicator"></div>
                        <div class="title-content">
                            <h1 class="breadcrumb-title">{{ $title }}</h1>
                            <div class="title-underline"></div>
                        </div>
                    </div>
                </div>
                
                {{-- Right Section: Optional Status (can be removed if not needed) --}}
                <div class="breadcrumb-status d-none d-xl-flex">
                    <span class="status-badge">
                        <i class="material-icons material-symbols-rounded">check_circle</i>
                        <span>Active</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Elegant & Modern Styling --}}
<style>
    /* Main Wrapper */
    .modern-breadcrumb-wrapper {
        position: relative;
        animation: breadcrumbFadeIn 0.4s ease-out;
    }
    
    /* Premium Card */
    .modern-breadcrumb-card {
        position: relative;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 
            0 2px 8px rgba(0, 0, 0, 0.04),
            0 4px 16px rgba(0, 0, 0, 0.06),
            0 0 0 1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modern-breadcrumb-card:hover {
        box-shadow: 
            0 4px 16px rgba(0, 0, 0, 0.06),
            0 8px 24px rgba(0, 0, 0, 0.08),
            0 0 0 1px rgba(13, 110, 253, 0.15);
        transform: translateY(-2px);
    }
    
    /* Background Decoration */
    .breadcrumb-decoration {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 0;
    }
    
    .decoration-gradient {
        position: absolute;
        top: 0;
        right: 0;
        width: 40%;
        height: 100%;
        background: radial-gradient(ellipse at top right, rgba(13, 110, 253, 0.05), transparent 70%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .modern-breadcrumb-card:hover .decoration-gradient {
        opacity: 1;
    }
    
    .decoration-pattern {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20px 20px, rgba(13, 110, 253, 0.015) 1px, transparent 1px);
        background-size: 40px 40px;
    }
    
    /* Top Accent Line */
    .breadcrumb-accent-line {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0d6efd 0%, #6366f1 50%, #8b5cf6 100%);
        transform-origin: left;
        animation: accentExpand 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Content Area */
    .breadcrumb-content {
        position: relative;
        padding: 1.5rem 1.75rem;
        z-index: 1;
    }
    
    @media (min-width: 768px) {
        .breadcrumb-content {
            padding: 1.75rem 2rem;
        }
    }
    
    @media (min-width: 1200px) {
        .breadcrumb-content {
            padding: 2rem 2.5rem;
        }
    }
    
    /* Modern Back Button */
    .modern-back-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 1.5px solid #e9ecef;
        border-radius: 12px;
        color: #495057;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .modern-back-button:hover {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        border-color: #0d6efd;
        color: #ffffff;
        transform: translateX(-4px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }
    
    .modern-back-button:active {
        transform: translateX(-2px) scale(0.98);
    }
    
    .modern-back-button:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }
    
    .back-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .back-icon-wrapper i {
        font-size: 1.125rem;
        font-weight: 500;
    }
    
    .modern-back-button:hover .back-icon-wrapper {
        transform: translateX(-3px);
    }
    
    .back-text {
        font-size: 0.875rem;
        letter-spacing: 0.01em;
    }
    
    @media (max-width: 575.98px) {
        .back-text {
            display: none;
        }
        .modern-back-button {
            padding: 0.625rem;
        }
    }
    
    .button-ripple {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modern-back-button:hover .button-ripple {
        opacity: 0.2;
    }
    
    /* Elegant Separator */
    .breadcrumb-separator {
        width: 1px;
        height: 32px;
        background: linear-gradient(180deg, transparent 0%, #dee2e6 50%, transparent 100%);
        display: none;
    }
    
    @media (min-width: 992px) {
        .breadcrumb-separator {
            display: block;
        }
    }
    
    /* Title Wrapper */
    .breadcrumb-title-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-grow: 1;
        min-width: 0;
    }
    
    /* Title Indicator */
    .title-indicator {
        width: 4px;
        height: 36px;
        background: linear-gradient(180deg, #0d6efd 0%, #6366f1 100%);
        border-radius: 4px;
        box-shadow: 0 0 12px rgba(13, 110, 253, 0.3);
        display: none;
        animation: indicatorPulse 2s ease-in-out infinite;
    }
    
    @media (min-width: 1024px) {
        .title-indicator {
            display: block;
        }
    }
    
    /* Title Content */
    .title-content {
        flex-grow: 1;
        min-width: 0;
    }
    
    .breadcrumb-title {
        margin: 0;
        font-size: clamp(1.125rem, 2vw, 1.625rem);
        font-weight: 700;
        color: #212529;
        line-height: 1.3;
        letter-spacing: -0.02em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        transition: color 0.3s ease;
    }
    
    .modern-breadcrumb-card:hover .breadcrumb-title {
        color: #0d6efd;
    }
    
    /* Title Underline */
    .title-underline {
        height: 2px;
        width: 50px;
        background: linear-gradient(90deg, #0d6efd 0%, #6366f1 70%, transparent 100%);
        border-radius: 2px;
        margin-top: 0.5rem;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modern-breadcrumb-card:hover .title-underline {
        width: 80px;
    }
    
    /* Status Badge */
    .breadcrumb-status {
        flex-shrink: 0;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.08) 0%, rgba(99, 102, 241, 0.08) 100%);
        border: 1px solid rgba(13, 110, 253, 0.15);
        border-radius: 10px;
        color: #0d6efd;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        transition: all 0.3s ease;
    }
    
    .status-badge:hover {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.12) 0%, rgba(99, 102, 241, 0.12) 100%);
        border-color: rgba(13, 110, 253, 0.25);
    }
    
    .status-badge i {
        font-size: 1rem;
        font-weight: 500;
    }
    
    /* Animations */
    @keyframes breadcrumbFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes accentExpand {
        from {
            transform: scaleX(0);
        }
        to {
            transform: scaleX(1);
        }
    }
    
    @keyframes indicatorPulse {
        0%, 100% {
            box-shadow: 0 0 12px rgba(13, 110, 253, 0.3);
            opacity: 1;
        }
        50% {
            box-shadow: 0 0 20px rgba(13, 110, 253, 0.5);
            opacity: 0.85;
        }
    }
    
    
    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .modern-breadcrumb-wrapper,
        .modern-breadcrumb-card,
        .modern-back-button,
        .breadcrumb-title,
        .title-underline,
        .breadcrumb-accent-line,
        .title-indicator {
            animation: none !important;
            transition: none !important;
        }
    }
    
    /* Print Styles */
    @media print {
        .modern-breadcrumb-card {
            background: white;
            box-shadow: none;
            border: 1px solid #dee2e6;
        }
        
        .breadcrumb-decoration,
        .breadcrumb-accent-line,
        .breadcrumb-status {
            display: none;
        }
        
        .modern-back-button {
            display: none;
        }
    }
    
    /* Responsive Mobile Refinements */
    @media (max-width: 767.98px) {
        .modern-breadcrumb-card {
            border-radius: 12px;
        }
        
        .breadcrumb-content {
            padding: 1.25rem 1rem;
        }
        
        .breadcrumb-title {
            font-size: 1.125rem;
        }
        
        .title-underline {
            height: 2px;
        }
    }
</style>

<script>
(function () {
    var homeUrl = @json(url('/'));
    var laravelPrevious = @json(url()->previous());

    window.handleBackNavigation = function handleBackNavigation() {
        var currentUrl = window.location.href;

        function sameOrigin(u) {
            try {
                return new URL(u).origin === window.location.origin;
            } catch (e) {
                return false;
            }
        }

        function stripHash(u) {
            try {
                var parsed = new URL(u);
                parsed.hash = '';
                return parsed.href;
            } catch (e) {
                return u;
            }
        }

        var currentNoHash = stripHash(currentUrl);

        // 1) Browser history — matches real "back" (fixes broken sessionStorage stack after browser Back/forward)
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        // 2) Same-origin referrer (e.g. opened in new tab from this site)
        var referrer = document.referrer;
        if (referrer && stripHash(referrer) !== currentNoHash && sameOrigin(referrer)) {
            window.location.href = referrer;
            return;
        }

        // 3) Laravel session previous URL
        if (laravelPrevious && stripHash(laravelPrevious) !== currentNoHash) {
            window.location.href = laravelPrevious;
            return;
        }

        window.location.href = homeUrl;
    };
})();
</script>
