@extends('admin.layouts.master')

@section('title', 'Dashboard')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-calendar.css') }}?v=4">
<style>
.admin-dashboard-surface {
    background-color: var(--bs-light-bg-subtle, #f8f9fa);
    min-height: 100%;
}

/* Page header card (reference design) */
.dashboard-hero-card {
    border: 1px solid var(--bs-border-color-translucent);
    transition: box-shadow 0.2s ease;
}

.dashboard-hero-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.08) !important;
}

/* Birthday notification banner — reference design */
.dashboard-birthday-banner {
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    min-height: 6.25rem;
}

.dashboard-birthday-banner .birthday-banner-accent {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 6px;
    background: linear-gradient(180deg, #1a3354 0%, #0f2340 100%);
    border-radius: 1rem 0 0 1rem;
    z-index: 3;
}

.dashboard-birthday-banner .birthday-banner-inner {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.125rem 3.25rem 1.125rem 1.5rem;
    min-height: 6.25rem;
}

.dashboard-birthday-banner .birthday-banner-text {
    flex: 1 1 auto;
    min-width: 0;
    padding-left: 0.35rem;
}

.dashboard-birthday-banner .birthday-banner-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1a1a1a;
    letter-spacing: -0.01em;
    line-height: 1.3;
}

.dashboard-birthday-banner .birthday-banner-subtitle {
    font-size: 0.875rem;
    color: #5c6670;
    line-height: 1.55;
    max-width: 42rem;
}

.dashboard-birthday-banner .birthday-banner-link {
    color: #004a93;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
}

.dashboard-birthday-banner .birthday-banner-link:hover {
    color: #003366;
    text-decoration: underline;
}

.dashboard-birthday-banner .birthday-banner-close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 4;
    width: 1.75rem;
    height: 1.75rem;
    padding: 0;
    border: none;
    border-radius: 0.375rem;
    background: #eef1f4;
    color: #dc3545;
    font-size: 1.125rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.dashboard-birthday-banner .birthday-banner-close:hover {
    background: #e2e6ea;
}

.dashboard-birthday-banner .birthday-banner-illustration {
    flex: 0 0 auto;
    align-self: flex-end;
    margin-bottom: -0.35rem;
    margin-right: -0.25rem;
    pointer-events: none;
    user-select: none;
}

.dashboard-birthday-banner .birthday-banner-illustration svg {
    display: block;
    width: clamp(7rem, 14vw, 9.5rem);
    height: auto;
}

#confetti-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.dashboard-birthday-banner.is-dismissed {
    display: none !important;
}

/* Birthday wishes received panel */
.dashboard-birthday-wish-item {
    border: 1px solid var(--bs-border-color-translucent);
    border-left: 3px solid var(--bs-primary);
    background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.04) 0%, transparent 100%);
    transition: box-shadow 0.15s ease, transform 0.15s ease;
}

.dashboard-birthday-wish-item:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 74, 147, 0.08);
    transform: translateY(-1px);
}

.dashboard-birthday-wish-item__actions .btn-wish-reply {
    font-size: 0.8125rem;
    font-weight: 500;
}

.dashboard-birthday-wish-item .wish-icon-wrap {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.15rem;
}

@media (max-width: 575.98px) {
    .dashboard-birthday-banner .birthday-banner-illustration {
        display: none;
    }

    .dashboard-birthday-banner .birthday-banner-inner {
        padding-right: 2.75rem;
    }
}

.dashboard-panel {
    border: 0;
    border-radius: 1rem;
    background: var(--bs-body-bg);
    box-shadow: 0 2px 12px rgba(16, 24, 40, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.dashboard-panel .card-header {
    border-bottom: 1px solid var(--bs-border-color-translucent);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

.dashboard-panel .card-header .material-icons.material-symbols-rounded {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.6rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    font-size: 1.2rem !important;
}

/* Dashboard metric cards — pixel-perfect reference */
.dashboard-stats-grid > .col > a {
    color: inherit;
}

.dashboard-stats-grid > .col > a:hover,
.dashboard-stats-grid > .col > a:focus {
    color: inherit;
}

.dashboard-stats-grid > .col > a .stat-label {
    color: var(--stat-label-color);
}

.dashboard-stats-grid > .col > a .stat-meta {
    color: #94a3b8;
}

.dashboard-stats-grid > .col > a .dashboard-stat-value {
    color: var(--stat-value-color);
}

.dashboard-stats-grid {
    --stat-radius: 12px;
    --stat-accent-width: 5px;
    --stat-pad-y: 20px;
    --stat-pad-x: 20px;
    --stat-pad-left: 24px;
    --stat-icon-size: 40px;
    --stat-icon-radius: 8px;
    --stat-label-size: 13px;
    --stat-label-color: #5f6b7a;
    --stat-value-size: 32px;
    --stat-value-color: #111827;
    --stat-card-min-h: 148px;
}

.dashboard-stat-card {
    position: relative;
    border: 0;
    border-radius: var(--stat-radius);
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.dashboard-stat-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: var(--stat-accent-width);
    background: var(--stat-accent, #2f80ed);
    z-index: 1;
}

.dashboard-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(15, 23, 42, 0.05), 0 10px 20px rgba(15, 23, 42, 0.08);
}

.dashboard-stat-card .dashboard-stat-card-inner,
.dashboard-stat-card .card-body.dashboard-stat-card-inner {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: var(--stat-icon-size) 1fr;
    column-gap: 12px;
    row-gap: 8px;
    align-content: space-between;
    min-height: var(--stat-card-min-h);
    height: 100%;
    padding: var(--stat-pad-y) var(--stat-pad-x) 26px var(--stat-pad-left);
}

.dashboard-stat-card:has(.stat-meta) {
    --stat-card-min-h: 172px;
}

.dashboard-stat-card:has(.stat-meta) .dashboard-stat-card-inner,
.dashboard-stat-card:has(.stat-meta) .card-body.dashboard-stat-card-inner {
    align-content: start;
    row-gap: 4px;
}

.dashboard-stat-card:has(.stat-meta) .dashboard-stat-value {
    margin: 0;
    padding-top: 8px;
}

.dashboard-stat-card .stat-icon {
    width: var(--stat-icon-size);
    height: var(--stat-icon-size);
    border-radius: var(--stat-icon-radius);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    line-height: 1;
    flex-shrink: 0;
    grid-column: 1;
    align-self: center;
    margin: 0;
}

.dashboard-stat-card .stat-label {
    grid-column: 2;
    align-self: center;
    margin: 0;
    padding: 0;
    font-size: var(--stat-label-size);
    font-weight: 400;
    line-height: 1.35;
    color: var(--stat-label-color);
    max-width: 100%;
}

.dashboard-stat-card .stat-meta {
    grid-column: 1 / -1;
    margin: 0;
    font-size: 12px;
    line-height: 1.35;
    color: #94a3b8;
}

.dashboard-stat-card .dashboard-stat-value {
    grid-column: 1 / -1;
    margin: 0;
    font-size: var(--stat-value-size);
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    color: var(--stat-value-color);
}

.dashboard-stat-card .stat-link-hint {
    position: absolute;
    left: var(--stat-pad-left);
    bottom: 14px;
    font-size: 11px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.dashboard-stat-card:hover .stat-link-hint {
    opacity: 1;
}

.dashboard-stat-card.card-blue { --stat-accent: #2f80ed; }
.dashboard-stat-card.card-green { --stat-accent: #27ae60; }
.dashboard-stat-card.card-amber { --stat-accent: #e8a317; }
.dashboard-stat-card.card-rose { --stat-accent: #dc3545; }
.dashboard-stat-card.card-navy { --stat-accent: #1e3a5f; }

.dashboard-stat-card.card-blue .stat-icon { background: rgba(47, 128, 237, 0.12); color: #2f80ed; }
.dashboard-stat-card.card-green .stat-icon { background: rgba(39, 174, 96, 0.12); color: #27ae60; }
.dashboard-stat-card.card-amber .stat-icon { background: rgba(232, 163, 23, 0.14); color: #c98a0e; }
.dashboard-stat-card.card-rose .stat-icon { background: rgba(220, 53, 69, 0.12); color: #dc3545; }
.dashboard-stat-card.card-navy .stat-icon { background: rgba(30, 58, 95, 0.12); color: #1e3a5f; }
.dashboard-stat-card.card-blue .stat-link-hint { color: #2f80ed; }
.dashboard-stat-card.card-green .stat-link-hint { color: #27ae60; }
.dashboard-stat-card.card-amber .stat-link-hint { color: #c98a0e; }
.dashboard-stat-card.card-rose .stat-link-hint { color: #dc3545; }
.dashboard-stat-card.card-navy .stat-link-hint { color: #1e3a5f; }

@media (max-width: 575.98px) {
    .dashboard-stats-grid {
        --stat-value-size: 28px;
        --stat-card-min-h: 132px;
    }
}

.dashboard-panel {
    border: 1px solid var(--bs-border-color-translucent);
    transition: box-shadow 0.2s ease;
}

.dashboard-panel:hover {
    box-shadow: 0 0.5rem 1rem rgba(16, 24, 40, 0.08) !important;
}

/* Today's Birthdays panel — pixel-perfect reference */
.dashboard-birthdays-panel {
    --bd-primary: #004a99;
    --bd-primary-hover: #003d80;
    --bd-border: #e5e7eb;
    --bd-text: #1f2937;
    --bd-muted: #6b7280;
    --bd-pill-bg: #e7f1ff;
    border: 1px solid #eef0f3;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 4px 14px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.dashboard-birthdays-panel .card-header {
    padding: 1rem 1.25rem 0 !important;
}

.dashboard-birthdays-panel .card-body {
    padding: 1rem 1.25rem !important;
}

.dashboard-birthdays-panel .card-footer {
    padding: 0.75rem 1.25rem 1rem !important;
}

.dashboard-birthdays-panel__title {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--bd-text);
    letter-spacing: -0.01em;
    line-height: 1.3;
}

.dashboard-birthdays-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    min-width: 1.75rem;
    padding: 0;
    border-radius: 50%;
    background: var(--bd-primary);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    flex-shrink: 0;
}

.dashboard-birthdays-count--wide {
    width: auto;
    min-width: 1.75rem;
    padding: 0 0.35rem;
    border-radius: 999px;
}

.dashboard-birthdays-divider {
    margin: 0.75rem 0 0;
    border: 0;
    border-top: 1px solid #e5e7eb;
    opacity: 1;
}

.dashboard-birthdays-toolbar {
    padding: 0.5rem 1.25rem;
    background: #f9fafb;
    border-bottom: 1px solid #eef0f3;
    gap: 0.5rem;
}

.dashboard-birthdays-toolbar .btn {
    font-size: 0.75rem;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
}

.dashboard-birthday-item {
    background: #fff;
    border: 1px solid var(--bd-border) !important;
    border-radius: 8px !important;
    padding: 0.75rem !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dashboard-birthday-item:hover {
    border-color: #d1d5db !important;
    box-shadow: 0 1px 4px rgba(0, 74, 153, 0.06);
}

.dashboard-birthday-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-height: 2.5rem;
}

.dashboard-avatar-wrap {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.dashboard-birthdays-panel .dashboard-avatar,
.dashboard-birthdays-panel--upcoming .dashboard-avatar,
.dashboard-birthdays-panel .dashboard-avatar-initial,
.dashboard-birthdays-panel--upcoming .dashboard-avatar-initial {
    width: 40px;
    height: 40px;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.dashboard-avatar-initial {
    line-height: 1;
    user-select: none;
}

@media (max-width: 575.98px) {
    .dashboard-birthday-row {
        flex-wrap: wrap;
    }
    .dashboard-birthday-wish-btn {
        width: 100%;
        margin-top: 0.25rem;
        justify-content: center;
    }
    .dashboard-birthday-info {
        flex: 1 1 calc(100% - 3.25rem);
    }
}

.dashboard-birthday-info {
    flex: 1 1 auto;
    min-width: 0;
}

.dashboard-birthday-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--bd-primary);
    line-height: 1.25;
    margin: 0;
}

.dashboard-birthday-designation {
    font-size: 0.8125rem;
    font-weight: 400;
    color: var(--bd-muted);
    line-height: 1.3;
    margin: 0.125rem 0 0;
}

.dashboard-birthday-wish-btn {
    flex-shrink: 0;
    padding: 0.375rem 0.875rem;
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.2;
    color: var(--bd-primary) !important;
    border: 1px solid var(--bd-primary) !important;
    background: #fff !important;
    border-radius: 6px !important;
    white-space: nowrap;
}

.dashboard-birthday-wish-btn:hover,
.dashboard-birthday-wish-btn:focus {
    color: #fff !important;
    background: var(--bd-primary) !important;
    border-color: var(--bd-primary) !important;
}

.dashboard-birthday-detail {
    margin-top: 0;
    padding-top: 0;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition:
        max-height 0.25s ease,
        opacity 0.15s ease,
        margin-top 0.2s ease,
        padding-top 0.2s ease,
        visibility 0s linear 0.2s;
}

.dashboard-birthday-item:hover .dashboard-birthday-detail,
.dashboard-birthday-item:focus-within .dashboard-birthday-detail {
    margin-top: 0.75rem;
    padding-top: 0;
    max-height: 10rem;
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transition:
        max-height 0.25s ease,
        opacity 0.15s ease,
        margin-top 0.2s ease,
        padding-top 0.2s ease,
        visibility 0s;
}

.dashboard-birthday-contact-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    max-width: 100%;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    background: var(--bd-pill-bg);
    color: var(--bd-primary);
    font-size: 0.8125rem;
    font-weight: 400;
    line-height: 1.2;
}

.dashboard-birthday-contact-pill i {
    flex-shrink: 0;
    font-size: 0.875rem;
    opacity: 0.9;
}

.dashboard-birthday-contact-pill span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dashboard-birthday-actions-compact {
    margin-top: 0.5rem;
    gap: 0.375rem !important;
}

.dashboard-birthday-actions-compact .btn {
    font-size: 0.6875rem;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    font-weight: 500;
}

.dashboard-birthdays-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.dashboard-birthdays-see-all {
    color: var(--bd-primary);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    margin-left: auto;
}

.dashboard-birthdays-see-all:hover {
    color: var(--bd-primary-hover);
    text-decoration: underline;
}

.dashboard-birthday-badge {
    font-size: 0.625rem;
    font-weight: 600;
    vertical-align: middle;
}

.dashboard-birthdays-panel .dashboard-list-scroll {
    max-height: 22rem;
}

.dashboard-birthdays-panel--upcoming {
    border: 1px solid #eef0f3;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 4px 14px rgba(15, 23, 42, 0.04);
}

/* Calendar card shell (grid styles in dashboard-calendar.css) */
.dashboard-birthdays-panel--calendar {
    border: 1px solid #eef0f3;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 4px 14px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.dashboard-birthdays-panel--calendar .card-header {
    padding: 1.25rem 1.5rem 0 !important;
}

.dashboard-birthdays-panel--calendar .card-body {
    padding: 1rem 1.5rem 1.5rem !important;
}

.dashboard-birthdays-panel--calendar .dashboard-birthdays-panel__title {
    font-size: 1.0625rem;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.01em;
}

.dashboard-birthdays-panel--calendar .dashboard-birthdays-divider {
    margin-top: 0.875rem;
}

.dashboard-birthdays-panel--calendar .dashboard-calendar-date-badge {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #2c5da7;
    line-height: 1.3;
}

/* Calendar: force override layout master styles (loads after head) */
#dashboard-calendar-container .calendar-component .calendar-cell.is-selected {
    background-color: #004a8f !important;
    border: 1px solid #004a8f !important;
    outline: none !important;
    box-shadow: none !important;
}

#dashboard-calendar-container .calendar-component .calendar-cell.is-selected .day-number {
    color: #fff !important;
}

@media (max-width: 575.98px) {
    .dashboard-birthdays-panel--calendar .card-header,
    .dashboard-birthdays-panel--calendar .card-body {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}


.dashboard-avatar {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.dashboard-list-scroll {
    max-height: 23rem;
    overflow-y: auto;
}

@media (max-width: 991.98px) {
    .dashboard-list-scroll {
        max-height: none;
    }
}

/* Birthday wish modal — reference-aligned */
#customWishModal {
    --wish-navy: #004a99;
    --wish-navy-hover: #003d80;
    --wish-border: #e5e7eb;
    --wish-muted: #6b7280;
}

#customWishModal .dashboard-wish-modal-dialog {
    max-width: 32rem;
}

#customWishModal .dashboard-wish-modal {
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
}

#customWishModal .dashboard-wish-modal__header {
    padding: 1.25rem 1.5rem 0.75rem;
    border: 0;
}

#customWishModal .dashboard-wish-modal__title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    letter-spacing: -0.01em;
}

#customWishModal .dashboard-wish-modal__divider {
    border: 0;
    border-top: 1px solid var(--wish-border);
    opacity: 1;
    margin: 0 1.5rem;
}

#customWishModal .dashboard-wish-modal__body {
    padding: 1rem 1.5rem 1.25rem;
}

#customWishModal .dashboard-wish-intro {
    font-size: 0.9375rem;
    color: #374151;
    line-height: 1.5;
    margin-bottom: 1.25rem;
}

#customWishModal .dashboard-wish-name-inline {
    display: inline-block;
    width: auto;
    min-width: 3ch;
    max-width: min(100%, 18rem);
    border: 0;
    background: transparent;
    color: var(--wish-navy);
    font-size: inherit;
    font-weight: 700;
    padding: 0;
    margin: 0 0.125rem;
    vertical-align: baseline;
    outline: none;
    box-shadow: none;
}

#customWishModal .dashboard-wish-options {
    margin-bottom: 1.25rem;
}

#customWishModal .dashboard-wish-options .form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.375rem;
}

#customWishModal .dashboard-wish-options .form-select,
#customWishModal .dashboard-wish-options .form-control {
    font-size: 0.875rem;
    border-color: var(--wish-border);
    border-radius: 6px;
}

#customWishModal .dashboard-wish-options .form-select:focus,
#customWishModal .dashboard-wish-options .form-control:focus {
    border-color: var(--wish-navy);
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 153, 0.15);
}

#customWishModal .dashboard-wish-message-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

#customWishModal .dashboard-wish-textarea {
    font-size: 0.875rem;
    line-height: 1.5;
    border-color: var(--wish-border);
    border-radius: 6px;
    min-height: 9.5rem;
    resize: vertical;
}

#customWishModal .dashboard-wish-textarea:focus {
    border-color: var(--wish-navy);
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 153, 0.15);
}

#customWishModal .dashboard-wish-channels {
    gap: 1.5rem;
}

#customWishModal .dashboard-wish-channels .form-check-input {
    width: 1.125rem;
    height: 1.125rem;
    margin-top: 0.125rem;
    border-color: #9ca3af;
}

#customWishModal .dashboard-wish-channels .form-check-input:checked {
    background-color: var(--wish-navy);
    border-color: var(--wish-navy);
}

#customWishModal .dashboard-wish-channels .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 153, 0.2);
}

#customWishModal .dashboard-wish-channels .form-check-label {
    font-size: 0.875rem;
    color: #374151;
    padding-left: 0.25rem;
}

#customWishModal .dashboard-wish-modal__footer {
    padding: 0 1.5rem 1.5rem;
    gap: 0.75rem;
}

#customWishModal .dashboard-wish-btn-cancel {
    color: var(--wish-navy);
    border: 1px solid var(--wish-navy);
    background: #fff;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 1.25rem;
    transition: background-color 0.15s ease, color 0.15s ease;
}

#customWishModal .dashboard-wish-btn-cancel:hover {
    color: #fff;
    background: var(--wish-navy);
    border-color: var(--wish-navy);
}

#customWishModal .dashboard-wish-btn-send {
    color: #fff;
    background: var(--wish-navy);
    border: 1px solid var(--wish-navy);
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

#customWishModal .dashboard-wish-btn-send:hover:not(:disabled) {
    background: var(--wish-navy-hover);
    border-color: var(--wish-navy-hover);
}

#customWishModal .dashboard-wish-btn-send:disabled {
    opacity: 0.7;
}

.dashboard-empty-state {
    text-align: center;
    padding: 2rem 1.25rem;
    color: var(--bs-secondary);
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.03) 0%, transparent 100%);
    border-radius: 0.75rem;
    border: 1px dashed var(--bs-border-color-translucent);
}

.dashboard-empty-state .material-icons {
    font-size: 2.75rem;
    margin-bottom: 0.75rem;
    opacity: 0.4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.06);
}

.dashboard-empty-state p {
    font-size: 0.875rem;
}

/* Dashboard feed panels (Admin Summary, Tweets, Classes, Notices) */
.dashboard-feed-panel {
    --dashboard-feed-navy: #004a8f;
    --dashboard-feed-gray: #f4f6f8;
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.5rem rgba(15, 23, 42, 0.06);
}

.dashboard-feed-panel .card-header {
    background: #fff !important;
    border-bottom: 0 !important;
    padding-bottom: 0;
}

.dashboard-feed-panel__title {
    font-size: 1.0625rem;
    font-weight: 600;
    color: #1e293b;
    letter-spacing: -0.01em;
}

.dashboard-feed-count-badge {
    width: 2rem;
    height: 2rem;
    min-width: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--dashboard-feed-navy);
    color: #fff;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
}

.dashboard-feed-divider {
    border: 0;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    opacity: 1;
    margin: 0.75rem 0 0;
}

.dashboard-feed-btn-primary {
    background: var(--dashboard-feed-navy);
    color: #fff;
    border: 0;
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 0.4375rem 0.875rem;
    border-radius: 0.375rem;
    transition: background 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-feed-btn-primary:hover,
.dashboard-feed-btn-primary:focus {
    background: #003d75;
    color: #fff;
    box-shadow: 0 2px 8px rgba(0, 74, 143, 0.25);
}

.dashboard-feed-footer {
    padding-top: 0.75rem;
    margin-top: 0.25rem;
    text-align: end;
}

.dashboard-feed-footer .dashboard-feed-see-all {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--dashboard-feed-navy);
    text-decoration: none;
}

.dashboard-feed-footer .dashboard-feed-see-all:hover {
    text-decoration: underline;
    color: #003d75;
}

/* See-all expanded feed panel */
.dashboard-feed-expanded {
    --dashboard-feed-navy: #004a8f;
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.5rem rgba(15, 23, 42, 0.06);
}

.dashboard-feed-expanded__toolbar {
    gap: 1rem;
}

.dashboard-feed-expanded-tabs {
    gap: 0.35rem;
    flex-wrap: wrap;
}

.dashboard-feed-expanded-tabs .nav-link {
    border: 0;
    border-radius: 2rem;
    padding: 0.5rem 1.125rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    background: #f1f5f9;
    transition: background 0.2s ease, color 0.2s ease;
}

.dashboard-feed-expanded-tabs .nav-link:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.dashboard-feed-expanded-tabs .nav-link.active {
    background: var(--dashboard-feed-navy);
    color: #fff;
}

.dashboard-feed-expanded-search {
    max-width: 220px;
    min-width: 160px;
}

.dashboard-feed-expanded-search .input-group-text {
    background: #fff;
    border-right: 0;
    color: #94a3b8;
}

.dashboard-feed-expanded-search .form-control {
    border-left: 0;
    font-size: 0.875rem;
}

.dashboard-feed-expanded-search .form-control:focus {
    box-shadow: none;
    border-color: var(--bs-border-color);
}

.dashboard-feed-expanded-search .input-group:focus-within .input-group-text,
.dashboard-feed-expanded-search .input-group:focus-within .form-control {
    border-color: #86b7fe;
}

.dashboard-feed-expanded-meta {
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.dashboard-feed-expanded-count {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.dashboard-feed-mark-all-read {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--dashboard-feed-navy);
    text-decoration: none;
    padding: 0;
    border: 0;
    background: transparent;
}

.dashboard-feed-mark-all-read:hover {
    text-decoration: underline;
    color: #003d75;
}

.dashboard-feed-expanded-back {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.dashboard-feed-expanded-back:hover {
    color: var(--dashboard-feed-navy);
}

.dashboard-feed-expanded-card {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem 1.125rem;
    margin-bottom: 0.75rem;
    background: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-feed-expanded-card:last-child {
    margin-bottom: 0;
}

.dashboard-feed-expanded-card--clickable {
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.dashboard-feed-expanded-card--clickable:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}

.dashboard-feed-expanded-card--unread {
    border-color: rgba(0, 74, 143, 0.25);
    background: #fafbfc;
}

.dashboard-feed-expanded-card__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.dashboard-feed-expanded-card__title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
    line-height: 1.4;
}

.dashboard-feed-expanded-card__meta {
    font-size: 0.75rem;
    color: #64748b;
    white-space: nowrap;
    flex-shrink: 0;
    text-align: right;
}

.dashboard-feed-expanded-card__meta strong {
    color: #475569;
    font-weight: 600;
}

.dashboard-feed-expanded-card__body {
    font-size: 0.8125rem;
    color: #475569;
    line-height: 1.55;
    margin: 0;
}

.dashboard-feed-expanded-list {
    max-height: min(62vh, 520px);
    overflow-y: auto;
    padding-right: 0.25rem;
}

.dashboard-feed-expanded-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #64748b;
    font-size: 0.875rem;
}

.dashboard-feed-expanded-birthday-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.dashboard-feed-expanded-birthday-row .dashboard-birthday-wish-btn {
    margin-left: auto;
}

.dashboard-feed-empty {
    text-align: center;
    padding: 2.5rem 1.25rem 2rem;
}

.dashboard-feed-empty__icon {
    width: 4.5rem;
    height: 4.5rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: rgba(0, 74, 143, 0.08);
    color: var(--dashboard-feed-navy);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.dashboard-tweet-item {
    padding: 0.875rem 1rem;
    margin-bottom: 0.625rem;
    border-radius: 0.5rem;
    border-left: 4px solid var(--dashboard-feed-navy, #004a8f);
    background: var(--dashboard-feed-gray, #f4f6f8);
    transition: background 0.2s ease, transform 0.15s ease;
}

.dashboard-tweet-item:hover {
    background: #eef2f6;
    transform: translateX(2px);
}

.dashboard-tweet-item:last-child {
    margin-bottom: 0;
}

/* Today's Classes cards */
.dashboard-class-card {
    padding: 1rem 1.125rem;
    margin-bottom: 0.625rem;
    border-radius: 0.5rem;
    border: 0;
    border-left: 4px solid var(--dashboard-feed-navy, #004a8f);
    background: var(--dashboard-feed-gray, #f4f6f8);
    transition: background 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-class-card:hover {
    background: #eef2f6;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
}

.dashboard-class-card:last-child {
    margin-bottom: 0;
}

.dashboard-class-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: rgba(0, 74, 143, 0.1);
    color: var(--dashboard-feed-navy, #004a8f);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.dashboard-class-topic {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.dashboard-class-meta {
    font-size: 0.8125rem;
    color: #64748b;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
}

.dashboard-class-meta span {
    white-space: nowrap;
}

.dashboard-notice-tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem;
    margin-bottom: 1rem;
    background: var(--dashboard-feed-gray, #f4f6f8);
    border-radius: 0.5rem;
}

.dashboard-notice-tab {
    flex: 1 1 auto;
    min-width: 0;
    border: 0;
    background: transparent;
    color: #1e293b;
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    text-align: center;
    transition: background 0.2s ease, color 0.2s ease;
    white-space: nowrap;
}

@media (max-width: 575.98px) {
    .dashboard-notice-tab {
        font-size: 0.75rem;
        padding: 0.4375rem 0.5rem;
        white-space: normal;
    }
}

.dashboard-notice-tab:hover {
    background: rgba(0, 74, 143, 0.06);
}

.dashboard-notice-tab.active {
    background: var(--dashboard-feed-navy, #004a8f);
    color: #fff;
}

.dashboard-notice-tab-empty {
    opacity: 0.55;
}

.dashboard-notice-list-empty {
    text-align: center;
    padding: 1.5rem 1rem;
    color: #64748b;
    font-size: 0.875rem;
}

.dashboard-list-scroll::-webkit-scrollbar {
    width: 6px;
}

.dashboard-list-scroll::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.04);
    border-radius: 3px;
}

.dashboard-list-scroll::-webkit-scrollbar-thumb {
    background: rgba(var(--bs-primary-rgb), 0.25);
    border-radius: 3px;
}

.dashboard-list-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(var(--bs-primary-rgb), 0.4);
}

.dashboard-panel .card-header .badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35em 0.65em;
    min-width: 1.75rem;
    text-align: center;
}

/* Notifications panel - ringing bell + item design */
.dashboard-notification-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem 1.125rem;
    margin-bottom: 0.625rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    text-align: left;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.dashboard-notification-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}

.dashboard-notification-item-unread {
    border-color: rgba(0, 74, 143, 0.2);
    background: #fafbfc;
}

.dashboard-notification-item .notification-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--bs-border-radius-sm);
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    flex-shrink: 0;
    font-size: 1.125rem;
    line-height: 1;
}

.dashboard-notification-bell--ring {
    transform-origin: top center;
    animation: dashboard-bell-ring 1.25s ease-in-out infinite;
}

@keyframes dashboard-bell-ring {
    0%,
    100% {
        transform: rotate(0);
    }

    8% {
        transform: rotate(16deg);
    }

    16% {
        transform: rotate(-14deg);
    }

    24% {
        transform: rotate(12deg);
    }

    32% {
        transform: rotate(-10deg);
    }

    40% {
        transform: rotate(8deg);
    }

    48% {
        transform: rotate(-6deg);
    }

    56% {
        transform: rotate(4deg);
    }

    64% {
        transform: rotate(-2deg);
    }

    72% {
        transform: rotate(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-notification-bell--ring {
        animation: none;
    }
}

#dashboard-notifications-panel .dashboard-panel-bell--ring {
    display: inline-block;
    transform-origin: top center;
    animation: dashboard-bell-ring 1.25s ease-in-out infinite;
}

.dashboard-notification-body {
    flex-grow: 1;
    min-width: 0;
}

.dashboard-notification-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #8b2942;
    line-height: 1.35;
    display: block;
}

.dashboard-notification-message {
    font-size: 0.8125rem;
    color: #475569;
    margin: 0.5rem 0 0;
    line-height: 1.5;
}

.dashboard-notification-time {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.375rem;
    display: block;
}

.dashboard-notification-time::before {
    display: none;
}

/* Blinking "New" tag for unread notifications */
.dashboard-notification-new-tag {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.3em 0.6em;
    flex-shrink: 0;
    animation: dashboard-notification-blink 1s ease-in-out infinite;
}

@keyframes dashboard-notification-blink {

    0%,
    100% {
        opacity: 1;
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--bs-danger-rgb), 0.5);
    }

    50% {
        opacity: 0.9;
        transform: scale(1.03);
        box-shadow: 0 0 0 6px rgba(var(--bs-danger-rgb), 0);
    }
}

/* Notices panel - item design and blinking "New" tag */
.dashboard-notice-item {
    display: block;
    padding: 1rem 1.125rem;
    border-radius: 0.5rem;
    border: 0;
    border-left: 4px solid var(--dashboard-feed-navy, #004a8f);
    background: var(--dashboard-feed-gray, #f4f6f8);
    text-decoration: none;
    color: inherit;
    transition: background 0.2s ease, transform 0.15s ease;
}

.dashboard-notice-item:hover {
    background: #eef2f6;
    transform: translateX(2px);
}

.dashboard-notice-item-new {
    background: #eef4fa;
}

.dashboard-notice-item .notice-icon-wrap {
    display: none;
}

.dashboard-notice-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--dashboard-feed-navy, #004a8f);
    line-height: 1.4;
    display: block;
}

.dashboard-notice-date {
    font-size: 0.8125rem;
    color: #64748b;
    margin-top: 0.375rem;
    display: block;
}

.dashboard-notice-date::before {
    display: none;
}

.dashboard-notice-attachment {
    font-size: 0.8125rem;
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(var(--bs-danger-rgb), 0.08);
    transition: background 0.2s ease;
}

.dashboard-notice-attachment:hover {
    background: rgba(var(--bs-danger-rgb), 0.14);
}

.dashboard-notice-new-tag {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.3em 0.6em;
    animation: dashboard-notice-blink 1.2s ease-in-out infinite;
}

@keyframes dashboard-notice-blink {

    0%,
    100% {
        opacity: 1;
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--bs-danger-rgb), 0.45);
    }

    50% {
        opacity: 0.9;
        transform: scale(1.03);
        box-shadow: 0 0 0 6px rgba(var(--bs-danger-rgb), 0);
    }
}

.dashboard-stat-card:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

table>thead {
    background-color: transparent !important;
}
</style>

@php
$user = Auth::user();
$isAdminSummary = hasRole('Admin');
$daysOld = $isAdminSummary ? 10 : null;
$notifications = ($user && $user->user_id) ? notification()->getNotifications($user->user_id, 10, false, $daysOld) :
collect();
$notificationBadgeCount = ($user && $user->user_id)
? ($isAdminSummary ? notification()->getUnreadCount($user->user_id, $daysOld) : $notifications->count())
: 0;
$notices = get_notice_notification_by_role();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening' ); $userName=$user ? (trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'User')) : 'User';

    $todayBirthdayWishNotifications = collect();
    $myBirthdayWishesSummary = '';
    if (($isMyBirthday ?? false) && $user && $user->user_id) {
    $todayBirthdayWishNotifications = \App\Models\Notification::with('sender')
    ->where('receiver_user_id', $user->user_id)
    ->where('type', 'birthday')
    ->whereDate('created_at', today())
    ->orderByDesc('created_at')
    ->get();

    $myBirthdayWishSenderNames = $todayBirthdayWishNotifications->map(function ($notification) {
    if ($notification->sender) {
    return trim((string) ($notification->sender->first_name ?? $notification->sender->name ?? ''));
    }
    if (!empty($notification->message) && preg_match('/^(.+?)\s+wished you/i', $notification->message, $matches)) {
    return trim($matches[1]);
    }
    return null;
    })->filter()->unique()->values();

    $wishNameCount = $myBirthdayWishSenderNames->count();
    $wishTotal = (int) ($myBirthdayWishCount ?? 0);

    if ($wishNameCount === 1) {
    $myBirthdayWishesSummary = $myBirthdayWishSenderNames->first() . ' has sent their wish.';
    } elseif ($wishNameCount === 2) {
    $myBirthdayWishesSummary = $myBirthdayWishSenderNames->implode(' and ') . ' have sent their wishes.';
    } elseif ($wishNameCount > 2) {
    $others = $wishNameCount - 2;
    $myBirthdayWishesSummary = $myBirthdayWishSenderNames->take(2)->implode(', ')
    . ' and ' . $others . ' ' . ($others === 1 ? 'other' : 'others') . ' have sent their wishes.';
    } elseif ($wishTotal > 0) {
    $myBirthdayWishesSummary = $wishTotal === 1
    ? '1 person has sent their wish.'
    : $wishTotal . ' people have sent their wishes.';
    }
    }
    @endphp

    <div class="container-fluid">
        @if($isMyBirthday ?? false)
        {{-- Birthday Banner with Confetti (reference design) --}}
        <div class="dashboard-birthday-banner rounded-4 shadow-sm mb-3 position-relative overflow-hidden"
            id="birthday-banner">
            <div class="birthday-banner-accent" aria-hidden="true"></div>
            <canvas id="confetti-canvas" aria-hidden="true"></canvas>
            <button type="button" class="birthday-banner-close" id="birthday-banner-dismiss"
                aria-label="Dismiss birthday message">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="birthday-banner-inner">
                <div class="birthday-banner-text">
                    <h2 class="birthday-banner-title mb-2">Happy Birthday {{ $userName }}</h2>
                    <p class="birthday-banner-subtitle mb-0">
                        Wishing you a fantastic year ahead 🎉
                        @if(!empty($myBirthdayWishesSummary))
                        {{ ' ' . $myBirthdayWishesSummary }}
                        @endif
                        @if(($myBirthdayWishCount ?? 0) > 0)
                        <a href="{{ route('admin.dashboard.feed', ['tab' => 'wishes']) }}"
                            class="birthday-banner-link" id="btn-view-birthday-wishes">View all wishes →</a>
                        @endif
                    </p>
                </div>
                <div class="birthday-banner-illustration d-none d-sm-block" aria-hidden="true">
                    <svg viewBox="0 0 152 88" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="">
                        <ellipse cx="118" cy="72" rx="28" ry="6" fill="#E8EDF3" opacity="0.9" />
                        <rect x="98" y="52" width="22" height="18" rx="3" fill="#F4C430" />
                        <rect x="98" y="52" width="22" height="5" rx="2" fill="#E5A820" />
                        <path d="M99 52h20v3H99z" fill="#D4921A" />
                        <rect x="124" y="48" width="18" height="16" rx="3" fill="#E74C3C" />
                        <rect x="124" y="48" width="18" height="4" rx="2" fill="#C0392B" />
                        <path d="M108 38c0-8 6-14 14-14s14 6 14 14c0 6-4 11-10 13l-4 10-4-10c-6-2-10-7-10-13z"
                            fill="#3498DB" />
                        <path d="M108 38c0-8 6-14 14-14" stroke="#2980B9" stroke-width="1.5" stroke-linecap="round" />
                        <line x1="122" y1="24" x2="122" y2="14" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <circle cx="88" cy="30" r="11" fill="#2ECC71" />
                        <line x1="88" y1="19" x2="88" y2="11" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <circle cx="72" cy="36" r="10" fill="#F1C40F" />
                        <line x1="72" y1="26" x2="72" y2="18" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <circle cx="56" cy="28" r="9" fill="#E74C3C" />
                        <line x1="56" y1="19" x2="56" y2="12" stroke="#7f8c8d" stroke-width="1.2"
                            stroke-linecap="round" />
                        <rect x="78" y="58" width="16" height="14" rx="2.5" fill="#9B59B6" />
                        <rect x="78" y="58" width="16" height="4" rx="1.5" fill="#8E44AD" />
                    </svg>
                </div>
            </div>
        </div>
        @endif

        <div class="card dashboard-hero-card shadow-sm rounded-4 border-0 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="min-w-0">
                        <p class="text-body-secondary mb-1 mb-md-2">
                            {{ $greeting }},
                            <span class="text-primary fw-semibold">{{ $userName }}</span>
                        </p>
                        <h1 class="h2 fw-bold text-dark mb-0">Dashboard</h1>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2 text-primary">
                            <i class="bi bi-clock fs-5" aria-hidden="true"></i>
                            <span class="fs-4 fw-semibold tabular-nums"
                                id="dashboard-live-time">{{ now()->format('H:i') }}</span>
                        </div>
                        <p class="text-body-secondary small mb-0 mt-1">
                            {{ now()->format('l, d F Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if(($isMyBirthday ?? false) && ($myBirthdayWishCount ?? 0) > 0)
        <div class="card dashboard-panel shadow-sm rounded-4 mb-4" id="dashboard-birthday-wishes-panel">
            <div class="card-header bg-body py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                <span
                    class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary p-2">
                    <i class="bi bi-balloon-heart-fill" aria-hidden="true"></i>
                </span>
                <h5 class="mb-0 fw-semibold">Birthday Wishes Received</h5>
                <span class="badge rounded-pill text-bg-primary">{{ $myBirthdayWishCount }}</span>
                <button type="button"
                    class="btn btn-sm btn-outline-primary rounded-pill ms-auto d-none d-md-inline-flex align-items-center gap-1"
                    data-bs-toggle="collapse" data-bs-target="#dashboard-birthday-wishes-collapse" aria-expanded="false"
                    aria-controls="dashboard-birthday-wishes-collapse" id="btn-toggle-birthday-wishes">
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    <span class="small">Show / Hide</span>
                </button>
            </div>
            <div class="collapse" id="dashboard-birthday-wishes-collapse">
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if($todayBirthdayWishNotifications->isEmpty())
                    <div class="dashboard-empty-state py-4">
                        <i class="bi bi-balloon-heart text-primary opacity-50 fs-1 d-block mb-2" aria-hidden="true"></i>
                        <p class="mb-0 small">Wishes received today will appear here.</p>
                    </div>
                    @else
                    <ul class="list-unstyled mb-0 ps-0">
                        @foreach($todayBirthdayWishNotifications as $wish)
                        <li class="mb-2">
                            @include('admin.dashboard.partials.wish-received-item', ['wish' => $wish, 'layout' => 'dashboard'])
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(hasRole('Security Card') || hasRole('Admin Security'))
        @php
        $idCardApprovalRoute = hasRole('Admin Security')
        ? route('admin.security.employee_idcard_approval.approval3')
        : route('admin.security.employee_idcard_approval.approval2');
        @endphp
        <div class="dashboard-stats-grid row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-lg-3">
            <div class="col">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                            <p class="stat-label">Pending Permanent ID Requests</p>
                            <p class="stat-meta">Today</p>
                            @php $v = (int) ($todayPendingPermanentIdCardRequests ?? 0); @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <p class="stat-meta mb-0">Total pending: {{ $fullPendingPermanentIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                            <p class="stat-label">Pending Contractual ID Requests</p>
                            <p class="stat-meta">Today</p>
                            @php $v = (int) ($todayPendingContractualIdCardRequests ?? 0); @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <p class="stat-meta mb-0">Total pending: {{ $fullPendingContractualIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-copy"></i></span>
                            <p class="stat-label">Duplicate Permanent ID Requests</p>
                            <p class="stat-meta">Today</p>
                            @php $v = (int) ($todayDuplicatePermIdCardRequests ?? 0); @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <p class="stat-meta mb-0">Total pending: {{ $fullDuplicatePermIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-files"></i></span>
                            <p class="stat-label">Duplicate Contractual ID Requests</p>
                            <p class="stat-meta">Today</p>
                            @php $v = (int) ($todayDuplicateContractualIdCardRequests ?? 0); @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <p class="stat-meta mb-0">Total pending: {{ $fullDuplicateContractualIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.security.family_idcard_approval.index') }}"
                    class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                            <p class="stat-label">Requested Family ID</p>
                            <p class="stat-meta">Today</p>
                            @php $v = (int) ($todayFamilyApprovals ?? 0); @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <p class="stat-meta mb-0">Total pending: {{ $fullFamilyApprovals ?? 0 }}</p>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.security.vehicle_pass_approval.index') }}"
                    class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-green h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                            <p class="stat-label">Requested Vehicle Pass</p>
                            <p class="stat-meta">Today</p>
                            @php $v = (int) ($todayVehicleApprovals ?? 0); @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <p class="stat-meta mb-0">Total pending: {{ $fullVehicleApprovals ?? 0 }}</p>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        @endif
        @if(!hasRole('Security Card') && !hasRole('Admin Security'))
        <div class="dashboard-stats-grid row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5">
            <div class="col">
                <a href="{{ route('admin.dashboard.active_course') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                            <p class="stat-label">Total Active Courses</p>
                            @php $v = (int) $totalActiveCourses; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('admin.dashboard.incoming_course') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-green h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                            <p class="stat-label">Upcoming Courses</p>
                            @php $v = (int) $upcomingCourses; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('admin.dashboard.upcoming_events') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
                            <p class="stat-label">Upcoming Events</p>
                            @php $v = 2; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                @if(hasRole('Student-OT'))
                <a href="{{ route('medical.exception.ot.view') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-rose h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-heart-pulse"></i></span>
                            <p class="stat-label">Medical Exception</p>
                            @php $v = (int) $exemptionCount; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
                @else
                <a href="{{ route('admin.dashboard.guest_faculty') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-rose h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                            <p class="stat-label">Total Guest Faculty</p>
                            @php $v = (int) $total_guest_faculty; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
                @endif
            </div>

            @if(($todayApproval1IdCardRequests ?? 0) > 0)
            <div class="col">
                <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}"
                    class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                            <p class="stat-label">Today's Pending ID Card Requests
                                (Approval I)</p>
                            @php $v = (int) $todayApproval1IdCardRequests; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            @if(($todayApproval1DuplicateIdCardRequests ?? 0) > 0)
            <div class="col">
                <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}"
                    class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-copy"></i></span>
                            <p class="stat-label">Today's Pending Duplicate ID Card
                                Requests (Approval I)</p>
                            @php $v = (int) $todayApproval1DuplicateIdCardRequests; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">Go to approvals <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <div class="col">
                @if(hasRole('Student-OT'))
                <a href="{{ route('ot.mdo.escrot.exemption.view') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-navy h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-person-gear"></i></span>
                            <p class="stat-label">OT MDO/Escort</p>
                            @php $v = (int) $MDO_count; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
                @else
                <a href="{{ route('admin.dashboard.inhouse_faculty') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-navy h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
                            <p class="stat-label">Total Inhouse Faculty</p>
                            @php $v = (int) $total_internal_faculty; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
                @endif
            </div>

            @if(hasRole('Internal Faculty') || hasRole('Guest Faculty'))
            <div class="col">
                <a href="{{ route('admin.dashboard.sessions') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-green h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                            <p class="stat-label">Session Details</p>
                            @php $v = (int) $totalSessions; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            @if(isset($isCCorACC) && $isCCorACC)
            <div class="col">
                <a href="{{ route('admin.dashboard.students') }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body dashboard-stat-card-inner">
                            <span class="stat-icon" aria-hidden="true"><i
                                    class="bi bi-person-lines-fill"></i></span>
                            <p class="stat-label">Total Students</p>
                            @php $v = (int) $totalStudents; @endphp
                            <div class="dashboard-stat-value">{{ $v < 10 ? sprintf('%02d', $v) : $v }}</div>
                            <span class="stat-link-hint">View <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            @endif
        </div>
        @endif

        <div class="row g-3 g-lg-4">
            <div class="col-8">
                @php
                $noticeTabKeys = ['office-orders', 'work-allocation', 'notice-circular'];
                $noticeTabLabels = [
                    'office-orders' => 'Office Orders',
                    'work-allocation' => 'Work Allocation',
                    'notice-circular' => 'Notice/ Circular/ Order',
                ];
                $noticeTabCounts = ['office-orders' => 0, 'work-allocation' => 0, 'notice-circular' => 0];
                $resolveDashboardNoticeTab = function ($type) {
                    $t = strtolower((string) ($type ?? ''));
                    if (str_contains($t, 'office order')) {
                        return 'office-orders';
                    }
                    if (str_contains($t, 'course notice')) {
                        return 'work-allocation';
                    }
                    return 'notice-circular';
                };
                foreach ($notices as $noticeForTab) {
                    $tabKey = $resolveDashboardNoticeTab($noticeForTab->notice_type ?? '');
                    $noticeTabCounts[$tabKey]++;
                }
                $defaultNoticeTab = 'office-orders';
                foreach ($noticeTabKeys as $tabKeyCandidate) {
                    if ($noticeTabCounts[$tabKeyCandidate] > 0) {
                        $defaultNoticeTab = $tabKeyCandidate;
                        break;
                    }
                }
                @endphp
                <div class="card dashboard-panel dashboard-feed-panel mb-3" id="dashboard-notices-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 gap-md-3">
                            <h5 class="dashboard-feed-panel__title mb-0">Notices</h5>
                            @if(hasRole('Admin'))
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn btn-sm dashboard-feed-btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                <span>Add New Notice</span>
                            </a>
                            @endif
                        </div>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3 dashboard-list-scroll">
                        @if(count($notices) === 0)
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-file-earmark-x"></i>
                            </span>
                            <p class="mb-3 text-body-secondary">No notices available.</p>
                            @if(hasRole('Admin'))
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn dashboard-feed-btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
                                <span>Add New Notice</span>
                            </a>
                            @endif
                        </div>
                        @else
                        <div class="dashboard-notice-tabs" role="tablist" aria-label="Notice categories">
                            @foreach($noticeTabKeys as $tabKey)
                            <button type="button"
                                class="dashboard-notice-tab {{ $tabKey === $defaultNoticeTab ? 'active' : '' }}{{ $noticeTabCounts[$tabKey] === 0 ? ' dashboard-notice-tab-empty' : '' }}"
                                role="tab" aria-selected="{{ $tabKey === $defaultNoticeTab ? 'true' : 'false' }}"
                                data-notice-tab="{{ $tabKey }}"
                                id="dashboard-notice-tab-{{ $tabKey }}">
                                {{ $noticeTabLabels[$tabKey] }}@if($noticeTabCounts[$tabKey] > 0):
                                {{ $noticeTabCounts[$tabKey] }}@endif
                            </button>
                            @endforeach
                        </div>
                        <p class="dashboard-notice-list-empty d-none mb-0" id="dashboard-notice-tab-empty" role="status">
                            No notices in this category.
                        </p>
                        <ul class="list-unstyled mb-0 ps-0" id="dashboard-notice-list">
                            @foreach($notices as $notice)
                            @php
                            $noticeTab = $resolveDashboardNoticeTab($notice->notice_type ?? '');
                            $noticeDate = $notice->created_at ?? $notice->display_date ?? null;
                            $isNewNotice = $noticeDate && \Carbon\Carbon::parse($noticeDate)->diffInDays(now()) < 7;
                            $displayFrom = !empty($notice->display_date)
                                ? \Carbon\Carbon::parse($notice->display_date)->format('j F, Y')
                                : null;
                            $displayTo = !empty($notice->expiry_date)
                                ? \Carbon\Carbon::parse($notice->expiry_date)->format('j F, Y')
                                : null;
                            if ($displayFrom && $displayTo) {
                                $noticeDateLabel = $displayFrom . ' to ' . $displayTo;
                            } elseif ($displayFrom) {
                                $noticeDateLabel = $displayFrom;
                            } elseif ($noticeDate) {
                                $noticeDateLabel = date('j F, Y', strtotime($noticeDate));
                            } else {
                                $noticeDateLabel = '—';
                            }
                            @endphp
                            <li class="mb-2 {{ $noticeTab !== $defaultNoticeTab ? 'd-none' : '' }}"
                                data-notice-tab-item="{{ $noticeTab }}">
                                <div
                                    class="dashboard-notice-item {{ $isNewNotice ? 'dashboard-notice-item-new' : '' }}">
                                    <span class="notice-icon-wrap" aria-hidden="true"><span
                                            class="material-icons material-symbols-rounded">description</span></span>
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <span class="dashboard-notice-title">{{ $notice->notice_title }}</span>
                                            @if($isNewNotice)
                                            <span
                                                class="badge bg-danger dashboard-notice-new-tag flex-shrink-0">New</span>
                                            @endif
                                        </div>
                                        <small class="dashboard-notice-date">{{ $noticeDateLabel }}</small>
                                        @if($notice->document)
                                        <a href="{{ asset('storage/' . $notice->document) }}" target="_blank"
                                            class="dashboard-notice-attachment text-danger text-decoration-none">
                                            <i class="bi bi-paperclip" aria-hidden="true"></i>View attachment
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        <div class="dashboard-feed-footer">
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'notices']) }}"
                                class="dashboard-feed-see-all">See all</a>
                        </div>
                        @endif
                    </div>
                </div>
                @if(hasRole('Admin') || hasRole('Training-Induction'))
                <div class="card dashboard-panel dashboard-feed-panel mb-4" id="dashboard-notifications-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h5 class="dashboard-feed-panel__title mb-0 d-flex align-items-center gap-2">
                                @if($notificationBadgeCount > 0)
                                <i class="bi bi-bell-fill text-primary dashboard-panel-bell--ring"
                                    aria-hidden="true"></i>
                                @else
                                <i class="bi bi-bell text-primary opacity-75" aria-hidden="true"></i>
                                @endif
                                <span>{{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}</span>
                            </h5>
                            <span class="dashboard-feed-count-badge"
                                aria-label="{{ $notificationBadgeCount }} items">{{ $notificationBadgeCount }}</span>
                        </div>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3 dashboard-list-scroll">
                        @if($notifications->isEmpty())
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-bell-slash"></i>
                            </span>
                            <p class="mb-0 text-body-secondary small">No notifications available.</p>
                        </div>
                        @else
                        <ul class="list-unstyled mb-0 ps-0">
                            @foreach($notifications as $notification)
                            <li class="mb-0">
                                <button type="button"
                                    class="dashboard-notification-item {{ empty($notification->is_read) ? 'dashboard-notification-item-unread' : '' }}"
                                    data-notification-id="{{ $notification->pk }}">
                                    <span
                                        class="notification-icon-wrap dashboard-notification-bell {{ empty($notification->is_read) ? 'dashboard-notification-bell--ring' : '' }}"
                                        aria-hidden="true">
                                        <i class="bi bi-bell-fill"></i>
                                    </span>
                                    <div class="dashboard-notification-body">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <span
                                                class="dashboard-notification-title">{{ $notification->title ?? 'Notification' }}</span>
                                            @if(empty($notification->is_read))
                                            <span class="badge bg-danger dashboard-notification-new-tag">New</span>
                                            @endif
                                        </div>
                                        <span
                                            class="dashboard-notification-time">{{ isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at)->diffForHumans() : '—' }}</span>
                                        <p class="dashboard-notification-message mb-0">
                                            {{ Str::limit(\App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? ''), 120) }}
                                        </p>
                                    </div>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                        <div class="dashboard-feed-footer">
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'notifications']) }}"
                                class="dashboard-feed-see-all">See all</a>
                        </div>
                        @endif
                    </div>
                </div>

                @php
                $campusTweetCount = 3;
                @endphp
                <div class="card dashboard-panel dashboard-feed-panel mb-4" id="dashboard-campus-tweets-panel">
                    <div class="card-header py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h5 class="dashboard-feed-panel__title mb-0">Campus Tweets</h5>
                            <span class="dashboard-feed-count-badge"
                                aria-label="{{ $campusTweetCount }} items">{{ $campusTweetCount }}</span>
                        </div>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3">
                        <div class="dashboard-tweet-item">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> unread notices and total
                                <strong class="text-body">{{ count($notices) }}</strong> notices.</span>
                        </div>
                        <div class="dashboard-tweet-item">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> purchase orders for
                                approval.</span>
                        </div>
                        <div class="dashboard-tweet-item">
                            <span class="small text-body-secondary"><a href="#"
                                    class="link-primary text-decoration-none fw-medium">Click Here</a> for menu of
                                departmental canteen for next 2 weeks.</span>
                        </div>
                        <div class="dashboard-feed-footer">
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'notifications']) }}"
                                class="dashboard-feed-see-all">See all</a>
                        </div>
                    </div>
                </div>
                @endif

                @if(hasRole('Student-OT') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
                <div class="card dashboard-panel dashboard-feed-panel mb-4" id="dashboard-todays-classes-panel">
                    <div class="card-header py-3 px-4">
                        <h5 class="dashboard-feed-panel__title mb-0">Today's Classes</h5>
                        <hr class="dashboard-feed-divider">
                    </div>
                    <div class="card-body pt-0 px-4 pb-3">
                        @if($todayTimetable && $todayTimetable->isNotEmpty())
                        <div class="dashboard-list-scroll pe-1">
                            @foreach($todayTimetable as $entry)
                            <div class="dashboard-class-card">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="dashboard-class-icon" aria-hidden="true">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                    <span class="fw-semibold text-primary">{{ $entry['session_date'] }} ·
                                        {{ $entry['session_time'] }}</span>
                                </div>
                                <div class="dashboard-class-topic">{{ $entry['topic'] }}</div>
                                <div class="dashboard-class-meta">
                                    <span><i class="bi bi-person me-1 opacity-75" aria-hidden="true"></i>Faculty:
                                        {{ $entry['faculty_name'] }}</span>
                                    <span><i class="bi bi-people me-1 opacity-75" aria-hidden="true"></i>Group:
                                        {{ $entry['group_name'] ?? 'N/A' }}</span>
                                    <span><i class="bi bi-geo-alt me-1 opacity-75" aria-hidden="true"></i>Venue:
                                        {{ $entry['session_venue'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="dashboard-feed-empty">
                            <span class="dashboard-feed-empty__icon" aria-hidden="true">
                                <i class="bi bi-calendar-x"></i>
                            </span>
                            <p class="mb-0 text-body-secondary small">No classes scheduled for today.</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>

            <div class="col-4">
                <div class="card dashboard-panel dashboard-birthdays-panel border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="dashboard-birthdays-panel__title mb-0">Today's Birthdays 🎉</h5>
                            <span class="dashboard-birthdays-count {{ $emp_dob_data->count() > 9 ? 'dashboard-birthdays-count--wide' : '' }}"
                                aria-label="{{ $emp_dob_data->count() }} birthdays today">{{ $emp_dob_data->count() }}</span>
                        </div>
                        <hr class="dashboard-birthdays-divider">
                    </div>
                    <div class="card-body dashboard-list-scroll">
                        @if($emp_dob_data->isEmpty())
                        <div class="dashboard-empty-state py-4">
                            <i class="bi bi-gift text-primary opacity-50 fs-1 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0 small text-body-secondary">No birthdays today.</p>
                        </div>
                        @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($emp_dob_data as $employee)
                            @php
                            $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary'];
                            $avClass = $avClasses[$loop->index % count($avClasses)];
                            $photo = !empty($employee->profile_picture) ? asset('storage/' . $employee->profile_picture) : null;
                            $email = trim((string)($employee->email ?? ''));
                            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                            $wishCount = $birthdayWishCounts[$employee->pk] ?? 0;
                            $hasContact = $email !== '' || !empty($employee->mobile) || !empty($employee->office_extension_no);
                            @endphp
                            <article class="dashboard-birthday-item" @if($hasContact) tabindex="0" @endif>
                                <div class="dashboard-birthday-row">
                                    <x-dashboard-birthday-avatar :photo="$photo" :name="$fullName" :color-class="$avClass" />
                                    <div class="dashboard-birthday-info">
                                        <p class="dashboard-birthday-name text-truncate mb-0">{{ $fullName }}</p>
                                        <p class="dashboard-birthday-designation text-truncate mb-0">{{ $employee->designation_name }}</p>
                                        @if($wishCount > 0)
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle dashboard-birthday-badge mt-1"
                                            title="{{ $wishCount }} wishes sent">🎁 {{ $wishCount }}</span>
                                        @endif
                                    </div>
                                    <button type="button"
                                        class="btn btn-sm dashboard-birthday-wish-btn btn-custom-wish"
                                        data-name="{{ $fullName }}"
                                        data-email="{{ $email }}"
                                        data-mobile="{{ $employee->mobile ?? '' }}"
                                        data-pk="{{ $employee->pk }}"
                                        title="Send birthday wish to {{ $fullName }}">Wish them</button>
                                </div>
                                @if($hasContact)
                                <div class="dashboard-birthday-detail">
                                    <div class="d-flex flex-wrap gap-2">
                                        @if(!empty($employee->mobile))
                                        <div class="dashboard-birthday-contact-pill">
                                            <i class="bi bi-telephone" aria-hidden="true"></i>
                                            <span>{{ $employee->mobile }}</span>
                                        </div>
                                        @endif
                                        @if($email !== '')
                                        <div class="dashboard-birthday-contact-pill">
                                            <i class="bi bi-envelope" aria-hidden="true"></i>
                                            <span>{{ $email }}</span>
                                        </div>
                                        @endif
                                        @if(!empty($employee->office_extension_no))
                                        <div class="dashboard-birthday-contact-pill">
                                            <i class="bi bi-telephone-outbound" aria-hidden="true"></i>
                                            <span>Ext {{ $employee->office_extension_no }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </article>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @if($emp_dob_data->isNotEmpty())
                    <div class="card-footer bg-white border-0">
                        <div class="dashboard-birthdays-footer w-100">
                            <span class="visually-hidden">More actions</span>
                            <a href="{{ route('admin.dashboard.feed', ['tab' => 'birthdays']) }}"
                                class="dashboard-birthdays-see-all">See all</a>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="card dashboard-panel dashboard-birthdays-panel--calendar border-0">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <h5 class="dashboard-birthdays-panel__title mb-0">Calendar</h5>
                            <span class="dashboard-calendar-date-badge mb-0">
                                {{ now()->format('d-m-Y') }}
                            </span>
                        </div>
                        <hr class="dashboard-birthdays-divider mb-0">
                    </div>
                    <div class="card-body">
                        <div id="dashboard-calendar-container" aria-live="polite">
                            <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()"
                                :events="$events" theme="gov-red" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Birthday Wish Modal -->
    <div class="modal fade" id="customWishModal" tabindex="-1" aria-labelledby="customWishModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered dashboard-wish-modal-dialog">
            <div class="modal-content dashboard-wish-modal">
                <div class="modal-header dashboard-wish-modal__header">
                    <h5 class="modal-title dashboard-wish-modal__title mb-0" id="customWishModalLabel">
                        Wish on their birthday
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr class="dashboard-wish-modal__divider">
                <div class="modal-body dashboard-wish-modal__body">
                    <input type="hidden" id="wish-recipient-email">
                    <input type="hidden" id="wish-recipient-mobile">
                    <input type="hidden" id="wish-modal-mode" value="birthday">

                    <p class="dashboard-wish-intro mb-0" id="wish-modal-intro-birthday">
                        Wish
                        <input type="text" class="dashboard-wish-name-inline" id="wish-recipient-name" readonly
                            aria-label="Recipient name" size="16">
                        on the occasion of their birthday.
                    </p>
                    <p class="dashboard-wish-intro mb-0 d-none" id="wish-modal-intro-reply">
                        Your reply to
                        <input type="text" class="dashboard-wish-name-inline" id="wish-reply-name-inline" readonly
                            aria-label="Recipient name" size="16">
                        for their birthday wish.
                    </p>

                    <div class="dashboard-wish-options row g-3 mt-3" id="wish-modal-extra">
                        <div class="col-sm-6">
                            <label class="form-label" for="wish-template-select">Message template</label>
                            <select class="form-select" id="wish-template-select">
                                <option value="formal">Formal Birthday Wish</option>
                                <option value="casual">Casual Birthday Wish</option>
                                <option value="professional">Professional Birthday Wish</option>
                                <option value="custom">Write Custom Message</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="wish-subject">
                                Email subject
                                <span class="fw-normal text-body-secondary">(for email)</span>
                            </label>
                            <input type="text" class="form-control" id="wish-subject" value="Happy Birthday!">
                        </div>
                    </div>

                    <div class="mt-4" id="wish-modal-message-wrap">
                        <label class="form-label dashboard-wish-message-label d-block" for="wish-message" id="wish-message-label">Your message</label>
                        <textarea class="form-control dashboard-wish-textarea" id="wish-message" rows="7"
                            placeholder="Write your birthday wish here…"></textarea>
                    </div>

                    <div class="d-flex flex-wrap align-items-center dashboard-wish-channels mt-4 opacity-50 pe-none"
                        id="wish-modal-channels" aria-hidden="true" title="Temporarily unavailable">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="send-via-whatsapp" disabled>
                            <label class="form-check-label text-body-secondary" for="send-via-whatsapp">
                                Via WhatsApp
                            </label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="send-via-email" disabled>
                            <label class="form-check-label text-body-secondary" for="send-via-email">
                                Via Email
                            </label>
                        </div>
                    </div>
                    <p class="small text-body-secondary mb-0 mt-2" id="wish-modal-hint">
                        <i class="bi bi-bell me-1" aria-hidden="true"></i>Send delivers an in-app notification with your message.
                    </p>
                </div>
                <div class="modal-footer dashboard-wish-modal__footer d-flex justify-content-end border-0">
                    <button type="button" class="btn dashboard-wish-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn dashboard-wish-btn-send" id="btn-send-wish">Send</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Birthday wish modal logic
    (function() {
        const templates = {
            formal: function(name) {
                return "Dear " + name +
                    ",\n\nOn the occasion of your birthday, I extend my heartfelt wishes for a wonderful year ahead. May this special day bring you joy, success, and good health.\n\nWarm regards,";
            },
            casual: function(name) {
                return "Hey " + name +
                    "! 🎂🎉\n\nWishing you a fantastic birthday! Hope your day is filled with joy, laughter, and all things wonderful. Have an amazing year ahead!\n\nCheers!";
            },
            professional: function(name) {
                return "Dear " + name +
                    ",\n\nWishing you a very Happy Birthday! May this new year of your life bring you continued success and fulfilment in all your endeavours.\n\nBest wishes,";
            },
            custom: function(name) {
                return "Dear " + name + ",\n\n";
            }
        };

        var replyTemplate = function(name) {
            return "Dear " + name + ",\n\nThank you so much for your lovely birthday wishes! I truly appreciate your thoughtfulness.\n\nWarm regards,";
        };

        var currentRecipient = { mode: 'birthday' };

        function setNameFieldSize(input, name) {
            if (!input) return;
            input.value = name || '';
            input.size = Math.max(4, Math.min(28, (name || '').length + 1));
        }

        function setWishModalMode(mode, name) {
            var isReply = mode === 'reply';
            currentRecipient.mode = mode;
            var modeInput = document.getElementById('wish-modal-mode');
            if (modeInput) modeInput.value = mode;
            document.getElementById('customWishModalLabel').textContent = isReply ? 'Reply to birthday wish' : 'Wish on their birthday';
            var introBirthday = document.getElementById('wish-modal-intro-birthday');
            var introReply = document.getElementById('wish-modal-intro-reply');
            if (introBirthday) introBirthday.classList.toggle('d-none', isReply);
            if (introReply) introReply.classList.toggle('d-none', !isReply);
            var extra = document.getElementById('wish-modal-extra');
            var channels = document.getElementById('wish-modal-channels');
            if (extra) extra.classList.toggle('d-none', isReply);
            if (channels) channels.classList.toggle('d-none', isReply);
            var messageLabel = document.getElementById('wish-message-label');
            var messageField = document.getElementById('wish-message');
            if (messageLabel) messageLabel.textContent = isReply ? 'Your reply' : 'Your message';
            if (messageField) {
                messageField.placeholder = isReply ? 'Write your thank-you reply…' : 'Write your birthday wish here…';
            }
            setNameFieldSize(document.getElementById('wish-recipient-name'), name);
            setNameFieldSize(document.getElementById('wish-reply-name-inline'), name);
        }

        function openWishModal(recipient, mode) {
            currentRecipient = Object.assign({}, recipient, { mode: mode });
            setWishModalMode(mode, currentRecipient.name);
            document.getElementById('wish-recipient-email').value = currentRecipient.email || '';
            document.getElementById('wish-recipient-mobile').value = currentRecipient.mobile || '';
            if (mode === 'reply') {
                document.getElementById('wish-message').value = replyTemplate(currentRecipient.name || '');
                document.getElementById('wish-subject').value = 'Thank you for the birthday wishes!';
            } else {
                document.getElementById('wish-template-select').value = 'formal';
                document.getElementById('wish-subject').value = 'Happy Birthday ' + (currentRecipient.name || '') + '!';
                document.getElementById('wish-message').value = templates.formal(currentRecipient.name || '');
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('customWishModal')).show();
        }

        document.addEventListener('click', function(e) {
            var replyBtn = e.target.closest('.btn-wish-reply');
            if (replyBtn) {
                e.preventDefault();
                e.stopPropagation();
                openWishModal({
                    name: replyBtn.dataset.name || '',
                    email: replyBtn.dataset.email || '',
                    mobile: replyBtn.dataset.mobile || '',
                    employee_pk: replyBtn.dataset.pk || ''
                }, 'reply');
                return;
            }
            var btn = e.target.closest('.btn-custom-wish');
            if (!btn) return;
            openWishModal({
                name: btn.dataset.name || '',
                email: btn.dataset.email || '',
                mobile: btn.dataset.mobile || '',
                employee_pk: btn.dataset.pk || ''
            }, 'birthday');
        });

        var templateSelect = document.getElementById('wish-template-select');
        if (templateSelect) {
            templateSelect.addEventListener('change', function() {
                if (currentRecipient.mode === 'reply') return;
                var name = currentRecipient.name || '';
                var tpl = templates[this.value] || templates.custom;
                document.getElementById('wish-message').value = tpl(name);
            });
        }

        var sendBtn = document.getElementById('btn-send-wish');
        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                var message = document.getElementById('wish-message').value.trim();
                var subject = document.getElementById('wish-subject').value.trim();
                var isReply = currentRecipient.mode === 'reply';

                if (!message) {
                    alert('Please enter a message.');
                    return;
                }
                if (!currentRecipient.employee_pk) {
                    alert('Could not identify the recipient. Please try again.');
                    return;
                }

                var defaultTitle = isReply
                    ? 'Thank you for the birthday wishes!'
                    : ('Happy Birthday ' + (currentRecipient.name || '') + '!');

                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '{{ csrf_token() }}';
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

                fetch('{{ route("admin.birthday-wish.send-notification") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            employee_pks: [parseInt(currentRecipient.employee_pk, 10)],
                            message: message,
                            title: subject || defaultTitle
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('customWishModal')).hide();
                            showToast(data.message || (isReply ? 'Reply sent!' : 'Birthday wish notification sent!'), 'success');
                        } else {
                            alert('Failed to send notification: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(function(err) {
                        alert('Error sending notification: ' + (err.message || 'Unknown error'));
                    })
                    .finally(function() {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = 'Send';
                    });
            });
        }

        function openWhatsApp(mobile, message) {
            var phone = mobile.replace(/[^0-9]/g, '');
            if (phone.length === 10) phone = '91' + phone;
            var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
            window.open(url, '_blank');
        }

        function showToast(msg, type) {
            var toastHtml = '<div class="toast align-items-center text-bg-' + (type || 'primary') +
                ' border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
            var div = document.createElement('div');
            div.innerHTML = toastHtml;
            document.body.appendChild(div);
            setTimeout(function() {
                div.remove();
            }, 4000);
        }
    })();

    window.markAsReadDashboard = function(notificationId, clickedElement) {
        if (clickedElement && clickedElement.dataset.processing === 'true') {
            return;
        }
        if (clickedElement) {
            clickedElement.dataset.processing = 'true';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            '{{ csrf_token() }}';

        fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json().then(data => ({
                ok: response.ok,
                data
            })))
            .then(({
                ok,
                data
            }) => {
                if (!ok) {
                    throw new Error(data.error || 'Failed to mark notification as read');
                }
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                if (data.success) {
                    location.reload();
                    return;
                }
                throw new Error(data.error || 'Unknown error occurred');
            })
            .catch(error => {
                if (clickedElement) {
                    clickedElement.dataset.processing = 'false';
                }
                alert('An error occurred: ' + (error.message || 'Unknown error'));
            });
    };

    window.markAsRead = window.markAsReadDashboard;

    // Use event delegation to avoid inline onclick (also helps JS linters in Blade).
    document.addEventListener('click', function(e) {
        const btn = e.target && e.target.closest ? e.target.closest(
            '.dashboard-notification-item[data-notification-id]') : null;
        if (!btn) return;
        const id = btn.dataset.notificationId;
        if (!id) return;
        window.markAsReadDashboard(id, btn);
    });

    document.addEventListener('click', function(e) {
        const tabBtn = e.target && e.target.closest ? e.target.closest('.dashboard-notice-tab[data-notice-tab]') :
            null;
        if (!tabBtn) return;

        const activeTab = tabBtn.dataset.noticeTab;
        if (!activeTab) return;

        document.querySelectorAll('.dashboard-notice-tab[data-notice-tab]').forEach(function(button) {
            const isActive = button.dataset.noticeTab === activeTab;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        let visibleCount = 0;
        document.querySelectorAll('[data-notice-tab-item]').forEach(function(item) {
            const show = item.dataset.noticeTabItem === activeTab;
            item.classList.toggle('d-none', !show);
            if (show) {
                visibleCount++;
            }
        });

        const emptyState = document.getElementById('dashboard-notice-tab-empty');
        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const liveTimeEl = document.getElementById('dashboard-live-time');
        if (liveTimeEl) {
            const formatLiveTime = function(date) {
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return hours + ':' + minutes;
            };

            const updateLiveTime = function() {
                liveTimeEl.textContent = formatLiveTime(new Date());
            };

            updateLiveTime();
            setInterval(updateLiveTime, 1000);
        }

        const calendarContainer = document.getElementById('dashboard-calendar-container');

        function loadDashboardCalendar(year, month) {
            if (!calendarContainer) return;

            const url = new URL("{{ route('admin.dashboard') }}", window.location.origin);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            url.searchParams.set('calendar_only', '1');

            calendarContainer.style.opacity = '0.6';
            calendarContainer.style.pointerEvents = 'none';

            fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function(response) {
                    return response.json().then(function(data) {
                        return {
                            ok: response.ok,
                            data: data
                        };
                    });
                })
                .then(function(result) {
                    if (!result.ok || !result.data || !result.data.html) {
                        throw new Error('Failed to load calendar');
                    }

                    calendarContainer.innerHTML = result.data.html;
                    const refreshedComponent = calendarContainer.querySelector('.calendar-component');
                    if (refreshedComponent) {
                        bindCalendarComponent(refreshedComponent);
                    }
                })
                .catch(function(error) {
                    console.error(error);
                })
                .finally(function() {
                    calendarContainer.style.opacity = '1';
                    calendarContainer.style.pointerEvents = 'auto';
                });
        }

        function bindCalendarComponent(comp) {
            if (!comp || comp.dataset.bound === 'true') return;
            comp.dataset.bound = 'true';

            const yearSel = comp.querySelector('.calendar-year');
            const monthSel = comp.querySelector('.calendar-month');
            const monthLabel = comp.querySelector('.calendar-month-year-label');
            const cells = comp.querySelectorAll('.calendar-cell:not(.calendar-day-other)');

            function updateMonthLabel() {
                if (!monthLabel || !yearSel || !monthSel) return;
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const monthIndex = parseInt(monthSel.value, 10) - 1;
                monthLabel.textContent = (monthNames[monthIndex] || '') + ' ' + yearSel.value;
            }

            function shiftMonth(delta) {
                if (!yearSel || !monthSel) return;
                let month = parseInt(monthSel.value, 10) + delta;
                let year = parseInt(yearSel.value, 10);
                while (month < 1) {
                    month += 12;
                    year -= 1;
                }
                while (month > 12) {
                    month -= 12;
                    year += 1;
                }
                monthSel.value = String(month);
                yearSel.value = String(year);
                loadDashboardCalendar(year, month);
            }

            function shiftYear(delta) {
                if (!yearSel || !monthSel) return;
                const year = parseInt(yearSel.value, 10) + delta;
                yearSel.value = String(year);
                loadDashboardCalendar(year, monthSel.value);
            }

            comp.addEventListener('click', function(e) {
                if (e.target.closest('.calendar-nav-year-prev')) {
                    e.preventDefault();
                    shiftYear(-1);
                    return;
                }
                if (e.target.closest('.calendar-nav-year-next')) {
                    e.preventDefault();
                    shiftYear(1);
                    return;
                }
                if (e.target.closest('.calendar-nav-month-prev')) {
                    e.preventDefault();
                    shiftMonth(-1);
                    return;
                }
                if (e.target.closest('.calendar-nav-month-next')) {
                    e.preventDefault();
                    shiftMonth(1);
                    return;
                }

                const td = e.target.closest('.calendar-cell:not(.calendar-day-other)');
                if (!td || !td.dataset.date) return;
                const prev = comp.querySelector('.calendar-cell.is-selected');
                if (prev) {
                    prev.classList.remove('is-selected');
                    prev.setAttribute('aria-pressed', 'false');
                }
                td.classList.add('is-selected');
                td.setAttribute('aria-pressed', 'true');
                comp.dispatchEvent(new CustomEvent('dateSelected', {
                    detail: {
                        date: td.dataset.date
                    }
                }));
            });

            cells.forEach(function(cell) {
                cell.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });

                cell.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        cell.click();
                    }
                    const selectable = comp.querySelectorAll('.calendar-cell:not(.calendar-day-other)');
                    const idx = Array.prototype.indexOf.call(selectable, cell);
                    let targetIdx = null;
                    if (ev.key === 'ArrowLeft') targetIdx = idx - 1;
                    if (ev.key === 'ArrowRight') targetIdx = idx + 1;
                    if (ev.key === 'ArrowUp') targetIdx = idx - 7;
                    if (ev.key === 'ArrowDown') targetIdx = idx + 7;
                    if (targetIdx !== null && selectable[targetIdx]) {
                        selectable[targetIdx].focus();
                        ev.preventDefault();
                    }
                });
            });

            if (yearSel && monthSel) {
                yearSel.addEventListener('change', function() {
                    loadDashboardCalendar(this.value, monthSel.value);
                });

                monthSel.addEventListener('change', function() {
                    loadDashboardCalendar(yearSel.value, this.value);
                });
            }

            updateMonthLabel();

            const holidaysToggle = comp.querySelector('.calendar-holidays-toggle');
            const holidaysPanel = comp.querySelector('.calendar-holidays-panel');
            if (holidaysToggle && holidaysPanel) {
                holidaysToggle.addEventListener('click', function() {
                    const isOpen = !holidaysPanel.hidden;
                    holidaysPanel.hidden = isOpen;
                    holidaysToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                    holidaysToggle.textContent = isOpen ? 'Show holidays this month' : 'Hide holidays this month';
                });
            }

            const filterButtons = comp.querySelectorAll('.calendar-holiday-filter');
            const holidayItems = comp.querySelectorAll('.calendar-holiday-list__item');

            function applyHolidayFilter(type) {
                filterButtons.forEach(function(btn) {
                    const active = btn.dataset.filter === type;
                    btn.classList.toggle('active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                holidayItems.forEach(function(item) {
                    item.classList.toggle('is-hidden', item.dataset.holidayType !== type);
                });
            }

            filterButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    applyHolidayFilter(btn.dataset.filter || 'gazetted');
                });
            });

            if (filterButtons.length && holidayItems.length) {
                applyHolidayFilter('gazetted');
            }
        }

        document.querySelectorAll('.calendar-component').forEach(function(comp) {
            bindCalendarComponent(comp);
        });
    });

    // ── View birthday wishes panel (scroll + expand) ──
    (function() {
        var viewLink = document.getElementById('btn-view-birthday-wishes');
        var panel = document.getElementById('dashboard-birthday-wishes-panel');
        var collapseEl = document.getElementById('dashboard-birthday-wishes-collapse');
        var toggleBtn = document.getElementById('btn-toggle-birthday-wishes');
        if (!panel || !collapseEl) return;

        function expandWishesPanel() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                var instance = bootstrap.Collapse.getOrCreateInstance(collapseEl, {
                    toggle: false
                });
                instance.show();
            } else {
                collapseEl.classList.add('show');
            }
            panel.classList.add('is-expanded');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', 'true');
            }
        }

        function scrollToWishesPanel() {
            window.setTimeout(function() {
                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 200);
        }

        if (viewLink && viewLink.getAttribute('href') === '#') {
            viewLink.addEventListener('click', function(e) {
                e.preventDefault();
                expandWishesPanel();
                scrollToWishesPanel();
                if (history.replaceState) {
                    history.replaceState(null, '', '#dashboard-birthday-wishes-panel');
                } else {
                    window.location.hash = 'dashboard-birthday-wishes-panel';
                }
            });
        }

        if (window.location.hash === '#dashboard-birthday-wishes-panel') {
            expandWishesPanel();
            scrollToWishesPanel();
        }

        collapseEl.addEventListener('shown.bs.collapse', function() {
            panel.classList.add('is-expanded');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        });
        collapseEl.addEventListener('hidden.bs.collapse', function() {
            panel.classList.remove('is-expanded');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        });
    })();

    // ── Birthday banner dismiss (session only) ──
    (function() {
        var dismissBtn = document.getElementById('birthday-banner-dismiss');
        var banner = document.getElementById('birthday-banner');
        if (!banner) return;
        if (sessionStorage.getItem('dashboardBirthdayBannerDismissed') === '1') {
            banner.classList.add('is-dismissed');
            return;
        }
        if (!dismissBtn) return;
        dismissBtn.addEventListener('click', function() {
            banner.classList.add('is-dismissed');
            sessionStorage.setItem('dashboardBirthdayBannerDismissed', '1');
        });
    })();

    // ── Confetti Effect for Birthday Banner ──
    (function() {
        var canvas = document.getElementById('confetti-canvas');
        var banner = document.getElementById('birthday-banner');
        if (!canvas || !banner || banner.classList.contains('is-dismissed')) return;
        var ctx = canvas.getContext('2d');
        var W, H, particles = [],
            colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#4caf50',
                '#ffeb3b', '#ff9800', '#ff5722', '#fff'
            ];

        function resize() {
            W = canvas.width = canvas.parentElement.offsetWidth;
            H = canvas.height = canvas.parentElement.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        for (var i = 0; i < 80; i++) {
            particles.push({
                x: Math.random() * W,
                y: Math.random() * H - H,
                r: Math.random() * 5 + 2,
                d: Math.random() * 80,
                color: colors[Math.floor(Math.random() * colors.length)],
                tilt: Math.random() * 10 - 5,
                tiltAngle: 0,
                tiltAngleInc: Math.random() * 0.07 + 0.05
            });
        }

        var animFrame;

        function draw() {
            ctx.clearRect(0, 0, W, H);
            particles.forEach(function(p) {
                ctx.beginPath();
                ctx.lineWidth = p.r;
                ctx.strokeStyle = p.color;
                ctx.moveTo(p.x + p.tilt + p.r / 2, p.y);
                ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r / 2);
                ctx.stroke();
            });
            update();
            animFrame = requestAnimationFrame(draw);
        }

        function update() {
            particles.forEach(function(p) {
                p.tiltAngle += p.tiltAngleInc;
                p.y += (Math.cos(p.d) + 1 + p.r / 2) * 0.6;
                p.x += Math.sin(p.d) * 0.5;
                p.tilt = Math.sin(p.tiltAngle) * 12;
                if (p.y > H) {
                    p.y = -10;
                    p.x = Math.random() * W;
                }
            });
        }

        draw();
        // Stop confetti after 8 seconds
        setTimeout(function() {
            cancelAnimationFrame(animFrame);
            if (ctx) ctx.clearRect(0, 0, W, H);
        }, 8000);
    })();

    // ── Quick Wish All Button ──
    (function() {
        var btn = document.getElementById('btn-quick-wish-all');
        if (!btn) return;

        btn.addEventListener('click', function() {
            if (!confirm('Send birthday wishes (email + notification) to all birthday people today?'))
                return;

            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            '';
            var allCards = document.querySelectorAll('.btn-custom-wish');
            var recipients = [];
            allCards.forEach(function(card) {
                var name = card.dataset.name || '';
                var email = card.dataset.email || '';
                var pk = card.dataset.pk || '';
                if (email && pk) {
                    recipients.push({
                        email: email,
                        name: name,
                        employee_pk: parseInt(pk)
                    });
                }
            });

            if (recipients.length === 0) {
                alert('No recipients with email found.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

            fetch('{{ route("admin.birthday-wish.send-bulk-email") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        recipients: recipients,
                        subject: 'Happy Birthday!',
                        message_template: "Dear {name},\n\nWishing you a very Happy Birthday! May this special day bring you joy, success, and good health.\n\nWarm regards,\n{{ $userName ?? 'Team' }}"
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.success) {
                        var div = document.createElement('div');
                        div.innerHTML =
                            '<div class="toast align-items-center text-bg-success border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                            '<div class="d-flex"><div class="toast-body">🎉 ' + data.message +
                            '</div>' +
                            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
                        document.body.appendChild(div);
                        setTimeout(function() {
                            div.remove();
                        }, 5000);
                    } else {
                        alert('Error: ' + (data.error || 'Unknown'));
                    }
                })
                .catch(function(err) {
                    alert('Error: ' + err.message);
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML =
                        '<i class="bi bi-stars" aria-hidden="true"></i><span class="small">Wish All</span>';
                });
        });
    })();
    </script>
    @endpush
    @endsection