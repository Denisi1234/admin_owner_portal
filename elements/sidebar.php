<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'admin';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (($userRole === 'admin') ? 'Super Admin' : 'Property Owner');
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : (($userRole === 'admin') ? 'admin@fastnet.com' : 'owner@fastnet.com');
?>
<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            <?php if ($userRole === 'admin'): ?>
            <!-- ================= PLATFORM ADMIN ENGINE MENU ================= -->
            <li><a class="has-arrow " href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-025-dashboard"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="index.php">Overview</a></li>
                    <li><a href="guest-list.php">All Guests</a></li>
                    <li><a href="concierge-list.php">Concierge Directory</a></li>
                    <li><a href="room-list.php">System Rooms</a></li>
                    <li><a href="reviews.php">Platform Reviews</a></li>
                </ul>
            </li>
            <li><a class="has-arrow " href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-050-info"></i>
                    <span class="nav-text">Management</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="ecom-customers.php">Verify Lodges & Owners</a></li>
                    <li><a href="email-compose.php">Messages & Support</a></li>
                    <li><a href="form-element.php">System Configurations</a></li>
                </ul>
            </li>
            <li><a class="has-arrow " href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-041-graph"></i>
                    <span class="nav-text">System Reports</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="chart-chartist.php">Platform Analytics</a></li>
                    <li><a href="chart-chartjs.php">Financial Reports</a></li>
                </ul>
            </li>
            <?php else: ?>
            <!-- ================= PROPERTY OWNER MENU ================= -->
            <li><a class="has-arrow " href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-025-dashboard"></i>
                    <span class="nav-text">Lodge Dashboard</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="index.php">Overview</a></li>
                    <li><a href="onboarding.php">Onboard New Lodge</a></li>
                    <li><a href="guest-list.php">Guest List</a></li>
                    <li><a href="room-list.php">My Rooms</a></li>
                    <li><a href="reviews.php">My Reviews</a></li>
                </ul>
            </li>
            <li><a class="has-arrow " href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-050-info"></i>
                    <span class="nav-text">Operations</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="app-profile.php">Property Profile</a></li>
                    <li><a href="email-compose.php">Messages & Admin Support</a></li>
                    <li><a href="app-calender.php">Availability Calendar</a></li>
                    <li><a href="concierge-list.php">Concierge Desk</a></li>
                </ul>
            </li>
            <li><a class="has-arrow " href="javascript:void()" aria-expanded="false">
                    <i class="flaticon-041-graph"></i>
                    <span class="nav-text">Lodge Finance</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="chart-flot.php">Earnings Chart</a></li>
                    <li><a href="ecom-invoice.php">Payout Invoices</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <li><a class="ai-icon" href="page-login.php?action=logout" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="text-danger me-2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
<?php
$sideAvatar = $_SESSION['user_avatar'] ?? '';
if (empty($sideAvatar)) {
    $sDb = getDbConnection();
    $sUid = $_SESSION['user_id'] ?? 2;
    $sUser = $sDb->query("SELECT profile_photo_url FROM users WHERE id = {$sUid}")->fetch();
    $sideAvatar = $sUser['profile_photo_url'] ?? '';
}
?>
        <div class="dropdown header-profile2 ">
            <div class="header-info2 text-center">
                <?php if (!empty($sideAvatar)): ?>
                    <img src="<?php echo htmlspecialchars($sideAvatar); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:50%;" alt="" />
                <?php else: ?>
                    <div class="mx-auto rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-16 mb-2" style="width:50px; height:50px;">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="sidebar-info">
                    <div>
                        <h5 class="font-w500 mb-0"><?php echo htmlspecialchars($userName); ?></h5>
                        <span class="fs-12"><?php echo htmlspecialchars($userEmail); ?></span>
                    </div>
                </div>
                <div>
                    <a href="email-compose.php" class="btn btn-md text-secondary">Contact Us</a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p class="text-center"><strong>FastNet Portal</strong> © 2026 All Rights Reserved</p>
            <p class="fs-12 text-center">Made with <span class="heart"></span> by FastNet</p>
        </div>
    </div>
</div>