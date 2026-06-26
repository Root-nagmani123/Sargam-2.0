<style>
    .leave-module .leave-apply-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .06);
    }

    .leave-module .leave-apply-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #101828;
        margin-bottom: 0;
    }

    .leave-module .leave-apply-divider {
        border-color: #eaecf0;
        margin: 1rem 0 1.5rem;
        opacity: 1;
    }

    .leave-module .leave-type-tabs {
        display: inline-flex;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }

    .leave-module .leave-type-tabs .btn {
        border: none;
        border-radius: 0;
        border-bottom: 3px solid transparent;
        padding: 0.55rem 1.35rem;
        font-weight: 600;
        color: #344054;
        background: #fff;
        box-shadow: none;
    }

    .leave-module .leave-type-tabs .btn:hover:not(.active):not(.disabled) {
        background: #f9fafb;
        color: #004a93;
    }

    .leave-module .leave-type-tabs .btn.active {
        background: #e8f0fb;
        color: #004a93;
        border-bottom-color: #004a93;
    }

    .leave-module .leave-form-row {
        display: grid;
        grid-template-columns: minmax(160px, 220px) 1fr;
        gap: 0.75rem 1.25rem;
        align-items: center;
        margin-bottom: 1.1rem;
    }

    .leave-module .leave-form-row.align-top {
        align-items: start;
    }

    .leave-module .leave-form-label {
        font-weight: 600;
        color: #344054;
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .leave-module .leave-form-field .form-control,
    .leave-module .leave-form-field .form-select {
        border-color: #d0d5dd;
        border-radius: 8px;
        min-height: 42px;
    }

    .leave-module .leave-form-field .form-control:focus,
    .leave-module .leave-form-field .form-select:focus {
        border-color: #84adff;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    .leave-module .leave-form-field .form-control[readonly] {
        background: #f2f4f7;
        color: #667085;
    }

    .leave-module .leave-date-wrap {
        position: relative;
    }

    .leave-module .leave-date-wrap .form-control {
        padding-right: 2.5rem;
    }

    .leave-module .leave-date-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667085;
        pointer-events: none;
        font-size: 20px;
    }

    .leave-module .leave-aside-card {
        border: 1px solid #b6d4fe;
        background: #f0f6ff;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
    }

    .leave-module .leave-aside-card + .leave-aside-card {
        margin-top: 1rem;
    }

    .leave-module .leave-aside-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #004a93;
        margin-bottom: 0.65rem;
        font-size: 0.95rem;
    }

    .leave-module .leave-aside-head .material-icons {
        font-size: 22px;
    }

    .leave-module .pt-balance-value {
        color: #198754;
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .leave-module .pt-balance-as-on {
        color: #667085;
        font-size: 0.82rem;
        margin-top: 0.15rem;
    }

    .leave-module .leave-note-text {
        color: #344054;
        font-size: 0.88rem;
        line-height: 1.5;
        margin-bottom: 0;
    }

    .leave-module .attachment-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #101828;
        margin-bottom: 0.85rem;
    }

    .leave-module .attachment-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .leave-module .attachment-table thead th {
        background: #004a93;
        color: #fff;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border: none;
        padding: 0.75rem 0.85rem;
        font-weight: 600;
    }

    .leave-module .attachment-table tbody td {
        vertical-align: middle;
        padding: 0.65rem 0.85rem;
        border-color: #eaecf0;
    }

    .leave-module .leave-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1.5rem;
    }

    .leave-module .leave-actions .btn-cancel {
        background: #f2f4f7;
        border: 1px solid #d0d5dd;
        color: #344054;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
    }

    .leave-module .leave-actions .btn-draft {
        border: 1px solid #004a93;
        color: #004a93;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        background: #fff;
    }

    .leave-module .leave-actions .btn-submit {
        background: #004a93;
        border-color: #004a93;
        font-weight: 600;
        padding: 0.5rem 1.35rem;
        border-radius: 8px;
    }

    @media (max-width: 767.98px) {
        .leave-module .leave-form-row {
            grid-template-columns: 1fr;
            gap: 0.35rem;
        }
    }

    /* Shared with balance page */
    .leave-module .pt-balance-card {
        border: 1px solid #d1e7dd;
        background: #f8fff9;
        border-radius: 0.75rem;
    }

    .leave-module .info-alert {
        background: #e7f1ff;
        border: 1px solid #b6d4fe;
        color: #084298;
        border-radius: 0.5rem;
    }
</style>
