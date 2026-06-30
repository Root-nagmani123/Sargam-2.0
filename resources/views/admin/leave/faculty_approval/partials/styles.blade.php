<style>
    /* ── Filter controls (mirrors PT Exemption toolbar) ── */
    .faculty-leave-approval-page .fl-filter-select {
        width: 180px;
        min-height: 40px;
        height: 40px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        font-size: 0.9375rem;
        color: #344054;
        padding: 0.5rem 2.25rem 0.5rem 0.875rem;
        background-position: right 0.75rem center;
    }

    .faculty-leave-approval-page .fl-filter-select:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }

    .faculty-leave-approval-page .fl-daterange-wrap {
        position: relative;
    }

    .faculty-leave-approval-page .fl-daterange-input {
        width: 215px;
        padding-left: 2.25rem;
        padding-right: 0.875rem;
        cursor: pointer;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .faculty-leave-approval-page .fl-daterange-input::placeholder {
        color: #344054;
    }

    .faculty-leave-approval-page .fl-daterange-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667085;
        font-size: 0.95rem;
        pointer-events: none;
    }

    .faculty-leave-approval-page .fl-download-btn {
        height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 1.1rem;
        font-size: 0.9375rem;
        font-weight: 500;
        color: #004a93;
        border-radius: 8px;
        background: #fff;
    }

    .faculty-leave-approval-page .fl-download-btn:hover {
        color: #004a93;
        background: #fff;
    }

    .faculty-leave-approval-page .fl-download-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    /* ── Status badges ── */
    .faculty-leave-approval-page .approval-status {
        font-size: 0.8125rem;
        font-weight: 500;
        padding: 0.35rem 0.75rem;
        line-height: 1.2;
    }

    .faculty-leave-approval-page .approval-status--pending {
        background: #fef0c7;
        color: #b54708;
    }

    .faculty-leave-approval-page .approval-status--approved {
        background: #ecfdf3;
        color: #027a48;
    }

    .faculty-leave-approval-page .approval-status--rejected {
        background: #fef3f2;
        color: #b42318;
    }

    /* ── Icon row actions ── */
    .faculty-leave-approval-page .approval-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.85rem;
        height: 1.85rem;
        padding: 0;
        border: 0;
        background: transparent;
        border-radius: 6px;
        line-height: 1;
        cursor: pointer;
        text-decoration: none;
        transition: color 0.15s ease, background-color 0.15s ease, transform 0.15s ease;
    }

    .faculty-leave-approval-page .approval-action-btn:hover {
        transform: translateY(-1px);
    }

    .faculty-leave-approval-page .approval-action-btn i {
        font-size: 1.05rem;
    }

    .faculty-leave-approval-page .approval-action-btn--view {
        color: #475467;
    }

    .faculty-leave-approval-page .approval-action-btn--view:hover {
        color: #101828;
        background: #f2f4f7;
    }

    .faculty-leave-approval-page .approval-action-btn--approve {
        color: #12b76a;
    }

    .faculty-leave-approval-page .approval-action-btn--approve:hover {
        color: #027a48;
        background: #ecfdf3;
    }

    .faculty-leave-approval-page .approval-action-btn--reject {
        color: #f04438;
    }

    .faculty-leave-approval-page .approval-action-btn--reject:hover {
        color: #b42318;
        background: #fef3f2;
    }

    @media (max-width: 767.98px) {
        .faculty-leave-approval-page .fl-filter-select,
        .faculty-leave-approval-page .fl-daterange-input {
            width: 100%;
        }
    }
</style>
