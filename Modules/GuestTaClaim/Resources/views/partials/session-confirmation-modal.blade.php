<div class="modal fade" id="gtcSessionConfirmModal" tabindex="-1" aria-labelledby="gtcSessionConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-5" id="gtcSessionConfirmTitle">Confirm session attendance</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-3 align-items-start">
                    <span class="rounded-circle bg-primary-subtle text-primary p-3"><i class="bi bi-calendar2-check fs-4"></i></span>
                    <div>
                        <p class="mb-1 fw-semibold" data-gtc-confirm-context>Review the session list before continuing.</p>
                        <p class="text-muted mb-0" data-gtc-confirm-message>Unconfirmed sessions will only be marked taken after you confirm.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Review sessions</button>
                <button type="button" class="btn btn-primary" data-gtc-confirm-sessions><i class="bi bi-check2-circle me-1"></i>Confirm and continue</button>
            </div>
        </div>
    </div>
</div>
