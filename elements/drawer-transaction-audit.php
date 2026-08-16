<!-- Detailed Transaction Offcanvas Drawer Component -->
<div class="offcanvas offcanvas-end offcanvas-ledger shadow-lg" tabindex="-1" id="ledgerDrawer" aria-labelledby="ledgerDrawerLabel">
    <div class="offcanvas-header bg-primary text-white py-3">
        <h5 class="offcanvas-title font-w700 text-white" id="ledgerDrawerLabel"><i class="fas fa-file-invoice-dollar me-2"></i>Transaction Audit Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4" style="background: #f8fafc;">
        
        <div class="text-center pb-3 border-bottom mb-3">
            <span class="badge bg-light text-primary border font-w700 fs-13 mb-1" id="drawer-tx-id">TX-00000000</span>
            <h3 class="font-w700 text-dark mb-0" id="drawer-gross-amount">TSh 0</h3>
            <small class="text-muted" id="drawer-tx-date">Date: -</small>
        </div>

        <!-- Financial Split Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <h6 class="font-w700 text-dark border-bottom pb-2 mb-3"><i class="fas fa-calculator me-1 text-primary"></i> Financial Split Breakdown</h6>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted font-w500">Gross Reservation Price:</span>
                    <strong class="text-dark" id="drawer-gross">-</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted font-w500">Platform Commission (10%):</span>
                    <strong class="text-danger" id="drawer-platform-fee">-</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-top pt-2 mt-1">
                    <span class="text-dark font-w700">Net Owner Payout (90%):</span>
                    <strong class="text-success fs-16" id="drawer-owner-net">-</strong>
                </div>
            </div>
        </div>

        <!-- Booking & Stay Information -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <h6 class="font-w700 text-dark border-bottom pb-2 mb-3"><i class="fas fa-calendar-check me-1 text-primary"></i> Booking Details</h6>
                <div class="row g-2 text-sm">
                    <div class="col-6"><span class="text-muted font-w500">Booking Code:</span> <strong class="text-primary d-block" id="drawer-booking-ref">-</strong></div>
                    <div class="col-6"><span class="text-muted font-w500">Booking Status:</span> <span class="badge bg-success d-inline-block" id="drawer-booking-status">-</span></div>
                    <div class="col-6"><span class="text-muted font-w500">Check In:</span> <span class="text-dark font-w600 d-block" id="drawer-check-in">-</span></div>
                    <div class="col-6"><span class="text-muted font-w500">Check Out:</span> <span class="text-dark font-w600 d-block" id="drawer-check-out">-</span></div>
                </div>
            </div>
        </div>

        <!-- Parties Involved Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <h6 class="font-w700 text-dark border-bottom pb-2 mb-3"><i class="fas fa-users me-1 text-primary"></i> Parties & Lodge</h6>
                <div class="mb-2">
                    <span class="text-muted font-w500 d-block small">Guest Customer:</span>
                    <strong class="text-dark" id="drawer-guest-name">-</strong>
                    <small class="text-muted d-block" id="drawer-guest-email">-</small>
                </div>
                <div class="mb-2">
                    <span class="text-muted font-w500 d-block small">Property Owner:</span>
                    <strong class="text-dark" id="drawer-owner-name">-</strong>
                    <small class="text-muted d-block" id="drawer-owner-email">-</small>
                </div>
                <div>
                    <span class="text-muted font-w500 d-block small">Lodge & Room:</span>
                    <strong class="text-dark" id="drawer-lodge-name">-</strong>
                </div>
            </div>
        </div>

        <!-- Payment & Payout Status Card -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3">
                <h6 class="font-w700 text-dark border-bottom pb-2 mb-3"><i class="fas fa-credit-card me-1 text-primary"></i> Payment Reference</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted font-w500">Payment Status:</span>
                    <span class="badge bg-success" id="drawer-payment-status">paid</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted font-w500">Payout Status:</span>
                    <span class="badge bg-warning text-dark" id="drawer-payout-status">Pending Settlement</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted font-w500">Gateway Ref:</span>
                    <strong class="text-dark font-w600" id="drawer-gateway-ref">-</strong>
                </div>
            </div>
        </div>

    </div>
</div>
