<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$userRole = $_SESSION['user_role'] ?? 'owner';
$apiToken = $_SESSION['api_token'] ?? '';

// Fetch Owners for Filter Dropdown
$db = getDbConnection();
$ownersList = $db->query("SELECT id, name, email FROM users WHERE role = 'owner' ORDER BY name ASC")->fetchAll();
$lodgesList = $db->query("SELECT id, name, city FROM properties ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Financial Transaction Ledger | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <style>
        .ledger-row {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .ledger-row:hover {
            background-color: #f1f5f9 !important;
        }
        .offcanvas-ledger {
            width: 480px !important;
            border-top-left-radius: 16px;
            border-bottom-left-radius: 16px;
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
                        <li class="breadcrumb-item"><a href="chart-flot.php">Financial Reports</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Financial Transaction Ledger</a></li>
                    </ol>
                </div>

                <!-- Ledger Container Card -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title font-w700 text-dark mb-0"><i class="fas fa-book text-primary me-2"></i>Official System Financial Transaction Ledger</h4>
                        <button type="button" id="btn-export-ledger" class="btn btn-outline-primary btn-sm font-w600">
                            <i class="fas fa-download me-1"></i> Export Ledger CSV
                        </button>
                    </div>
                    <div class="card-body p-3">
                        
                        <!-- Multi-Field Filter Bar -->
                        <div class="row g-2 mb-3 align-items-center">
                            <div class="col-xl-3 col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="ledger-search" class="form-control border-start-0" placeholder="Global search (Ref, guest)...">
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-3 col-6">
                                <input type="text" id="ledger-tx-id" class="form-control form-control-sm" placeholder="Tx ID (e.g. TX-00000001)">
                            </div>
                            <div class="col-xl-2 col-md-3 col-6">
                                <input type="text" id="ledger-booking-ref" class="form-control form-control-sm" placeholder="Booking Ref #">
                            </div>
                            <div class="col-xl-2 col-md-3 col-6">
                                <input type="number" id="ledger-min-amount" class="form-control form-control-sm" placeholder="Min Price (TSh)">
                            </div>
                            <div class="col-xl-3 col-md-3 col-6">
                                <input type="number" id="ledger-max-amount" class="form-control form-control-sm" placeholder="Max Price (TSh)">
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <input type="date" id="ledger-date-from" class="form-control form-control-sm" title="From Date">
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <input type="date" id="ledger-date-to" class="form-control form-control-sm" title="To Date">
                            </div>
                            <?php if ($userRole === 'admin'): ?>
                            <div class="col-xl-2 col-md-4">
                                <select id="ledger-owner-filter" class="form-select form-select-sm font-w600">
                                    <option value="">All Owners</option>
                                    <?php foreach ($ownersList as $o): ?>
                                        <option value="<?php echo $o['id']; ?>"><?php echo htmlspecialchars($o['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-xl-3 col-md-4">
                                <select id="ledger-lodge-filter" class="form-select form-select-sm font-w600">
                                    <option value="">All Lodges</option>
                                    <?php foreach ($lodgesList as $l): ?>
                                        <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name'] . ' (' . $l['city'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-xl-3 col-md-4">
                                <select id="ledger-type-filter" class="form-select form-select-sm font-w600">
                                    <option value="">All Transaction Types</option>
                                    <option value="Booking Payout">Booking Payout</option>
                                    <option value="Reservation">Reservation</option>
                                    <option value="Refund">Refund</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <select id="ledger-payment-filter" class="form-select form-select-sm font-w600">
                                    <option value="">All Payment Statuses</option>
                                    <option value="paid">Paid</option>
                                    <option value="pending">Pending</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <select id="ledger-payout-filter" class="form-select form-select-sm font-w600">
                                    <option value="">All Payout Statuses</option>
                                    <option value="Settled & Paid">Settled & Paid</option>
                                    <option value="Pending Settlement">Pending Settlement</option>
                                    <option value="Unconfirmed Reservation">Unconfirmed Reservation</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <select id="ledger-sort-by" class="form-select form-select-sm font-w600">
                                    <option value="created_at">Sort by Date (Newest)</option>
                                    <option value="total_price">Sort by Gross Amount</option>
                                    <option value="check_in">Sort by Check-In</option>
                                </select>
                            </div>
                            <div class="col-xl-3 col-md-4">
                                <button type="button" id="btn-apply-ledger-filters" class="btn btn-primary btn-sm w-100 font-w600">
                                    <i class="fas fa-filter me-1"></i> Apply Server Filters
                                </button>
                            </div>
                        </div>

                        <!-- Transaction Ledger Table -->
                        <div class="table-responsive bg-white border rounded-3 mb-3">
                            <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="ps-3">Transaction ID</th>
                                        <th>Date</th>
                                        <th>Owner</th>
                                        <th>Lodge</th>
                                        <th>Booking Ref</th>
                                        <th class="text-end">Gross Amount</th>
                                        <th class="text-end">Platform 10%</th>
                                        <th class="text-end">Owner 90%</th>
                                        <th class="text-center">Transaction Type</th>
                                        <th class="text-center">Payment Status</th>
                                        <th class="text-center">Payout Status</th>
                                        <th class="pe-3">Gateway Ref</th>
                                    </tr>
                                </thead>
                                <tbody id="ledger-tbody">
                                    <!-- Dynamic Ledger Rows -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <small class="text-muted font-w600" id="ledger-page-info">Showing page 1 of 1</small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0" id="ledger-pagination"></ul>
                            </nav>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Detailed Transaction Offcanvas Drawer -->
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

        <?php include 'elements/footer.php'; ?>
    </div>

    <?php include 'elements/page-js.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const apiToken = <?php echo json_encode($apiToken); ?>;
            let currentPage = 1;

            const tbody = document.getElementById('ledger-tbody');
            const pageInfo = document.getElementById('ledger-page-info');
            const paginationUl = document.getElementById('ledger-pagination');

            function formatCurrency(val) {
                return 'TSh ' + Math.round(val || 0).toLocaleString();
            }

            async function fetchLedger() {
                tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading real financial transaction ledger...</td></tr>`;

                const url = new URL('http://127.0.0.1:8000/api/finance/ledger');
                url.searchParams.append('page', currentPage);
                url.searchParams.append('per_page', 15);

                const search = document.getElementById('ledger-search').value.trim();
                const txId = document.getElementById('ledger-tx-id').value.trim();
                const bookingRef = document.getElementById('ledger-booking-ref').value.trim();
                const minAmount = document.getElementById('ledger-min-amount').value;
                const maxAmount = document.getElementById('ledger-max-amount').value;
                const dateFrom = document.getElementById('ledger-date-from').value;
                const dateTo = document.getElementById('ledger-date-to').value;
                const ownerId = document.getElementById('ledger-owner-filter') ? document.getElementById('ledger-owner-filter').value : '';
                const propertyId = document.getElementById('ledger-lodge-filter').value;
                const txType = document.getElementById('ledger-type-filter').value;
                const paymentStatus = document.getElementById('ledger-payment-filter').value;
                const payoutStatus = document.getElementById('ledger-payout-filter').value;
                const sortBy = document.getElementById('ledger-sort-by').value;

                if (search) url.searchParams.append('search', search);
                if (txId) url.searchParams.append('transaction_id', txId);
                if (bookingRef) url.searchParams.append('booking_reference', bookingRef);
                if (minAmount) url.searchParams.append('min_amount', minAmount);
                if (maxAmount) url.searchParams.append('max_amount', maxAmount);
                if (dateFrom) url.searchParams.append('date_from', dateFrom);
                if (dateTo) url.searchParams.append('date_to', dateTo);
                if (ownerId) url.searchParams.append('owner_id', ownerId);
                if (propertyId) url.searchParams.append('property_id', propertyId);
                if (txType) url.searchParams.append('transaction_type', txType);
                if (paymentStatus) url.searchParams.append('payment_status', paymentStatus);
                if (payoutStatus) url.searchParams.append('payout_status', payoutStatus);
                if (sortBy) url.searchParams.append('sort_by', sortBy);

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        }
                    });

                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const json = await res.json();
                    const items = json.data || [];

                    pageInfo.textContent = `Showing page ${json.current_page} of ${json.last_page} (${json.total} total transactions)`;

                    renderTable(items);
                    renderPagination(json.current_page, json.last_page);
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-danger"><i class="fas fa-exclamation-circle me-1"></i> Failed to load ledger records.</td></tr>`;
                }
            }

            function renderTable(items) {
                tbody.innerHTML = '';
                if (items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-muted">No financial transactions match your selected filter criteria.</td></tr>`;
                    return;
                }

                items.forEach(t => {
                    const txTypeBadge = (t.transaction_type === 'Booking Payout') ? 'bg-success' : ((t.transaction_type === 'Refund') ? 'bg-danger' : 'bg-info');
                    const paymentBadge = (t.payment_status === 'paid') ? 'bg-success' : ((t.payment_status === 'refunded') ? 'bg-danger' : 'bg-warning text-dark');
                    const payoutBadge = (t.payout_status === 'Settled & Paid') ? 'bg-success' : ((t.payout_status === 'Refunded') ? 'bg-danger' : 'bg-warning text-dark');

                    const tr = document.createElement('tr');
                    tr.className = 'ledger-row';
                    tr.innerHTML = `
                        <td class="ps-3 font-w700 text-primary">${t.transaction_id}</td>
                        <td><small class="text-muted font-w600">${t.date ? t.date.substring(0, 10) : 'N/A'}</small></td>
                        <td>
                            <strong class="text-dark d-block">${t.owner.name}</strong>
                            <small class="text-muted">${t.owner.email}</small>
                        </td>
                        <td>
                            <strong class="text-dark d-block">${t.lodge.name}</strong>
                            <small class="text-muted">${t.lodge.room_number}</small>
                        </td>
                        <td class="font-w700 text-dark">${t.booking_reference}</td>
                        <td class="text-end font-w600 text-dark">${formatCurrency(t.gross_amount)}</td>
                        <td class="text-end font-w600 text-danger">${formatCurrency(t.platform_fee_10)}</td>
                        <td class="text-end font-w700 text-success">${formatCurrency(t.owner_net_90)}</td>
                        <td class="text-center"><span class="badge ${txTypeBadge} font-w600">${t.transaction_type}</span></td>
                        <td class="text-center"><span class="badge ${paymentBadge} font-w600">${t.payment_status}</span></td>
                        <td class="text-center"><span class="badge ${payoutBadge} font-w600">${t.payout_status}</span></td>
                        <td class="pe-3"><small class="text-muted font-w600">${t.payment_reference}</small></td>
                    `;

                    tr.addEventListener('click', () => openDrawer(t));
                    tbody.appendChild(tr);
                });
            }

            function openDrawer(t) {
                document.getElementById('drawer-tx-id').textContent = t.transaction_id;
                document.getElementById('drawer-gross-amount').textContent = formatCurrency(t.gross_amount);
                document.getElementById('drawer-tx-date').textContent = 'Date: ' + (t.date || 'N/A');

                document.getElementById('drawer-gross').textContent = formatCurrency(t.gross_amount);
                document.getElementById('drawer-platform-fee').textContent = formatCurrency(t.platform_fee_10) + ` (${t.commission_rate}%)`;
                document.getElementById('drawer-owner-net').textContent = formatCurrency(t.owner_net_90) + ` (${100 - t.commission_rate}%)`;

                document.getElementById('drawer-booking-ref').textContent = t.booking_reference;
                document.getElementById('drawer-booking-status').textContent = t.booking_status;
                document.getElementById('drawer-check-in').textContent = t.check_in || 'N/A';
                document.getElementById('drawer-check-out').textContent = t.check_out || 'N/A';

                document.getElementById('drawer-guest-name').textContent = t.guest.name;
                document.getElementById('drawer-guest-email').textContent = t.guest.email;
                document.getElementById('drawer-owner-name').textContent = t.owner.name;
                document.getElementById('drawer-owner-email').textContent = t.owner.email;
                document.getElementById('drawer-lodge-name').textContent = `${t.lodge.name} (${t.lodge.room_number})`;

                document.getElementById('drawer-payment-status').textContent = t.payment_status;
                document.getElementById('drawer-payout-status').textContent = t.payout_status;
                document.getElementById('drawer-gateway-ref').textContent = t.payment_reference;

                const bsDrawer = new bootstrap.Offcanvas(document.getElementById('ledgerDrawer'));
                bsDrawer.show();
            }

            function renderPagination(current, last) {
                paginationUl.innerHTML = '';
                if (last <= 1) return;

                for (let p = 1; p <= last; p++) {
                    const li = document.createElement('li');
                    li.className = `page-item ${p === current ? 'active' : ''}`;
                    li.innerHTML = `<a class="page-link" href="javascript:void(0)">${p}</a>`;
                    li.addEventListener('click', () => {
                        currentPage = p;
                        fetchLedger();
                    });
                    paginationUl.appendChild(li);
                }
            }

            document.getElementById('btn-apply-ledger-filters').addEventListener('click', function() {
                currentPage = 1;
                fetchLedger();
            });

            // Debounced Search Input
            let searchTimer;
            document.getElementById('ledger-search').addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    currentPage = 1;
                    fetchLedger();
                }, 400);
            });

            // CSV Export Handler
            document.getElementById('btn-export-ledger').addEventListener('click', async function() {
                alert('Exporting official transaction ledger CSV...');
            });

            // Initial fetch
            fetchLedger();
        });
    </script>
</body>
</html>