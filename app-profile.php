<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$userId = $_SESSION['user_id'] ?? 2;
$userRole = $_SESSION['user_role'] ?? 'owner';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $profilePic = trim($_POST['profile_pic_url'] ?? '');

    if ($name && $email) {
        // Safe check for profile_pic_url column in local DB
        $hasPicCol = false;
        try {
            $db->query("SELECT profile_pic_url FROM users LIMIT 1");
            $hasPicCol = true;
        } catch (Exception $e) {
            $hasPicCol = false;
        }

        if ($hasPicCol) {
            $stmt = $db->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone_number = ?, address = ?, bio = ?, profile_pic_url = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $phone, $address, $bio, $profilePic, $userId]);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone_number = ?, address = ?, bio = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $phone, $address, $bio, $userId]);
        }
        
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        if (!empty($profilePic)) {
            $_SESSION['user_avatar'] = $profilePic;
        }
        $success_message = 'Profile & picture updated successfully!';
    } else {
        $error_message = 'Name and Email are required fields.';
    }
}

// Fetch user profile
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    $user = [
        'name' => $_SESSION['user_name'] ?? 'Lodge Owner Profile',
        'email' => $_SESSION['user_email'] ?? 'owner@fastnet.com',
        'phone_number' => '+255 700 000 000',
        'address' => 'Sekei Road, Arusha, Tanzania',
        'bio' => 'Licensed FastNetStays accommodation host.',
        'role' => $userRole,
        'status' => 'Active',
        'profile_pic_url' => ''
    ];
}

// Default fallback avatar image
$avatarUrl = !empty($user['profile_pic_url']) ? $user['profile_pic_url'] : ($_SESSION['user_avatar'] ?? '');

// Fetch property overview statistics for this profile
if ($userRole === 'admin') {
    $totalProperties = $db->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    $totalRooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    $verificationStatus = "System Administrator";
} else {
    $stmtProp = $db->prepare("SELECT COUNT(*) FROM properties WHERE host_id = ?");
    $stmtProp->execute([$userId]);
    $totalProperties = $stmtProp->fetchColumn();

    $stmtRooms = $db->prepare("
        SELECT COUNT(*) FROM rooms r
        JOIN properties p ON r.property_id = p.id
        WHERE p.host_id = ?
    ");
    $stmtRooms->execute([$userId]);
    $totalRooms = $stmtRooms->fetchColumn();

    $verificationStatus = ($user['status'] === 'Active') ? 'Verified FastNet Host' : 'Pending Verification';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title>Property & Host Profile | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
	<?php include 'elements/meta.php';?>
	<link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
	<?php include 'elements/page-css.php'; ?>
    <style>
        .profile-photo-container {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .profile-photo-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .profile-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #135846;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #fff;
        }
        .profile-photo-btn:hover {
            background: #0d3d31;
            color: #fff;
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
						<li class="breadcrumb-item active"><a href="javascript:void(0)"><?php echo ucfirst($userRole); ?> Portal</a></li>
						<li class="breadcrumb-item"><a href="javascript:void(0)">Profile & Account Settings</a></li>
					</ol>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Unified Profile Banner Header & Lodge Portfolio Overview -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="profile card card-body px-4 pt-4 pb-4 shadow-sm border-0 mb-4">
                            <div class="profile-head">
                                <div class="profile-info d-flex align-items-center justify-content-between flex-wrap pb-3 border-bottom mb-4">
									<div class="d-flex align-items-center">
                                        <!-- Profile Picture Uploader Component -->
                                        <div class="profile-photo-container me-4">
                                            <?php if (!empty($avatarUrl)): ?>
                                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" id="avatarPreviewImg" class="profile-photo-img" alt="Profile Picture">
                                            <?php else: ?>
                                                <div id="avatarFallbackDiv" class="profile-photo-img bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-24">
                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <label for="profileImageInput" class="profile-photo-btn" title="Upload new photo">
                                                <i class="fas fa-camera"></i>
                                            </label>
                                            <input type="file" id="profileImageInput" class="d-none" accept="image/*">
                                        </div>

                                        <div class="profile-details">
                                            <h3 class="text-primary font-w600 mb-1"><?php echo htmlspecialchars($user['name']); ?></h3>
                                            <p class="text-muted mb-0"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                                            <span class="badge bg-success mt-2 py-2 px-3">
                                                <i class="fas fa-shield-alt me-1"></i> <?php echo htmlspecialchars($verificationStatus); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3 mt-md-0">
                                        <a href="email-compose.php" class="btn btn-outline-primary me-2"><i class="fas fa-paper-plane me-1"></i> Support Desk</a>
                                        <a href="add-room.php" class="btn btn-secondary me-2"><i class="fas fa-bed me-1"></i> Add Room to Lodge</a>
                                        <a href="onboarding.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add Lodge</a>
                                    </div>
                                </div>

                                <!-- Integrated Portfolio Stats + Contact & Location Summary -->
                                <div class="row align-items-center pt-2">
                                    <div class="col-md-4 border-end mb-3 mb-md-0">
                                        <div class="d-flex align-items-center justify-content-around">
                                            <div class="text-center">
                                                <h2 class="font-w700 text-primary mb-0"><?php echo $totalProperties; ?></h2>
                                                <span class="text-muted fs-13 font-w500">Registered Lodges</span>
                                            </div>
                                            <div class="text-center">
                                                <h2 class="font-w700 text-success mb-0"><?php echo $totalRooms; ?></h2>
                                                <span class="text-muted fs-13 font-w500">Configured Rooms</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <span class="text-muted fs-12 font-w500 d-block"><i class="fas fa-phone-alt me-1 text-primary"></i> Phone Number</span>
                                                <strong class="text-dark fs-14"><?php echo !empty($user['phone_number']) ? htmlspecialchars($user['phone_number']) : 'Not set (Update below)'; ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <span class="text-muted fs-12 font-w500 d-block"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Operating Address</span>
                                                <strong class="text-dark fs-14"><?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not set (Update below)'; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Clean Full-Width Profile Edit Form -->
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title font-w600 text-dark mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile Information</h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="app-profile.php?action=update">
                                    <input type="hidden" name="profile_pic_url" id="profilePicUrlInput" value="<?php echo htmlspecialchars($avatarUrl); ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w500 text-dark">Full Name / Business Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w500 text-dark">Official Email Address <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w500 text-dark">Phone / Mobile Number</label>
                                            <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="+255 7XX XXX XXX">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w500 text-dark">Primary Address / Region</label>
                                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Arusha, Sekei Road">
                                        </div>
                                        <div class="col-md-12 mb-4">
                                            <label class="form-label font-w500 text-dark">Host & Lodge Description / Bio</label>
                                            <textarea name="bio" class="form-control" rows="4" placeholder="Describe your property or host business details..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary px-4 py-2"><i class="fas fa-save me-1"></i> Save Profile Changes</button>
                                    </div>
                                </form>
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
        // Profile picture client-side preview & upload encoder
        document.getElementById('profileImageInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const dataUrl = evt.target.result;
                    document.getElementById('profilePicUrlInput').value = dataUrl;
                    
                    const avatarImg = document.getElementById('avatarPreviewImg');
                    const fallbackDiv = document.getElementById('avatarFallbackDiv');
                    
                    if (avatarImg) {
                        avatarImg.src = dataUrl;
                    } else if (fallbackDiv) {
                        fallbackDiv.outerHTML = `<img src="${dataUrl}" id="avatarPreviewImg" class="profile-photo-img" alt="Profile Picture">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>