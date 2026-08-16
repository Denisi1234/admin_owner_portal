<div class="header">
	<div class="header-content">
		<nav class="navbar navbar-expand">
			<div class="collapse navbar-collapse justify-content-between">
				<div class="header-left">
					<div class="dashboard_bar">
						<?php echo $pageTitle; ?>
					</div>
				</div>
				<div class="nav-item d-flex align-items-center">
					<form action="room-list.php" method="GET" class="input-group search-area">
						<input type="text" id="header-search-input" name="search" class="form-control" placeholder="Search lodge name or location..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" autocomplete="off">
						<button type="submit" class="input-group-text border-0 bg-transparent" style="cursor: pointer;"><i class="flaticon-381-search-2"></i></button>
					</form>
				</div>
				<ul class="navbar-nav header-right">
					<li class="nav-item dropdown notification_dropdown">
						<a class="nav-link" href="javascript:void(0);">
							<svg xmlns="http://www.w3.org/2000/svg" width="26.309" height="23.678" viewBox="0 0 26.309 23.678">
								<path id="Path_1955" data-name="Path 1955" d="M163.217,78.043a7.409,7.409,0,0,1,10.5-10.454l.506.506.507-.506a7.409,7.409,0,0,1,10.5,10.454L175.181,88.686a1.316,1.316,0,0,1-1.912,0Zm11.008,7.823,9.1-9.632.027-.027a4.779,4.779,0,1,0-6.759-6.757l-1.435,1.437a1.317,1.317,0,0,1-1.861,0l-1.437-1.437a4.778,4.778,0,0,0-6.758,6.757l.026.027Z" transform="translate(-161.07 -65.42)" fill="#135846" fill-rule="evenodd" />
							</svg>
						</a>
					</li>
					<li class="nav-item dropdown notification_dropdown">
						<a class="nav-link bell-link" href="javascript:void(0);" title="Messages & Admin Chat">
							<svg xmlns="http://www.w3.org/2000/svg" width="26.667" height="24" viewBox="0 0 26.667 24">
								<g id="_014-mail" data-name="014-mail" transform="translate(0 -21.833)">
									<path id="Path_1962" data-name="Path 1962" d="M26.373,26.526A6.667,6.667,0,0,0,20,21.833H6.667A6.667,6.667,0,0,0,.293,26.526,6.931,6.931,0,0,0,0,28.5V39.166a6.669,6.669,0,0,0,6.667,6.667H20a6.669,6.669,0,0,0,6.667-6.667V28.5A6.928,6.928,0,0,0,26.373,26.526ZM6.667,24.5H20a4.011,4.011,0,0,1,3.947,3.36L13.333,33.646,2.72,27.86A4.011,4.011,0,0,1,6.667,24.5ZM24,39.166a4.012,4.012,0,0,1-4,4H6.667a4.012,4.012,0,0,1-4-4V30.873L12.693,36.34a1.357,1.357,0,0,0,1.28,0L24,30.873Z" transform="translate(0 0)" fill="#135846" />
								</g>
							</svg>
							<span class="badge light text-white bg-primary rounded-circle">NEW</span>
						</a>
					</li>
					<li class="nav-item dropdown notification_dropdown">
						<a class="nav-link" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
							<svg xmlns="http://www.w3.org/2000/svg" width="19.375" height="24" viewBox="0 0 19.375 24">
								<g id="_006-notification" data-name="006-notification" transform="translate(-341.252 -61.547)">
									<path id="Path_1954" data-name="Path 1954" d="M349.741,65.233V62.747a1.2,1.2,0,1,1,2.4,0v2.486a8.4,8.4,0,0,1,7.2,8.314v4.517l.971,1.942a3,3,0,0,1-2.683,4.342h-5.488a1.2,1.2,0,1,1-2.4,0h-5.488a3,3,0,0,1-2.683-4.342l.971-1.942V73.547a8.4,8.4,0,0,1,7.2-8.314Zm1.2,2.314a6,6,0,0,0-6,6v4.8a1.208,1.208,0,0,1-.127.536l-1.1,2.195a.6.6,0,0,0,.538.869h13.375a.6.6,0,0,0,.536-.869l-1.1-2.195a1.206,1.206,0,0,1-.126-.536v-4.8a6,6,0,0,0-6-6Z" transform="translate(0 0)" fill="#135846" fill-rule="evenodd" />
								</g>
							</svg>
							<span class="badge light text-white bg-primary rounded-circle">System</span>
						</a>
						<div class="dropdown-menu dropdown-menu-end p-0 shadow border-0" style="min-width: 280px;">
							<div class="card mb-0 border-0">
								<div class="card-header bg-primary text-white py-2">
									<h6 class="font-w600 text-white mb-0"><i class="fas fa-bell me-2"></i>System Activity Alerts</h6>
								</div>
								<div class="card-body p-3">
									<div class="d-flex align-items-center mb-2">
										<span class="p-2 bg-success text-white rounded-circle me-2"><i class="fas fa-shield-alt fs-14"></i></span>
										<div>
											<strong class="d-block fs-13 text-dark">FastNet Portal Active</strong>
											<small class="text-muted">Real-time database sync connected</small>
										</div>
									</div>
									<div class="d-flex align-items-center">
										<span class="p-2 bg-info text-white rounded-circle me-2"><i class="fas fa-user-shield fs-14"></i></span>
										<div>
											<strong class="d-block fs-13 text-dark">Account Verified</strong>
											<small class="text-muted">Logged in as <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'user'); ?></small>
										</div>
									</div>
								</div>
							</div>
						</div>
					</li>
<?php
$hdrAvatar = $_SESSION['user_avatar'] ?? '';
if (empty($hdrAvatar)) {
    $hDb = getDbConnection();
    $hUid = $_SESSION['user_id'] ?? 2;
    $hUser = $hDb->query("SELECT profile_photo_url FROM users WHERE id = {$hUid}")->fetch();
    $hdrAvatar = $hUser['profile_photo_url'] ?? '';
}
?>
					<li class="nav-item dropdown header-profile">
						<a class="nav-link" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
							<?php if (!empty($hdrAvatar)): ?>
								<img src="<?php echo htmlspecialchars($hdrAvatar); ?>" width="35" height="35" style="object-fit:cover; border-radius:50%;" alt="" />
							<?php else: ?>
								<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-14" style="width:35px; height:35px;">
									<?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
								</div>
							<?php endif; ?>
						</a>
						<div class="dropdown-menu dropdown-menu-end">
							<a href="./app-profile.php" class="dropdown-item ai-icon">
								<svg id="icon-user2" xmlns="http://www.w3.org/2000/svg" class="text-primary" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
									<circle cx="12" cy="7" r="4"></circle>
								</svg>
								<span class="ms-2">Profile </span>
							</a>
							<a href="./email-compose.php" class="dropdown-item ai-icon">
								<svg id="icon-inbox1" xmlns="http://www.w3.org/2000/svg" class="text-success" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
									<polyline points="22,6 12,13 2,6"></polyline>
								</svg>
								<span class="ms-2">Direct Messages </span>
							</a>
							<a href="./page-login.php?action=logout" class="dropdown-item ai-icon">
								<svg xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
									<polyline points="16 17 21 12 16 7"></polyline>
									<line x1="21" y1="12" x2="9" y2="12"></line>
								</svg>
								<span class="ms-2">Logout </span>
							</a>
						</div>
					</li>
				</ul>
			</div>
		</nav>
	</div>
</div>