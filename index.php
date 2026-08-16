<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'admin';
$userId = $_SESSION['user_id'] ?? 2;

// Fetch live properties from database
if ($userRole === 'admin') {
    $properties = $db->query("
        SELECT p.*, u.name as host_name, u.email as host_email,
               (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) as room_count
        FROM properties p
        LEFT JOIN users u ON p.host_id = u.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
    
    $rooms = $db->query("SELECT * FROM rooms")->fetchAll();
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

    $propertyIds = array_column($properties, 'id');
    $rooms = [];
    if (!empty($propertyIds)) {
        $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
        $stmt = $db->prepare("SELECT * FROM rooms WHERE property_id IN ($placeholders)");
        $stmt->execute($propertyIds);
        $rooms = $stmt->fetchAll();
    }
}

// Live metrics
$properties_count = count($properties);
$rooms_count = count($rooms);

$bookings_count = 0;
$checkins_today = 0;
$checkouts_today = 0;

$roomIds = array_column($rooms, 'id');
if ($userRole === 'admin') {
    try {
        $bookings_count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        $checkins_today = $db->query("SELECT COUNT(*) FROM bookings WHERE date(check_in) = date('now')")->fetchColumn();
        $checkouts_today = $db->query("SELECT COUNT(*) FROM bookings WHERE date(check_out) = date('now')")->fetchColumn();
    } catch (Exception $e) {}
} else if (!empty($roomIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id IN ($placeholders)");
        $stmt->execute($roomIds);
        $bookings_count = $stmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>FastNet Dashboard Overview | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
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
						<li class="breadcrumb-item active"><a href="javascript:void(0)"><?php echo ucfirst($userRole); ?> Control Center</a></li>
						<li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard Overview</a></li>
					</ol>
                </div>

				<!-- Live Counter Metrics -->
				<div class="row mb-4">
					<div class="col-xl-3 col-sm-6 mb-3">
						<div class="card booking shadow-sm border-0">
							<div class="card-body">
								<div class="booking-status d-flex align-items-center">
									<span class="p-3 bg-primary text-white rounded-circle me-3">
										<i class="fas fa-hotel fs-20"></i>
									</span>
									<div>
										<h2 class="mb-0 font-w700 text-dark"><?php echo $properties_count; ?></h2>
										<p class="mb-0 text-muted font-w500">Registered Lodges</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-sm-6 mb-3">
						<div class="card booking shadow-sm border-0">
							<div class="card-body">
								<div class="booking-status d-flex align-items-center">
									<span class="p-3 bg-success text-white rounded-circle me-3">
										<i class="fas fa-bed fs-20"></i>
									</span>
									<div>
										<h2 class="mb-0 font-w700 text-dark"><?php echo $rooms_count; ?></h2>
										<p class="mb-0 text-muted font-w500">Total Rooms</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-sm-6 mb-3">
						<div class="card booking shadow-sm border-0">
							<div class="card-body">
								<div class="booking-status d-flex align-items-center">
									<span class="p-3 bg-info text-white rounded-circle me-3">
										<i class="fas fa-calendar-check fs-20"></i>
									</span>
									<div>
										<h2 class="mb-0 font-w700 text-dark"><?php echo $bookings_count; ?></h2>
										<p class="mb-0 text-muted font-w500">Active Bookings</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-sm-6 mb-3">
						<div class="card booking shadow-sm border-0">
							<div class="card-body">
								<div class="booking-status d-flex align-items-center">
									<span class="p-3 bg-warning text-white rounded-circle me-3">
										<i class="fas fa-sign-in-alt fs-20"></i>
									</span>
									<div>
										<h2 class="mb-0 font-w700 text-dark"><?php echo $checkins_today; ?></h2>
										<p class="mb-0 text-muted font-w500">Check-ins Today</p>
									</div>
								</div>
							</div>
						</div>
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
</body>
</html>