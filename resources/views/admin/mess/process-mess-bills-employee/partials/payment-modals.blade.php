{{-- Bill receipt + Pay Now dialogs. Shared by the index grid and the
    Generate Invoice page, since both expose a per-row Payment action. --}}
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bill-receipt-modal-content">
            <div class="modal-header border-0 py-2 align-items-start">
                <h5 class="modal-title fw-bold" id="paymentDetailsModalLabel">Bill Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bill-receipt-modal-body">
                <div id="paymentDetailsContent" class="bill-receipt-content">
                    <div class="text-center py-4 text-muted">Loading...</div>
                </div>
                <div class="bill-receipt-actions">
                    <button type="button" class="btn btn-receipt-pay" id="paymentDetailsPayNowBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">payments</i> Pay Now
                    </button>
                    <button type="button" class="btn btn-receipt-print" id="paymentDetailsPrintBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">print</i> Print
                    </button>
                    <button type="button" class="btn btn-receipt-cancel" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pay Now (Payment Detail form) Modal - opens when user clicks "Pay Now" in Bill Receipt --}}
<div class="modal fade payment-detail-modal" id="payNowModal" tabindex="-1" aria-labelledby="payNowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content payment-detail-modal-content">
            <div class="modal-header payment-detail-modal-header">
                <h5 class="modal-title payment-detail-modal-title" id="payNowModalLabel">Payment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body payment-detail-modal-body">
                <form id="payNowForm">
                    @csrf
                    <div class="payment-detail-grid">
                        <div class="payment-detail-row">
                            <label class="payment-detail-label">Payment Mode</label>
                            <select name="payment_mode" id="payNowPaymentMode" class="payment-detail-input form-select  choices-select" data-placeholder="Select mode">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="deduct_from_salary">Deduct From Salary</option>
                                <option value="online">Online</option>
                            </select>
                            <div class="payment-detail-bank-wrap">
                                <label class="payment-detail-label">Bank Name</label>
                                <input type="text" name="bank_name" id="payNowBankName" class="payment-detail-input form-control " placeholder="Bank Name" autocomplete="off">
                            </div>
                        </div>
                        <div class="payment-detail-row payment-detail-cheque-row" id="payNowChequeRow">
                            <label class="payment-detail-label">Cheque Number</label>
                            <input type="text" name="cheque_number" id="payNowChequeNumber" class="payment-detail-input form-control " placeholder="Cheque Number" autocomplete="off">
                            <label class="payment-detail-label">Cheque Date</label>
                            <input type="text" name="cheque_date" id="payNowChequeDate" class="payment-detail-input form-control " value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                        <div class="payment-detail-row payment-detail-total-due-row">
                            <span class="payment-detail-label">Total Due Amount</span>
                            <span id="payNowTotalDueAmount" class="payment-detail-total-due-value">—</span>
                        </div>
                        <div class="payment-detail-row">
                            <label class="payment-detail-label">Amount</label>
                            <input type="number" name="amount" id="payNowAmount" class="payment-detail-input form-control " step="0.01" min="0" required placeholder="0.00">
                            <label class="payment-detail-label">Payment Date</label>
                            <input type="text" name="payment_date" id="payNowPaymentDate" class="payment-detail-input form-control " value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer payment-detail-modal-footer">
                <button type="button" class="btn payment-detail-save-btn" id="payNowSaveBtn">
                    <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">save</i> Save
                </button>
                <button type="button" class="btn payment-detail-cancel-btn" data-bs-dismiss="modal">
                    <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">close</i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>
