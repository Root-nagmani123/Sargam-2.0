{{--
    Estate Master screens — page-scoped styles (docs/design.md, usage rule 4).
    Pushed into @stack('styles') by every Estate Master page: Define Estate/Campus,
    Unit Type, Unit Sub Type, Block/Building and Eligibility - Criteria.

    Built only on --ds-* tokens (rule 2) and the Layer C components in
    sargam-app.css (.ds-page-header, .ds-card, .ds-toolbar, .ds-table-wrap /
    .ds-table-sticky, .ds-actions + .btn-icon, .ds-empty-state, .ds-form-*,
    .ds-btn-primary / .ds-btn-cancel). Everything here is scoped under
    .em-page / .em-modal so it cannot leak into other screens.

    The DataTables search / footer slots keep the programme-dt-* hook classes the
    global enhancer looks for; custom.css styles those with !important, so the few
    overrides that restore the token values need it too.
--}}
<style>
    /* ── Page header (.ds-page-header) ─────────────────────────────────── */
    .em-page .ds-page-header {
        margin-bottom: var(--ds-space-3);
    }

    .em-page .ds-page-subtitle {
        max-width: 72ch;
    }

    .em-page .ds-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        min-height: var(--ds-control-h);
        padding-top: 0;
        padding-bottom: 0;
        white-space: nowrap;
    }

    /* ── Card (.ds-card): 8px radius + default elevation (Layer A) ─────── */
    .em-page .ds-card {
        background: var(--ds-surface);
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius-card);
        box-shadow: var(--ds-shadow);
    }

    .em-page .ds-card > .ds-card-header {
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .em-page .em-card-title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--ds-ink);
    }

    .em-page .em-count {
        display: inline-flex;
        align-items: center;
        min-height: 1.5rem;
        padding: 0 var(--ds-space-2);
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--ds-ink-muted);
        background: var(--ds-surface);
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius);
    }

    /* Utility action (Print) — neutral: not a primary, cancel or back role. */
    .em-page .em-btn-utility {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        min-height: var(--ds-control-h-sm);
        padding: 0 var(--ds-space-3);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--ds-primary);
        background: var(--ds-surface);
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius);
    }

    .em-page .em-btn-utility:hover {
        color: var(--ds-primary);
        border-color: var(--ds-primary);
        box-shadow: var(--ds-shadow-sm);
    }

    /* ── Toolbar search (.ds-toolbar) — a 40px, 4px-radius control ─────── */
    .em-page .ds-toolbar .programme-dt-search {
        width: 300px;
        max-width: 100%;
    }

    .em-page .programme-dt-search .dataTables_filter input {
        height: var(--ds-control-h);
        border: 1px solid var(--ds-line) !important;
        border-radius: var(--ds-radius) !important;
        font-size: 0.875rem;
        color: var(--ds-ink);
    }

    .em-page .programme-dt-search .dataTables_filter input:focus {
        border-color: var(--ds-primary) !important;
        box-shadow: var(--ds-focus-ring) !important;
    }

    .em-page .programme-dt-search .dataTables_filter label::before {
        color: var(--ds-ink-muted);
    }

    /* ── Table (.ds-table-wrap / .ds-table-sticky) ─────────────────────── */
    .em-page .ds-table-wrap {
        max-height: 65vh;
    }

    .em-page .em-table {
        margin-bottom: 0;
    }

    /* The admin theme forces `.table th { background:#f3f3f3 !important;
       color:#787878 !important }`; restoring the tokens needs !important too. */
    .em-page .em-table > thead > tr > th {
        padding: var(--ds-space-2) var(--ds-space-3);
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--ds-ink-muted) !important;
        background: var(--ds-surface-2) !important;
        white-space: nowrap;
        vertical-align: middle;
        border-bottom: 1px solid var(--ds-line);
    }

    /* Sortable headers paint the caret at the right edge — keep it off the label. */
    .em-page .em-table > thead > tr > th.sorting,
    .em-page .em-table > thead > tr > th.sorting_asc,
    .em-page .em-table > thead > tr > th.sorting_desc {
        padding-right: var(--ds-space-5);
    }

    .em-page .em-table > tbody > tr > td {
        padding: var(--ds-space-2) var(--ds-space-3);
        font-size: 0.875rem;
        color: var(--ds-ink);
        vertical-align: middle;
        border-bottom: 1px solid var(--ds-line);
    }

    .em-page .em-table > tbody > tr:last-child > td {
        border-bottom: 0;
    }

    .em-page .em-col-sno {
        width: 5rem;
        color: var(--ds-ink-muted);
        white-space: nowrap;
    }

    .em-page .em-col-action {
        width: 7rem;
        text-align: right;
        white-space: nowrap;
    }

    .em-page .em-col-wrap {
        min-width: 12rem;
        max-width: 26rem;
        white-space: normal;
        line-height: 1.45;
    }

    .em-page .em-muted {
        color: var(--ds-ink-muted);
    }

    /* ── Row actions (.ds-actions + .btn-icon, 32px / 4px) ─────────────── */
    .em-page .ds-actions .btn-icon {
        border: 1px solid transparent;
        background: transparent;
    }

    .em-page .ds-actions .btn-icon i {
        font-size: 1rem;
        line-height: 1;
    }

    .em-page .em-act-edit { color: var(--ds-primary); }
    .em-page .em-act-delete { color: var(--ds-secondary); }

    .em-page .ds-actions .btn-icon:hover {
        background: var(--ds-surface-2);
        border-color: var(--ds-line);
    }

    .em-page .ds-actions .btn-icon:focus-visible {
        outline: 0;
        box-shadow: var(--ds-focus-ring);
    }

    /* ── Footer (pagination + "Showing N of M items"), token values ────── */
    .em-page .programme-dt-footer {
        padding: var(--ds-space-2) var(--ds-space-3);
        border-top: 1px solid var(--ds-line);
        background: var(--ds-surface);
    }

    .em-page .programme-dt-footer .paginate_button,
    .em-page .programme-dt-footer .pagination .page-link {
        border-radius: var(--ds-radius) !important;
        color: var(--ds-ink-muted) !important;
    }

    .em-page .programme-dt-footer .paginate_button.current,
    .em-page .programme-dt-footer .pagination .page-item.active .page-link {
        color: var(--ds-surface) !important;
        background: var(--ds-primary) !important;
        border-color: var(--ds-primary) !important;
    }

    .em-page .programme-dt-footer .paginate_button.first,
    .em-page .programme-dt-footer .paginate_button.last {
        display: none !important;
    }

    .em-page .programme-dt-count .dataTables_length select {
        border-color: var(--ds-line);
        border-radius: var(--ds-radius);
        color: var(--ds-ink);
    }

    .em-page .programme-dt-footer:empty {
        display: none;
    }

    /* ── Empty state (.ds-empty-state) ─────────────────────────────────── */
    .em-page .ds-empty-state .bi {
        display: block;
        margin-bottom: var(--ds-space-2);
        font-size: 2rem;
        color: var(--ds-ink-muted);
    }

    .em-page .em-empty-title {
        margin-bottom: var(--ds-space-1);
        font-weight: 600;
        color: var(--ds-ink);
    }

    /* ── Eligibility: Pay Scale → Unit Type → Unit Sub Type ────────────── */
    .em-page .em-chip {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        padding: 0.125rem var(--ds-space-2);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.4;
        white-space: normal;
        border-radius: var(--ds-radius);
    }

    .em-page .em-chip--type {
        color: var(--ds-primary);
        background: rgba(var(--bs-primary-rgb, 0 74 147), 0.08);
    }

    .em-page .em-chip--subtype {
        color: var(--ds-ink);
        background: var(--ds-surface-2);
        border: 1px solid var(--ds-line);
    }

    .em-page .em-flow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: var(--ds-space-2);
        margin: 0 0 var(--ds-space-3);
        padding: var(--ds-space-2) var(--ds-space-3);
        font-size: 0.875rem;
        color: var(--ds-ink);
        background: var(--ds-surface-2);
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius);
    }

    .em-page .em-flow__step {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-1);
        font-weight: 600;
    }

    .em-page .em-flow__arrow,
    .em-page .em-flow__note {
        color: var(--ds-ink-muted);
    }

    .em-page .em-flow__note {
        flex-basis: 100%;
    }

    /* ── Full-page Add / Edit form ─────────────────────────────────────── */
    .em-page .em-form-narrow {
        max-width: 35rem;
    }

    .em-page .em-form-narrow .form-control,
    .em-page .em-form-narrow .form-select {
        min-height: var(--ds-control-h);
        font-size: 0.875rem;
        color: var(--ds-ink);
        border-color: var(--ds-line);
        border-radius: var(--ds-radius);
    }

    .em-page .em-form-narrow .form-control:focus,
    .em-page .em-form-narrow .form-select:focus {
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }

    /* ── Add / Edit modal (.ds-modal) — modals take the 8px card radius ── */
    .em-modal .modal-dialog {
        max-width: 30rem;
    }

    .em-modal .modal-content {
        border-radius: var(--ds-radius-card);
    }

    .em-modal .form-control,
    .em-modal .form-select {
        border-color: var(--ds-line);
        border-radius: var(--ds-radius);
    }

    .em-modal .invalid-feedback {
        display: block;
    }

    .em-modal .form-control.is-invalid,
    .em-modal .form-select.is-invalid {
        border-color: var(--ds-secondary);
    }

    .em-modal .ds-btn-primary,
    .em-modal .ds-btn-cancel,
    .em-page .em-form-narrow .ds-btn-primary,
    .em-page .em-form-narrow .ds-btn-cancel {
        min-height: var(--ds-control-h);
        padding-top: 0;
        padding-bottom: 0;
    }

    /* sargam-app.css defines .ds-btn-cancel twice and the later copy hard-codes
       #f04438; pin it back to the documented token. */
    .em-modal .ds-btn-cancel,
    .em-page .em-form-narrow .ds-btn-cancel {
        color: var(--ds-secondary);
        border-color: var(--ds-secondary);
    }

    /* Destructive confirm keeps the brand red (--ds-secondary). */
    .em-modal .em-btn-delete {
        color: var(--ds-surface);
        background: var(--ds-secondary);
        border: 1px solid var(--ds-secondary);
    }

    .em-modal .em-btn-delete:hover {
        color: var(--ds-surface);
        filter: brightness(0.92);
    }

    @media (max-width: 575.98px) {
        .em-page .ds-page-header .ds-btn-primary {
            width: 100%;
            justify-content: center;
        }

        .em-page .ds-toolbar .programme-dt-search {
            width: 100%;
        }
    }
</style>
