{{-- Opened by buttons with data-gtc-action="<modal id>" data-url="..." data-claim="..." data-total="..." --}}
<div class="modal fade" id="gtcApproveModal" tabindex="-1" aria-labelledby="gtcApproveTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" class="modal-content">@csrf
        <div class="modal-header"><h2 class="modal-title fs-5" id="gtcApproveTitle">Approve claim <span data-fill="claim"></span></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><label class="form-label" for="approve_remark">Remark (optional)</label>
            <textarea class="form-control" id="approve_remark" name="remark" rows="3" maxlength="1000" placeholder=""></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Approve and send to Accounts</button></div>
    </form></div>
</div>
<div class="modal fade" id="gtcReturnModal" tabindex="-1" aria-labelledby="gtcReturnTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" class="modal-content">@csrf
        <div class="modal-header"><h2 class="modal-title fs-5" id="gtcReturnTitle">Return claim <span data-fill="claim"></span></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><label class="form-label req" for="return_remark">What needs correcting</label>
            <textarea class="form-control" id="return_remark" name="remark" rows="3" maxlength="1000" required placeholder=""></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-outline-danger">Return to TA & Training</button></div>
    </form></div>
</div>
<div class="modal fade" id="gtcPayModal" tabindex="-1" aria-labelledby="gtcPayTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" class="modal-content">@csrf
        <div class="modal-header"><div><h2 class="modal-title fs-5" id="gtcPayTitle">Mark as paid</h2><p class="gtc-sub"><span data-fill="claim"></span>, <span data-fill="faculty"></span></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p class="mb-3">Total claimed: <b data-fill="totalText"></b></p>
            <label class="form-label" for="pay_amount">Amount paid (optional)</label>
            <input type="number" min="0" step="0.01" class="form-control" id="pay_amount" name="amount">
            <div class="form-text">Leave empty to record the full claimed amount.</div>
            <label class="form-label mt-3" for="pay_remark">Remark (optional)</label>
            <textarea class="form-control" id="pay_remark" name="remark" rows="3" maxlength="1000" placeholder="For example, paid through e-payment"></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success"><i class="bi bi-wallet2"></i> Confirm paid</button></div>
    </form></div>
</div>
