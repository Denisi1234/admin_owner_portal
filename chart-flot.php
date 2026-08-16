<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$userRole = $_SESSION['user_role'] ?? 'owner';
$apiToken = $_SESSION['api_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Lodge Financial Performance | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <style>
        .finance-card {
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .finance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06) !important;
        }
    </style>
</head>

<body>
    <?php include 'elements/pre-loader.php'; ?>

    <div id="main-wrapper">
        <?php include 'elements/nav-header.php'; ?>
        <?php include 'elements/chatbox.php'; ?>
        <?php include 'elements/header.php'; ?>
        <?php include 'elements/sidebar.php'; ?>

        <div class="content-body">
            <div class="container-fluid pb-5">
                
                <div class="row page-titles">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Finance</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Earnings & Financial Performance</a></li>
                    </ol>
                </div>

                <!-- Loading State -->
                <div id="finance-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading Financial Performance Data...</span>
                    </div>
                    <p class="text-muted font-w600 mt-3">Fetching real-time financial metrics from backend...</p>
                </div>

                <!-- Error State -->
                <div id="finance-error" class="alert alert-danger d-none alert-dismissible fade show my-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="finance-error-message">Unable to load financial reports. Please ensure you are logged in.</span>
                </div>

                <!-- Main Content (Hidden until loaded) -->
                <div id="finance-content" class="d-none">

                    <!-- Metric Cards Row -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-sm-6 mb-3">
                            <div class="card finance-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="text-muted font-w600">Gross Revenue</span>
                                        <span class="p-2 bg-success text-white rounded-circle">
                                            <i class="fas fa-wallet fs-18"></i>
                                        </span>
                                    </div>
                                    <h3 class="font-w700 text-dark mb-1" id="metric-gross-revenue">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>From confirmed & paid stays</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6 mb-3">
                            <div class="card finance-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="text-muted font-w600">Net Owner Payout (90%)</span>
                                        <span class="p-2 bg-primary text-white rounded-circle">
                                            <i class="fas fa-money-bill-wave fs-18"></i>
                                        </span>
                                    </div>
                                    <h3 class="font-w700 text-primary mb-1" id="metric-net-earnings">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-shield-alt text-primary me-1"></i>Authoritative 90% share per stay</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6 mb-3">
                            <div class="card finance-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="text-muted font-w600">Pending Revenue</span>
                                        <span class="p-2 bg-warning text-white rounded-circle">
                                            <i class="fas fa-clock fs-18"></i>
                                        </span>
                                    </div>
                                    <h3 class="font-w700 text-dark mb-1" id="metric-pending-revenue">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-hourglass-half text-warning me-1"></i>Unconfirmed reservation pipeline</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6 mb-3">
                            <div class="card finance-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="text-muted font-w600">Confirmed Bookings</span>
                                        <span class="p-2 bg-info text-white rounded-circle">
                                            <i class="fas fa-calendar-check fs-18"></i>
                                        </span>
                                    </div>
                                    <h3 class="font-w700 text-dark mb-1" id="metric-confirmed-count">0</h3>
                                    <small class="text-muted"><i class="fas fa-list-alt me-1"></i><span id="metric-total-count">0</span> total reservations</small>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Real Owner Payout Management Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="card-title font-w700 text-dark mb-0"><i class="fas fa-hand-holding-usd text-success me-2"></i>Owner Payout Management & Workflow</h5>
                                    <div class="d-flex gap-2">
                                        <select id="payout-status-filter" class="form-select form-select-sm font-w600" style="width: 180px;">
                                            <option value="">All Payout Statuses</option>
                                            <option value="REQUESTED">REQUESTED</option>
                                            <option value="PROCESSING">PROCESSING</option>
                                            <option value="PAID">PAID</option>
                                            <option value="FAILED">FAILED</option>
                                        </select>
                                        <button type="button" class="btn btn-success btn-sm font-w600" data-bs-toggle="modal" data-bs-target="#requestPayoutModal">
                                            <i class="fas fa-plus-circle me-1"></i> Request Payout
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 13.5px;">
                                            <thead class="bg-light text-muted">
                                                <tr>
                                                    <th class="ps-4">Reference</th>
                                                    <th>Owner</th>
                                                    <th>Lodge</th>
                                                    <th class="text-end">Amount</th>
                                                    <th>Payment Method</th>
                                                    <th class="text-center">Status Workflow</th>
                                                    <th>Processed By</th>
                                                    <th>Date</th>
                                                    <?php if ($userRole === 'admin'): ?><th class="text-end pe-4">Action</th><?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody id="payouts-tbody">
                                                <!-- Dynamic Payout History Rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="empty-payouts-state" class="text-center py-4 d-none">
                                        <small class="text-muted font-w600"><i class="fas fa-receipt me-1"></i> No payout records found.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions & Invoices Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title font-w700 text-dark mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Recent Booking Transactions & Payout Breakdown</h5>
                                    <a href="ecom-invoice.php" class="btn btn-outline-primary btn-sm font-w600">View Invoices</a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 14px;">
                                            <thead class="bg-light text-muted">
                                                <tr>
                                                    <th class="ps-4">Booking Ref</th>
                                                    <th>Guest Name</th>
                                                    <th>Lodge / Room</th>
                                                    <th class="text-end">Gross Amount</th>
                                                    <th class="text-end">Platform (10%)</th>
                                                    <th class="text-end">Owner Net (90%)</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="finance-transactions-tbody">
                                                <!-- Dynamic transaction rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                                <!-- Dynamic transaction rows -->
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Empty Transactions State -->
                                    <div id="empty-transactions-state" class="text-center py-5 d-none">
                                        <i class="fas fa-receipt fa-3x text-muted opacity-50 mb-3"></i>
                                        <h5 class="font-w700 text-dark">No Financial Transactions Yet</h5>
                                        <p class="text-muted mb-0">Once guests place confirmed bookings for your lodge, their transaction records will appear here.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Payout Request & Workflow Modals -->
        <?php include 'elements/modal-payout-operations.php'; ?>

        <?php include 'elements/footer.php'; ?>
    </div>

    <?php include 'elements/page-js.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const loadingEl = document.getElementById('finance-loading');
            const errorEl = document.getElementById('finance-error');
            const errorMsgEl = document.getElementById('finance-error-message');
            const contentEl = document.getElementById('finance-content');

            const apiToken = <?php echo json_encode($apiToken); ?>;
            const userRole = <?php echo json_encode($userRole); ?>;

            function formatCurrency(amount) {
                return 'TSh ' + Math.round(amount || 0).toLocaleString();
            }

            async function fetchFinanceData() {
                try {
                    const res = await fetch('http://127.0.0.1:8000/api/finance/overview', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}: Failed to retrieve finance data.`);
                    }

                    const data = await res.json();
                    const m = data.metrics || {};
                    const monthly = data.monthly_chart || [];
                    const txs = data.recent_transactions || [];

                    // Render metrics
                    document.getElementById('metric-gross-revenue').textContent = formatCurrency(m.gross_revenue);
                    document.getElementById('metric-net-earnings').textContent = formatCurrency(m.net_earnings);
                    document.getElementById('metric-pending-revenue').textContent = formatCurrency(m.pending_revenue);
                    document.getElementById('metric-confirmed-count').textContent = (m.confirmed_bookings || 0).toLocaleString();
                    document.getElementById('metric-total-count').textContent = (m.total_bookings || 0).toLocaleString();





                    // Render transactions table
                    const txTbody = document.getElementById('finance-transactions-tbody');
                    const emptyTxState = document.getElementById('empty-transactions-state');
                    txTbody.innerHTML = '';

                    if (txs.length === 0) {
                        emptyTxState.classList.remove('d-none');
                    } else {
                        emptyTxState.classList.add('d-none');
                        txs.forEach(t => {
                            const tr = document.createElement('tr');
                            const statusClass = (t.status === 'Confirmed' || t.status === 'Completed' || t.payment_status === 'paid') ? 'bg-success' : ((t.status === 'Cancelled') ? 'bg-danger' : 'bg-warning text-dark');
                            tr.innerHTML = `
                                <td class="ps-4 font-w700 text-primary">${t.booking_code}</td>
                                <td>
                                    <div class="font-w600 text-dark">${t.guest_name}</div>
                                    <small class="text-muted">${t.guest_email}</small>
                                </td>
                                <td>
                                    <div class="font-w600 text-dark">${t.property_name}</div>
                                    <small class="text-muted">${t.room_number}</small>
                                </td>
                                <td class="text-end font-w600 text-dark">${formatCurrency(t.gross_amount)}</td>
                                <td class="text-end font-w600 text-danger">${formatCurrency(t.platform_fee)} <span class="badge bg-light text-muted font-w500 small">(${t.commission_rate}%)</span></td>
                                <td class="text-end font-w700 text-success">${formatCurrency(t.net_payout)} <span class="badge bg-light text-success font-w600 small">(${t.owner_share_percent}%)</span></td>
                                <td class="text-center">
                                    <span class="badge ${statusClass} font-w600 px-3 py-1">${t.status}</span>
                                </td>
                            `;
                            txTbody.appendChild(tr);
                        });
                    }

                    loadingEl.classList.add('d-none');
                    contentEl.classList.remove('d-none');

                } catch (err) {
                    loadingEl.classList.add('d-none');
                    errorMsgEl.textContent = err.message || 'Failed to load financial records from backend.';
                    errorEl.classList.remove('d-none');
                }
            }

            // Real Payout Table Fetcher
            const payoutsTbody = document.getElementById('payouts-tbody');
            const emptyPayoutsState = document.getElementById('empty-payouts-state');
            const payoutStatusFilter = document.getElementById('payout-status-filter');

            async function fetchPayouts() {
                const status = payoutStatusFilter.value;
                let url = 'http://127.0.0.1:8000/api/payouts';
                if (status) url += '?status=' + encodeURIComponent(status);

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        }
                    });
                    if (!res.ok) throw new Error('Payout fetch error');
                    const json = await res.json();
                    const list = json.data || [];

                    payoutsTbody.innerHTML = '';
                    if (list.length === 0) {
                        emptyPayoutsState.classList.remove('d-none');
                    } else {
                        emptyPayoutsState.classList.add('d-none');
                        list.forEach(p => {
                            let badgeClass = 'bg-warning text-dark';
                            if (p.status === 'PROCESSING') badgeClass = 'bg-info';
                            if (p.status === 'PAID') badgeClass = 'bg-success';
                            if (p.status === 'FAILED') badgeClass = 'bg-danger';

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="ps-4 font-w700 text-primary">${p.payout_reference}</td>
                                <td>
                                    <div class="font-w600 text-dark">${p.owner ? p.owner.name : 'Owner #' + p.owner_id}</div>
                                    <small class="text-muted">${p.owner ? p.owner.email : ''}</small>
                                </td>
                                <td>${p.property ? p.property.name : 'All Lodges'}</td>
                                <td class="text-end font-w700 text-dark">${formatCurrency(p.amount)}</td>
                                <td>
                                    <div class="font-w600 text-dark">${p.payment_method}</div>
                                    <small class="text-muted">${p.account_details || ''}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge ${badgeClass} font-w700 px-3 py-1">${p.status}</span>
                                </td>
                                <td><small class="text-muted font-w600">${p.processor ? p.processor.name : 'Automated System'}</small></td>
                                <td><small class="text-muted">${p.created_at ? p.created_at.substring(0,10) : ''}</small></td>
                                ${userRole === 'admin' ? `
                                    <td class="text-end pe-4">
                                        ${p.status !== 'PAID' && p.status !== 'FAILED' ? `
                                            <button type="button" class="btn btn-outline-primary btn-xs font-w600 btn-process-payout"
                                                data-id="${p.id}"
                                                data-ref="${p.payout_reference}"
                                                data-amount="${p.amount}"
                                                data-status="${p.status}">
                                                <i class="fas fa-edit me-1"></i> Process
                                            </button>
                                        ` : '<span class="text-muted small">Completed</span>'}
                                    </td>
                                ` : ''}
                            `;
                            payoutsTbody.appendChild(tr);
                        });

                        document.querySelectorAll('.btn-process-payout').forEach(btn => {
                            btn.addEventListener('click', function() {
                                document.getElementById('update-payout-id').value = this.dataset.id;
                                document.getElementById('update-ref-text').textContent = this.dataset.ref;
                                document.getElementById('update-amount-text').textContent = formatCurrency(this.dataset.amount);
                                document.getElementById('update-status-badge').textContent = this.dataset.status;

                                const updateModal = new bootstrap.Modal(document.getElementById('updatePayoutModal'));
                                updateModal.show();
                            });
                        });
                    }
                } catch (e) {
                    payoutsTbody.innerHTML = `<tr><td colspan="9" class="text-center py-3 text-danger">Failed to retrieve payout history.</td></tr>`;
                }
            }

            payoutStatusFilter.addEventListener('change', fetchPayouts);

            // Handle Request Payout Form Submit
            document.getElementById('form-request-payout').addEventListener('submit', async function(e) {
                e.preventDefault();
                const errDiv = document.getElementById('payout-request-alert');
                errDiv.classList.add('d-none');

                const amount = document.getElementById('req-amount').value;
                const method = document.getElementById('req-method').value;
                const account = document.getElementById('req-account').value;
                const notes = document.getElementById('req-notes').value;

                try {
                    const res = await fetch('http://127.0.0.1:8000/api/payouts/request', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        },
                        body: JSON.stringify({
                            amount: parseFloat(amount),
                            payment_method: method,
                            account_details: account,
                            notes: notes
                        })
                    });

                    const jsonRes = await res.json();
                    if (!res.ok) {
                        throw new Error(jsonRes.message || 'Payout request failed.');
                    }

                    bootstrap.Modal.getInstance(document.getElementById('requestPayoutModal')).hide();
                    this.reset();
                    await fetchFinanceData();
                    await fetchPayouts();
                } catch (err) {
                    errDiv.textContent = err.message;
                    errDiv.classList.remove('d-none');
                }
            });

            // Handle Process Status Form Submit
            document.getElementById('form-update-payout').addEventListener('submit', async function(e) {
                e.preventDefault();
                const errDiv = document.getElementById('payout-update-alert');
                errDiv.classList.add('d-none');

                const id = document.getElementById('update-payout-id').value;
                const status = document.getElementById('update-new-status').value;
                const notes = document.getElementById('update-notes').value;

                try {
                    const res = await fetch(`http://127.0.0.1:8000/api/payouts/${id}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        },
                        body: JSON.stringify({
                            status: status,
                            notes: notes
                        })
                    });

                    const jsonRes = await res.json();
                    if (!res.ok) {
                        throw new Error(jsonRes.message || 'Payout update failed.');
                    }

                    bootstrap.Modal.getInstance(document.getElementById('updatePayoutModal')).hide();
                    this.reset();
                    await fetchFinanceData();
                    await fetchPayouts();
                } catch (err) {
                    errDiv.textContent = err.message;
                    errDiv.classList.remove('d-none');
                }
            });

            // Initial loads
            await fetchFinanceData();
            await fetchPayouts();
        });
    </script>
</body>
</html>