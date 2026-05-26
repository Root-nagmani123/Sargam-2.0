@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
.admin-dashboard-surface {
    background: linear-gradient(160deg, #f0f4f9 0%, #e8eef6 50%, #f5f7fb 100%);
    min-height: 100%;
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

.dashboard-stat-card {
    border: 0 !important;
    border-left: 5px solid var(--bs-border-color) !important;
    border-radius: var(--bs-border-radius-lg, 0.5rem) !important;
    background: #fff !important;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05), 0 1px 3px rgba(16, 24, 40, 0.08) !important;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease !important;
    position: relative !important;
}

.dashboard-stat-card:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(16, 24, 40, 0.1), 0 1px 2px rgba(16, 24, 40, 0.06) !important;
}

.dashboard-stat-card .card-body {
    position: relative;
}

/* Simple tiles: icon + label + value (matches design reference) */
.dashboard-stat-card .stat-card-stack {
    min-height: 8.25rem !important;
}

.dashboard-stat-card .stat-card-stack .dashboard-stat-value {
    margin-top: auto;
    padding-top: 0.25rem;
}

.dashboard-stat-label {
    font-size: 0.9375rem;
    line-height: 1.35;
    font-weight: 700;
    color: #212529;
    letter-spacing: -0.01em;
}

.dashboard-stat-value {
    font-size: clamp(1.85rem, 2.4vw, 2.35rem);
    line-height: 1;
    letter-spacing: -0.035em;
    color: #212529;
    font-variant-numeric: tabular-nums;
}

.dashboard-stat-card.card-blue {
    border-left-color: var(--bs-primary);
}

.dashboard-stat-card.card-green {
    border-left-color: var(--bs-success);
}

.dashboard-stat-card.card-amber {
    border-left-color: var(--bs-warning);
}

.dashboard-stat-card.card-rose {
    border-left-color: var(--bs-danger);
}

.dashboard-stat-card.card-navy {
    border-left-color: #003a75;
}

.dashboard-panel {
    border: 0;
    border-radius: 0.9rem;
    background: var(--bs-body-bg);
    box-shadow: 0 2px 8px rgba(16, 24, 40, 0.07);
}

.dashboard-panel .card-header {
    border-bottom: 1px solid var(--bs-border-color-translucent);
    background: transparent;
    padding-top: 0.9rem !important;
    padding-bottom: 0.9rem !important;
}

/* ── Today's Birthday panel (overrides .dashboard-panel chrome for comp match) ── */
.card.dashboard-panel.dashboard-birthday-panel {
    border: 1px solid #e5e9ef !important;
    border-radius: 0.75rem !important;
    box-shadow: none !important;
    background: #fff !important;
    overflow: hidden;
}

.card.dashboard-panel.dashboard-birthday-panel>.card-header {
    background: #fff !important;
    border-bottom: 1px solid #eef1f6 !important;
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

.card.dashboard-panel.dashboard-birthday-panel>.card-body {
    background: #fff;
}

.dashboard-birthday-panel-title-main {
    font-size: 1.0625rem;
    line-height: 1.35;
    letter-spacing: -0.02em;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.dashboard-birthday-count-badge {
    min-width: 2.25rem;
    height: 2.25rem;
    padding: 0 0.45rem;
    border-radius: 10px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8125rem;
    background-color: #003a75 !important;
    color: #fff !important;
}

.dashboard-birthday-header-actions .btn-icon-bday {
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.dashboard-birthday-item.card {
    border: 1px solid #e8ecf2 !important;
    border-radius: 0.65rem !important;
    background: #fff !important;
    box-shadow: none !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dashboard-birthday-item.card:hover {
    border-color: #c5d4f0 !important;
    box-shadow: 0 2px 10px rgba(13, 110, 253, 0.08);
}

.dashboard-birthday-item .card-body {
    padding: 1rem 1.1rem !important;
}

.dashboard-birthday-row-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.dashboard-birthday-user {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    min-width: 0;
    flex: 1 1 auto;
}

.dashboard-birthday-avatar-wrap {
    flex-shrink: 0;
}

.dashboard-birthday-avatar,
.dashboard-birthday-item .dashboard-avatar {
    width: 3rem;
    height: 3rem;
    font-size: 1rem;
}

/* List default (design 3): blue name; hover (design 2): black name + contact strip */
.dashboard-birthday-name {
    font-weight: 700;
    font-size: 1rem;
    color: #0d6efd;
    line-height: 1.3;
    text-transform: none;
    transition: color 0.2s ease;
}

.dashboard-birthday-item:hover .dashboard-birthday-name,
.dashboard-birthday-item:focus-within .dashboard-birthday-name {
    color: #212529;
}

.dashboard-birthday-designation {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-top: 0.15rem;
    line-height: 1.35;
    text-transform: none;
}

/* Contact strip: hidden by default on fine-pointer devices; shown on hover / focus-within */
.dashboard-birthday-contact-block {
    margin-top: 0;
    padding-top: 0;
    border-top: 0 solid transparent;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: max-height 0.4s ease, opacity 0.25s ease, margin-top 0.25s ease, padding-top 0.25s ease, border-top-width 0.25s ease;
}

@media (hover: hover) and (pointer: fine) {

    .dashboard-birthday-item:hover .dashboard-birthday-contact-block,
    .dashboard-birthday-item:focus-within .dashboard-birthday-contact-block {
        margin-top: 0.875rem;
        padding-top: 0.875rem;
        border-top: 1px solid #eef1f6;
        max-height: 18rem;
        opacity: 1;
        overflow: visible;
    }
}

@media (hover: none),
(pointer: coarse) {
    .dashboard-birthday-contact-block {
        margin-top: 0.875rem;
        padding-top: 0.875rem;
        border-top: 1px solid #eef1f6;
        max-height: none;
        opacity: 1;
        overflow: visible;
    }
}

.dashboard-birthday-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

.dashboard-birthday-contact-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.9rem;
    border-radius: 0.375rem;
    background: #e8f2fc;
    color: #0b5ed7;
    font-size: 0.8125rem;
    font-weight: 500;
    max-width: 100%;
    min-width: 0;
}

.dashboard-birthday-contact-pill .material-icons,
.dashboard-birthday-contact-pill .material-symbols-rounded {
    font-size: 1.05rem !important;
    flex-shrink: 0;
    opacity: 0.95;
}

.dashboard-birthday-contact-pill .text-truncate {
    min-width: 0;
}

.dashboard-birthday-actions-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.dashboard-birthday-wishes-pill {
    font-size: 0.65rem;
    max-width: 100%;
    white-space: normal;
    text-align: center;
    line-height: 1.2;
}

.dashboard-birthday-btn-wish {
    font-weight: 600;
    padding: 0.4rem 0.95rem;
    border-width: 1.5px;
}

.dashboard-birthday-empty {
    padding: 1.35rem 1rem;
    border-radius: 0.85rem;
    background: linear-gradient(160deg, rgba(var(--bs-primary-rgb), 0.04) 0%, rgba(var(--bs-info-rgb), 0.03) 100%);
    border: 1px dashed rgba(var(--bs-primary-rgb), 0.2);
}

.dashboard-birthday-empty .material-icons,
.dashboard-birthday-empty .material-symbols-rounded {
    font-size: 2rem !important;
    opacity: 0.55;
}

/* Upcoming birthdays strip */
.dashboard-upcoming-scroll {
    max-height: 16rem;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.dashboard-upcoming-badge {
    font-size: clamp(0.6rem, 1.5vw, 0.68rem);
    font-weight: 600;
}

.dashboard-upcoming-role {
    font-size: clamp(0.65rem, 1.6vw, 0.72rem);
}

.dashboard-upcoming-countdown {
    font-size: clamp(0.58rem, 1.4vw, 0.65rem);
    margin-top: 0.2rem;
}

.dashboard-upcoming-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.65rem;
    border-radius: 0.75rem;
    background: rgba(var(--bs-warning-rgb), 0.06);
    border: 1px solid rgba(var(--bs-warning-rgb), 0.12);
    flex-wrap: wrap;
}

.dashboard-upcoming-row .dashboard-upcoming-meta {
    flex: 1 1 8rem;
    min-width: 0;
}

.dashboard-upcoming-avatar {
    width: clamp(2rem, 7vw, 2.5rem);
    height: clamp(2rem, 7vw, 2.5rem);
    font-size: clamp(0.7rem, 2vw, 0.8rem);
}

.dashboard-upcoming-date {
    text-align: end;
    flex: 0 1 auto;
    min-width: min(100%, 5.5rem);
}

@media (max-width: 400px) {
    .dashboard-upcoming-date {
        text-align: start;
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
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

.dashboard-welcome {
    background: #fff !important;
    border-radius: 1rem;
    color: var(--bs-body-color);
    padding: 1.25rem 1.5rem !important;
    margin-bottom: 1.5rem;
    box-shadow: var(--bs-box-shadow-sm);
    position: relative;
    overflow: hidden;
}

.dashboard-welcome .dashboard-welcome-title {
    font-size: clamp(1.35rem, 2.5vw, 1.75rem);
    letter-spacing: -0.02em;
}

.dashboard-welcome .dashboard-welcome-time {
    color: #003a75;
}

.dashboard-welcome .dashboard-welcome-time .material-icons {
    font-size: 1.35rem;
    line-height: 1;
    vertical-align: middle;
}

.dashboard-stat-card .stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: var(--bs-border-radius, 0.375rem);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.dashboard-stat-card.card-blue .stat-icon {
    background: rgba(var(--bs-primary-rgb), 0.14);
    color: var(--bs-primary);
}

.dashboard-stat-card.card-green .stat-icon {
    background: rgba(var(--bs-success-rgb), 0.14);
    color: var(--bs-success);
}

.dashboard-stat-card.card-amber .stat-icon {
    background: rgba(var(--bs-warning-rgb), 0.14);
    color: var(--bs-warning);
}

.dashboard-stat-card.card-rose .stat-icon {
    background: rgba(var(--bs-danger-rgb), 0.14);
    color: var(--bs-danger);
}

.dashboard-stat-card.card-navy .stat-icon {
    background: rgba(0, 58, 117, 0.14);
    color: #003a75;
}

.dashboard-stat-card.card-navy .stat-link-hint {
    color: #003a75 !important;
}

/* Hint must not consume layout when hidden (opacity alone still reserves space) */
.dashboard-stat-card .stat-link-hint {
    position: absolute;
    top: 0.9rem;
    right: 0.9rem;
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1;
}

.dashboard-stat-card:hover .stat-link-hint {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
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

.dashboard-tweet-item {
    padding: 12px 14px 12px 16px;
    margin-bottom: 10px;
    border-radius: 10px;
    border-left: 4px solid var(--bs-primary);
    background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.05) 0%, transparent 100%);
    transition: background 0.2s ease, transform 0.15s ease;
}

.dashboard-tweet-item:hover {
    background: linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.08) 0%, transparent 100%);
    transform: translateX(2px);
}

.dashboard-tweet-item:last-child {
    margin-bottom: 0;
}

/* Today's Classes cards */
.dashboard-class-card {
    padding: 14px 16px;
    margin-bottom: 12px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-left: 4px solid var(--bs-primary);
    background: linear-gradient(180deg, #fff 0%, rgba(248, 250, 252, 0.7) 100%);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.dashboard-class-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
}

.dashboard-class-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem !important;
}

.dashboard-class-topic {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-bottom: 8px;
    padding-left: 2.5rem;
}

.dashboard-class-meta {
    font-size: 0.8125rem;
    color: var(--bs-secondary);
    padding-left: 2.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 12px 16px;
}

.dashboard-class-meta span {
    white-space: nowrap;
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

/* Notifications panel - item design */
.dashboard-notification-item {
    display: block;
    width: 100%;
    padding: 1rem 1.15rem;
    border-radius: 0.85rem;
    border: 1px solid #e9ecef;
    border-left: 4px solid #dee2e6;
    background: #fff;
    text-align: left;
    transition: all 0.2s ease;
    cursor: pointer;
}

.dashboard-notification-item:hover {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.dashboard-notification-item-unread {
    border-left-color: #003a75;
}

.dashboard-notification-title {
    font-size: 0.9375rem;
    font-weight: 600;
    line-height: 1.35;
}

.dashboard-notification-message {
    font-size: 0.8125rem;
    color: #6c757d;
    line-height: 1.45;
}

/* Notices panel - item design and blinking "New" tag */
.dashboard-notice-item {
    display: block;
    padding: 16px 18px;
    margin-bottom: 10px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-left: 4px solid transparent;
    background: linear-gradient(180deg, #fff 0%, rgba(248, 250, 252, 0.8) 100%);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.dashboard-notice-item:hover {
    background: linear-gradient(180deg, #fff 0%, rgba(248, 250, 252, 1) 100%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    transform: translateY(-1px);
}

.dashboard-notice-item-new {
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    border-left-color: var(--bs-primary);
    border-color: rgba(var(--bs-primary-rgb), 0.15);
}

.dashboard-notice-item-new:hover {
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.12) 0%, rgba(var(--bs-primary-rgb), 0.04) 100%);
}

.dashboard-notice-item .notice-icon-wrap {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem !important;
}

.dashboard-notice-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--bs-body-color);
    line-height: 1.35;
}

.dashboard-notice-date {
    font-size: 0.8125rem;
    color: var(--bs-secondary);
    margin-top: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.dashboard-notice-date::before {
    content: '';
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--bs-secondary);
    opacity: 0.6;
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

/* Notice dashboard tabs (category filters) */
.dashboard-notice-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
}
.dashboard-notice-tab {
    border: none;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 0.875rem;
    font-weight: 600;
    background: #e9ecef;
    color: #495057;
    transition: background 0.2s ease, color 0.2s ease;
    cursor: pointer;
}
.dashboard-notice-tab:hover {
    background: #dee2e6;
}
.dashboard-notice-tab.active {
    background: #004a93;
    color: #fff;
}
.dashboard-notice-tab-pane { min-height: 1rem; }
.dashboard-notice-card-lite {
    border-left: 4px solid #0d6efd;
    border-radius: 10px;
    background: #f1f3f5;
    padding: 14px 16px;
    margin-bottom: 10px;
}
.dashboard-notice-card-title {
    color: #0d6efd;
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.35;
    margin: 0;
}
.dashboard-notice-card-range {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-top: 6px;
    display: block;
}
.dashboard-notice-card-sub {
    font-size: 0.75rem;
    color: #868e96;
    margin-top: 4px;
}

.dashboard-stat-card:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

table>thead {
    background-color: transparent !important;
}

/* ── Notice card v2 (tabbed design) ── */
.dashboard-notice-tabs {
    gap: 0.5rem !important;
    border-bottom: none;
}

.dashboard-notice-tabs .nav-link {
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 0.45rem 1rem;
    color: #495057;
    background: transparent;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem !important;
    white-space: nowrap;
    transition: all 0.2s ease;
    line-height: 1.4;
}

.dashboard-notice-tabs .nav-link:hover {
    background: #f0f0f0;
}

.dashboard-notice-tabs .nav-link.active {
    background: #003a75;
    color: #fff;
    border-color: #003a75;
}

.dashboard-notice-tabs::-webkit-scrollbar {
    height: 0;
}

.dashboard-notice-card-v2 {
    position: relative;
    padding: 1rem 1.15rem;
    border-radius: 0.85rem;
    background: #fff;
    border: 1px solid #e9ecef;
    border-left: 4px solid #003a75;
    transition: all 0.2s ease;
}

.dashboard-notice-card-v2:hover {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.dashboard-notice-v2-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #003a75;
    line-height: 1.45;
}

.dashboard-notice-v2-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.2rem;
}

.dashboard-notice-see-all {
    font-size: 0.875rem;
    font-weight: 600;
    color: #003a75;
    text-decoration: none;
}

.dashboard-notice-see-all:hover {
    text-decoration: underline;
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
$noticeList = collect($notices)->unique('pk');
$noticeCategoryTabs = $noticeList->isEmpty()
    ? collect()
    : $noticeList->groupBy(function ($n) {
        if (!empty($n->notice_category_master_pk)) {
            return 'c:' . $n->notice_category_master_pk;
        }

        return 'leg:' . md5((string) ($n->notice_type ?? 'other'));
    })->map(function ($items, $tabKey) {
        $first = $items->first();
        $label = $first->category_name ?? $first->notice_type ?? 'Other';

        return [
            'key' => $tabKey,
            'label' => $label,
            'sort' => (int) ($first->category_sort_order ?? 99999),
            'total' => $items->count(),
            'preview' => $items->sortByDesc(function ($row) {
                return $row->display_date ?? '';
            })->take(4)->values(),
        ];
    })->sortBy('sort')->values();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening' ); $userName=$user ? ($user->
    first_name ?? $user->name ?? 'User') : 'User';
    @endphp

    <div class="container-fluid">
        @if($isMyBirthday ?? false)
        {{-- Birthday Banner with Confetti --}}
        <div class="birthday-banner-wrapper mb-3 position-relative" id="birthday-banner">
            <canvas id="confetti-canvas"></canvas>
            <div class="card birthday-banner-card border rounded-3 shadow-sm bg-white overflow-hidden mb-0">
                <div class="d-flex align-items-stretch min-h-0">
                    <div class="birthday-banner-stripe flex-shrink-0" aria-hidden="true"></div>
                    <div class="d-flex flex-grow-1 align-items-center justify-content-between min-w-0 position-relative">
                        <div class="birthday-banner-body flex-grow-1 min-w-0 py-3 py-md-4 ps-3 ps-md-4 pe-2">
                            <h5 class="birthday-banner-heading fw-bold text-dark mb-2">Happy Birthday
                                {{ $userName }}</h5>
                            <p class="mb-0 birthday-banner-sub text-body lh-base">
                                Wishing you a fantastic year ahead 🎉
                                @if(($myBirthdayWishCount ?? 0) > 0)
                                <span id="birthday-wishers-summary" class="birthday-wishers-summary"
                                    data-wish-count="{{ $myBirthdayWishCount }}">{{ $myBirthdayWishCount }}
                                    {{ $myBirthdayWishCount === 1 ? 'person has' : 'people have' }} sent their
                                    wishes.</span>
                                <button type="button"
                                    class="birthday-banner-wishes-btn btn btn-link text-decoration-underline p-0 border-0 align-baseline"
                                    data-bs-toggle="modal" data-bs-target="#birthdayWishesReceivedModal"
                                    title="See who wished you and send a reply">View all wishes →</button>
                                @endif
                            </p>
                        </div>
                        <div class="birthday-banner-graphic d-flex flex-shrink-0 align-self-stretch d-none d-sm-flex position-relative"
                            aria-hidden="true">
                            <svg class="birthday-banner-svg" viewBox="0 0 160 120" fill="none"
                                xmlns="http://www.w3.org/2000/svg" role="img" aria-label="">
                                <ellipse cx="118" cy="108" rx="38" ry="8" fill="#000" fill-opacity="0.06" />
                                <rect x="88" y="82" width="22" height="18" rx="2" fill="#c9a227" />
                                <rect x="88" y="82" width="22" height="6" fill="#e8c84a" />
                                <rect x="97" y="76" width="4" height="12" rx="1" fill="#b8860b" />
                                <rect x="112" y="88" width="18" height="14" rx="2" fill="#c9a227" />
                                <rect x="112" y="88" width="18" height="5" fill="#e8c84a" />
                                <rect x="119" y="83" width="4" height="10" rx="1" fill="#b8860b" />
                                <rect x="68" y="90" width="16" height="12" rx="2" fill="#c9a227" />
                                <rect x="68" y="90" width="16" height="4" fill="#e8c84a" />
                                <path d="M76 84v8" stroke="#b8860b" stroke-width="2" stroke-linecap="round" />
                                <line x1="102" y1="58" x2="102" y2="76" stroke="#888" stroke-width="1.2" />
                                <ellipse cx="102" cy="50" rx="11" ry="14" fill="#e53935" />
                                <ellipse cx="102" cy="50" rx="7" ry="9" fill="#ff6659" opacity="0.45" />
                                <line x1="78" y1="62" x2="78" y2="84" stroke="#888" stroke-width="1.2" />
                                <ellipse cx="78" cy="54" rx="10" ry="13" fill="#1e88e5" />
                                <ellipse cx="78" cy="54" rx="6" ry="8" fill="#64b5f6" opacity="0.45" />
                                <line x1="128" y1="55" x2="128" y2="82" stroke="#888" stroke-width="1.2" />
                                <ellipse cx="128" cy="47" rx="10" ry="13" fill="#43a047" />
                                <ellipse cx="128" cy="47" rx="6" ry="8" fill="#81c784" opacity="0.45" />
                                <line x1="58" y1="68" x2="58" y2="88" stroke="#888" stroke-width="1.2" />
                                <ellipse cx="58" cy="60" rx="9" ry="12" fill="#fdd835" />
                                <ellipse cx="58" cy="60" rx="5" ry="7" fill="#fff59d" opacity="0.5" />
                                <line x1="140" y1="64" x2="140" y2="86" stroke="#888" stroke-width="1.2" />
                                <ellipse cx="140" cy="56" rx="8" ry="11" fill="#8e24aa" />
                                <ellipse cx="140" cy="56" rx="5" ry="7" fill="#ce93d8" opacity="0.45" />
                                <line x1="112" y1="42" x2="112" y2="58" stroke="#888" stroke-width="1.2" />
                                <ellipse cx="112" cy="34" rx="8" ry="10" fill="#fafafa" stroke="#e0e0e0"
                                    stroke-width="1" />
                            </svg>
                            <button type="button"
                                class="birthday-banner-dismiss btn p-0 border-0 position-absolute top-0 end-0"
                                onclick="var b=document.getElementById('birthday-banner'); if(b) b.classList.add('d-none');"
                                aria-label="Dismiss birthday message" title="Dismiss">
                                <span class="birthday-banner-dismiss-icon" aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <button type="button"
                            class="birthday-banner-dismiss birthday-banner-dismiss--mobile btn p-0 border-0 position-absolute top-0 end-0 d-sm-none"
                            onclick="var b=document.getElementById('birthday-banner'); if(b) b.classList.add('d-none');"
                            aria-label="Dismiss birthday message" title="Dismiss">
                            <span class="birthday-banner-dismiss-icon" aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <style>
        .birthday-banner-wrapper {
            position: relative;
        }

        #confetti-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .birthday-banner-card {
            position: relative;
            z-index: 2;
            border-color: #e3e6ea !important;
        }

        .birthday-banner-stripe {
            width: 0.45rem;
            background: #1a2b5f;
        }

        .birthday-banner-heading {
            font-size: 1.125rem;
            line-height: 1.35;
        }

        .birthday-banner-sub {
            font-size: 0.875rem;
        }

        .birthday-wishers-summary::before,
        .birthday-banner-wishes-btn::before {
            content: ' ';
        }

        .birthday-banner-wishes-btn {
            color: #1a2b5f !important;
            font-size: inherit;
            font-weight: 400;
            vertical-align: baseline;
        }

        .birthday-banner-wishes-btn:hover {
            color: #0f1a3d !important;
        }

        .birthday-banner-wishes-btn:focus-visible {
            outline: 2px solid #1a2b5f;
            outline-offset: 2px;
            border-radius: 0.125rem;
        }

        .birthday-banner-graphic {
            width: clamp(7.5rem, 18vw, 10.5rem);
            padding: 0.35rem 0.75rem 0.35rem 0;
            align-items: flex-end;
            justify-content: flex-end;
        }

        .birthday-banner-svg {
            width: 100%;
            height: auto;
            max-height: 6.5rem;
            display: block;
        }

        .birthday-banner-dismiss-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.65rem;
            height: 1.65rem;
            background: #eceff1;
            color: #e53935;
            font-size: 1.15rem;
            line-height: 1;
            font-weight: 400;
            border-radius: 0.2rem;
        }

        .birthday-banner-dismiss:hover .birthday-banner-dismiss-icon {
            background: #e2e6ea;
        }

        .birthday-banner-dismiss--mobile {
            z-index: 3;
            margin: 0.4rem;
        }

        @media (min-width: 576px) {
            .birthday-banner-heading {
                font-size: 1.25rem;
            }

            .birthday-banner-sub {
                font-size: 0.9375rem;
            }

            .birthday-banner-graphic .birthday-banner-dismiss {
                margin: 0.35rem 0.15rem 0 0;
            }
        }
        </style>
        @if(($myBirthdayWishCount ?? 0) > 0)
        <script>
        (function () {
            var el = document.getElementById('birthday-wishers-summary');
            if (!el) return;
            var url = @json(route('admin.birthday-wish.my-wishes-today'));

            function buildWishersText(wishes) {
                var seen = {};
                var names = [];
                (wishes || []).forEach(function (w) {
                    var sid = w.sender_user_id != null ? String(w.sender_user_id) : '';
                    var key = sid !== '' ? sid : ('wish:' + String(w.pk || names.length));
                    if (seen[key]) return;
                    seen[key] = true;
                    names.push((w.sender_name || '').trim() || 'Colleague');
                });
                var total = names.length;
                if (total === 0) return { text: '', count: 0 };
                var text = '';
                if (total === 1) text = names[0] + ' has sent their wish.';
                else if (total === 2) text = names[0] + ' and ' + names[1] + ' have sent their wishes.';
                else {
                    var others = total - 2;
                    text = names[0] + ', ' + names[1] + ' and ' + others + ' other' + (others === 1 ? '' : 's') + ' have sent their wishes.';
                }
                return { text: text, count: total };
            }

            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.success || !data.wishes || !data.wishes.length) return;
                    var summary = buildWishersText(data.wishes);
                    if (!summary.text) return;
                    el.textContent = summary.text;
                    el.setAttribute('data-wish-count', String(summary.count));
                    document.querySelectorAll('.dashboard-birthday-wishes-pill').forEach(function (badge) {
                        badge.textContent = '🎁 ' + summary.count + (summary.count === 1 ? ' wish' : ' wishes');
                        badge.setAttribute('title', summary.count + ' wishes received');
                    });
                })
                .catch(function () {});
        })();
        </script>
        @include('admin.birthday-wish.partials.received_wishes_modal')
        @endif
        @endif

        <div
            class="dashboard-welcome bg-white shadow-sm p-4 p-lg-5 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="flex-grow-1">
                <p class="small text-body mb-1">{{ $greeting }}, <span
                        class="text-primary fw-medium">{{ $userName }}</span></p>
                <h2 class="dashboard-welcome-title fw-bold text-dark mb-1">Dashboard</h2>
                <p class="small text-muted mb-0">{{ now()->format('l, d F Y') }}</p>
            </div>
            <div class="dashboard-welcome-time d-none d-sm-flex align-items-center gap-2 flex-shrink-0">
                <span class="material-icons material-symbols-rounded align-middle" aria-hidden="true">schedule</span>
                <span class="fw-semibold lh-1" id="dashboard-live-time">{{ now()->format('h:i A') }}</span>
            </div>
        </div>

        @if(hasRole('Security Card') || hasRole('Admin Security'))
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                @php
                $idCardApprovalRoute = hasRole('Admin Security')
                ? route('admin.security.employee_idcard_approval.approval3')
                : route('admin.security.employee_idcard_approval.approval2');
                @endphp
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">badge</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Pending Permanent ID Requests</p>
                            <p class="small text-muted mb-0">Today</p>
                            <div class="dashboard-stat-value fw-bold pt-1">
                                {{ $todayPendingPermanentIdCardRequests ?? 0 }}</div>
                            <p class="small text-muted mb-0">Total pending:
                                {{ $fullPendingPermanentIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint text-primary small">Go to approvals <span
                                    class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">badge</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Pending Contractual ID Requests</p>
                            <p class="small text-muted mb-0">Today</p>
                            <div class="dashboard-stat-value fw-bold pt-1">
                                {{ $todayPendingContractualIdCardRequests ?? 0 }}</div>
                            <p class="small text-muted mb-0">Total pending:
                                {{ $fullPendingContractualIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint text-primary small">Go to approvals <span
                                    class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">copy_all</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Duplicate Permanent ID Requests</p>
                            <p class="small text-muted mb-0">Today</p>
                            <div class="dashboard-stat-value fw-bold pt-1">{{ $todayDuplicatePermIdCardRequests ?? 0 }}
                            </div>
                            <p class="small text-muted mb-0">Total pending: {{ $fullDuplicatePermIdCardRequests ?? 0 }}
                            </p>
                            <span class="stat-link-hint text-warning small">Go to approvals <span
                                    class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="{{ $idCardApprovalRoute }}" class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">content_copy</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Duplicate Contractual ID Requests</p>
                            <p class="small text-muted mb-0">Today</p>
                            <div class="dashboard-stat-value fw-bold pt-1">
                                {{ $todayDuplicateContractualIdCardRequests ?? 0 }}</div>
                            <p class="small text-muted mb-0">Total pending:
                                {{ $fullDuplicateContractualIdCardRequests ?? 0 }}</p>
                            <span class="stat-link-hint text-warning small">Go to approvals <span
                                    class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-4 col-md-6">
                <a href="{{ route('admin.security.family_idcard_approval.index') }}"
                    class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">diversity_3</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Requested Family ID</p>
                            <p class="small text-muted mb-0">Today</p>
                            <div class="dashboard-stat-value fw-bold pt-1">{{ $todayFamilyApprovals ?? 0 }}</div>
                            <p class="small text-muted mb-0">Total pending: {{ $fullFamilyApprovals ?? 0 }}</p>
                            <span class="stat-link-hint text-primary small">Go to approvals <span
                                    class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-4 col-md-6">
                <a href="{{ route('admin.security.vehicle_pass_approval.index') }}"
                    class="text-decoration-none d-block h-100">
                    <div class="card dashboard-stat-card border-0 card-green h-100">
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">directions_car</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Requested Vehicle Pass</p>
                            <p class="small text-muted mb-0">Today</p>
                            <div class="dashboard-stat-value fw-bold pt-1">{{ $todayVehicleApprovals ?? 0 }}</div>
                            <p class="small text-muted mb-0">Total pending: {{ $fullVehicleApprovals ?? 0 }}</p>
                            <span class="stat-link-hint text-success small">Go to approvals <span
                                    class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        @endif
        @if(!hasRole('Security Card') && !hasRole('Admin Security'))
        @php
        $dashboardStatFigure = function ($value): string {
        $n = (int) $value;
        $s = (string) $n;
        return strlen($s) >= 3 ? $s : str_pad($s, 2, '0', STR_PAD_LEFT);
        };
        @endphp
        <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5">
            <div class="col">
                <a href="{{ route('admin.dashboard.active_course') }}" class="text-decoration-none d-block h-100"
                    aria-label="Total Active Courses — open list">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">library_books</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Total Active Courses</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($totalActiveCourses) }}
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('admin.dashboard.incoming_course') }}" class="text-decoration-none d-block h-100"
                    aria-label="Upcoming Courses — open list">
                    <div class="card dashboard-stat-card border-0 card-green h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">event</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Upcoming Courses</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($upcomingCourses) }}</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('admin.dashboard.upcoming_events') }}" class="text-decoration-none d-block h-100"
                    aria-label="Upcoming Events — open list">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">campaign</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Upcoming Events</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure(2) }}</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                @if(hasRole('Student-OT'))
                <a href="{{ route('medical.exception.ot.view') }}" class="text-decoration-none d-block h-100"
                    aria-label="Medical Exception — open list">
                    <div class="card dashboard-stat-card border-0 card-rose h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">monitor_heart</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Medical Exception</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($exemptionCount) }}</div>
                        </div>
                    </div>
                </a>
                @else
                <a href="{{ route('admin.dashboard.guest_faculty') }}" class="text-decoration-none d-block h-100"
                    aria-label="Total Guest Faculty — open list">
                    <div class="card dashboard-stat-card border-0 card-rose h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">badge</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Total Guest Faculty</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($total_guest_faculty) }}
                            </div>
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
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">badge</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Today's Pending ID Card Requests (Approval I)
                            </p>
                            <div class="dashboard-stat-value fw-bold pt-1">{{ $todayApproval1IdCardRequests }}</div>
                            <span class="stat-link-hint text-primary small">Go to approvals
                                <span class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span>
                            </span>
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
                        <div class="card-body d-flex flex-column gap-1 p-4 pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">content_copy</span></span>
                            <p class="small fw-bold text-body lh-sm mb-0">Today's Pending Duplicate ID Card Requests
                                (Approval I)</p>
                            <div class="dashboard-stat-value fw-bold pt-1">{{ $todayApproval1DuplicateIdCardRequests }}
                            </div>
                            <span class="stat-link-hint text-warning small">Go to approvals
                                <span class="material-icons material-symbols-rounded align-middle"
                                    style="font-size: 1rem;">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <div class="col">
                @if(hasRole('Student-OT'))
                <a href="{{ route('ot.mdo.escrot.exemption.view') }}" class="text-decoration-none d-block h-100"
                    aria-label="OT MDO/Escort — open list">
                    <div class="card dashboard-stat-card border-0 card-blue h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack pe-5">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">manage_accounts</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">OT MDO/Escort</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($MDO_count) }}</div>
                            <span class="stat-link-hint text-primary small">View <span
                                    class="material-icons material-symbols-rounded align-middle fs-6">arrow_forward</span></span>
                        </div>
                    </div>
                </a>
                @else
                <a href="{{ route('admin.dashboard.inhouse_faculty') }}" class="text-decoration-none d-block h-100"
                    aria-label="Total Inhouse Faculty — open list">
                    <div class="card dashboard-stat-card border-0 card-navy h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">groups</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Total Inhouse Faculty</p>
                            <div class="dashboard-stat-value fw-bold">
                                {{ $dashboardStatFigure($total_internal_faculty) }}</div>
                        </div>
                    </div>
                </a>
                @endif
            </div>

            @if(hasRole('Internal Faculty') || hasRole('Guest Faculty'))
            <div class="col">
                <a href="{{ route('admin.dashboard.sessions') }}" class="text-decoration-none d-block h-100"
                    aria-label="Session details — open list">
                    <div class="card dashboard-stat-card border-0 card-green h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">history</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Session Details</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($totalSessions) }}</div>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            @if(isset($isCCorACC) && $isCCorACC)
            <div class="col">
                <a href="{{ route('admin.dashboard.students') }}" class="text-decoration-none d-block h-100"
                    aria-label="Total Students — open list">
                    <div class="card dashboard-stat-card border-0 card-amber h-100">
                        <div class="card-body d-flex flex-column h-100 p-4 stat-card-stack">
                            <span class="stat-icon align-self-start" aria-hidden="true"><span
                                    class="material-icons material-symbols-rounded">contacts</span></span>
                            <p class="dashboard-stat-label mb-0 mt-3">Total Students</p>
                            <div class="dashboard-stat-value fw-bold">{{ $dashboardStatFigure($totalStudents) }}</div>
                        </div>
                    </div>
                </a>
            </div>
            @endif
        </div>
        @endif

        <div class="row g-3 g-lg-4">
            <div class="col-lg-7">
                @php
                $noticeTypes = [];
                foreach($notices as $n) {
                $type = $n->notice_type ?? 'General';
                $noticeTypes[$type][] = $n;
                }
                $noticeTabKeys = array_keys($noticeTypes);
                @endphp
                <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                    <div class="card-body p-3 p-md-4">
                        {{-- Header --}}
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h5 class="mb-0 fw-bold text-dark">Notices</h5>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <a href="{{ route('admin.notice.create') }}"
                                class="btn btn-sm rounded-1 d-inline-flex align-items-center gap-1 text-white"
                                style="background:#003a75;">
                                <span class="material-icons material-symbols-rounded" style="font-size:16px;">add</span>
                                Add New Notice
                            </a>
                            @endif
                        </div>

                        {{-- Tabs --}}
                        @if(count($noticeTabKeys) > 0)
                        <ul class="nav dashboard-notice-tabs mb-3 flex-nowrap overflow-auto" id="noticeTypeTabs"
                            role="tablist">
                            @foreach($noticeTabKeys as $idx => $tabKey)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $idx === 0 ? 'active' : '' }}" id="notice-tab-{{ $idx }}"
                                    data-bs-toggle="pill" data-bs-target="#notice-pane-{{ $idx }}" type="button"
                                    role="tab" aria-controls="notice-pane-{{ $idx }}"
                                    aria-selected="{{ $idx === 0 ? 'true' : 'false' }}">
                                    {{ $tabKey }}: {{ count($noticeTypes[$tabKey]) }}
                                </button>
                            </li>
                            @endforeach
                        </ul>
                        @endif

                        {{-- Content --}}
                        <div class="dashboard-list-scroll">
                            @if(count($notices) === 0)
                            <div class="dashboard-empty-state">
                                <span class="material-icons material-symbols-rounded">description</span>
                                <p class="mb-0 small">No notices available.</p>
                            </div>
                            @else
                            <div class="tab-content" id="noticeTypeTabContent">
                                @foreach($noticeTabKeys as $idx => $tabKey)
                                <div class="tab-pane fade {{ $idx === 0 ? 'show active' : '' }}"
                                    id="notice-pane-{{ $idx }}" role="tabpanel" aria-labelledby="notice-tab-{{ $idx }}">
                                    <div class="d-grid gap-2">
                                        @foreach($noticeTypes[$tabKey] as $notice)
                                        @php
                                        $noticeDate = $notice->created_at ?? $notice->display_date ?? null;
                                        $noticeDateEnd = $notice->expiry_date ?? null;
                                        $isNewNotice = $noticeDate &&
                                        \Carbon\Carbon::parse($noticeDate)->diffInDays(now()) < 7; @endphp <div
                                            class="dashboard-notice-card-v2">
                                            <div class="dashboard-notice-v2-title">{{ $notice->notice_title }}</div>
                                            <div class="dashboard-notice-v2-date">
                                                {{ $noticeDate ? date('d F, Y', strtotime($noticeDate)) : '—' }}@if($noticeDateEnd)
                                                to {{ date('d F, Y', strtotime($noticeDateEnd)) }}@endif
                                            </div>
                                            @if($notice->document)
                                            <a href="{{ asset('storage/' . $notice->document) }}" target="_blank"
                                                class="dashboard-notice-attachment text-danger text-decoration-none mt-1">
                                                <span class="material-icons material-symbols-rounded"
                                                    style="font-size: 1rem;">attach_file</span>View attachment
                                            </a>
                                            @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- See all --}}
                    @if(count($notices) > 0)
                    <div class="text-end mt-3">
                        <a href="{{ route('admin.communications.hub', ['section' => 'notices']) }}" class="dashboard-notice-see-all">See all</a>
                    </div>
                    @endif
                </div>
            </div>
            @if(hasRole('Admin') || hasRole('Training-Induction'))
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-3 p-md-4">
                    {{-- Header --}}
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="mb-0 fw-bold text-dark">{{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}</h5>
                        <span class="badge text-bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:1.75rem;height:1.75rem;font-size:0.75rem;">{{ $notificationBadgeCount }}</span>
                    </div>

                    {{-- List --}}
                    <div class="dashboard-list-scroll">
                    @if($notifications->isEmpty())
                    <div class="dashboard-empty-state">
                        <span class="material-icons material-symbols-rounded">notifications_off</span>
                        <p class="mb-0 small">No notifications available.</p>
                    </div>
                    @else
                    <div class="d-grid gap-2">
                        @foreach($notifications as $notification)
                        <button type="button"
                            class="dashboard-notification-item {{ empty($notification->is_read) ? 'dashboard-notification-item-unread' : '' }}"
                            data-notification-id="{{ $notification->pk }}">
                            <div class="flex-grow-1 min-w-0">
                                <div class="dashboard-notification-title text-danger">{{ $notification->title ?? 'Notification' }}</div>
                                <div class="text-muted" style="font-size:0.8rem;">{{ isset($notification->created_at) ? \Carbon\Carbon::parse($notification->created_at)->diffForHumans() : '—' }}</div>
                                <p class="dashboard-notification-message mb-0 mt-1">
                                    {{ Str::limit(\App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? ''), 120) }}
                                </p>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endif
                    </div>

                    {{-- See all --}}
                    @if($notifications->isNotEmpty())
                    <div class="text-end mt-3">
                        <a href="{{ route('admin.communications.hub', ['section' => 'notifications']) }}" class="dashboard-notice-see-all">See all</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-3 p-md-4">
                    {{-- Header --}}
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="mb-0 fw-bold text-dark">Campus Tweets</h5>
                        <span class="badge text-bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:1.75rem;height:1.75rem;font-size:0.75rem;">{{ $notifications->count() }}</span>
                    </div>

                    <div class="d-grid gap-2">
                        <div class="dashboard-notice-card-v2">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> unread notices and total
                                <strong class="text-body">{{ count($notices) }}</strong> notices.</span>
                        </div>
                        <div class="dashboard-notice-card-v2">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> purchase orders</span>
                        </div>
                        <div class="dashboard-notice-card-v2">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> unread notices and total
                                <strong class="text-body">{{ count($notices) }}</strong> notices.</span>
                        </div>
                        <div class="dashboard-notice-card-v2">
                            <span class="small text-body-secondary">You have <strong
                                    class="text-body">{{ $notifications->count() }}</strong> purchase orders</span>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <a href="{{ route('admin.communications.hub', ['section' => 'notifications']) }}" class="dashboard-notice-see-all">See all</a>
                    </div>
                </div>
            </div>
            @endif

            @if(hasRole('Student-OT') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
            <div class="card dashboard-panel shadow-sm rounded-4 mb-4">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-1">
                    <span class="material-icons material-symbols-rounded text-primary">fact_check</span>
                    <h5 class="mb-0 fw-semibold">Today's Classes</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    @if($todayTimetable && $todayTimetable->isNotEmpty())
                    <div class="dashboard-list-scroll pe-2">
                        @foreach($todayTimetable as $entry)
                        <div class="dashboard-class-card">
                            <div class="d-flex align-items-center gap-1 mb-2">
                                <span class="dashboard-class-icon"><span
                                        class="material-icons material-symbols-rounded">schedule</span></span>
                                <span class="text-primary fw-semibold">{{ $entry['session_date'] }} ·
                                    {{ $entry['session_time'] }}</span>
                            </div>
                            <div class="dashboard-class-topic">{{ $entry['topic'] }}</div>
                            <div class="dashboard-class-meta">
                                <span>Faculty: {{ $entry['faculty_name'] }}</span>
                                <span>Group: {{ $entry['group_name'] ?? 'N/A' }}</span>
                                <span>Venue: {{ $entry['session_venue'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="dashboard-empty-state">
                        <span class="material-icons material-symbols-rounded">event_busy</span>
                        <p class="mb-0 small">No classes scheduled for today.</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        <div class="col-lg-5">
            <div class="card dashboard-panel dashboard-birthday-panel mb-4">
                <div class="card-header py-3 px-3 px-md-4">
                    <div
                        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-3">
                        <h4 class="dashboard-birthday-panel-title-main mb-0 fw-bold text-dark">
                            <span>Today's Birthdays</span><span class="lh-1" aria-hidden="true">🎉</span>
                        </h4>
                        <div
                            class="d-flex align-items-center gap-1 dashboard-birthday-header-actions flex-wrap w-100 w-sm-auto justify-content-between justify-content-sm-end">
                            <span class="badge dashboard-birthday-count-badge flex-shrink-0"
                                title="Birthdays today">{{ $emp_dob_data->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4 dashboard-list-scroll">
                    @if($emp_dob_data->isEmpty())
                    <div class="dashboard-empty-state dashboard-birthday-empty">
                        <span class="material-icons material-symbols-rounded" aria-hidden="true">card_giftcard</span>
                        <p class="mb-0 small">No birthdays today.</p>
                    </div>
                    @else
                    <div class="d-grid gap-2 gap-md-3">
                        @foreach($emp_dob_data as $employee)
                        @php
                        $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning',
                        'text-bg-danger', 'text-bg-secondary'];
                        $avClass = $avClasses[$loop->index % count($avClasses)];
                        $photo = !empty($employee->profile_picture) ? asset('storage/' . $employee->profile_picture) :
                        null;
                        $email = trim((string)($employee->email ?? ''));
                        $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                        @endphp
                        <div class="card dashboard-birthday-item mb-0">
                            <div class="card-body">
                                <div class="dashboard-birthday-row-main">
                                    <div class="dashboard-birthday-user">
                                        <div class="dashboard-birthday-avatar-wrap position-relative">
                                            @if($photo)
                                            <img src="{{ $photo }}" alt=""
                                                class="rounded-circle object-fit-cover dashboard-birthday-avatar dashboard-avatar"
                                                width="48" height="48" loading="lazy"
                                                onerror="this.classList.add('d-none'); var f=this.nextElementSibling; if(f){ f.classList.remove('d-none'); f.classList.add('d-inline-flex'); }">
                                            <div class="rounded-circle {{ $avClass }} fw-semibold d-none align-items-center justify-content-center dashboard-birthday-avatar dashboard-avatar dashboard-birthday-avatar-fallback"
                                                aria-hidden="true">
                                                {{ strtoupper(substr((string)($employee->first_name ?? ''), 0, 1)) }}
                                            </div>
                                            @else
                                            <div
                                                class="rounded-circle {{ $avClass }} fw-semibold d-inline-flex align-items-center justify-content-center dashboard-birthday-avatar dashboard-avatar">
                                                {{ strtoupper(substr((string)($employee->first_name ?? ''), 0, 1)) }}
                                            </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="dashboard-birthday-name text-truncate" title="{{ $fullName }}">
                                                {{ $fullName }}</div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                                <div class="dashboard-birthday-designation text-truncate mb-0"
                                                    title="{{ $employee->designation_name }}">
                                                    {{ $employee->designation_name }}</div>
                                                @if((int) ($user->user_id ?? 0) === (int) ($employee->pk ?? 0) &&
                                                ($myBirthdayWishCount ?? 0) > 0)
                                                <span
                                                    class="badge rounded-1 bg-success-subtle text-success border border-success-subtle dashboard-birthday-wishes-pill"
                                                    title="{{ $myBirthdayWishCount }} wishes received">
                                                    🎁 {{ $myBirthdayWishCount }}
                                                    {{ $myBirthdayWishCount === 1 ? 'wish' : 'wishes' }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary rounded-1 flex-shrink-0 btn-custom-wish dashboard-birthday-btn-wish"
                                        data-name="{{ $fullName }}" data-email="{{ $email }}"
                                        data-mobile="{{ $employee->mobile ?? '' }}" data-pk="{{ $employee->pk }}"
                                        title="Send a birthday wish">
                                        Wish them
                                    </button>
                                </div>
                                @if($email !== '' || !empty($employee->mobile) ||
                                !empty($employee->office_extension_no))
                                <div class="dashboard-birthday-contact-block">
                                    <div class="dashboard-birthday-contact-row">
                                        @if(!empty($employee->mobile))
                                        <span class="dashboard-birthday-contact-pill" title="{{ $employee->mobile }}">
                                            <span class="material-icons material-symbols-rounded"
                                                aria-hidden="true">call</span>
                                            <span class="text-truncate">{{ $employee->mobile }}</span>
                                        </span>
                                        @endif
                                        @if($email !== '')
                                        <span class="dashboard-birthday-contact-pill" title="{{ $email }}">
                                            <span class="material-icons material-symbols-rounded"
                                                aria-hidden="true">mail</span>
                                            <span class="text-truncate">{{ $email }}</span>
                                        </span>
                                        @endif
                                        @if(!empty($employee->office_extension_no))
                                        <span class="dashboard-birthday-contact-pill" title="Office extension">
                                            <span class="material-icons material-symbols-rounded"
                                                aria-hidden="true">local_phone</span>
                                            <span class="text-truncate">Ext {{ $employee->office_extension_no }}</span>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="text-end pt-3 mt-3 border-top">
                        <a href="{{ route('admin.communications.hub', ['section' => 'birthdays']) }}"
                            class="link-primary fw-semibold small text-decoration-none">See all</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card dashboard-panel border shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4 rounded-0">
                    <h5 class="mb-0 fw-bold text-dark">Calendar</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="dashboard-calendar-container">
                        <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events"
                            theme="gov-red" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Custom Birthday Wish Modal -->
    <div class="modal fade" id="customWishModal" tabindex="-1" aria-labelledby="customWishModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3 bg-white">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="customWishModalLabel">Wish on their birthday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <p class="mb-4 text-secondary lh-base" id="wish-birthday-intro" aria-live="polite"></p>
                    <details class="mb-3 border rounded-3 px-3 py-2 bg-light">
                        <summary class="small text-muted fw-semibold user-select-none mb-0">Message template</summary>
                        <div class="pt-2">
                            <label class="form-label visually-hidden" for="wish-template-select">Choose template</label>
                            <select class="form-select form-select-sm mt-1" id="wish-template-select">
                                <option value="formal">Formal Birthday Wish</option>
                                <option value="casual">Casual Birthday Wish</option>
                                <option value="professional">Professional Birthday Wish</option>
                                <option value="custom">Write Custom Message</option>
                            </select>
                        </div>
                    </details>
                    <input type="hidden" id="wish-recipient-name" value="">
                    <input type="hidden" id="wish-recipient-email" value="">
                    <input type="hidden" id="wish-recipient-mobile" value="">
                    <input type="hidden" id="wish-subject" value="Happy Birthday!">
                    <div class="mb-3">
                        <label for="wish-message" class="form-label fw-semibold small text-secondary mb-2">Your message</label>
                        <textarea class="form-control rounded-3 shadow-sm border" id="wish-message" rows="8"
                            placeholder="Write your birthday message here..."></textarea>
                    </div>
                    <div class="d-flex flex-wrap gap-4 column-gap-4 row-gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input wish-channel-check" type="checkbox" id="send-via-whatsapp">
                            <label class="form-check-label small" for="send-via-whatsapp">Via WhatsApp</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input wish-channel-check" type="checkbox" id="send-via-email" checked>
                            <label class="form-check-label small" for="send-via-email">Via Email</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-3 px-4 fw-semibold" id="btn-send-wish">Send</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        #customWishModal .modal-content {
            border: 1px solid #e8ecf2 !important;
        }
        #customWishModal .wish-channel-check:checked {
            background-color: #003a75;
            border-color: #003a75;
        }
        #customWishModal .wish-channel-check:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 58, 117, 0.2);
        }
    </style>

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

        var currentRecipient = {};

        function setWishBirthdayIntro(name) {
            var el = document.getElementById('wish-birthday-intro');
            if (!el) return;
            el.textContent = '';
            el.appendChild(document.createTextNode('wish '));
            var nameSpan = document.createElement('span');
            nameSpan.className = 'fw-bold text-dark';
            nameSpan.textContent = name || '';
            el.appendChild(nameSpan);
            el.appendChild(document.createTextNode(' on the occasion of their birthday'));
        }

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-custom-wish');
            if (!btn) return;
            currentRecipient = {
                name: btn.dataset.name || '',
                email: btn.dataset.email || '',
                mobile: btn.dataset.mobile || '',
                employee_pk: btn.dataset.pk || ''
            };
            document.getElementById('wish-recipient-name').value = currentRecipient.name;
            document.getElementById('wish-recipient-email').value = currentRecipient.email;
            document.getElementById('wish-recipient-mobile').value = currentRecipient.mobile;
            setWishBirthdayIntro(currentRecipient.name);

            var emailCheckbox = document.getElementById('send-via-email');
            var whatsappCheckbox = document.getElementById('send-via-whatsapp');
            emailCheckbox.checked = currentRecipient.email !== '';
            emailCheckbox.disabled = currentRecipient.email === '';
            whatsappCheckbox.checked = false;
            whatsappCheckbox.disabled = currentRecipient.mobile === '';

            document.getElementById('wish-template-select').value = 'formal';
            document.getElementById('wish-subject').value = 'Happy Birthday ' + currentRecipient.name + '!';
            document.getElementById('wish-message').value = templates.formal(currentRecipient.name);

            var modal = new bootstrap.Modal(document.getElementById('customWishModal'));
            modal.show();
        });

        var templateSelect = document.getElementById('wish-template-select');
        if (templateSelect) {
            templateSelect.addEventListener('change', function() {
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
                var sendEmail = document.getElementById('send-via-email').checked;
                var sendWhatsapp = document.getElementById('send-via-whatsapp').checked;

                if (!message) {
                    alert('Please enter a message.');
                    return;
                }
                if (!sendEmail && !sendWhatsapp) {
                    alert('Please select at least one channel (Email or WhatsApp).');
                    return;
                }

                var sent = false;

                if (sendEmail && currentRecipient.email) {
                    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                        'content') || '{{ csrf_token() }}';
                    sendBtn.disabled = true;
                    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

                    fetch('{{ route("admin.birthday-wish.send-email") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                email: currentRecipient.email,
                                subject: subject,
                                message: message,
                                employee_pk: currentRecipient.employee_pk ? parseInt(
                                    currentRecipient.employee_pk) : null
                            })
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                if (sendWhatsapp && currentRecipient.mobile) {
                                    openWhatsApp(currentRecipient.mobile, message);
                                }
                                bootstrap.Modal.getInstance(document.getElementById('customWishModal'))
                                    .hide();
                                showToast('Birthday wish sent via email!', 'success');
                            } else {
                                alert('Failed to send email: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch(function(err) {
                            alert('Error sending email: ' + err.message);
                        })
                        .finally(function() {
                            sendBtn.disabled = false;
                            sendBtn.innerHTML = 'Send';
                        });
                    sent = true;
                }

                if (sendWhatsapp && currentRecipient.mobile && !sendEmail) {
                    openWhatsApp(currentRecipient.mobile, message);
                    // Send in-app notification for WhatsApp-only
                    if (currentRecipient.employee_pk) {
                        var csrfToken2 = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || '{{ csrf_token() }}';
                        fetch('{{ route("admin.birthday-wish.send-notification") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken2
                            },
                            body: JSON.stringify({
                                employee_pks: [parseInt(currentRecipient.employee_pk)]
                            })
                        }).catch(function() {});
                    }
                    bootstrap.Modal.getInstance(document.getElementById('customWishModal')).hide();
                    showToast('Birthday wish sent via WhatsApp!', 'success');
                    sent = true;
                }

                if (!sent) {
                    alert('No valid email or mobile for the selected channels.');
                }
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

    document.addEventListener('DOMContentLoaded', function() {
        const liveTimeEl = document.getElementById('dashboard-live-time');
        if (liveTimeEl) {
            const formatLiveTime = function(date) {
                let hours = date.getHours();
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                return String(hours).padStart(2, '0') + ':' + minutes + ' ' + ampm;
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
            const cells = comp.querySelectorAll('.calendar-cell');

            comp.addEventListener('click', function(e) {
                const td = e.target.closest('.calendar-cell');
                if (!td) return;
                const prev = comp.querySelector('.calendar-cell.is-selected');
                if (prev) prev.classList.remove('is-selected');
                td.classList.add('is-selected');
                comp.dispatchEvent(new CustomEvent('dateSelected', {
                    detail: {
                        date: td.dataset.date
                    }
                }));
            });

            cells.forEach(function(cell) {
                cell.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        cell.click();
                    }
                    const idx = Array.prototype.indexOf.call(cells, cell);
                    let targetIdx = null;
                    if (ev.key === 'ArrowLeft') targetIdx = idx - 1;
                    if (ev.key === 'ArrowRight') targetIdx = idx + 1;
                    if (ev.key === 'ArrowUp') targetIdx = idx - 7;
                    if (ev.key === 'ArrowDown') targetIdx = idx + 7;
                    if (targetIdx !== null && cells[targetIdx]) {
                        cells[targetIdx].focus();
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

                var yMin = parseInt(yearSel.options[0].value, 10);
                var yMax = parseInt(yearSel.options[yearSel.options.length - 1].value, 10);

                function clampYearMonth(y, m) {
                    while (m < 1) {
                        m += 12;
                        y -= 1;
                    }
                    while (m > 12) {
                        m -= 12;
                        y += 1;
                    }
                    if (y < yMin) {
                        y = yMin;
                        m = 1;
                    }
                    if (y > yMax) {
                        y = yMax;
                        m = 12;
                    }
                    return { y: y, m: m };
                }

                function wireNav(btn, handler) {
                    if (!btn) return;
                    btn.addEventListener('click', function() {
                        var curY = parseInt(yearSel.value, 10);
                        var curM = parseInt(monthSel.value, 10);
                        var next = handler(curY, curM);
                        var cl = clampYearMonth(next.y, next.m);
                        loadDashboardCalendar(cl.y, cl.m);
                    });
                }

                wireNav(comp.querySelector('.calendar-nav-first'), function(y, m) {
                    return { y: y - 1, m: m };
                });
                wireNav(comp.querySelector('.calendar-nav-prev'), function(y, m) {
                    return { y: y, m: m - 1 };
                });
                wireNav(comp.querySelector('.calendar-nav-next'), function(y, m) {
                    return { y: y, m: m + 1 };
                });
                wireNav(comp.querySelector('.calendar-nav-last'), function(y, m) {
                    return { y: y + 1, m: m };
                });
            }

            var holidayToggle = comp.querySelector('[data-calendar-holidays-toggle]');
            var holidayCollapse = comp.querySelector('#dashboardCalendarHolidayCollapse');
            if (holidayToggle && holidayCollapse && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                holidayCollapse.addEventListener('shown.bs.collapse', function() {
                    holidayToggle.textContent = 'Hide holidays this month';
                });
                holidayCollapse.addEventListener('hidden.bs.collapse', function() {
                    holidayToggle.textContent = 'Show holidays this month';
                });
            }
        }

        document.querySelectorAll('.calendar-component').forEach(function(comp) {
            bindCalendarComponent(comp);
        });
    });

    // ── Confetti Effect for Birthday Banner ──
    (function() {
        var canvas = document.getElementById('confetti-canvas');
        if (!canvas) return;
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
            if (!confirm('Send birthday wishes notification to all birthday people today?')) return;

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
                        '<span class="material-icons material-symbols-rounded" style="font-size:14px;">celebration</span><span style="font-size:0.75rem;">Wish All</span>';
                });
        });
    })();
    </script>
    @endpush
    @endsection