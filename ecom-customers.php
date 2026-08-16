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

        <?php include 'elements/footer.php'; ?>
    </div>

    <?php include 'elements/page-js.php'; ?>
</body>
</html>