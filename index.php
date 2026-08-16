<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'admin';
$userId = $_SESSION['user_id'] ?? 2;
$apiToken = $_SESSION['api_token'] ?? '';

// Fetch live properties from database
if ($userRole === 'admin') {
    $properties = $db->query("
        SELECT p.*, u.name as host_name, u.email as host_email,
               (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) as room_count
        FROM properties p
        LEFT JOIN users u ON p.host_id = u.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT p.*, u.name as host_name, u.email as host_email,
               (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) as room_count
        FROM properties p
        LEFT JOIN users u ON p.host_id = u.id
        WHERE p.host_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId]);
    $properties = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>FastNet Dashboard Overview | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
	<?php include 'elements/meta.php';?>
	<link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
	<?php include 'elements/page-css.php'; ?>
	<style>
		.stat-card-link {
			text-decoration: none !important;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			display: block;
		}
		.stat-card-link:hover {
			transform: translateY(-3px);
			box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
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
							<li class="breadcrumb-item active"><a href="javascript:void(0)"><?php echo ($userRole === 'admin') ? 'Super Admin Control Center' : 'Lodge Owner Portal'; ?></a></li>
							<li class="breadcrumb-item"><a href="javascript:void(0)">Real-Time Platform Intelligence</a></li>
						</ol>
					</div>
					<!-- Date Range Selector -->
					<div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
						<div class="d-inline-flex gap-2 align-items-center">
							<select id="dash-date-range" class="form-select font-w700 shadow-sm" style="width: 170px;">
								<option value="today">Today</option>
								<option value="yesterday">Yesterday</option>
								<option value="7_days">Last 7 Days</option>
								<option value="30_days" selected>Last 30 Days</option>
								<option value="this_month">This Month</option>
								<option value="last_month">Last Month</option>
								<option value="custom">Custom Range</option>
							</select>
							<div id="dash-custom-range" class="d-none d-flex gap-1">
								<input type="date" id="dash-date-from" class="form-control form-control-sm">
								<input type="date" id="dash-date-to" class="form-control form-control-sm">
								<button type="button" id="btn-dash-apply-range" class="btn btn-primary btn-sm font-w600">Apply</button>
							</div>
						</div>
					</div>
                </div>

				<!-- Metric Cards Grid (Interactive Links) -->
				<div class="row g-3 mb-4">
					<!-- Card 1: Total Lodge Owners -->
					<div class="col-xl-3 col-sm-6">
						<a href="ecom-customers.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-primary text-white rounded-circle"><i class="fas fa-users-cog fs-18"></i></span>
										<span class="badge bg-light text-primary font-w600">Owners</span>
									</div>
									<h2 class="font-w700 text-dark mt-2 mb-0" id="stat-total-owners">0</h2>
									<small class="text-muted"><span id="stat-active-owners" class="text-success font-w600">0</span> Active / <span id="stat-suspended-owners" class="text-danger font-w600">0</span> Suspended</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 2: Total Lodges -->
					<div class="col-xl-3 col-sm-6">
						<a href="ecom-customers.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-success text-white rounded-circle"><i class="fas fa-hotel fs-18"></i></span>
										<span class="badge bg-light text-success font-w600">Lodges</span>
									</div>
									<h2 class="font-w700 text-dark mt-2 mb-0" id="stat-total-lodges">0</h2>
									<small class="text-muted"><span id="stat-approved-lodges" class="text-success font-w600">0</span> Active / <span id="stat-pending-lodges" class="text-warning font-w600">0</span> Pending</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 3: Total Rooms -->
					<div class="col-xl-3 col-sm-6">
						<a href="room-list.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-info text-white rounded-circle"><i class="fas fa-bed fs-18"></i></span>
										<span class="badge bg-light text-info font-w600">Rooms</span>
									</div>
									<h2 class="font-w700 text-dark mt-2 mb-0" id="stat-total-rooms">0</h2>
									<small class="text-muted"><i class="fas fa-door-open me-1"></i>Platform-wide inventory</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 4: Total Customers -->
					<div class="col-xl-3 col-sm-6">
						<a href="guest-list.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-warning text-white rounded-circle"><i class="fas fa-user-friends fs-18"></i></span>
										<span class="badge bg-light text-warning font-w600">Guests</span>
									</div>
									<h2 class="font-w700 text-dark mt-2 mb-0" id="stat-total-customers">0</h2>
									<small class="text-muted"><i class="fas fa-check-circle text-warning me-1"></i>Registered travelers</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 5: Bookings Breakdown -->
					<div class="col-xl-3 col-sm-6">
						<a href="chart-chartjs.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-secondary text-white rounded-circle"><i class="fas fa-calendar-check fs-18"></i></span>
										<span class="badge bg-light text-dark font-w600">Bookings</span>
									</div>
									<h2 class="font-w700 text-dark mt-2 mb-0" id="stat-total-bookings">0</h2>
									<small class="text-muted"><span id="stat-confirmed-bookings" class="text-success font-w600">0</span> Paid / <span id="stat-cancelled-bookings" class="text-danger font-w600">0</span> Cancelled</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 6: Gross Booking Value -->
					<div class="col-xl-3 col-sm-6">
						<a href="chart-chartist.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-primary text-white rounded-circle"><i class="fas fa-wallet fs-18"></i></span>
										<span class="badge bg-light text-primary font-w600">Gross</span>
									</div>
									<h3 class="font-w700 text-dark mt-2 mb-0" id="stat-gross-value">TSh 0</h3>
									<small class="text-muted"><i class="fas fa-coins me-1 text-primary"></i>Total reservation volume</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 7: Platform Commission (10%) -->
					<div class="col-xl-3 col-sm-6">
						<a href="chart-chartist.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-danger text-white rounded-circle"><i class="fas fa-percentage fs-18"></i></span>
										<span class="badge bg-light text-danger font-w600">FastNet 10%</span>
									</div>
									<h3 class="font-w700 text-danger mt-2 mb-0" id="stat-platform-fee">TSh 0</h3>
									<small class="text-muted"><i class="fas fa-shield-alt text-danger me-1"></i>Platform revenue share</small>
								</div>
							</div>
						</a>
					</div>

					<!-- Card 8: Owner Earnings (90%) -->
					<div class="col-xl-3 col-sm-6">
						<a href="chart-flot.php" class="stat-card-link">
							<div class="card border-0 shadow-sm rounded-3 bg-white h-100">
								<div class="card-body p-3">
									<div class="d-flex align-items-center justify-content-between">
										<span class="p-2 bg-success text-white rounded-circle"><i class="fas fa-hand-holding-usd fs-18"></i></span>
										<span class="badge bg-light text-success font-w600">Owner 90%</span>
									</div>
									<h3 class="font-w700 text-success mt-2 mb-0" id="stat-owner-net">TSh 0</h3>
									<small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Net payouts payable</small>
								</div>
							</div>
						</a>
					</div>
				</div>

				<!-- Live Property & Lodge Directory Table -->
				<div class="row">
					<div class="col-xl-12">
						<div class="card shadow-sm border-0 mb-4">
							<div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
								<h4 class="card-title font-w600 text-dark mb-0">
									<i class="fas fa-building me-2 text-primary"></i>
									<?php echo ($userRole === 'admin') ? 'Platform Lodges & Accommodations' : 'My Registered Lodges'; ?>
								</h4>
								<div>
									<a href="add-room.php" class="btn btn-secondary me-2 btn-sm"><i class="fas fa-plus me-1"></i> Add Room</a>
									<a href="onboarding.php" class="btn btn-primary btn-sm"><i class="fas fa-plus-circle me-1"></i> Onboard New Lodge</a>
								</div>
							</div>
							<div class="card-body p-0">
								<div class="table-responsive">
									<table class="table card-table display mb-0 table-responsive-lg">
										<thead class="bg-light">
											<tr>
												<th>Lodge Name</th>
												<th>Owner / Host</th>
												<th>City & Area</th>
												<th>Address</th>
												<th>Nightly Rate</th>
												<th>Rooms Count</th>
												<th>Status</th>
												<th class="text-end">Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php if (empty($properties)): ?>
												<tr>
													<td colspan="8" class="text-center text-muted py-4">No lodges registered yet. Click "Onboard New Lodge" to get started.</td>
												</tr>
											<?php else: ?>
												<?php foreach ($properties as $p): ?>
													<?php 
														$statusBadge = 'bg-warning text-dark';
														if ($p['status'] === 'Active') $statusBadge = 'bg-success text-white';
														if ($p['status'] === 'Removed') $statusBadge = 'bg-danger text-white';
													?>
													<tr>
														<td>
															<div class="d-flex align-items-center">
																<img src="<?php echo htmlspecialchars(!empty($p['image_url']) ? $p['image_url'] : 'assets/images/room/room1.jpg'); ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="">
																<div>
																	<strong class="d-block text-dark fs-15"><?php echo htmlspecialchars($p['name']); ?></strong>
																	<span class="fs-12 text-muted">ID: #PROP-<?php echo $p['id']; ?></span>
																</div>
															</div>
														</td>
														<td>
															<strong class="d-block fs-14"><?php echo htmlspecialchars($p['host_name'] ?? 'Owner'); ?></strong>
															<span class="fs-12 text-muted"><?php echo htmlspecialchars($p['host_email'] ?? ''); ?></span>
														</td>
														<td>
															<span class="fw-bold"><?php echo htmlspecialchars($p['city']); ?></span>, 
															<span><?php echo htmlspecialchars($p['area']); ?></span>
														</td>
														<td>
															<span class="fs-13 text-muted"><?php echo htmlspecialchars($p['address'] ?? 'N/A'); ?></span>
														</td>
														<td>
															<strong class="text-primary">TSh <?php echo number_format($p['price_per_night'] ?? 0); ?></strong>
														</td>
														<td>
															<span class="badge bg-primary px-3 py-2"><?php echo $p['room_count']; ?> Rooms</span>
														</td>
														<td>
															<span class="badge <?php echo $statusBadge; ?> px-3 py-2"><?php echo htmlspecialchars($p['status'] ?? 'Pending'); ?></span>
														</td>
														<td class="text-end">
															<a href="room-list.php?search=<?php echo urlencode($p['name']); ?>" class="btn btn-outline-primary btn-xs me-1"><i class="fas fa-eye"></i> View Rooms</a>
															<a href="add-room.php" class="btn btn-primary btn-xs"><i class="fas fa-plus"></i> Add Room</a>
														</td>
													</tr>
												<?php endforeach; ?>
											<?php endif; ?>
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
		document.addEventListener('DOMContentLoaded', function() {
			const apiToken = <?php echo json_encode($apiToken); ?>;
			const userRole = <?php echo json_encode($userRole); ?>;

			const rangeSelect = document.getElementById('dash-date-range');
			const customContainer = document.getElementById('dash-custom-range');
			const btnApplyCustom = document.getElementById('btn-dash-apply-range');

			function formatCurrency(amount) {
				return 'TSh ' + Math.round(amount || 0).toLocaleString();
			}

			rangeSelect.addEventListener('change', function() {
				if (this.value === 'custom') {
					customContainer.classList.remove('d-none');
				} else {
					customContainer.classList.add('d-none');
					fetchDashboardStats(this.value);
				}
			});

			btnApplyCustom.addEventListener('click', function() {
				const from = document.getElementById('dash-date-from').value;
				const to = document.getElementById('dash-date-to').value;
				if (!from || !to) {
					alert('Please select both From and To dates.');
					return;
				}
				fetchDashboardStats('custom', from, to);
			});

			async function fetchDashboardStats(range = '30_days', dateFrom = null, dateTo = null) {
				let endpoint = 'http://127.0.0.1:8000/api/admin/dashboard-stats';
				if (userRole !== 'admin') {
					endpoint = 'http://127.0.0.1:8000/api/finance/overview';
				}

				const url = new URL(endpoint);
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
					if (!res.ok) throw new Error('Backend dashboard stats error');
					const json = await res.json();

					if (userRole === 'admin') {
						const o = json.owners || {};
						const l = json.lodges || {};
						const r = json.rooms || {};
						const c = json.customers || {};
						const b = json.bookings || {};
						const f = json.financials || {};

						document.getElementById('stat-total-owners').textContent = (o.total || 0).toLocaleString();
						document.getElementById('stat-active-owners').textContent = (o.active || 0).toLocaleString();
						document.getElementById('stat-suspended-owners').textContent = (o.suspended || 0).toLocaleString();

						document.getElementById('stat-total-lodges').textContent = (l.total || 0).toLocaleString();
						document.getElementById('stat-approved-lodges').textContent = (l.approved || 0).toLocaleString();
						document.getElementById('stat-pending-lodges').textContent = (l.pending || 0).toLocaleString();

						document.getElementById('stat-total-rooms').textContent = (r.total || 0).toLocaleString();
						document.getElementById('stat-total-customers').textContent = (c.total || 0).toLocaleString();

						document.getElementById('stat-total-bookings').textContent = (b.total || 0).toLocaleString();
						document.getElementById('stat-confirmed-bookings').textContent = (b.confirmed || 0).toLocaleString();
						document.getElementById('stat-cancelled-bookings').textContent = (b.cancelled || 0).toLocaleString();

						document.getElementById('stat-gross-value').textContent = formatCurrency(f.gross_booking_value);
						document.getElementById('stat-platform-fee').textContent = formatCurrency(f.platform_commission);
						document.getElementById('stat-owner-net').textContent = formatCurrency(f.owner_earnings);
					} else {
						const cards = json.summary_cards || {};
						document.getElementById('stat-gross-value').textContent = formatCurrency(cards.gross_booking_value);
						document.getElementById('stat-platform-fee').textContent = formatCurrency(cards.platform_commission);
						document.getElementById('stat-owner-net').textContent = formatCurrency(cards.total_owner_earnings);
					}
				} catch (e) {
					console.error('Failed to load dashboard metrics from backend', e);
				}
			}

			// Initial load
			fetchDashboardStats('30_days');
		});
	</script>
</body>
</html>