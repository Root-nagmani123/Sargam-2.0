<style>
/* Notifications & notices — communications hub */
.comms-hub-feed-list {
    --comms-feed-accent: #004a93;
}
.comms-hub-section-header {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
}
.comms-hub-notification-card,
.comms-hub-notice-card.notice-feed-card {
    border: 1px solid var(--bs-border-color-translucent) !important;
    border-radius: 0.75rem !important;
    padding: 0 !important;
    margin-bottom: 0.75rem;
    background: #fff;
    overflow: visible;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
}
.comms-hub-desc-expandable .comms-hub-desc-preview {
    margin-top: 0;
    margin-bottom: 0;
}
.comms-hub-desc-expandable .comms-hub-desc-detail-block {
    margin-top: 0;
    padding-top: 0;
    border-top: 0 solid transparent;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: max-height 0.4s ease, opacity 0.25s ease, margin-top 0.25s ease, padding-top 0.25s ease, border-top-width 0.25s ease;
}
.comms-hub-desc-expandable .comms-hub-desc-detail-block .notice-feed-card-body {
    margin-top: 0;
    white-space: pre-wrap;
    word-break: break-word;
}
@media (hover: hover) and (pointer: fine) {
    .comms-hub-desc-expandable:hover .comms-hub-desc-detail-block,
    .comms-hub-desc-expandable:focus-within .comms-hub-desc-detail-block {
        margin-top: 0.65rem;
        padding-top: 0.65rem;
        border-top: 1px solid var(--bs-border-color-translucent);
        max-height: 32rem;
        opacity: 1;
        overflow: visible;
    }
}
@media (hover: none), (pointer: coarse) {
    .comms-hub-desc-expandable .comms-hub-desc-detail-block {
        margin-top: 0.65rem;
        padding-top: 0.65rem;
        border-top: 1px solid var(--bs-border-color-translucent);
        max-height: none;
        opacity: 1;
        overflow: visible;
    }
}
.comms-hub-notification-card .comms-hub-desc-wrap {
    padding: 0 1.15rem 1rem 4.25rem;
}
@media (max-width: 575.98px) {
    .comms-hub-notification-card .comms-hub-desc-wrap {
        padding-left: 1.15rem;
    }
}
.comms-hub-notice-card .comms-hub-desc-wrap {
    padding-top: 0;
}
.comms-hub-notification-card:hover,
.comms-hub-notice-card.notice-feed-card:hover {
    border-color: rgba(0, 74, 147, 0.22) !important;
    box-shadow: 0 0.35rem 1rem rgba(0, 74, 147, 0.1) !important;
    transform: translateY(-1px);
}
.comms-hub-notification-card.unread {
    border-color: rgba(0, 74, 147, 0.3) !important;
    background: linear-gradient(90deg, rgba(0, 74, 147, 0.07) 0%, #fff 12%);
}
.comms-hub-notification-card.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--comms-feed-accent);
    border-radius: 0.75rem 0 0 0.75rem;
}
.comms-hub-notification-card {
    position: relative;
}
.comms-hub-notification-card .comms-notification-open {
    padding: 1rem 1.15rem 1rem 1.25rem !important;
    color: inherit;
}
.comms-hub-notification-card .comms-notification-open:hover {
    background: rgba(0, 74, 147, 0.03);
}
.comms-hub-notice-card.notice-feed-card {
    padding: 1rem 1.15rem !important;
    border-left: 2px solid var(--comms-feed-accent) !important;
}
.comms-hub-notice-card.notice-feed-card.notice-feed-card-highlight {
    outline: 3px solid rgba(13, 110, 253, 0.35);
    outline-offset: 2px;
    background: #f8f9ff !important;
}
.comms-hub-feed-icon {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 1.1rem;
}
.comms-hub-notice-category-bar {
    background: var(--bs-body-tertiary);
    border-radius: 0.75rem;
    padding: 0.5rem 0.65rem;
}
.comms-hub-empty-state {
    border: 1px dashed var(--bs-border-color);
    border-radius: 0.75rem;
    background: var(--bs-body-tertiary);
}
@media (max-width: 575.98px) {
    .comms-hub-notification-card .notice-feed-card-meta {
        width: 100%;
        text-align: start !important;
    }
}
</style>
