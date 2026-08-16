<!-- Request Payout Modal Component -->
<div class="modal fade" id="requestPayoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px;">
            <form id="form-request-payout">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-w700 text-white"><i class="fas fa-hand-holding-usd me-2"></i>Request Owner Payout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="payout-request-alert" class="alert alert-danger d-none mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark">Payout Amount (TSh)</label>
                        <input type="number" step="0.01" min="1000" id="req-amount" class="form-control font-w700 fs-16" placeholder="e.g. 90000" required>
                        <small class="text-muted d-block mt-1">Cannot exceed available balance.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark">Payment Method</label>
                        <select id="req-method" class="form-select font-w600">
                            <option value="Mobile Money">Mobile Money (M-Pesa / TigoPesa / AirtelMoney)</option>
                            <option value="Bank Transfer">Bank Transfer (CRDB / NMB / Stanbic)</option>
                            <option value="Manual">Manual Over-the-Counter Cash</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark">Account Details / Phone / Bank No</label>
                        <input type="text" id="req-account" class="form-control" placeholder="e.g. M-Pesa: +255 712 345 678" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark">Notes / Reference Remarks</label>
                        <textarea id="req-notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-w600" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btn-submit-payout-req" class="btn btn-success font-w600"><i class="fas fa-paper-plane me-1"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Admin Update Payout Status Workflow Modal Component -->
<div class="modal fade" id="updatePayoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px;">
            <form id="form-update-payout">
                <input type="hidden" id="update-payout-id" value="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-w700 text-white" id="update-payout-title"><i class="fas fa-tasks me-2"></i>Process Owner Payout Workflow</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="payout-update-alert" class="alert alert-danger d-none mb-3"></div>

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted font-w500">Payout Ref:</span>
                            <strong class="text-primary font-w700" id="update-ref-text">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted font-w500">Amount:</span>
                            <strong class="text-dark font-w700" id="update-amount-text">-</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted font-w500">Current Status:</span>
                            <span class="badge bg-warning text-dark font-w600" id="update-status-badge">REQUESTED</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark">Advance Workflow Status To:</label>
                        <select id="update-new-status" class="form-select font-w700">
                            <option value="PROCESSING">PROCESSING (Verification & Transfer Initiated)</option>
                            <option value="PAID">PAID (Settled & Transferred Permanently)</option>
                            <option value="FAILED">FAILED (Rejected or Transfer Error)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-w600 text-dark">Audit Remarks / Bank Transaction Ref</label>
                        <textarea id="update-notes" class="form-control" rows="3" placeholder="Enter bank reference number or rejection reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-w600" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btn-submit-payout-update" class="btn btn-primary font-w600"><i class="fas fa-save me-1"></i> Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
