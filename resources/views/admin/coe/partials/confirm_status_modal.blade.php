{{-- COE — Activate / Deactivate confirm. One dialog, two variants: the script
     (CoeGrid.confirmStatus in public/js/coe-grid.js) sets .is-activate or
     .is-deactivate on the root and fills in the copy for the row clicked. --}}
<div class="modal fade" id="coeStatusModal" tabindex="-1" aria-labelledby="coeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="coe-confirm coe-status-confirm">
                <div class="coe-confirm__icon coe-status-confirm__icon" aria-hidden="true">
                    <i class="bi bi-toggle-on"></i>
                </div>
                <h5 class="coe-confirm__title" id="coeStatusModalLabel">Activate this record?</h5>
                <p class="coe-confirm__text" id="coeStatusModalText">Are you sure you want to activate this record?</p>
                <div class="coe-confirm__actions">
                    <button type="button" class="btn coe-btn coe-status-confirm__cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn coe-btn coe-status-confirm__ok" id="coeStatusConfirm">Yes</button>
                </div>
            </div>
        </div>
    </div>
</div>
