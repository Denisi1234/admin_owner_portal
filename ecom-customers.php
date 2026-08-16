<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$api_token = $_SESSION['api_token'] ?? '';

// Handle Owner & Lodge Review actions (Approve, Request Changes, Reject, Suspend)
$action_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['target_type']) && isset($_POST['target_id']) && isset($_POST['new_status'])) {
        $targetType = $_POST['target_type'];
        $targetId = intval($_POST['target_id']);
        $newStatus = $_POST['new_status'];
        $reason = $_POST['reason'] ?? '';

        if ($targetType === 'owner') {
            $ch = curl_init("http://127.0.0.1:8000/api/admin/verification/owner/{$targetId}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $newStatus, 'reason' => $reason]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$api_token}"
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            $dbStatus = ($newStatus === 'approved') ? 'Active' : 'Suspended';
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$dbStatus, $targetId]);
            $action_msg = "Owner status successfully updated to {$newStatus}!";
        } elseif ($targetType === 'lodge') {
            $ch = curl_init("http://127.0.0.1:8000/api/admin/verification/lodge/{$targetId}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $newStatus, 'reason' => $reason]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$api_token}"
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            $dbStatus = ($newStatus === 'Active') ? 'Active' : (($newStatus === 'changes_requested') ? 'Pending' : 'Removed');
            $stmt = $db->prepare("UPDATE properties SET status = ? WHERE id = ?");
            $stmt->execute([$dbStatus, $targetId]);
            $action_msg = "Lodge status successfully updated to {$newStatus}!";
        }
    }
}

// Fetch Owner Verification Requests safely (supports both Supabase PostgreSQL and SQLite)
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'pgsql') {
    $hasOwnerVerifTable = $db->query("SELECT table_name FROM information_schema.tables WHERE table_name='owner_verifications'")->fetchColumn();
} else {
    $hasOwnerVerifTable = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='owner_verifications'")->fetchColumn();
}

if ($hasOwnerVerifTable) {
    $owners = $db->query("
        SELECT u.*, ov.full_name as v_full_name, ov.id_number, ov.id_document_url, ov.business_registration_number, ov.business_document_url, ov.status as verification_status
        FROM users u
        LEFT JOIN owner_verifications ov ON u.id = ov.user_id
        WHERE u.role = 'owner'
        ORDER BY CASE WHEN u.status = 'Pending Verification' THEN 1 ELSE 2 END, u.created_at DESC
    ")->fetchAll();
} else {
    $owners = $db->query("
        SELECT u.*, u.name as v_full_name, NULL as id_number, NULL as id_document_url, NULL as business_registration_number, NULL as business_document_url, u.status as verification_status
        FROM users u
        WHERE u.role = 'owner'
        ORDER BY CASE WHEN u.status = 'Pending Verification' THEN 1 ELSE 2 END, u.created_at DESC
    ")->fetchAll();
}

// Fetch Lodge Verification Requests
$lodges = $db->query("
    SELECT p.*, u.name as host_name, u.email as host_email, u.status as host_status,
           (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) as room_count
    FROM properties p
    LEFT JOIN users u ON p.host_id = u.id
    ORDER BY CASE WHEN p.status = 'Pending' THEN 1 ELSE 2 END, p.created_at DESC
")->fetchAll();

// Status counters
$counts = [
    'pending_owners' => count(array_filter($owners, fn($o) => $o['status'] === 'Pending Verification')),
    'approved_owners' => count(array_filter($owners, fn($o) => $o['status'] === 'Active')),
    'pending_lodges' => count(array_filter($lodges, fn($l) => $l['status'] === 'Pending')),
    'approved_lodges' => count(array_filter($lodges, fn($l) => $l['status'] === 'Active')),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title>Admin Verification Control Center | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
	<?php include 'elements/meta.php';?>
	<link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
	<?php include 'elements/page-css.php'; ?>
    <style>
        .badge-draft { background-color: #6c757d; color: #fff; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-under_review { background-color: #17a2b8; color: #fff; }
        .badge-changes_requested { background-color: #fd7e14; color: #fff; }
        .badge-approved { background-color: #28a745; color: #fff; }
        .badge-rejected { background-color: #dc3545; color: #fff; }
        .badge-suspended { background-color: #343a40; color: #fff; }
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
            <div class="container-fluid">
				<div class="row page-titles">
					<ol class="breadcrumb">
						<li class="breadcrumb-item active"><a href="javascript:void(0)">Admin Engine</a></li>
						<li class="breadcrumb-item"><a href="javascript:void(0)">Verification Control Center</a></li>
					</ol>
                </div>

                <?php if (!empty($action_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($action_msg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Summary Counter Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card border-left-warning shadow-sm py-2">
                            <div class="card-body py-2">
                                <div class="text-xs font-w600 text-warning text-uppercase mb-1">Pending Owners</div>
                                <div class="h3 mb-0 font-w700 text-gray-800"><?php echo $counts['pending_owners']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card border-left-success shadow-sm py-2">
                            <div class="card-body py-2">
                                <div class="text-xs font-w600 text-success text-uppercase mb-1">Verified Owners</div>
                                <div class="h3 mb-0 font-w700 text-gray-800"><?php echo $counts['approved_owners']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card border-left-info shadow-sm py-2">
                            <div class="card-body py-2">
                                <div class="text-xs font-w600 text-info text-uppercase mb-1">Pending Lodges</div>
                                <div class="h3 mb-0 font-w700 text-gray-800"><?php echo $counts['pending_lodges']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card border-left-primary shadow-sm py-2">
                            <div class="card-body py-2">
                                <div class="text-xs font-w600 text-primary text-uppercase mb-1">Published Lodges</div>
                                <div class="h3 mb-0 font-w700 text-gray-800"><?php echo $counts['approved_lodges']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs for Queues -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom pt-3 pb-0">
                        <ul class="nav nav-tabs card-header-tabs" id="verifTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link font-w600" data-bs-toggle="tab" href="#tabOwnerFinance">
                                    <i class="fas fa-coins me-2 text-success"></i>Owner Financial Summary & Payouts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active font-w600" data-bs-toggle="tab" href="#tabLodgeVerif">
                                    <i class="fas fa-hotel me-2"></i>Lodge Verification Queue (<?php echo count($lodges); ?>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-w600" data-bs-toggle="tab" href="#tabOwnerVerif">
                                    <i class="fas fa-user-shield me-2"></i>Owner Identity Queue (<?php echo count($owners); ?>)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            
                            <!-- TAB 0: OWNER FINANCIAL MANAGEMENT & PAYOUTS -->
                            <div class="tab-pane fade" id="tabOwnerFinance">
                                <!-- Search, Filter & Sort Controls -->
                                <div class="row g-2 mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" id="owner-search-input" class="form-control border-start-0" placeholder="Search owner by name, email, or phone...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="owner-status-filter" class="form-select font-w600">
                                            <option value="">All Account Statuses</option>
                                            <option value="Active">Active</option>
                                            <option value="Pending Verification">Pending Verification</option>
                                            <option value="Suspended">Suspended</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="owner-sort-by" class="form-select font-w600">
                                            <option value="created_at">Sort by Date Joined</option>
                                            <option value="gross_value">Sort by Gross Revenue</option>
                                            <option value="net_earnings">Sort by Owner Earnings</option>
                                            <option value="total_bookings">Sort by Total Bookings</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button type="button" id="btn-refresh-owner-finance" class="btn btn-outline-primary w-100 font-w600">
                                            <i class="fas fa-sync-alt me-1"></i> Refresh
                                        </button>
                                    </div>
                                </div>

                                <!-- Owner Financial Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-3" style="font-size: 13.5px;">
                                        <thead class="bg-light text-muted">
                                            <tr>
                                                <th class="ps-3">Owner Profile</th>
                                                <th class="text-center">Lodges</th>
                                                <th class="text-center">Bookings</th>
                                                <th class="text-end">Gross Value</th>
                                                <th class="text-end">Platform (10%)</th>
                                                <th class="text-end">Owner Net (90%)</th>
                                                <th class="text-end pe-3">Outstanding Payout</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-end pe-3">Financial Profile</th>
                                            </tr>
                                        </thead>
                                        <tbody id="owner-finance-tbody">
                                            <!-- Dynamic backend-aggregated owner finance rows -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination Controls -->
                                <div class="d-flex justify-content-between align-items-center pt-2">
                                    <small class="text-muted font-w600" id="owner-finance-page-info">Showing 0 of 0 lodge owners</small>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="owner-finance-pagination">
                                            <!-- Dynamic pagination links -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>

                            <!-- TAB 1: LODGE VERIFICATION -->
                            <div class="tab-pane fade show active" id="tabLodgeVerif">
                                <div class="table-responsive">
                                    <table class="table card-table display mb-4 table-responsive-lg">
                                        <thead>
                                            <tr>
                                                <th>Lodge Details</th>
                                                <th>Owner Status</th>
                                                <th>Location</th>
                                                <th>Rooms Inventory</th>
                                                <th>Audit Status</th>
                                                <th class="text-end">Verification Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($lodges)): ?>
                                                <tr><td colspan="6" class="text-center text-muted py-4">No lodges submitted for review.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($lodges as $l): ?>
                                                    <?php 
                                                        $badgeClass = 'badge-pending';
                                                        if ($l['status'] === 'Active') $badgeClass = 'badge-approved';
                                                        if ($l['status'] === 'Removed') $badgeClass = 'badge-rejected';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo htmlspecialchars($l['image_url'] ?? 'assets/images/room/room1.jpg'); ?>" class="rounded me-3" style="width: 55px; height: 55px; object-fit: cover;" alt="">
                                                                <div>
                                                                    <strong class="text-dark fs-16 d-block"><?php echo htmlspecialchars($l['name']); ?></strong>
                                                                    <span class="fs-12 text-muted">ID: #PROP-<?php echo $l['id']; ?></span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <strong class="d-block"><?php echo htmlspecialchars($l['host_name'] ?? 'Owner'); ?></strong>
                                                            <span class="badge <?php echo ($l['host_status'] === 'Active') ? 'bg-success' : 'bg-warning text-dark'; ?> fs-11">
                                                                <?php echo ($l['host_status'] === 'Active') ? '✓ Owner Verified' : '⚠ Owner Unverified'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span><?php echo htmlspecialchars($l['city'] . ', ' . $l['area']); ?></span>
                                                            <span class="fs-12 d-block text-muted"><?php echo htmlspecialchars($l['address'] ?? ''); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-primary py-2 px-3"><?php echo $l['room_count']; ?> Rooms Configured</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $badgeClass; ?> px-3 py-2 fs-12"><?php echo htmlspecialchars($l['status']); ?></span>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group">
                                                                <?php if ($l['status'] !== 'Active'): ?>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="target_type" value="lodge">
                                                                        <input type="hidden" name="target_id" value="<?php echo $l['id']; ?>">
                                                                        <button type="submit" name="new_status" value="Active" class="btn btn-success btn-sm"><i class="fas fa-check-circle me-1"></i> Approve Lodge</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <?php if ($l['status'] !== 'Removed'): ?>
                                                                    <button type="button" class="btn btn-warning btn-sm text-dark ms-1" data-bs-toggle="modal" data-bs-target="#changeModalLodge<?php echo $l['id']; ?>"><i class="fas fa-edit me-1"></i> Request Changes</button>
                                                                    <form method="POST" class="d-inline ms-1">
                                                                        <input type="hidden" name="target_type" value="lodge">
                                                                        <input type="hidden" name="target_id" value="<?php echo $l['id']; ?>">
                                                                        <button type="submit" name="new_status" value="Removed" class="btn btn-danger btn-sm"><i class="fas fa-times-circle me-1"></i> Reject</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Request Changes Modal -->
                                                            <div class="modal fade text-start" id="changeModalLodge<?php echo $l['id']; ?>" tabindex="-1">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <form method="POST">
                                                                            <input type="hidden" name="target_type" value="lodge">
                                                                            <input type="hidden" name="target_id" value="<?php echo $l['id']; ?>">
                                                                            <input type="hidden" name="new_status" value="changes_requested">
                                                                            <div class="modal-header bg-warning">
                                                                                <h5 class="modal-title font-w600 text-dark">Request Changes for <?php echo htmlspecialchars($l['name']); ?></h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <label class="form-label font-w500">Reason for Requesting Changes</label>
                                                                                <textarea name="reason" class="form-control" rows="4" placeholder="e.g. Please re-upload a clearer image of your business license document." required></textarea>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary light" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-warning">Send Request to Owner</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 2: OWNER IDENTITY VERIFICATION -->
                            <div class="tab-pane fade" id="tabOwnerVerif">
                                <div class="table-responsive">
                                    <table class="table card-table display mb-4 table-responsive-lg">
                                        <thead>
                                            <tr>
                                                <th>Owner Profile</th>
                                                <th>Contact</th>
                                                <th>National ID / Passport</th>
                                                <th>Business Document</th>
                                                <th>Verification Status</th>
                                                <th class="text-end">Identity Review</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($owners)): ?>
                                                <tr><td colspan="6" class="text-center text-muted py-4">No owner identity registrations pending.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($owners as $o): ?>
                                                    <?php 
                                                        $oBadge = 'badge-pending';
                                                        if ($o['status'] === 'Active') $oBadge = 'badge-approved';
                                                        if ($o['status'] === 'Suspended') $oBadge = 'badge-suspended';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong class="text-dark fs-16 d-block"><?php echo htmlspecialchars($o['name']); ?></strong>
                                                            <span class="fs-12 text-muted">User ID: #OWNER-<?php echo $o['id']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span><?php echo htmlspecialchars($o['email']); ?></span>
                                                            <span class="fs-12 d-block text-muted"><?php echo htmlspecialchars($o['phone_number'] ?? 'No phone'); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($o['id_document_url'])): ?>
                                                                <a href="<?php echo htmlspecialchars($o['id_document_url']); ?>" target="_blank" class="btn btn-outline-info btn-xs"><i class="fas fa-file-alt me-1"></i> View ID Document</a>
                                                            <?php else: ?>
                                                                <span class="text-muted fs-12">No ID uploaded</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($o['business_document_url'])): ?>
                                                                <a href="<?php echo htmlspecialchars($o['business_document_url']); ?>" target="_blank" class="btn btn-outline-info btn-xs"><i class="fas fa-certificate me-1"></i> View Business License</a>
                                                            <?php else: ?>
                                                                <span class="text-muted fs-12">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $oBadge; ?> px-3 py-2 fs-12"><?php echo htmlspecialchars($o['status']); ?></span>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group">
                                                                <?php if ($o['status'] !== 'Active'): ?>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="target_type" value="owner">
                                                                        <input type="hidden" name="target_id" value="<?php echo $o['id']; ?>">
                                                                        <button type="submit" name="new_status" value="approved" class="btn btn-success btn-sm"><i class="fas fa-user-check me-1"></i> Approve Owner</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <?php if ($o['status'] !== 'Suspended'): ?>
                                                                    <form method="POST" class="d-inline ms-1">
                                                                        <input type="hidden" name="target_type" value="owner">
                                                                        <input type="hidden" name="target_id" value="<?php echo $o['id']; ?>">
                                                                        <button type="submit" name="new_status" value="suspended" class="btn btn-danger btn-sm"><i class="fas fa-user-slash me-1"></i> Suspend Owner</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
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

        <!-- Owner Detailed Financial Profile Modal -->
        <div class="modal fade" id="ownerFinancialModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title font-w700 text-white" id="modal-owner-name"><i class="fas fa-file-invoice-dollar me-2"></i>Owner Detailed Financial Profile</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="background: #f8fafc;">
                        <input type="hidden" id="modal-active-owner-id" value="">

                        <!-- Section 1: OWNER & LODGE INFORMATION CARDS -->
                        <div class="row g-3 mb-4">
                            <!-- Owner Info Card -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-header bg-transparent border-bottom py-2">
                                        <h6 class="font-w700 text-primary mb-0"><i class="fas fa-user-circle me-1"></i> Owner Information</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2 text-sm">
                                            <div class="col-6"><span class="text-muted font-w500">Name:</span> <strong class="text-dark d-block" id="modal-owner-name-text">-</strong></div>
                                            <div class="col-6"><span class="text-muted font-w500">Email:</span> <strong class="text-dark d-block" id="modal-owner-email">-</strong></div>
                                            <div class="col-6"><span class="text-muted font-w500">Phone:</span> <strong class="text-dark d-block" id="modal-owner-phone">-</strong></div>
                                            <div class="col-6"><span class="text-muted font-w500">Account Status:</span> <span class="badge bg-success d-inline-block" id="modal-owner-status">Active</span></div>
                                            <div class="col-12"><span class="text-muted font-w500">Registered Date:</span> <span class="text-dark font-w600" id="modal-owner-registered">-</span></div>
                                        </div>
                                        <div class="mt-3 pt-2 border-top d-flex gap-2">
                                            <button type="button" class="btn btn-success btn-xs font-w600 btn-update-owner-status" data-status="Active"><i class="fas fa-check-circle me-1"></i> Activate / Restore</button>
                                            <button type="button" class="btn btn-danger btn-xs font-w600 btn-update-owner-status" data-status="Suspended"><i class="fas fa-ban me-1"></i> Suspend</button>
                                            <button type="button" class="btn btn-secondary btn-xs font-w600 btn-update-owner-status" data-status="Deactivated"><i class="fas fa-power-off me-1"></i> Deactivate</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lodge Info Card -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-header bg-transparent border-bottom py-2">
                                        <h6 class="font-w700 text-primary mb-0"><i class="fas fa-hotel me-1"></i> Lodge Information</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row text-center mb-3">
                                            <div class="col-4">
                                                <small class="text-muted font-w600 d-block">Total Lodges</small>
                                                <h5 class="font-w700 text-dark mb-0" id="modal-total-lodges">0</h5>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted font-w600 d-block text-success">Active Lodges</small>
                                                <h5 class="font-w700 text-success mb-0" id="modal-active-lodges">0</h5>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted font-w600 d-block text-secondary">Inactive Lodges</small>
                                                <h5 class="font-w700 text-secondary mb-0" id="modal-inactive-lodges">0</h5>
                                            </div>
                                        </div>
                                        <div id="modal-properties-container" class="d-flex flex-wrap gap-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: FINANCIAL SUMMARY METRICS -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-transparent border-bottom py-2">
                                <h6 class="font-w700 text-success mb-0"><i class="fas fa-wallet me-1"></i> Financial Summary</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3 text-center">
                                    <div class="col-md-3 col-6">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">Gross Revenue</small>
                                            <h6 class="font-w700 text-dark mb-0" id="modal-gross-revenue">TSh 0</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">10% Platform Commission</small>
                                            <h6 class="font-w700 text-danger mb-0" id="modal-platform-commission">TSh 0</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">90% Owner Earnings</small>
                                            <h6 class="font-w700 text-success mb-0" id="modal-owner-earnings">TSh 0</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">Available Balance</small>
                                            <h6 class="font-w700 text-primary mb-0" id="modal-available-balance">TSh 0</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-4">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">Pending Balance</small>
                                            <h6 class="font-w700 text-warning mb-0" id="modal-pending-balance">TSh 0</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-4">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">Total Paid Out</small>
                                            <h6 class="font-w700 text-info mb-0" id="modal-total-paid-out">TSh 0</h6>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-4">
                                        <div class="p-2 border rounded bg-white">
                                            <small class="text-muted font-w600 d-block">Total Refunded</small>
                                            <h6 class="font-w700 text-secondary mb-0" id="modal-total-refunded">TSh 0</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: COMPLETE TRANSACTION HISTORY & FILTERS -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-transparent border-bottom py-2 d-flex justify-content-between align-items-center">
                                <h6 class="font-w700 text-dark mb-0"><i class="fas fa-exchange-alt me-1 text-primary"></i> Complete Financial Transaction History</h6>
                                <span class="badge bg-light text-dark font-w600" id="modal-tx-count">0 Transactions</span>
                            </div>
                            <div class="card-body p-3">
                                <!-- Transaction Filter Controls -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-3 col-6">
                                        <input type="date" id="filter-tx-date-from" class="form-control form-control-sm" placeholder="From Date">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <input type="date" id="filter-tx-date-to" class="form-control form-control-sm" placeholder="To Date">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <select id="filter-tx-property" class="form-select form-select-sm">
                                            <option value="">All Lodges</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <input type="text" id="filter-tx-booking-code" class="form-control form-control-sm" placeholder="Booking Ref #">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <select id="filter-tx-payment-status" class="form-select form-select-sm">
                                            <option value="">All Payment Statuses</option>
                                            <option value="paid">Paid</option>
                                            <option value="pending">Pending</option>
                                            <option value="refunded">Refunded</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <select id="filter-tx-type" class="form-select form-select-sm">
                                            <option value="">All Transaction Types</option>
                                            <option value="Booking Payout">Booking Payout</option>
                                            <option value="Reservation">Reservation</option>
                                            <option value="Refund">Refund</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <select id="filter-tx-payout-status" class="form-select form-select-sm">
                                            <option value="">All Payout Statuses</option>
                                            <option value="Settled & Paid">Settled & Paid</option>
                                            <option value="Pending Settlement">Pending Settlement</option>
                                            <option value="Unconfirmed Reservation">Unconfirmed Reservation</option>
                                            <option value="Refunded">Refunded</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <button type="button" id="btn-apply-tx-filters" class="btn btn-primary btn-sm w-100 font-w600">
                                            <i class="fas fa-filter me-1"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>

                                <!-- Filtered Transactions Table -->
                                <div class="table-responsive bg-white border rounded-3">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3">Date</th>
                                                <th>Booking Ref</th>
                                                <th>Guest</th>
                                                <th>Lodge / Room</th>
                                                <th class="text-end">Gross Amount</th>
                                                <th class="text-end">Platform (10%)</th>
                                                <th class="text-end">Owner Net (90%)</th>
                                                <th class="text-center">Transaction Type</th>
                                                <th class="text-center">Payout Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal-tx-tbody">
                                            <!-- Dynamic filtered transaction rows -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary btn-sm font-w600" data-bs-dismiss="modal">Close Profile</button>
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
            let currentSearch = '';
            let currentStatus = '';
            let currentSort = 'created_at';

            const tbody = document.getElementById('owner-finance-tbody');
            const searchInput = document.getElementById('owner-search-input');
            const statusFilter = document.getElementById('owner-status-filter');
            const sortBySelect = document.getElementById('owner-sort-by');
            const btnRefresh = document.getElementById('btn-refresh-owner-finance');
            const pageInfo = document.getElementById('owner-finance-page-info');
            const paginationUl = document.getElementById('owner-finance-pagination');

            function formatCurrency(val) {
                return 'TSh ' + Math.round(val || 0).toLocaleString();
            }

            async function fetchOwnerFinance() {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading owner financial profiles from backend...</td></tr>`;

                const url = new URL('http://127.0.0.1:8000/api/admin/owners/financial-summary');
                url.searchParams.append('page', currentPage);
                url.searchParams.append('per_page', 15);
                if (currentSearch) url.searchParams.append('search', currentSearch);
                if (currentStatus) url.searchParams.append('status', currentStatus);
                if (currentSort) url.searchParams.append('sort_by', currentSort);

                try {
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        }
                    });

                    if (!res.ok) throw new Error(`HTTP ${res.status}`);

                    const json = await res.json();
                    const owners = json.data || [];
                    
                    pageInfo.textContent = `Showing page ${json.current_page} of ${json.last_page} (${json.total} total lodge owners)`;

                    renderTable(owners);
                    renderPagination(json.current_page, json.last_page);
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger"><i class="fas fa-exclamation-circle me-1"></i> Failed to load owner financial summaries.</td></tr>`;
                }
            }

            function renderTable(owners) {
                tbody.innerHTML = '';
                if (owners.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">No lodge owners match your criteria.</td></tr>`;
                    return;
                }

                owners.forEach(o => {
                    const tr = document.createElement('tr');
                    const badgeClass = (o.account_status === 'Active') ? 'bg-success' : ((o.account_status === 'Suspended') ? 'bg-danger' : 'bg-warning text-dark');

                    tr.innerHTML = `
                        <td class="ps-3">
                            <strong class="text-dark fs-15 d-block">${o.owner_name}</strong>
                            <small class="text-muted">${o.owner_email}</small>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border font-w600">${o.property_count} Lodges</span></td>
                        <td class="text-center"><span class="badge bg-light text-dark border font-w600">${o.total_bookings} Stays</span></td>
                        <td class="text-end font-w600 text-dark">${formatCurrency(o.gross_booking_value)}</td>
                        <td class="text-end font-w600 text-danger">${formatCurrency(o.platform_commission)}</td>
                        <td class="text-end font-w700 text-success">${formatCurrency(o.owner_earnings)}</td>
                        <td class="text-end pe-3 font-w700 text-primary">${formatCurrency(o.outstanding_payout)}</td>
                        <td class="text-center"><span class="badge ${badgeClass} font-w600 px-3 py-1">${o.account_status}</span></td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-outline-primary btn-sm font-w600 btn-view-profile" data-id="${o.owner_id}">
                                <i class="fas fa-eye me-1"></i> Profile
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.querySelectorAll('.btn-view-profile').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const ownerId = this.dataset.id;
                        openOwnerProfileModal(ownerId);
                    });
                });
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
                        fetchOwnerFinance();
                    });
                    paginationUl.appendChild(li);
                }
            }

            async function openOwnerProfileModal(ownerId) {
                const modalEl = new bootstrap.Modal(document.getElementById('ownerFinancialModal'));
                modalEl.show();

                document.getElementById('modal-active-owner-id').value = ownerId;
                document.getElementById('modal-owner-name').textContent = 'Loading Profile...';
                document.getElementById('modal-tx-tbody').innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading owner profile and complete transactions...</td></tr>`;

                // Reset modal filters
                document.getElementById('filter-tx-date-from').value = '';
                document.getElementById('filter-tx-date-to').value = '';
                document.getElementById('filter-tx-booking-code').value = '';
                document.getElementById('filter-tx-payment-status').value = '';
                document.getElementById('filter-tx-type').value = '';
                document.getElementById('filter-tx-payout-status').value = '';

                await loadOwnerProfileData(ownerId);
            }

            async function loadOwnerProfileData(ownerId) {
                const url = new URL(`http://127.0.0.1:8000/api/admin/owners/${ownerId}/financial-profile`);

                const dateFrom = document.getElementById('filter-tx-date-from').value;
                const dateTo = document.getElementById('filter-tx-date-to').value;
                const propertyId = document.getElementById('filter-tx-property').value;
                const bookingCode = document.getElementById('filter-tx-booking-code').value.trim();
                const paymentStatus = document.getElementById('filter-tx-payment-status').value;
                const txType = document.getElementById('filter-tx-type').value;
                const payoutStatus = document.getElementById('filter-tx-payout-status').value;

                if (dateFrom) url.searchParams.append('date_from', dateFrom);
                if (dateTo) url.searchParams.append('date_to', dateTo);
                if (propertyId) url.searchParams.append('property_id', propertyId);
                if (bookingCode) url.searchParams.append('booking_code', bookingCode);
                if (paymentStatus) url.searchParams.append('payment_status', paymentStatus);
                if (txType) url.searchParams.append('transaction_type', txType);
                if (payoutStatus) url.searchParams.append('payout_status', payoutStatus);

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${apiToken}`
                        }
                    });
                    if (!res.ok) throw new Error('Profile load error');
                    const data = await res.json();

                    const info = data.owner_information || {};
                    const lodges = data.lodge_information || {};
                    const fin = data.financial_summary || {};
                    const txs = data.transactions || [];

                    // 1. Owner Information
                    document.getElementById('modal-owner-name').textContent = `Financial Profile — ${info.name}`;
                    document.getElementById('modal-owner-name-text').textContent = info.name;
                    document.getElementById('modal-owner-email').textContent = info.email;
                    document.getElementById('modal-owner-phone').textContent = info.phone;
                    document.getElementById('modal-owner-status').textContent = info.account_status;
                    document.getElementById('modal-owner-registered').textContent = info.registered_date || 'N/A';

                    // 2. Lodge Information
                    document.getElementById('modal-total-lodges').textContent = lodges.total_lodges || 0;
                    document.getElementById('modal-active-lodges').textContent = lodges.active_lodges || 0;
                    document.getElementById('modal-inactive-lodges').textContent = lodges.inactive_lodges || 0;

                    const propsDiv = document.getElementById('modal-properties-container');
                    const propSelect = document.getElementById('filter-tx-property');
                    propsDiv.innerHTML = '';

                    // Retain option selection if set
                    const selectedProp = propSelect.value;
                    propSelect.innerHTML = `<option value="">All Lodges (${lodges.total_lodges || 0})</option>`;

                    if (lodges.lodges_list && lodges.lodges_list.length > 0) {
                        lodges.lodges_list.forEach(p => {
                            const badgeColor = (p.status === 'Active') ? 'bg-success' : 'bg-secondary';
                            propsDiv.innerHTML += `<span class="badge ${badgeColor} py-1 px-2 font-w500 me-1 mb-1"><i class="fas fa-hotel me-1"></i> ${p.name} (${p.city})</span>`;
                            
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = `${p.name} (${p.city})`;
                            if (String(p.id) === String(selectedProp)) opt.selected = true;
                            propSelect.appendChild(opt);
                        });
                    } else {
                        propsDiv.innerHTML = `<span class="text-muted small">No properties registered.</span>`;
                    }

                    // 3. Financial Summary
                    document.getElementById('modal-gross-revenue').textContent = formatCurrency(fin.gross_revenue);
                    document.getElementById('modal-platform-commission').textContent = formatCurrency(fin.platform_commission);
                    document.getElementById('modal-owner-earnings').textContent = formatCurrency(fin.owner_earnings);
                    document.getElementById('modal-available-balance').textContent = formatCurrency(fin.available_balance);
                    document.getElementById('modal-pending-balance').textContent = formatCurrency(fin.pending_balance);
                    document.getElementById('modal-total-paid-out').textContent = formatCurrency(fin.total_paid_out);
                    document.getElementById('modal-total-refunded').textContent = formatCurrency(fin.total_refunded);

                    // 4. Transactions List & Count
                    document.getElementById('modal-tx-count').textContent = `${txs.length} Transactions`;
                    const txTbody = document.getElementById('modal-tx-tbody');
                    txTbody.innerHTML = '';

                    if (txs.length === 0) {
                        txTbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-search me-1"></i> No financial transactions match your selected filter criteria.</td></tr>`;
                    } else {
                        txs.forEach(t => {
                            const txTypeBadge = (t.transaction_type === 'Booking Payout') ? 'bg-success' : ((t.transaction_type === 'Refund') ? 'bg-danger' : 'bg-info');
                            const payoutBadge = (t.payout_status === 'Settled & Paid') ? 'bg-success' : ((t.payout_status === 'Refunded') ? 'bg-danger' : 'bg-warning text-dark');

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="ps-3"><small class="text-muted font-w600">${t.transaction_date || 'N/A'}</small></td>
                                <td class="font-w700 text-primary">${t.booking_code}</td>
                                <td>
                                    <div class="font-w600 text-dark">${t.guest_name}</div>
                                    <small class="text-muted">${t.guest_email}</small>
                                </td>
                                <td>
                                    <div class="font-w600 text-dark">${t.property_name}</div>
                                    <small class="text-muted">${t.room_number}</small>
                                </td>
                                <td class="text-end font-w600 text-dark">${formatCurrency(t.gross_amount)}</td>
                                <td class="text-end font-w600 text-danger">${formatCurrency(t.platform_fee)}</td>
                                <td class="text-end font-w700 text-success">${formatCurrency(t.owner_net_payout)}</td>
                                <td class="text-center"><span class="badge ${txTypeBadge} font-w600">${t.transaction_type}</span></td>
                                <td class="text-center"><span class="badge ${payoutBadge} font-w600">${t.payout_status}</span></td>
                            </tr>
                            `;
                            txTbody.appendChild(tr);
                        });
                    }

                } catch (e) {
                    document.getElementById('modal-owner-name').textContent = 'Error Loading Profile';
                }
            }

            document.getElementById('btn-apply-tx-filters').addEventListener('click', function() {
                const ownerId = document.getElementById('modal-active-owner-id').value;
                if (ownerId) {
                    loadOwnerProfileData(ownerId);
                }
            });

            document.querySelectorAll('.btn-update-owner-status').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const ownerId = document.getElementById('modal-active-owner-id').value;
                    const newStatus = this.dataset.status;
                    if (!ownerId) return;

                    const reason = prompt(`Enter reason for updating owner status to ${newStatus}:`, `Super Admin status update to ${newStatus}`);
                    if (reason === null) return;

                    try {
                        const res = await fetch(`http://127.0.0.1:8000/api/admin/users/${ownerId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${apiToken}`
                            },
                            body: JSON.stringify({
                                status: newStatus,
                                reason: reason
                            })
                        });
                        const json = await res.json();
                        if (!res.ok) throw new Error(json.message || 'Status update failed.');
                        alert(json.message || 'Status updated successfully.');
                        await loadOwnerProfileData(ownerId);
                        await fetchOwnerFinance();
                    } catch (err) {
                        alert(err.message);
                    }
                });
            });

            // Debounced Search Input
            let searchTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    currentSearch = this.value.trim();
                    currentPage = 1;
                    fetchOwnerFinance();
                }, 400);
            });

            statusFilter.addEventListener('change', function() {
                currentStatus = this.value;
                currentPage = 1;
                fetchOwnerFinance();
            });

            sortBySelect.addEventListener('change', function() {
                currentSort = this.value;
                currentPage = 1;
                fetchOwnerFinance();
            });

            btnRefresh.addEventListener('click', function() {
                fetchOwnerFinance();
            });

            // Initial fetch
            fetchOwnerFinance();
        });
    </script>
</body>
</html>