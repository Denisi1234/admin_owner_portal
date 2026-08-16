<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$userRole = $_SESSION['user_role'] ?? 'owner';
$userName = $_SESSION['user_name'] ?? 'Property Owner';
$userEmail = $_SESSION['user_email'] ?? 'owner@fastnet.com';
$apiToken = $_SESSION['api_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Payout Invoices & Statements | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
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
                        <li class="breadcrumb-item"><a href="chart-flot.php">Lodge Finance</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Payout Invoices & Statements</a></li>
                    </ol>
                </div>

                <!-- Loading State -->
                <div id="invoice-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading Payout Invoices...</span>
                    </div>
                    <p class="text-muted font-w600 mt-3">Retrieving real payout statement records from backend...</p>
                </div>

                <!-- Error State -->
                <div id="invoice-error" class="alert alert-danger d-none alert-dismissible fade show my-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="invoice-error-message">Unable to load payout statements. Please ensure backend is online.</span>
                </div>

                <!-- Main Invoice Container -->
                <div id="invoice-content" class="d-none">
                    <div class="card mt-3 border-0 shadow-sm rounded-3">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title font-w700 text-dark mb-0">FastNetStays Official Payout Statement</h4>
                                <small class="text-muted" id="statement-ref">Statement Ref: STMT-<?php echo date('Ym'); ?></small>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm font-w600" onclick="window.print();">
                                <i class="fas fa-print me-1"></i> Print Statement
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <h6 class="font-w700 text-primary mb-2">Platform Issuer:</h6>
                                    <div class="font-w700 text-dark">FastNetStays Platform Services Ltd.</div>
                                    <div class="text-muted small">Financial Settlement Division</div>
                                    <div class="text-muted small">Dar es Salaam, Tanzania</div>
                                    <div class="text-muted small">Support Email: finance@fastnetstays.com</div>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <h6 class="font-w700 text-primary mb-2">Beneficiary Owner:</h6>
                                    <div class="font-w700 text-dark"><?php echo htmlspecialchars($userName); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($userEmail); ?></div>
                                    <div class="text-muted small">Role: <?php echo ucfirst(htmlspecialchars($userRole)); ?></div>
                                    <div class="text-muted small">Settlement Currency: <strong>TZS</strong></div>
                                </div>
                            </div>

                            <!-- Payout Items Table -->
                            <div class="table-responsive">
                                <table class="table table-striped align-middle" style="font-size: 14px;">
                                    <thead class="bg-light text-muted">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Booking Code</th>
                                            <th>Guest</th>
                                            <th>Property / Room</th>
                                            <th class="text-end">Gross Booking</th>
                                            <th class="text-end">FastNet (10%)</th>
                                            <th class="text-end pe-3">Owner Net (90%)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-items-tbody">
                                        <!-- Dynamic Invoice Item Rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Totals Table -->
                            <div class="row mt-4">
                                <div class="col-lg-5 col-sm-5 ms-auto">
                                    <table class="table table-clear">
                                        <tbody>
                                            <tr>
                                                <td class="left font-w600 text-muted">Total Gross Bookings</td>
                                                <td class="right font-w700 text-dark" id="invoice-subtotal">TSh 0</td>
                                            </tr>
                                            <tr>
                                                <td class="left font-w600 text-muted">Total FastNet Commission (10%)</td>
                                                <td class="right font-w700 text-danger" id="invoice-commission">TSh 0</td>
                                            </tr>
                                            <tr class="border-top border-2 border-primary">
                                                <td class="left font-w700 text-dark fs-16">Total Net Payout Payable (90%)</td>
                                                <td class="right font-w700 text-primary fs-18" id="invoice-total">TSh 0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php include 'elements/footer.php'; ?>
    </div>

    <?php include 'elements/page-js.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const loadingEl = document.getElementById('invoice-loading');
            const errorEl = document.getElementById('invoice-error');
            const errorMsgEl = document.getElementById('invoice-error-message');
            const contentEl = document.getElementById('invoice-content');

            const apiToken = <?php echo json_encode($apiToken); ?>;

            function formatCurrency(amount) {
                return 'TSh ' + Math.round(amount || 0).toLocaleString();
            }

            try {
                const res = await fetch('http://127.0.0.1:8000/api/finance/overview', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${apiToken}`
                    }
                });

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: Unable to retrieve payout invoice data.`);
                }

                const data = await res.json();
                const m = data.metrics || {};
                const txs = data.recent_transactions || [];

                document.getElementById('invoice-subtotal').textContent = formatCurrency(m.gross_revenue);
                document.getElementById('invoice-commission').textContent = formatCurrency(m.platform_fee);
                document.getElementById('invoice-total').textContent = formatCurrency(m.net_earnings);

                const itemsTbody = document.getElementById('invoice-items-tbody');
                itemsTbody.innerHTML = '';

                if (txs.length === 0) {
                    itemsTbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No confirmed booking payout statements found for this period.</td>
                        </tr>
                    `;
                } else {
                    txs.forEach((t, idx) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="ps-3 font-w600 text-muted">${idx + 1}</td>
                            <td class="font-w700 text-primary">${t.booking_code}</td>
                            <td class="font-w600 text-dark">${t.guest_name}</td>
                            <td>${t.property_name} (${t.room_number})</td>
                            <td class="text-end font-w600 text-dark">${formatCurrency(t.gross_amount)}</td>
                            <td class="text-end font-w600 text-danger">${formatCurrency(t.platform_fee)}</td>
                            <td class="text-end pe-3 font-w700 text-success">${formatCurrency(t.net_payout)}</td>
                        `;
                        itemsTbody.appendChild(tr);
                    });
                }

                loadingEl.classList.add('d-none');
                contentEl.classList.remove('d-none');

            } catch (err) {
                loadingEl.classList.add('d-none');
                errorMsgEl.textContent = err.message || 'Failed to fetch invoice statement records.';
                errorEl.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>