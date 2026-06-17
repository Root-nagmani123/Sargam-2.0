{{-- Bootstrap Icons (loaded here so icons render in BOTH the timetable and master layouts) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    /* ===== Session Moderator / Escort Duty — modern UI (scoped to .otmdo) ===== */
    .otmdo {
        --otmdo-primary: #004a93;
        --otmdo-primary-soft: #eaf1f9;
        --otmdo-border: #e9edf2;
        --otmdo-muted: #6b7280;
        --otmdo-ink: #111827;
    }

    /* Cards */
    .otmdo .otmdo-card {
        border: 1px solid var(--otmdo-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 6px 18px rgba(16, 24, 40, .05);
    }

    /* Header */
    .otmdo .otmdo-title {
        font-weight: 800;
        letter-spacing: -.01em;
        color: var(--otmdo-ink);
    }

    .otmdo .otmdo-id-label {
        font-size: .8125rem;
        color: var(--otmdo-muted);
    }

    .otmdo .otmdo-id-value {
        font-size: .8125rem;
        font-weight: 700;
        color: var(--otmdo-ink);
    }

    .otmdo .otmdo-back {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--otmdo-ink);
        border: 1px solid var(--otmdo-border);
        background: #fff;
        transition: background-color .15s ease, color .15s ease;
        text-decoration: none;
    }

    .otmdo .otmdo-back:hover {
        background: var(--otmdo-primary-soft);
        color: var(--otmdo-primary);
    }

    /* Summary stat cards */
    .otmdo .otmdo-stat {
        border: 1px solid var(--otmdo-border);
        border-left: 4px solid var(--otmdo-primary);
        border-radius: 12px;
        background: #fff;
        height: 100%;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .otmdo .otmdo-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 24, 40, .08);
    }

    .otmdo .otmdo-stat-label {
        font-size: .8125rem;
        font-weight: 600;
        color: var(--otmdo-muted);
    }

    .otmdo .otmdo-stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        color: var(--otmdo-ink);
    }

    .otmdo .otmdo-stat-ico {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex: 0 0 auto;
    }

    /* Filter toolbar */
    .otmdo .otmdo-toolbar {
        border: 1px solid var(--otmdo-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }

    .otmdo .otmdo-toolbar .form-label {
        font-size: .75rem;
        font-weight: 600;
        color: var(--otmdo-muted);
        margin-bottom: .25rem;
    }

    .otmdo .otmdo-toolbar .form-select,
    .otmdo .otmdo-toolbar .form-control {
        border-radius: 9px;
        font-size: .875rem;
        border-color: #dfe4ea;
    }

    .otmdo .otmdo-toolbar .form-select:focus,
    .otmdo .otmdo-toolbar .form-control:focus {
        border-color: var(--otmdo-primary);
        box-shadow: 0 0 0 .2rem rgba(0, 74, 147, .12);
    }

    /* Table */
    .otmdo .otmdo-table {
        margin-bottom: 0;
    }

    .otmdo .otmdo-table thead th {
        background: #f5f7fa;
        color: var(--otmdo-muted);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
        border: 0;
        padding: .85rem 1rem;
        vertical-align: middle;
    }

    .otmdo .otmdo-table tbody td {
        padding: .95rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f2f5;
        color: #374151;
        font-size: .9rem;
    }

    .otmdo .otmdo-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .otmdo .otmdo-table tbody tr {
        transition: background-color .15s ease;
    }

    .otmdo .otmdo-table tbody tr:hover {
        background: #f8fafc;
    }

    .otmdo .otmdo-date {
        font-weight: 600;
        color: #1f2937;
        white-space: nowrap;
    }

    .otmdo .otmdo-time {
        font-size: .8rem;
        color: var(--otmdo-muted);
        white-space: nowrap;
    }

    /* Duty-type pills */
    .otmdo .otmdo-badge {
        display: inline-flex;
        align-items: center;
        font-weight: 600;
        font-size: .75rem;
        padding: .35rem .75rem;
        border-radius: 999px;
        line-height: 1.1;
    }

    .otmdo .otmdo-badge-escort {
        background: var(--otmdo-primary-soft);
        color: var(--otmdo-primary);
    }

    .otmdo .otmdo-badge-mdo {
        background: #e7f6ec;
        color: #1a7f4b;
    }

    .otmdo .otmdo-badge-neutral {
        background: #f1f3f5;
        color: #495057;
    }

    /* Status pills */
    .otmdo .otmdo-status-pending {
        background: #fff4e0;
        color: #b76e00;
    }

    .otmdo .otmdo-status-completed {
        background: #e7f6ec;
        color: #1a7f4b;
    }

    /* Clickable stat card */
    .otmdo a.otmdo-stat {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .otmdo .otmdo-empty {
        border: 1px dashed #d8dde3;
        border-radius: 12px;
        background: #f9fafb;
    }

    @media (max-width: 575.98px) {
        .otmdo .otmdo-header-actions {
            width: 100%;
        }

        .otmdo .otmdo-stat-value {
            font-size: 1.5rem;
        }
    }

    @media print {
        .otmdo .otmdo-no-print {
            display: none !important;
        }
    }
</style>
