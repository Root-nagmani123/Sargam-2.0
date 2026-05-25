<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.notice-module-page .notice-card {
    border-radius: 1rem;
    border: 1px solid var(--bs-border-color-translucent);
    transition: box-shadow 0.25s ease;
}
.notice-module-page .notice-card:hover {
    box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.08) !important;
}
.notice-module-page .notice-form-header {
    border-bottom: 1px solid var(--bs-border-color);
    padding-bottom: 1rem;
    margin-bottom: 0.25rem;
}
.notice-module-page .notice-title-highlight {
    background: linear-gradient(180deg, transparent 58%, #fff3cd 58%);
    padding: 0 0.12em;
}
.notice-module-page .notice-form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    margin-bottom: 0.35rem;
}
.notice-module-page .form-control,
.notice-module-page .form-select {
    border-radius: 0.5rem;
    min-height: calc(2.5rem + 2px);
}
.notice-module-page .form-control:focus,
.notice-module-page .form-select:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.12);
}
.notice-module-page .btn-notice-save {
    background-color: #004a93;
    border-color: #004a93;
}
.notice-module-page .btn-notice-save:hover {
    background-color: #003d7a;
    border-color: #003d7a;
}
.notice-module-page .btn-notice-cancel {
    color: #004a93;
    border-color: #004a93;
}
.notice-module-page .btn-notice-cancel:hover {
    background-color: rgba(0, 74, 147, 0.06);
    color: #004a93;
    border-color: #004a93;
}
.notice-module-page .notice-filter-panel {
    background: var(--bs-light);
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.75rem;
}
.notice-module-page .notice-table thead th {
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--bs-secondary-color);
    border-bottom-width: 2px;
    white-space: nowrap;
}
.notice-module-page .notice-table tbody tr {
    transition: background-color 0.15s ease;
}
.notice-module-page .notice-table tbody tr:hover {
    background-color: rgba(0, 74, 147, 0.04);
}
.notice-module-page .notice-action-btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    transition: background-color 0.15s ease, color 0.15s ease;
}
.notice-module-page .notice-action-btn:hover:not(:disabled) {
    background-color: rgba(0, 74, 147, 0.08);
}
.notice-module-page .notice-doc-link {
    font-size: 0.875rem;
}
.notice-module-page .note-editor.note-frame {
    border-radius: 0.5rem;
    border-color: var(--bs-border-color);
}
@media (max-width: 767.98px) {
    .notice-module-page .notice-table {
        font-size: 0.875rem;
    }
}
</style>
