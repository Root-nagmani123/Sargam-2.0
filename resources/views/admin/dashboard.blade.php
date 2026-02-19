@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
/* ========================================
   GIGW-COMPLIANT DASHBOARD STYLES
   Following Government of India Web Guidelines
   ======================================== */

/* ========================================
   INDIAN GOVERNMENT THEME - GIGW COMPLIANT
   Official Government of India Design System
   
   Design Principles:
   - Professional & Formal appearance
   - Solid colors (no flashy gradients)
   - Conservative styling
   - High contrast for accessibility
   - Clean, structured layout
   - Government color palette
   - WCAG 2.1 AA compliant
   ======================================== */

/* ACCESSIBILITY & TYPOGRAPHY - GIGW Compliant */
:root {
    /* Official Government Colors */
    --primary-color: #004a93; /* Government Blue */
    --primary-dark: #003366;
    --primary-light: #1565c0;
    --secondary-color: #dc3545; /* Government Red */
    --success-color: #28a745; /* Government Green */
    --warning-color: #ff9800; /* Saffron-inspired */
    --info-color: #0066cc;
    
    /* Tricolor Inspiration (Subtle) */
    --saffron: #ff9933;
    --white: #ffffff;
    --green: #138808;
    
    /* Text Colors - WCAG 2.1 AA Compliant */
    --text-primary: #1a1a1a;
    --text-secondary: #4a4a4a;
    --text-muted: #6c757d;
    --text-light: #9e9e9e;
    
    /* Background Colors */
    --bg-white: #ffffff;
    --bg-light: #f5f7fa;
    --bg-lighter: #fafbfc;
    --bg-government: #f8f9fa;
    --border-color: #dee2e6;
    
    /* Shadows - Subtle Government Style */
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 2px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.12);
    
    /* Transitions - Professional */
    --transition-base: all 0.2s ease;
    --transition-slow: all 0.3s ease;
    
    /* Border Radius - Conservative */
    --border-radius-base: 8px;
    --border-radius-lg: 12px;
    
    /* Government Typography */
    --font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
    --font-size-base: 14px;
    --line-height-base: 1.6;
}

/* GIGW: Minimum 4.5:1 contrast ratio for text */
.calendar-component thead th {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    color: var(--text-primary);
    font-weight: 600;
}

.line {
    height: 2px;
    background: linear-gradient(90deg, #e0e0e0 0%, #d0d0d0 100%);
    border-radius: 2px;
}

.content-text p {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--text-primary);
    margin-bottom: 1rem;
    letter-spacing: 0.02em;
}

/* SMOOTH SCROLLING - Enhanced UX */
.card-body {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 transparent;
    scroll-behavior: smooth;
}

.card-body::-webkit-scrollbar {
    width: 8px;
}

.card-body::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 4px;
}

.card-body::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #c1c1c1 0%, #a8a8a8 100%);
    border-radius: 4px;
    transition: var(--transition-base);
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #a8a8a8 0%, #909090 100%);
}

/* HIGH CONTRAST FOR ACCESSIBILITY (GIGW Standard) */
h1,
h2,
h3,
h4,
h5,
h6 {
    color: var(--text-primary) !important;
    font-weight: 700;
    letter-spacing: -0.02em;
}

h2 {
    font-size: 1.5rem;
    line-height: 1.3;
}

h3 {
    font-size: 1.25rem;
    line-height: 1.4;
}

/* FOCUS STATES - WCAG 2.1 Compliant */
a:focus,
button:focus,
input:focus,
select:focus,
textarea:focus,
[tabindex]:focus {
    outline: 3px solid #004a93;
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(0, 74, 147, 0.15);
}
</style>
<style>
.user-card {
    border-radius: 20px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
}

.user-card img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #1a1a1a;
}

.user-role {
    font-size: 15px;
    color: #555;
    margin-bottom: 8px;
}

.user-email,
.user-phone {
    font-size: 14px;
    color: #333;
    margin: 0;
}

/* Soft pastel card backgrounds */
.bg-soft-green {
    background: #E6F2E8;
}

.bg-soft-beige {
    background: #EFE7DC;
}

.bg-soft-lavender {
    background: #E3E1EA;
}

.bg-soft-rose {
    background: #F0E0E0;
}

.bg-soft-blue {
    background: #DCE7EF;
}

.user-card {
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: .2s ease-in-out;
}

.user-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.profile-img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
}

.user-role {
    font-size: 14px;
    color: #555;
    font-weight: 500;
    margin-top: 2px;
}

.user-email,
.user-phone {
    font-size: 14px;
    color: #333;
    letter-spacing: 0.2px;
}

.birthday-card {
    border-radius: 12px;
    padding: 12px;
    display: block;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    min-height: 130px;
    transition: all 0.25s ease;
}

.birthday-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

.birthday-photo {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
}

.emp-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.2;
    color: #000;
}

.emp-desg {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 500;
    color: #555;
    line-height: 1.3;
}

.emp-email,
.emp-phone {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 400;
    color: #555;
    line-height: 1.4;
}
</style>
<style>
/* ================================
   MODERN UI ENHANCEMENTS (GIGW)
================================ */

/* --- Global Card Styling --- */
.content-card-modern {
    border-radius: 12px !important;
    background: #ffffff;
    transition: var(--transition-base);
}

.content-card-modern:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
}

/* --- Modern Section Headers --- */
.section-header-modern {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    border-left: 3px solid var(--primary-color);
    padding-left: 12px;
    margin-bottom: 0.75rem;
}

/* --- Divider --- */
.divider-modern {
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, #e5e5e5, transparent);
    margin: 0.75rem 0;
}

/* --- Notice Sidebar --- */
.card-header.bg-danger {
    border-radius: 18px 18px 0 0 !important;
    padding: 14px 20px;
}

.card-body {
    padding: 20px !important;
}

.card-body p {
    color: #202020;
    line-height: 1.6;
}

/* --- Notice Items --- */
.notice-item-modern {
    padding: 8px 10px;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: #f8f9fa;
    border-left: 2px solid transparent;
}

.notice-item-modern:hover {
    background: #e7f3ff;
    border-left-color: var(--primary-color);
    transform: translateX(2px);
}

/* --- Calendar Card --- */
.calendar-component {
    border-radius: 20px;
    background: #fff;
    box-shadow: var(--shadow-sm);
    border: 1px solid #e6e6e6;
    padding: 18px;
}

.calendar-component table {
    border-collapse: separate !important;
    border-spacing: 4px !important;
}

/* Highlight Active Day */
.calendar-cell.is-selected {
    background: var(--primary-color) !important;
    color: #fff !important;
    font-weight: 600;
}

/* --- Dropdown Alignment --- */
.x-dropdown {
    margin-bottom: 10px;
    display: inline-block;
    width: 100%;
}

/* --- Teacher Dropdown Column --- */
.col-3 .x-dropdown {
    width: 100%;
}

/* Birthday card styles moved above */

/* --- Smooth Scrolling --- */
.content-card-body-modern {
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) #f1f1f1;
}

.content-card-body-modern::-webkit-scrollbar {
    width: 8px;
}

.content-card-body-modern::-webkit-scrollbar-thumb {
    background: #c9c9c9;
    border-radius: 10px;
}

.content-card-body-modern::-webkit-scrollbar-thumb:hover {
    background: #a3a3a3;
}

/* --- Buttons Modernized --- */
.btn-outline-primary {
    border-radius: 10px;
    padding: 6px 12px;
    border-width: 1.5px;
}

.btn-outline-primary:hover {
    background: var(--primary-color);
    color: #fff;
}

/* --- GIGW Compliant Focus Styles --- */
*:focus-visible {
    outline: 3px solid var(--primary-color);
    outline-offset: 2px;
}

/* --- Improved Headings Spacing --- */
h3.fw-bold {
    margin-top: 30px;
}

/* Government Stat Cards */
.stat-card-modern {
    background: var(--bg-white);
    border: 1px solid var(--border-color);
    border-left: 3px solid transparent;
    transition: var(--transition-base);
}

.stat-card-modern:hover {
    border-left-color: var(--primary-color);
    box-shadow: var(--shadow-md);
    background: var(--bg-light);
}

.stat-card-modern:hover .stat-value-modern {
    color: var(--primary-color);
}

/* Modern Minimal Stat Cards - Bootstrap 5.3+ */
.stat-card-modern {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    overflow: hidden;
    position: relative;
}

.stat-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: var(--primary-color);
    transform: scaleY(0);
    transition: transform 0.25s ease;
}

.stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 74, 147, 0.2);
}

.stat-card-modern:hover::before {
    transform: scaleY(1);
}

/* Icon Container - Compact & Modern */
.stat-icon-modern {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.25s ease;
}

.stat-card-modern:hover .stat-icon-modern {
    transform: scale(1.05);
}

/* Government Icon Backgrounds - Solid Colors */
.icon-bg-blue {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
}

.icon-bg-green {
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
}

.icon-bg-yellow {
    background: #fff3cd;
    border: 1px solid #ffe69c;
}

.icon-bg-purple {
    background: #f3e5f5;
    border: 1px solid #e1bee7;
}

/* Text Styles - Minimal & Clean */
.stat-label-modern {
    font-size: 0.813rem;
    font-weight: 500;
    color: #6c757d;
    line-height: 1.2;
    letter-spacing: 0.01em;
}

.stat-value-modern {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.stat-card-modern:hover .stat-value-modern {
    color: var(--primary-color);
}

table>thead {
    background-color: #ffffff !important;
}

/* ========================================
   ADVANCED DASHBOARD DESIGN SYSTEM
   Bootstrap 5.3+ Modern UI Components
   ======================================== */

/* Government Theme Backgrounds - Solid Colors */
.bg-gradient-primary {
    background: #004a93 !important; /* Solid Government Blue */
    border-bottom: 3px solid #003366;
}

.bg-gradient-danger {
    background: #dc3545 !important; /* Solid Government Red */
    border-bottom: 3px solid #c82333;
}

.bg-gradient-info {
    background: #0066cc !important; /* Solid Government Info Blue */
    border-bottom: 3px solid #004a93;
}

/* Government Style Cards - Professional & Formal */
.dashboard-main-card,
.dashboard-notice-card {
    border: 1px solid var(--border-color);
    background: var(--bg-white);
    transition: var(--transition-base);
}

.dashboard-main-card:hover,
.dashboard-notice-card:hover {
    box-shadow: var(--shadow-md) !important;
    border-color: var(--primary-color);
}

/* Government Icon Wrapper - Conservative */
.dashboard-icon-wrapper {
    background: rgba(255, 255, 255, 0.25) !important;
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: var(--transition-base);
}

.dashboard-icon-wrapper:hover {
    background: rgba(255, 255, 255, 0.35) !important;
}

/* Custom Scrollbar for Dashboard */
.dashboard-scrollable-content {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 74, 147, 0.3) transparent;
}

.dashboard-scrollable-content::-webkit-scrollbar {
    width: 6px;
}

.dashboard-scrollable-content::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 10px;
}

.dashboard-scrollable-content::-webkit-scrollbar-thumb {
    background: rgba(0, 74, 147, 0.3);
    border-radius: 10px;
    transition: background 0.3s ease;
}

.dashboard-scrollable-content::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 74, 147, 0.5);
}

/* Dashboard Sections */
.dashboard-section {
    position: relative;
}

/* Government Section Styling */
.section-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--bg-light);
    border: 1px solid var(--border-color);
    transition: var(--transition-base);
}

.section-icon-wrapper:hover {
    background: var(--primary-color);
    color: white !important;
    border-color: var(--primary-color);
}

.section-divider {
    height: 2px;
    background: var(--border-color);
    border: none;
    margin: 1rem 0;
}

.section-header-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-light);
    border: 1px solid var(--border-color);
    transition: var(--transition-base);
}

.section-header-icon:hover {
    background: var(--primary-color);
    color: white !important;
}

/* Government Style Notification Items */
.notification-item {
    background: var(--bg-white);
    border: 1px solid var(--border-color);
    border-left: 3px solid transparent;
    transition: var(--transition-base);
    position: relative;
}

.notification-item:hover {
    border-left-color: var(--primary-color);
    background: var(--bg-light);
    box-shadow: var(--shadow-sm);
}

.notification-indicator {
    margin-top: 0.4rem;
    flex-shrink: 0;
}

.notification-action {
    opacity: 0;
    transition: opacity 0.25s ease;
}

.notification-item:hover .notification-action {
    opacity: 1;
}

/* Government Info Card */
.info-card {
    background: var(--bg-light);
    border: 1px solid var(--border-color);
    border-left: 4px solid var(--info-color);
    transition: var(--transition-base);
}

.info-card:hover {
    border-left-width: 6px;
    box-shadow: var(--shadow-sm);
}

.info-icon-wrapper {
    flex-shrink: 0;
}

/* Timetable Modern Styles */
.timetable-container-modern {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 8px;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 74, 147, 0.3) transparent;
}

.timetable-container-modern::-webkit-scrollbar {
    width: 5px;
}

.timetable-container-modern::-webkit-scrollbar-thumb {
    background: rgba(0, 74, 147, 0.3);
    border-radius: 10px;
}

/* Government Timetable Cards */
.timetable-card-modern {
    background: var(--bg-white);
    border: 1px solid var(--border-color);
    border-left: 4px solid var(--success-color);
    transition: var(--transition-base);
    position: relative;
}

.timetable-card-modern:hover {
    border-left-width: 6px;
    box-shadow: var(--shadow-md);
    background: var(--bg-light);
}

.timetable-topic-modern {
    font-size: 0.938rem;
    line-height: 1.4;
    color: #1a1a1a;
}

.timetable-details-modern {
    font-size: 0.813rem;
}

/* Government Notice Cards */
.notice-card-modern {
    background: var(--bg-white);
    border: 1px solid var(--border-color);
    border-left: 4px solid var(--secondary-color);
    transition: var(--transition-base);
    position: relative;
}

.notice-card-modern:hover {
    border-left-width: 6px;
    box-shadow: var(--shadow-md);
    background: var(--bg-light);
}

.notice-icon-wrapper {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Government Birthday Cards */
.birthday-card-modern {
    background: var(--bg-white);
    border: 1px solid var(--border-color);
    transition: var(--transition-base);
    position: relative;
}

.birthday-card-modern:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--primary-color);
}

.birthday-photo-modern {
    width: 60px;
    height: 60px;
    object-fit: cover;
    transition: all 0.3s ease;
}

.birthday-card-modern:hover .birthday-photo-modern {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.emp-name-modern {
    font-size: 0.938rem;
    line-height: 1.3;
}

.emp-desg-modern {
    font-size: 0.75rem;
    line-height: 1.3;
}

.birthday-details {
    font-size: 0.813rem;
}

/* Government Empty State */
.empty-state {
    padding: 3rem 1rem;
    background: var(--bg-light);
    border: 1px dashed var(--border-color);
    border-radius: var(--border-radius-base);
}

.empty-state-icon {
    opacity: 0.4;
    color: var(--text-muted);
    transition: var(--transition-base);
}

.empty-state:hover .empty-state-icon {
    opacity: 0.6;
}

/* Responsive Improvements */
@media (max-width: 992px) {
    .dashboard-main-card,
    .dashboard-notice-card {
        max-height: none !important;
        height: auto !important;
    }
    
    .dashboard-scrollable-content {
        max-height: none !important;
    }
}

@media (max-width: 768px) {
    .stat-card-modern {
        margin-bottom: 0.5rem;
    }
    
    .stat-value-modern {
        font-size: 1.5rem;
    }
    
    .stat-icon-modern {
        width: 42px;
        height: 42px;
    }
    
    .birthday-photo-modern {
        width: 50px;
        height: 50px;
    }
    
    .section-icon-wrapper {
        width: 35px;
        height: 35px;
    }
    
    .dashboard-icon-wrapper {
        width: 40px;
        height: 40px;
    }
    
    .timetable-card-modern {
        padding: 0.75rem !important;
    }
    
    .notice-card-modern {
        padding: 0.75rem !important;
    }
}

/* Link hover effects */
a.text-decoration-none:hover .stat-card-modern {
    text-decoration: none;
}

/* Smooth transitions for all interactive elements */
.stat-card-modern,
.birthday-card-modern,
.timetable-card-modern,
.notice-card-modern,
.notification-item {
    will-change: transform;
}

/* Government Typography */
body {
    font-family: var(--font-family);
    font-size: var(--font-size-base);
    line-height: var(--line-height-base);
    color: var(--text-primary);
}

/* Government Badge Styles */
.badge {
    font-weight: 600;
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
}

/* Government Button Styles */
.btn-outline-primary {
    border: 1.5px solid var(--primary-color);
    color: var(--primary-color);
    font-weight: 500;
    transition: var(--transition-base);
}

.btn-outline-primary:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.btn-outline-danger {
    border: 1.5px solid var(--secondary-color);
    color: var(--secondary-color);
    font-weight: 500;
}

.btn-outline-danger:hover {
    background: var(--secondary-color);
    color: white;
}

/* Government Card Headers */
.card-header {
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
}

/* Subtle Animations - Government Style */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.dashboard-section,
.notification-item,
.timetable-card-modern,
.notice-card-modern,
.birthday-card-modern {
    animation: fadeIn 0.3s ease-out;
}

/* Compact Timetable Card Design - Modern Minimal */
.timetable-container {
    max-height: 240px;
    overflow-y: auto;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 transparent;
}

.timetable-container::-webkit-scrollbar {
    width: 5px;
}

.timetable-container::-webkit-scrollbar-track {
    background: transparent;
    border-radius: 3px;
}

.timetable-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.timetable-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.timetable-card {
    background: #fff;
    border-left: 3px solid #dc3545;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    transition: all 0.25s ease;
    min-height: 100px;
    display: flex;
    flex-direction: column;
}

.timetable-card:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    transform: translateX(2px);
    border-left-color: #c82333;
}

.timetable-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.timetable-time-badge {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 0.688rem;
    font-weight: 600;
    white-space: nowrap;
}

.timetable-sno {
    background: #f8f9fa;
    color: #6c757d;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.688rem;
    font-weight: 600;
}

.timetable-topic {
    font-size: 0.813rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 6px 0;
    line-height: 1.3;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.timetable-details {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 0.688rem;
    color: #555;
    margin-top: auto;
}

.timetable-detail-item {
    display: flex;
    align-items: center;
    gap: 3px;
}

.timetable-detail-item i {
    font-size: 12px;
    color: #6c757d;
}
</style>


<div class="container-fluid admin-dashboard-page">

    <div class="row g-3 mb-3">

        <!-- Total Active Courses -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.active_course') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-blue d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-book-fill text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Total Active Courses</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $totalActiveCourses }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Upcoming Courses -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.incoming_course') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-green d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-calendar-event-fill text-success fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Upcoming Courses</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $upcomingCourses }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Upcoming Events -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.upcoming_events') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-yellow d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-calendar-check-fill text-warning fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Upcoming Events</div>
                            <div class="stat-value-modern fw-bold text-dark">2</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        @if(hasRole('Student-OT'))
        <!-- Medical Exception -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('medical.exception.ot.view') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-purple d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-heart-pulse-fill text-info fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Medical Exception</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $exemptionCount }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @else
        <!-- Total Guest Faculty -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.guest_faculty') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-yellow d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-person-badge-fill text-warning fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Total Guest Faculty</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $total_guest_faculty }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(hasRole('Student-OT'))
        <!-- OT MDO/Escort -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('ot.mdo.escrot.exemption.view') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-purple d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-people-fill text-info fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">OT MDO/Escort</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $MDO_count }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @else
        <!-- Total Inhouse Faculty -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.inhouse_faculty') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-purple d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-person-video3-fill text-info fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Total Inhouse Faculty</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $total_internal_faculty }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty'))
        <!-- Total Sessions -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.sessions') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-blue d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-clock-history text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Session Details</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $totalSessions }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if(isset($isCCorACC) && $isCCorACC)
        <!-- Total Students - Only for CC/ACC -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="{{ route('admin.dashboard.students') }}" class="text-decoration-none">
                <div class="stat-card-modern h-100">
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="stat-icon-modern icon-bg-green d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-people-fill text-success fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-label-modern text-muted small mb-1">Total Students</div>
                            <div class="stat-value-modern fw-bold text-dark">{{ $totalStudents }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif

    </div>

    <!-- Advanced Dashboard Content Section - Bootstrap 5.3+ Design System -->
    <div class="row g-4 mb-4">
        <!-- LEFT CONTENT PANEL -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg h-100 dashboard-main-card" style="max-height: 720px;">
                <!-- Government Style Card Header -->
                <div class="card-header bg-gradient-primary border-0 rounded-top px-4 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="dashboard-icon-wrapper rounded p-2">
                                <i class="bi bi-clipboard-data-fill text-white fs-5"></i>
                            </div>
                            <div>
                                <h2 class="mb-0 fw-bold text-white fs-5">Admin & Campus Summary</h2>
                                <small class="text-white text-opacity-90">Overview of key activities and information</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card Body with Custom Scrollbar -->
                <div class="card-body p-4 dashboard-scrollable-content" style="overflow-y: auto; max-height: calc(720px - 100px);">
                    @php
                    // Define notifications early so it can be used in badge
                    $user = Auth::user();
                    $notifications = $user ? notification()->getNotifications($user->user_id, 10) : collect();
                    @endphp
                    
                    <!-- Admin Summary / Notifications Section -->
                    <section aria-labelledby="{{ hasRole('Admin') ? 'admin-summary-title' : 'notifications-title' }}"
                        class="dashboard-section mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="section-icon-wrapper rounded p-2">
                                    <i class="bi bi-{{ hasRole('Admin') ? 'shield-check' : 'bell' }}-fill text-primary"></i>
                                </div>
                                <h3 id="{{ hasRole('Admin') ? 'admin-summary-title' : 'notifications-title' }}"
                                    class="mb-0 fw-bold fs-6 text-dark">
                                    {{ hasRole('Admin') ? 'Admin Summary' : 'Notifications' }}
                                </h3>
                            </div>
                            <span class="badge bg-primary text-white px-3" style="font-weight: 600;">
                                {{ $notifications->count() }} {{ $notifications->count() === 1 ? 'item' : 'items' }}
                            </span>
                        </div>
                        
                        <div class="section-divider mb-3"></div>

                        <div class="content-text">

                            <script>
                            // Define markAsRead function for Admin Summary notifications - Define early to ensure availability
                            if (typeof window.markAsRead === 'undefined' || window.markAsReadDashboard === undefined) {
                                window.markAsReadDashboard = function(notificationId, clickedElement) {
                                    console.log('markAsReadDashboard called with notificationId:', notificationId);

                                    // Prevent multiple clicks
                                    if (clickedElement && clickedElement.dataset.processing === 'true') {
                                        return;
                                    }
                                    if (clickedElement) {
                                        clickedElement.dataset.processing = 'true';
                                    }

                                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                                        ?.getAttribute('content') || '{{ csrf_token() }}';

                                    fetch('/admin/notifications/mark-read-redirect/' + notificationId, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken
                                            }
                                        })
                                        .then(response => {
                                            console.log('Response status:', response.status);
                                            return response.json().then(data => {
                                                if (!response.ok) {
                                                    throw new Error(data.error ||
                                                        'Failed to mark notification as read');
                                                }
                                                return data;
                                            });
                                        })
                                        .then(data => {
                                            console.log('Response data:', data);
                                            if (data.success && data.redirect_url) {
                                                window.location.href = data.redirect_url;
                                            } else if (data.success) {
                                                location.reload();
                                            } else {
                                                console.error('Failed to mark notification as read. Response:',
                                                    data);
                                                if (clickedElement) {
                                                    clickedElement.dataset.processing = 'false';
                                                }
                                                const errorMsg = data.error || 'Unknown error occurred';
                                                alert('Failed to mark notification as read: ' + errorMsg);
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            if (clickedElement) {
                                                clickedElement.dataset.processing = 'false';
                                            }
                                            alert('An error occurred: ' + (error.message || 'Unknown error'));
                                        });
                                };
                                // Also set as markAsRead for compatibility
                                window.markAsRead = window.markAsReadDashboard;
                            }
                            </script>

                            @if($notifications->isEmpty())
                            <div class="empty-state text-center py-5">
                                <div class="empty-state-icon mb-3">
                                    <i class="bi bi-bell-slash text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                                <p class="text-muted mb-0 fw-medium">No notifications available</p>
                                <small class="text-muted">You're all caught up!</small>
                            </div>
                            @else
                            <div class="notification-list">
                                @foreach($notifications as $notification)
                                <div class="notification-item p-3 mb-2 rounded border" 
                                    style="cursor: pointer; transition: all 0.2s ease;"
                                    onclick="window.markAsReadDashboard({{ $notification->pk }}, this)"
                                    onmouseover="this.style.transform='translateX(4px)'; this.style.borderColor='rgba(0, 74, 147, 0.3)';"
                                    onmouseout="this.style.transform='translateX(0)'; this.style.borderColor='rgba(0,0,0,0.1)';">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="notification-indicator">
                                            <i class="bi bi-circle-fill text-primary" style="font-size: 0.5rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-0 small text-dark lh-sm">{{ $notification->message }}</p>
                                        </div>
                                        <div class="notification-action">
                                            <i class="bi bi-chevron-right text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </section>

                    <!-- Campus Summary Section -->
                    <section aria-labelledby="campus-summary-title" class="dashboard-section mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="section-icon-wrapper rounded p-2">
                                <i class="bi bi-building-fill text-info"></i>
                            </div>
                            <h2 id="campus-summary-title" class="mb-0 fw-bold fs-6 text-dark">
                                Campus Summary
                            </h2>
                        </div>
                        
                        <div class="section-divider mb-3"></div>

                        <div class="info-card rounded p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="info-icon-wrapper">
                                    <i class="bi bi-info-circle-fill text-info fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 small text-dark lh-base">
                                        Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access to various administrative functions.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Today's Timetable Section -->
                    @if(hasRole('Student-OT') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
                    <section aria-labelledby="timetable-title" class="dashboard-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="section-icon-wrapper rounded p-2">
                                    <i class="bi bi-calendar-day-fill text-success"></i>
                                </div>
                                <h2 id="timetable-title" class="mb-0 fw-bold fs-6 text-dark">
                                    Today's Classes
                                </h2>
                            </div>
                                <a href="{{ route('calendar.index') }}" class="btn btn-outline-primary btn-sm px-3">
                                    <i class="bi bi-arrow-right me-1"></i>View All
                                </a>
                        </div>
                        
                        <div class="section-divider mb-3"></div>

                        @if($todayTimetable && $todayTimetable->isNotEmpty())
                        <div class="timetable-container-modern">
                            @foreach($todayTimetable as $entry)
                            <div class="timetable-card-modern p-3 mb-3 rounded border shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-success text-white px-3 py-1" style="font-weight: 600;">
                                        <i class="bi bi-clock me-1"></i>{{ $entry['session_time'] }}
                                    </span>
                                    <span class="badge bg-secondary text-white px-2 py-1" style="font-weight: 600;">
                                        #{{ $entry['sno'] }}
                                    </span>
                                </div>
                                <h6 class="timetable-topic-modern fw-bold mb-3 text-dark">{{ $entry['topic'] }}</h6>
                                <div class="timetable-details-modern d-flex flex-wrap gap-3">
                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                        <i class="bi bi-person-fill text-primary"></i>
                                        <span>{{ $entry['faculty_name'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                        <i class="bi bi-geo-alt-fill text-danger"></i>
                                        <span>{{ $entry['session_venue'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                        <i class="bi bi-calendar3 text-info"></i>
                                        <span>{{ $entry['session_date'] }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="empty-state text-center py-4">
                            <div class="empty-state-icon mb-3">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                            <p class="text-muted mb-0 fw-medium">No classes scheduled for today</p>
                            <small class="text-muted">Enjoy your free time!</small>
                        </div>
                        @endif
                    </section>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT NOTICE PANEL -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-lg h-100 dashboard-notice-card" style="max-height: 720px;">
                <!-- Government Style Notice Header -->
                <div class="card-header bg-gradient-danger border-0 rounded-top px-4 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="dashboard-icon-wrapper rounded p-2">
                                <i class="bi bi-megaphone-fill text-white fs-5"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-white fs-5">Notices</h5>
                                <small class="text-white text-opacity-90">Important announcements and updates</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notice Body with Custom Scrollbar -->
                <div class="card-body p-4 dashboard-scrollable-content" style="overflow-y: auto; max-height: calc(720px - 100px);">
                    @php $notices = get_notice_notification_by_role() @endphp
                    @if(count($notices) === 0)
                    <div class="empty-state text-center py-5">
                        <div class="empty-state-icon mb-3">
                            <i class="bi bi-file-earmark-text text-white text-opacity-50" style="font-size: 3rem;"></i>
                        </div>
                        <p class="text-muted mb-0 fw-medium">No notices available</p>
                        <small class="text-muted">Check back later for updates</small>
                    </div>
                    @else
                    @foreach($notices as $notice)
                    <div class="notice-card-modern p-3 mb-3 rounded border">
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="notice-icon-wrapper rounded p-2 flex-shrink-0" style="background: #fee; border: 1px solid #fcc;">
                                <i class="bi bi-file-earmark-text-fill text-danger"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-2 text-dark">{{ $notice->notice_title }}</h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <i class="bi bi-calendar3"></i>
                                        Posted on: {{ date('d M, Y', strtotime($notice->created_at)) }}
                                    </small>
                                </div>
                                @if($notice->document)
                                <a href="{{ asset('storage/' . $notice->document) }}" target="_blank"
                                    class="btn btn-sm btn-outline-danger px-3 mt-2">
                                    <i class="bi bi-paperclip me-1"></i>View Attachment
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Birthdays Section Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="section-header-icon rounded p-3" style="background: #fee; border: 1px solid #fcc;">
                        <i class="bi bi-balloon-fill text-danger fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark fs-4">Today's Birthdays</h3>
                        <small class="text-muted">Celebrating {{ $emp_dob_data->count() }} {{ $emp_dob_data->count() === 1 ? 'birthday' : 'birthdays' }} today</small>
                    </div>
                </div>
                <hr class="my-0">
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT SIDE: Birthday Cards -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-body p-4">
                        @if($emp_dob_data->isEmpty())
                        <div class="empty-state text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="bi bi-balloon text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                            <p class="text-muted mb-0 fw-medium fs-5">No Birthdays Today</p>
                            <small class="text-muted">Check back tomorrow for birthday celebrations!</small>
                        </div>
                        @else
                        <div class="row g-3">
                            @php
                            // Government theme colors - solid backgrounds
                            $colors = [
                                '#f0f7f0', // Light green
                                '#fff8e1', // Light saffron/yellow
                                '#e3f2fd', // Light blue
                                '#f3e5f5', // Light purple
                                '#fce4ec', // Light pink
                                '#f5f5f5'  // Light gray
                            ];
                            @endphp

                            @foreach($emp_dob_data as $employee)
                            <div class="col-6">
                                <div class="birthday-card-modern p-3 rounded border shadow-sm h-100"
                                    style="background: {{ $colors[$loop->index % count($colors)] }}; border-color: var(--border-color) !important;">
                                    
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        @php
                                        $photo = !empty($employee->profile_picture)
                                        ? asset('storage/' . $employee->profile_picture)
                                        : 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
                                        @endphp

                                        <div class="position-relative">
                                            <img src="{{ $photo }}" class="birthday-photo-modern rounded-circle border border-3 border-white shadow" alt="{{ $employee->first_name }}">
                                            <div class="position-absolute top-0 start-100 translate-middle">
                                                <span class="badge bg-danger rounded-circle p-1">
                                                    <i class="bi bi-balloon-fill text-white" style="font-size: 0.6rem;"></i>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex-grow-1">
                                            <h6 class="emp-name-modern fw-bold mb-1 text-dark">
                                                {{ strtoupper($employee->first_name) }} {{ strtoupper($employee->last_name) }}
                                            </h6>
                                            <p class="emp-desg-modern mb-0 small text-muted fw-medium">
                                                {{ strtoupper($employee->designation_name) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="birthday-details pt-2 border-top border-white border-opacity-50">
                                        <div class="d-flex align-items-center gap-2 mb-2 small">
                                            <i class="bi bi-envelope-fill text-muted"></i>
                                            <span class="text-muted text-truncate">{{ $employee->email }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 small">
                                            <i class="bi bi-telephone-fill text-muted"></i>
                                            <span class="text-muted">{{ $employee->mobile }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>



                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- RIGHT SIDE: Calendar -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header bg-gradient-info border-0 rounded-top px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="dashboard-icon-wrapper rounded p-2">
                                <i class="bi bi-calendar3-fill text-white fs-5"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-white fs-5">Calendar</h5>
                                <small class="text-white text-opacity-90">View events and important dates</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events"
                            theme="gov-red" />
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



@push('scripts')
<script>
// Define markAsRead function for Admin Summary notifications - Always override to ensure it works
window.markAsRead = function(notificationId, clickedElement) {
    console.log('markAsRead called with notificationId:', notificationId);

    // Prevent multiple clicks
    if (clickedElement && clickedElement.dataset.processing === 'true') {
        console.log('Already processing, ignoring click');
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
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Failed to mark notification as read');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success && data.redirect_url) {
                // Notification remains visible until redirect happens
                window.location.href = data.redirect_url;
            } else if (data.success) {
                // If no redirect URL, just reload (notification will remain visible if not filtered)
                location.reload();
            } else {
                console.error('Failed to mark notification as read:', data.error || 'Unknown error');
                if (clickedElement) {
                    clickedElement.dataset.processing = 'false';
                }
                alert('Failed to mark notification as read. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (clickedElement) {
                clickedElement.dataset.processing = 'false';
            }
            alert('An error occurred: ' + (error.message || 'Unknown error'));
        });
};

// Lightweight calendar interactions (vanilla JS)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.calendar-component').forEach(function(comp) {
        const yearSel = comp.querySelector('.calendar-year');
        const monthSel = comp.querySelector('.calendar-month');
        const cells = comp.querySelectorAll('.calendar-cell');


        // Click a date -> emit custom event
        comp.addEventListener('click', function(e) {
            const td = e.target.closest('.calendar-cell');
            if (!td) return;
            const prev = comp.querySelector('.calendar-cell.is-selected');
            if (prev) prev.classList.remove('is-selected');
            td.classList.add('is-selected');


            const date = td.dataset.date;
            comp.dispatchEvent(new CustomEvent('dateSelected', {
                detail: {
                    date
                }
            }));
        });


        // keyboard support for cells
        cells.forEach(function(cell) {
            cell.addEventListener('keydown', function(ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    cell.click();
                }
                // Arrow navigation (left/right/up/down)
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


        // Change month/year -> navigate by query params (simple behavior)
        yearSel.addEventListener('change', function() {
            const y = this.value;
            const m = monthSel.value;
            const url = new URL(window.location.href);
            url.searchParams.set('year', y);
            url.searchParams.set('month', m);
            window.location.href = url.toString();
        });
        monthSel.addEventListener('change', function() {
            const y = yearSel.value;
            const m = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('year', y);
            url.searchParams.set('month', m);
            window.location.href = url.toString();
        });
    });
});
</script>

@endpush
@endsection