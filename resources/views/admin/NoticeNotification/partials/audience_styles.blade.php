{{-- Styling for the cascading target-audience picker (create + edit forms). --}}
<style>
    .audience-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .audience-panel-title {
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #004a93;
        margin-bottom: 0.75rem;
    }

    .audience-preview {
        max-height: 180px;
        overflow-y: auto;
        padding: 0.5rem;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
    }

    .audience-chip {
        display: inline-block;
        margin: 0.125rem;
        padding: 0.125rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #1e293b;
        white-space: nowrap;
    }

    /* Choices.js sits inside .form-control-styled rows — match the form's borders. */
    .audience-panel .choices__inner {
        border-color: #cbd5e1;
        border-radius: 0.375rem;
        background: #fff;
        font-size: 0.875rem;
        min-height: 2.5rem;
    }

    .audience-panel .choices.is-focused .choices__inner {
        border-color: #004a93;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.12);
    }

    .audience-panel .choices__list--multiple .choices__item {
        background-color: #004a93;
        border-color: #004a93;
        font-size: 0.75rem;
    }

    .audience-panel .choices__list--dropdown {
        z-index: 30;
    }

    .audience-panel .js-audience-select-all,
    .audience-panel .js-audience-clear {
        font-size: 0.75rem;
        text-decoration: none;
    }
</style>
