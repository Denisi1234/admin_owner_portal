<?php 
     if (session_status() === PHP_SESSION_NONE) {
         session_start();
     }
	 require_once __DIR__ . '/config/dz.php';

     $db = getDbConnection();
     $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'admin';
     $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2;

     if ($userRole === 'admin') {
         $stmt = $db->query("
             SELECT b.*, u.name as guest_name, u.profile_photo_url as guest_photo, r.room_number, r.room_type_id 
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             JOIN users u ON b.guest_id = u.id
             ORDER BY b.created_at DESC
         ");
         $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
     } else {
         $stmt = $db->prepare("
             SELECT b.*, u.name as guest_name, u.profile_photo_url as guest_photo, r.room_number, r.room_type_id 
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             JOIN properties p ON r.property_id = p.id
             JOIN users u ON b.guest_id = u.id
             WHERE p.host_id = ?
             ORDER BY b.created_at DESC
         ");
         $stmt->execute([$userId]);
         $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
     }
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <!-- PAGE TITLE HERE -->
	<title><?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
	<?php include 'elements/meta.php';?>
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
	<?php include 'elements/page-css.php'; ?>
</head>
<body>

	<!--*******************
		Preloader start
	********************-->
	 <?php include 'elements/pre-loader.php'; ?>
	<!--*******************
		Preloader end
	********************-->

	<!--**********************************
		Main wrapper start
	***********************************-->
	<div id="main-wrapper">

		<!--**********************************
			Nav header start
		***********************************-->
		<?php include 'elements/nav-header.php'; ?>
		<!--**********************************
			Nav header end
		***********************************-->
		
		<!--**********************************
			Chat box start
		***********************************-->
		<?php include 'elements/chatbox.php'; ?>
		<!--**********************************
			Chat box End
		***********************************-->
		
		<!--**********************************
			Header start
		***********************************-->
		<?php include 'elements/header.php'; ?>
		<!--**********************************
			Header end ti-comment-alt
		***********************************-->

		<!--**********************************
			Sidebar start
		***********************************-->
		<?php include 'elements/sidebar.php'; ?>
		<!--**********************************
			Sidebar end
		***********************************-->
		
		<!--**********************************
			Content body start
		***********************************-->
		<div class="content-body">
			<!-- row -->
			<div class="container-fluid">
				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="card-action coin-tabs mb-2">
						<ul class="nav nav-tabs" role="tablist">
							<li class="nav-item">
								<a class="nav-link active" data-bs-toggle="tab" href="#AllGuest">All Guest</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#Pending">Pending</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#Booked">Booked</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#Canceled">Canceled</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#Refund">Refund</a>
							</li>
						</ul>
					</div>
					<div class="d-flex align-items-center mb-2 flex-wrap"> 
						<div class="guest-calendar">
							<div id="reportrange" class="pull-right reportrange" style="width: 100%">
								<span></span><b class="caret"></b>
								<i class="fas fa-chevron-down ms-3"></i>
							</div>
						</div>
						<div class="newest ms-3">
							<select class="default-select">
								<option>Newest</option>
								<option>Oldest</option>
							</select>
						</div>	
					</div>
				</div>
				<div class="row mt-4">
					<div class="col-xl-12">
						<div class="card">
							<div class="card-body p-0">
								<div class="tab-content">
                                <?php
                                $tabs = [
                                    'AllGuest' => $bookings,
                                    'Pending' => array_filter($bookings, function($b) { return strtolower($b['payment_status'] ?? '') === 'pending'; }),
                                    'Booked' => array_filter($bookings, function($b) { return in_array(strtolower($b['payment_status'] ?? ''), ['paid', 'successful', 'booked']); }),
                                    'Canceled' => array_filter($bookings, function($b) { return strtolower($b['payment_status'] ?? '') === 'canceled'; }),
                                    'Refund' => array_filter($bookings, function($b) { return strtolower($b['payment_status'] ?? '') === 'refunded'; })
                                ];
                                $tabIndex = 0;
                                foreach($tabs as $tabId => $tabBookings): 
                                ?>
                                    <div class="tab-pane fade <?php echo $tabIndex === 0 ? 'active show' : ''; ?>" id="<?php echo $tabId; ?>">
                                        <div class="table-responsive">
                                            <table class="table card-table display mb-4 shadow-hover default-table table-responsive-lg" id="guestTable-all<?php echo $tabIndex; ?>">
                                                <thead>
                                                    <tr>
                                                        <th class="bg-none">
                                                            <div class="form-check style-1">
                                                              <input class="form-check-input" type="checkbox" value="" id="checkAll<?php echo $tabIndex; ?>">
                                                            </div>
                                                        </th>
                                                        <th>Guest</th>
                                                        <th>Order Date</th>
                                                        <th>Check In</th>
                                                        <th>Check Out</th>
                                                        <th>Special Request</th>
                                                        <th>Room Type</th>
                                                        <th class="text-center">Status</th>
                                                        <th class="bg-none"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($tabBookings)): ?>
                                                        <tr>
                                                            <td colspan="9" class="text-center">No guests found in this category.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach($tabBookings as $b): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check style-1">
                                                                      <input class="form-check-input" type="checkbox" value="">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="concierge-bx d-flex align-items-center">
                                                                        <?php 
                                                                            $photo = !empty($b['guest_photo']) ? $b['guest_photo'] : 'assets/images/avatar/1.jpg'; 
                                                                        ?>
                                                                        <img class="me-3 rounded" src="<?php echo htmlspecialchars($photo); ?>" alt="" style="width:40px; height:40px; object-fit:cover;">
                                                                        <div>
                                                                            <h5 class="fs-16 mb-0 text-nowrap"><a class="text-black" href="javascript:void(0);"><?php echo htmlspecialchars($b['guest_name'] ?? 'Unknown'); ?></a></h5>
                                                                            <span class="text-primary fs-14">#GST-<?php echo str_pad($b['guest_id'] ?? 0, 5, '0', STR_PAD_LEFT); ?></span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="text-nowrap">
                                                                    <span><?php echo !empty($b['created_at']) ? date('M jS Y h:i A', strtotime($b['created_at'])) : 'N/A'; ?></span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <h5 class="text-nowrap"><?php echo !empty($b['check_in']) ? date('M jS, Y', strtotime($b['check_in'])) : 'N/A'; ?></h5>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <h5 class="text-nowrap"><?php echo !empty($b['check_out']) ? date('M jS, Y', strtotime($b['check_out'])) : 'N/A'; ?></h5>
                                                                    </div>
                                                                </td>
                                                                <td class="request">
                                                                    <a href="javascript:void(0);" class="btn btn-sm">View Notes</a>
                                                                </td>
                                                                <td>
                                                                    <span class="font-w500 text-nowrap"><?php echo htmlspecialchars(($b['room_type_id'] ?? 'Std') . ' - ' . ($b['room_number'] ?? '')); ?></span>
                                                                </td>
                                                                <td>
                                                                    <div class="request">
                                                                        <?php 
                                                                        $statusClass = 'text-primary';
                                                                        $statusText = strtoupper($b['payment_status'] ?? 'PENDING');
                                                                        if (in_array($statusText, ['PAID', 'SUCCESSFUL', 'BOOKED'])) {
                                                                            $statusClass = 'text-success';
                                                                            $statusText = 'BOOKED';
                                                                        } elseif (in_array($statusText, ['CANCELED', 'REFUNDED'])) {
                                                                            $statusClass = 'text-danger';
                                                                        }
                                                                        ?>
                                                                        <a href="javascript:void(0);" class="btn btn-md <?php echo $statusClass; ?>"><?php echo $statusText; ?></a>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="dropdown dropend">
                                                                        <a href="javascript:void(0);" class="btn-link" data-bs-toggle="dropdown" aria-expanded="false">
                                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                <path d="M11 12C11 12.5523 11.4477 13 12 13C12.5523 13 13 12.5523 13 12C13 11.4477 12.5523 11 12 11C11.4477 11 11 11.4477 11 12Z" stroke="#262626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                <path d="M18 12C18 12.5523 18.4477 13 19 13C19.5523 13 20 12.5523 20 12C20 11.4477 19.5523 11 19 11C18.4477 11 18 11.4477 18 12Z" stroke="#262626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                <path d="M4 12C4 12.5523 4.44772 13 5 13C5.55228 13 6 12.5523 6 12C6 11.4477 5.55228 11 5 11C4.44772 11 4 11.4477 4 12Z" stroke="#262626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                            </svg>
                                                                        </a>
                                                                        <div class="dropdown-menu">
                                                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
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
                                <?php $tabIndex++; endforeach; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!--**********************************
			Content body end
		***********************************-->
		
		
		
		<!--**********************************
			Footer start
		***********************************-->
		<?php include 'elements/footer.php'; ?>
		<!--**********************************
			Footer end
		***********************************-->

		<!--**********************************
		   Support ticket button start
		***********************************-->
		
		<!--**********************************
		   Support ticket button end
		***********************************-->


	</div>
	<!--**********************************
		Main wrapper end
	***********************************-->

	<!--**********************************
		Scripts
	***********************************-->
	<!-- Required vendors -->
	<?php include 'elements/page-js.php'; ?>
	
	<script>
		$(function() {

			var start = moment().subtract(29, 'days');
			var end = moment();

			function cb(start, end) {
				$('#reportrange span').html(start.format('D MMMM YYYY') + ' &nbsp - &nbsp ' + end.format('D MMMM YYYY'));
    }

    $('#reportrange').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);

    cb(start, end);
    
});
</script>z

</body>
</html>