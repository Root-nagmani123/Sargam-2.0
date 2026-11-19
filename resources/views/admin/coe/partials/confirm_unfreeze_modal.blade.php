{{-- COE question papers — Unfreeze confirm (administrator, Published only).
     Open it with CoeGrid.confirm({ modal: '#qpUnfreezeModal', confirm: '#qpUnfreezeConfirm', … })
     in public/js/coe-grid.js. --}}
<div class="modal fade" id="qpUnfreezeModal" tabindex="-1" aria-labelledby="qpUnfreezeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="coe-confirm">
                <div class="coe-confirm__icon coe-confirm__icon--unfreeze" aria-hidden="true">
                    <i class="bi bi-unlock"></i>
                </div>
                <h5 class="coe-confirm__title" id="qpUnfreezeModalLabel">Unfreeze Question Paper?</h5>
                <p class="coe-confirm__text">
                    The paper will no longer be published. It goes back to the faculty, who can
                    edit it or freeze it again.
                </p>
                {{-- Required: the reason is saved on the paper, logged and sent to the faculty and Rajbhasha. --}}
                <div class="text-start mt-3">
                    <label for="qpUnfreezeRemarks" class="form-label small fw-semibold mb-1">Remarks <span class="text-danger">*</span></label>
                    <textarea id="qpUnfreezeRemarks" class="form-control coe-control" rows="3" maxlength="500"
                              placeholder="Why is this question paper being unfrozen?" aria-describedby="qpUnfreezeRemarksError"></textarea>
                    <div class="coe-field-error d-none" id="qpUnfreezeRemarksError">Enter the reason for unfreezing.</div>
                </div>
                <div class="coe-confirm__actions">
                    <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn coe-btn coe-btn-primary" id="qpUnfreezeConfirm">Yes, Unfreeze</button>
                </div>
            </div>
        </div>
    </div>
</div>
