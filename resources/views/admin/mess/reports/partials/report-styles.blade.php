<style>
/* ── Report chrome (Figma "Mess Management View" → Reports) ── */
.mess-report-page .mess-export-bar .dropdown-menu { min-width: 10rem; }
/* Light-blue caption row above each group's table (vendor / buyer). */
.mess-report-group-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: var(--ds-space-2);
    padding: var(--ds-space-2) var(--ds-space-3);
    background: rgba(var(--bs-primary-rgb), .1);
    color: var(--ds-primary);
    font-size: .8125rem;
    font-weight: 500;
}
.mess-report-page .mess-dt-card .table tfoot td { background: var(--ds-surface-2); font-weight: 600; }

.mess-report-sno-th,
.mess-report-sno-cell {
    width: 4.5rem;
    min-width: 4.5rem;
    max-width: 4.5rem;
    padding-left: 0.35rem !important;
    padding-right: 0.35rem !important;
    vertical-align: middle;
}

.mess-report-sno {
    display: inline-block;
    min-width: 1.75rem;
    font-variant-numeric: tabular-nums;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.2;
    color: var(--bs-secondary-color, #6c757d);
}

.mess-report-sort-th {
    white-space: nowrap;
    user-select: none;
}

.mess-report-sort-link:hover {
    color: var(--bs-primary) !important;
}

.mess-report-sort-icon {
    font-size: 1rem;
    line-height: 1;
    opacity: 0.95;
}

.mess-report-sort-icon--muted {
    opacity: 0.35;
    font-size: 0.9rem;
}

.stock-summary-report .mess-report-sno {
    color: inherit;
}

@media print {

    .material-symbols-rounded,
    .material-icons,
    .material-symbol-rounded,
    .mess-report-sort-icon {
        display: none !important;
        font-size: 0 !important;
        line-height: 0 !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        visibility: hidden !important;
    }

    .mess-report-sort-link {
        text-decoration: none;
        color: inherit;
    }
}
</style>