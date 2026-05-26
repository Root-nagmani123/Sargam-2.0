<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
/* Notice Notification module — government-portal UI tokens */
.notice-module-page {
    --notice-primary: #004a93;
    --notice-primary-hover: #003d7a;
    --notice-surface: #f8f9fa;
    --notice-radius: 0.75rem;
    --notice-radius-lg: 1rem;
}

.notice-module-page .notice-card {
    border-radius: var(--notice-radius-lg);
    border: 1px solid var(--bs-border-color-translucent);
    transition: box-shadow 0.25s ease;
}
.notice-module-page .notice-card:hover {
    box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.08) !important;
}

.notice-module-page .notice-form-header {
    border-bottom: 1px solid var(--bs-border-color);
    padding-bottom: 1rem;
}
.notice-module-page .notice-title-highlight {
    background: linear-gradient(180deg, transparent 58%, var(--notice-highlight) 58%);
    padding: 0 0.12em;
}
.notice-module-page .notice-form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin-bottom: 0.35rem;
}

.notice-module-page .form-control,
.notice-module-page .form-select {
    border-radius: 0.5rem;
    min-height: calc(2.5rem + 2px);
    border-color: var(--bs-border-color);
}
.notice-module-page .form-control:focus,
.notice-module-page .form-select:focus {
    border-color: var(--notice-primary);
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.12);
}
.notice-module-page .form-control::placeholder {
    color: var(--bs-secondary-color);
    opacity: 0.85;
}

.notice-module-page .btn-notice-save {
    background-color: var(--notice-primary);
    border-color: var(--notice-primary);
}
.notice-module-page .btn-notice-save:hover,
.notice-module-page .btn-notice-save:focus {
    background-color: var(--notice-primary-hover);
    border-color: var(--notice-primary-hover);
}
.notice-module-page .btn-notice-cancel {
    color: var(--notice-primary);
    border-color: var(--notice-primary);
}
.notice-module-page .btn-notice-cancel:hover {
    background-color: rgba(0, 74, 147, 0.06);
    color: var(--notice-primary);
    border-color: var(--notice-primary);
}

.notice-module-page .notice-filter-panel {
    background: var(--notice-surface);
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: var(--notice-radius);
}

.notice-module-page .notice-list-header {
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.notice-module-page .notice-table thead th {
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--bs-secondary-color);
    border-bottom-width: 2px;
    white-space: nowrap;
    background-color: var(--notice-surface);
}
.notice-module-page .notice-table tbody tr {
    transition: background-color 0.15s ease;
}
.notice-module-page .notice-table tbody tr:hover {
    background-color: rgba(0, 74, 147, 0.04);
}

.notice-module-page .notice-action-btn {
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
}
.notice-module-page .notice-action-btn:hover:not(:disabled) {
    background-color: rgba(0, 74, 147, 0.08);
    transform: translateY(-1px);
}

.notice-module-page .note-editor.note-frame {
    border-radius: 0.5rem;
    border-color: var(--bs-border-color);
    overflow: hidden;
}
.notice-module-page .note-editor.note-frame .note-toolbar {
    background: var(--notice-surface);
    border-bottom: 1px solid var(--bs-border-color);
}

/* Feed / hub toolbar (reference: pill tabs + search) */
.notice-module-page .notice-feed-toolbar {
    border-radius: var(--notice-radius);
    background: var(--notice-surface);
    padding: 0.75rem 1rem;
    gap: 0.75rem;
}
.notice-module-page .notice-feed-pills {
    gap: 0.35rem;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: thin;
    padding-bottom: 2px;
}
.notice-module-page .notice-feed-pills .nav-link {
    border: none;
    border-radius: 2rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--bs-body-color);
    background: #e9ecef;
    white-space: nowrap;
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}
.notice-module-page .notice-feed-pills .nav-link:hover {
    background: #dee2e6;
}
.notice-module-page .notice-feed-pills .nav-link.active {
    background: var(--notice-primary);
    color: #fff;
    box-shadow: 0 2px 6px rgba(0, 74, 147, 0.25);
}
.notice-module-page .notice-feed-search .input-group-text {
    background: #fff;
    border-right: 0;
    border-color: var(--bs-border-color);
}
.notice-module-page .notice-feed-search .form-control {
    border-left: 0;
    min-height: auto;
}
.notice-module-page .notice-feed-search .form-control:focus {
    box-shadow: none;
    border-color: var(--bs-border-color);
}
.notice-module-page .notice-feed-search:focus-within .input-group-text,
.notice-module-page .notice-feed-search:focus-within .form-control {
    border-color: var(--notice-primary);
}

.notice-module-page .notice-feed-card {
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: var(--notice-radius);
    background: #fff;
    padding: 1rem 1.15rem;
    margin-bottom: 0.75rem;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.notice-module-page .notice-feed-card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.06);
    border-color: rgba(0, 74, 147, 0.2);
}
.notice-module-page .notice-feed-card-title {
    font-weight: 600;
    color: #212529;
    font-size: 1rem;
    margin: 0;
    flex: 1;
    min-width: 0;
}
.notice-module-page .notice-feed-card-meta {
    font-size: 0.8125rem;
    color: var(--bs-secondary-color);
}
.notice-module-page .notice-feed-card-body {
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.55;
    margin-top: 0.65rem;
}
.notice-module-page .notice-feed-card-highlight {
    outline: 3px solid var(--bs-primary);
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
    background: #f8f9ff;
}

.notice-module-page .notice-birthday-row-today {
    background: #fff5f5;
    border: 1px solid rgba(0, 74, 147, 0.08);
    border-radius: var(--notice-radius);
    transition: background 0.2s ease, box-shadow 0.2s ease;
}
.notice-module-page .notice-birthday-row-today:hover {
    background: #ffeded;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
.notice-module-page .notice-birthday-row-upcoming {
    background: #fff;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: var(--notice-radius);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.notice-module-page .notice-birthday-row-upcoming:hover {
    border-color: rgba(0, 74, 147, 0.25);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.notice-module-page .notice-birthday-name {
    color: var(--notice-primary);
    font-weight: 600;
}

@media (max-width: 767.98px) {
    .notice-module-page .notice-table {
        font-size: 0.875rem;
    }
    .notice-module-page .notice-feed-card-meta {
        white-space: normal;
        width: 100%;
    }
}
</style>
