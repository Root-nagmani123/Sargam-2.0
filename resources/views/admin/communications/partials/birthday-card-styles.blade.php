<style>
/* Dashboard-style birthday / wish cards (communications hub) */
.comms-hub-birthday-list .dashboard-birthday-item.card {
    border: 1px solid #e8ecf2 !important;
    border-radius: 0.65rem !important;
    background: #fff !important;
    box-shadow: none !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.comms-hub-birthday-list .dashboard-birthday-item.card:hover {
    border-color: #c5d4f0 !important;
    box-shadow: 0 2px 10px rgba(13, 110, 253, 0.08);
}
.comms-hub-birthday-list .dashboard-birthday-item .card-body {
    padding: 1rem 1.1rem !important;
}
.comms-hub-birthday-list .dashboard-birthday-row-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}
.comms-hub-birthday-list .dashboard-birthday-user {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    min-width: 0;
    flex: 1 1 auto;
}
.comms-hub-birthday-list .dashboard-birthday-avatar {
    width: 3rem;
    height: 3rem;
    font-size: 1rem;
}
.comms-hub-birthday-list .dashboard-birthday-name {
    font-weight: 700;
    font-size: 1rem;
    color: #0d6efd;
    line-height: 1.3;
    transition: color 0.2s ease;
}
.comms-hub-birthday-list .dashboard-birthday-item:hover .dashboard-birthday-name,
.comms-hub-birthday-list .dashboard-birthday-item:focus-within .dashboard-birthday-name {
    color: #212529;
}
.comms-hub-birthday-list .dashboard-birthday-designation {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-top: 0.15rem;
    line-height: 1.35;
}
.comms-hub-birthday-list .dashboard-birthday-contact-block,
.comms-hub-wish-detail-block {
    margin-top: 0;
    padding-top: 0;
    border-top: 0 solid transparent;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: max-height 0.4s ease, opacity 0.25s ease, margin-top 0.25s ease, padding-top 0.25s ease, border-top-width 0.25s ease;
}
@media (hover: hover) and (pointer: fine) {
    .comms-hub-birthday-list .dashboard-birthday-item:hover .dashboard-birthday-contact-block,
    .comms-hub-birthday-list .dashboard-birthday-item:focus-within .dashboard-birthday-contact-block,
    .comms-hub-wish-card:hover .comms-hub-wish-detail-block,
    .comms-hub-wish-card:focus-within .comms-hub-wish-detail-block {
        margin-top: 0.875rem;
        padding-top: 0.875rem;
        border-top: 1px solid #eef1f6;
        max-height: 24rem;
        opacity: 1;
        overflow: visible;
    }
}
@media (hover: none), (pointer: coarse) {
    .comms-hub-birthday-list .dashboard-birthday-contact-block,
    .comms-hub-wish-detail-block {
        margin-top: 0.875rem;
        padding-top: 0.875rem;
        border-top: 1px solid #eef1f6;
        max-height: none;
        opacity: 1;
        overflow: visible;
    }
}
.comms-hub-birthday-list .dashboard-birthday-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.comms-hub-birthday-list .dashboard-birthday-contact-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.9rem;
    border-radius: 0.375rem;
    background: #e8f2fc;
    color: #0b5ed7;
    font-size: 0.8125rem;
    font-weight: 500;
    max-width: 100%;
    min-width: 0;
}
.comms-hub-birthday-list .dashboard-birthday-contact-pill .material-icons,
.comms-hub-birthday-list .dashboard-birthday-contact-pill .material-symbols-rounded {
    font-size: 1.05rem !important;
    flex-shrink: 0;
}
.comms-hub-birthday-list .dashboard-birthday-btn-wish {
    font-weight: 600;
    padding: 0.4rem 0.95rem;
    border-width: 1.5px;
}
.comms-hub-birthday-today .dashboard-birthday-item {
    background: #fffafb !important;
}
.comms-hub-wish-card {
    border: 1px solid #e8ecf2;
    border-radius: 0.65rem;
    background: #fff;
    padding: 1rem 1.1rem;
    margin-bottom: 0.75rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.comms-hub-wish-card:hover {
    border-color: #c5d4f0;
    box-shadow: 0 2px 10px rgba(13, 110, 253, 0.08);
}
.comms-hub-wish-card.unread {
    border-color: rgba(0, 74, 147, 0.25);
    background: #f8fbff;
}
.comms-hub-wish-card .notice-feed-card-title {
    transition: color 0.2s ease;
}
.comms-hub-wish-card:hover .notice-feed-card-title {
    color: #212529;
}
.comms-hub-wish-detail-block .notice-feed-card-body {
    margin-top: 0;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
