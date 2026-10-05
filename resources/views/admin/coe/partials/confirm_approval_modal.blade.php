{{-- COE question papers — Send for Approval confirm. Shared by every coe-* grid;
     open it with CoeGrid.confirm({ modal: '#qpApprovalModal', confirm: '#qpApprovalConfirm', … })
     in public/js/coe-grid.js. --}}
<div class="modal fade" id="qpApprovalModal" tabindex="-1" aria-labelledby="qpApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="coe-confirm">
                <div class="coe-confirm__icon coe-confirm__icon--approve" aria-hidden="true">
                    {{-- Hand offering a tick. --}}
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.5 6.5l2 2 4.5-5"/>
                        <path d="M2.5 13.5h3v7h-3z"/>
                        <path d="M5.5 14.5h4.25a1.75 1.75 0 0 1 0 3.5H8.5"/>
                        <path d="M5.5 19.5h8l6.6-4.2a1.6 1.6 0 0 0-1.7-2.7l-4.3 2.4"/>
                    </svg>
                </div>
                <h5 class="coe-confirm__title" id="qpApprovalModalLabel">Send for Approval?</h5>
                <p class="coe-confirm__text">Are you sure you want to send this for approval?</p>
                <div class="coe-confirm__actions">
                    <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn coe-btn coe-btn-primary" id="qpApprovalConfirm">Confirm Verify</button>
                </div>
            </div>
        </div>
    </div>
</div>
