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
    <title>Financial Intelligence Dashboard | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .intel-card {
            border-radius: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .intel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
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
                
                <div class="row page-titles align-items-center">
                    <div class="col-sm-6">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="chart-flot.php">Finance</a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)">Financial Intelligence & Analytics</a></li>
                        </ol>
                    </div>
                    <!-- Date Range Filter Selector -->
                    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                        <div class="d-inline-flex gap-2 align-items-center">
                            <select id="date-range-select" class="form-select font-w700 shadow-sm" style="width: 180px;">
                                <option value="today">Today</option>
                                <option value="7_days">Last 7 Days</option>
                                <option value="30_days" selected>Last 30 Days</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <div id="custom-date-container" class="d-none d-flex gap-1">
                                <input type="date" id="range-date-from" class="form-control form-control-sm">
                                <input type="date" id="range-date-to" class="form-control form-control-sm">
                                <button type="button" id="btn-apply-custom-range" class="btn btn-primary btn-sm font-w600">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="intel-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading Financial Intelligence...</span>
                    </div>
                    <p class="text-muted font-w600 mt-3">Executing real backend aggregation queries...</p>
                </div>

                <!-- Error State -->
                <div id="intel-error" class="alert alert-danger d-none alert-dismissible fade show my-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="intel-error-message">Failed to load backend financial intelligence data.</span>
                </div>

                <!-- Main Financial Intelligence Content -->
                <div id="intel-content" class="d-none">

                    <!-- Summary Cards Row (6 Required Cards) -->
                    <div class="row g-3 mb-4">
                        <!-- Card 1: TOTAL OWNER EARNINGS -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card intel-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted font-w600">TOTAL OWNER EARNINGS (90%)</span>
                                        <span class="p-2 bg-success text-white rounded-circle"><i class="fas fa-hand-holding-usd fs-18"></i></span>
                                    </div>
                                    <h3 class="font-w700 text-success mb-1" id="card-total-owner-earnings">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Net payouts payable to owners</small>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: PLATFORM COMMISSION -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card intel-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted font-w600">PLATFORM COMMISSION (10%)</span>
                                        <span class="p-2 bg-danger text-white rounded-circle"><i class="fas fa-percentage fs-18"></i></span>
                                    </div>
                                    <h3 class="font-w700 text-danger mb-1" id="card-platform-commission">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-shield-alt text-danger me-1"></i>FastNet Stays platform revenue</small>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: GROSS BOOKING VALUE -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card intel-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted font-w600">GROSS BOOKING VALUE</span>
                                        <span class="p-2 bg-primary text-white rounded-circle"><i class="fas fa-wallet fs-18"></i></span>
                                    </div>
                                    <h3 class="font-w700 text-dark mb-1" id="card-gross-booking-value">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-calendar-check text-primary me-1"></i>Total guest reservation volume</small>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: PENDING PAYOUTS -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card intel-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted font-w600">PENDING PAYOUTS</span>
                                        <span class="p-2 bg-warning text-white rounded-circle"><i class="fas fa-clock fs-18"></i></span>
                                    </div>
                                    <h3 class="font-w700 text-dark mb-1" id="card-pending-payouts">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-hourglass-half text-warning me-1"></i>Requested / Processing workflow</small>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: COMPLETED PAYOUTS -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card intel-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted font-w600">COMPLETED PAYOUTS</span>
                                        <span class="p-2 bg-info text-white rounded-circle"><i class="fas fa-check-double fs-18"></i></span>
                                    </div>
                                    <h3 class="font-w700 text-info mb-1" id="card-completed-payouts">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-building text-info me-1"></i>Permanently settled bank/mobile payouts</small>
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: REFUNDS -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card intel-card border-0 shadow-sm bg-white h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted font-w600">REFUNDS</span>
                                        <span class="p-2 bg-secondary text-white rounded-circle"><i class="fas fa-undo-alt fs-18"></i></span>
                                    </div>
                                    <h3 class="font-w700 text-secondary mb-1" id="card-refunds">TSh 0</h3>
                                    <small class="text-muted"><i class="fas fa-times-circle text-secondary me-1"></i>Total cancelled stay refunds</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Charts Section -->
                    <div class="row mb-4">
                        <!-- Chart 1: Revenue Over Time & Split Breakdown -->
                        <div class="col-xl-8 col-lg-12 mb-3">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title font-w700 text-dark mb-0"><i class="fas fa-chart-area text-primary me-2"></i>Financial Performance Over Time</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 320px;">
                                        <canvas id="chart-financial-trends"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart 2: Payouts vs Commission Distribution -->
                        <div class="col-xl-4 col-lg-12 mb-3">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h5 class="card-title font-w700 text-dark mb-0"><i class="fas fa-chart-pie text-success me-2"></i>Financial Allocation</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div style="height: 250px;">
                                        <canvas id="chart-financial-doughnut"></canvas>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <small class="text-muted font-w600 d-block"><i class="fas fa-shield-alt text-success me-1"></i> Authoritative 90/10 Allocation</small>
                                    </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const apiToken = <?php echo json_encode($apiToken); ?>;

            const loadingEl = document.getElementById('intel-loading');
            const errorEl = document.getElementById('intel-error');
            const errorMsgEl = document.getElementById('intel-error-message');
            const contentEl = document.getElementById('intel-content');

            const dateRangeSelect = document.getElementById('date-range-select');
            const customContainer = document.getElementById('custom-date-container');
            const btnApplyCustom = document.getElementById('btn-apply-custom-range');

            let trendChartInstance = null;
            let doughnutChartInstance = null;

            function formatCurrency(amount) {
                return 'TSh ' + Math.round(amount || 0).toLocaleString();
            }

            dateRangeSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customContainer.classList.remove('d-none');
                } else {
                    customContainer.classList.add('d-none');
                    fetchFinancialIntelligence(this.value);
                }
            });

            btnApplyCustom.addEventListener('click', function() {
                const dateFrom = document.getElementById('range-date-from').value;
                const dateTo = document.getElementById('range-date-to').value;
                if (!dateFrom || !dateTo) {
                    alert('Please select both From and To dates.');
                    return;
                }
                fetchFinancialIntelligence('custom', dateFrom, dateTo);
            });

            async function fetchFinancialIntelligence(range = '30_days', dateFrom = null, dateTo = null) {
                loadingEl.classList.remove('d-none');
                errorEl.classList.add('d-none');
                contentEl.classList.add('d-none');

                const url = new URL('http://127.0.0.1:8000/api/finance/overview');
                url.searchParams.append('date_range', range);
                if (range === 'custom') {
                    if (dateFrom) url.searchParams.append('date_from', dateFrom);
                    if (dateTo) url.searchParams.append('date_to', dateTo);
                }

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        }
                    });

                    if (!res.ok) throw new Error(`HTTP ${res.status}: Backend query error.`);
                    const data = await res.json();

                    const cards = data.summary_cards || {};
                    const series = data.chart_series || [];

                    // Populate Summary Cards
                    document.getElementById('card-total-owner-earnings').textContent = formatCurrency(cards.total_owner_earnings);
                    document.getElementById('card-platform-commission').textContent = formatCurrency(cards.platform_commission);
                    document.getElementById('card-gross-booking-value').textContent = formatCurrency(cards.gross_booking_value);
                    document.getElementById('card-pending-payouts').textContent = formatCurrency(cards.pending_payouts);
                    document.getElementById('card-completed-payouts').textContent = formatCurrency(cards.completed_payouts);
                    document.getElementById('card-refunds').textContent = formatCurrency(cards.refunds);

                    // Render Financial Trends Chart
                    renderTrendsChart(series);

                    // Render Financial Allocation Doughnut Chart
                    renderDoughnutChart(cards);

                    loadingEl.classList.add('d-none');
                    contentEl.classList.remove('d-none');
                } catch (err) {
                    loadingEl.classList.add('d-none');
                    errorMsgEl.textContent = err.message || 'Failed to execute backend financial queries.';
                    errorEl.classList.remove('d-none');
                }
            }

            function renderTrendsChart(series) {
                const labels = series.map(s => s.period);
                const revenueData = series.map(s => s.revenue);
                const ownerData = series.map(s => s.owner_earnings);
                const feeData = series.map(s => s.platform_commission);
                const payoutData = series.map(s => s.payouts);
                const refundData = series.map(s => s.refunds);

                const ctx = document.getElementById('chart-financial-trends').getContext('2d');
                if (trendChartInstance) trendChartInstance.destroy();

                trendChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Gross Revenue',
                                data: revenueData,
                                borderColor: '#007bff',
                                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Owner Earnings (90%)',
                                data: ownerData,
                                borderColor: '#28a745',
                                backgroundColor: 'transparent',
                                borderDash: [5, 5],
                                tension: 0.3,
                            },
                            {
                                label: 'Platform Fee (10%)',
                                data: feeData,
                                borderColor: '#dc3545',
                                backgroundColor: 'transparent',
                                tension: 0.3,
                            },
                            {
                                label: 'Paid Out',
                                data: payoutData,
                                borderColor: '#17a2b8',
                                backgroundColor: 'transparent',
                                tension: 0.3,
                            },
                            {
                                label: 'Refunds',
                                data: refundData,
                                borderColor: '#6c757d',
                                backgroundColor: 'transparent',
                                tension: 0.3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.dataset.label + ': ' + formatCurrency(ctx.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(val) { return 'TSh ' + Math.round(val).toLocaleString(); }
                                }
                            }
                        }
                    }
                });
            }

            function renderDoughnutChart(cards) {
                const ctx = document.getElementById('chart-financial-doughnut').getContext('2d');
                if (doughnutChartInstance) doughnutChartInstance.destroy();

                doughnutChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Owner Net (90%)', 'Platform Fee (10%)', 'Refunds'],
                        datasets: [{
                            data: [
                                cards.total_owner_earnings || 0,
                                cards.platform_commission || 0,
                                cards.refunds || 0
                            ],
                            backgroundColor: ['#28a745', '#dc3545', '#6c757d'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.label + ': ' + formatCurrency(ctx.raw);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Initial fetch
            fetchFinancialIntelligence('30_days');
        });
    </script>
</body>
</html>