<?php 
     if (session_status() === PHP_SESSION_NONE) {
         session_start();
     }
	 require_once __DIR__ . '/config/dz.php';

     $db = getDbConnection();
     $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'admin';
     $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 2; // Default host John Doe

     // Handle adding a room
     $success_message = '';
     $error_message = '';
     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_room') {
         $property_id = $_POST['property_id'];
         $room_number = $_POST['room_number'];
         $room_type_id = $_POST['room_type_id'];
         $price = $_POST['price'];
         $capacity = $_POST['capacity'];
         $photos = isset($_POST['photos']) ? $_POST['photos'] : [];

         // Prepare curl request to Laravel API
         $api_token = isset($_SESSION['api_token']) ? $_SESSION['api_token'] : '';
         $apiUrl = "http://127.0.0.1:8000/api/properties/{$property_id}/rooms";

         $ch = curl_init($apiUrl);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
             'room_number' => $room_number,
             'room_type_id' => $room_type_id,
             'price' => $price,
             'capacity' => $capacity,
             'amenities' => ["AC", "Wifi", "Shower", "LED TV"],
             'photos' => $photos,
         ]));
         curl_setopt($ch, CURLOPT_HTTPHEADER, [
             'Content-Type: application/json',
             'Accept: application/json',
             "Authorization: Bearer {$api_token}",
         ]);
         $response = curl_exec($ch);
         $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);

         if ($httpCode === 201) {
             $success_message = "Room {$room_number} successfully added to property!";
         } else {
             $respData = json_decode($response, true);
             $error_message = isset($respData['message']) ? $respData['message'] : "Failed to add room via Laravel API.";
         }
     }

     // Load properties for the owner / admin dropdown
     if ($userRole === 'admin') {
         $properties = $db->query("SELECT * FROM properties ORDER BY name")->fetchAll();
     } else {
         $stmt = $db->prepare("SELECT * FROM properties WHERE host_id = ? ORDER BY name");
         $stmt->execute([$userId]);
         $properties = $stmt->fetchAll();
     }

     // Load rooms for list (with optional search filter by lodge name or location)
     $search = isset($_GET['search']) ? trim($_GET['search']) : '';
     $propertyIds = array_column($properties, 'id');
     $rooms = [];
     if (!empty($propertyIds)) {
         if (!empty($search)) {
             $searchTerm = '%' . $search . '%';
             $stmt = $db->prepare("
                 SELECT r.*, p.name as property_name, p.city, p.area, p.address 
                 FROM rooms r 
                 JOIN properties p ON r.property_id = p.id 
                 WHERE (p.name LIKE ? OR p.city LIKE ? OR p.area LIKE ? OR p.address LIKE ? OR r.room_number LIKE ?)
                 ORDER BY r.created_at DESC
             ");
             $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
             $rooms = $stmt->fetchAll();
         } else {
             $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
             $stmt = $db->prepare("
                 SELECT r.*, p.name as property_name, p.city, p.area, p.address 
                 FROM rooms r 
                 JOIN properties p ON r.property_id = p.id 
                 WHERE r.property_id IN ($placeholders)
                 ORDER BY r.created_at DESC, r.room_number ASC
             ");
             $stmt->execute($propertyIds);
             $rooms = $stmt->fetchAll();
         }
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
				<div class="mt-4 d-flex justify-content-between align-items-center flex-wrap">
					<div class="card-action coin-tabs mb-2">
                        <?php 
                        $all_count = count($rooms);
                        $active_count = count(array_filter($rooms, function($r) { return strtolower($r['status']) !== 'maintenance'; }));
                        $inactive_count = count(array_filter($rooms, function($r) { return strtolower($r['status']) === 'maintenance'; }));
                        ?>
						<ul class="nav nav-tabs" role="tablist">
							<li class="nav-item">
								<a class="nav-link active" data-bs-toggle="tab" href="#AllRooms">All Rooms (<?php echo $all_count; ?>)</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#ActiveEmployee">Active Rooms (<?php echo $active_count; ?>)</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" data-bs-toggle="tab" href="#InactiveEmployee">Inactive Rooms (<?php echo $inactive_count; ?>)</a>
							</li>
						</ul>
					</div>
					<div class="d-flex align-items-center mb-2"> 
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success me-3 mb-0"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger me-3 mb-0"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
						<a href="add-room.php" class="btn btn-primary font-w600"><i class="fas fa-plus-circle me-1"></i> Add Room to Lodge</a>
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
									<div class="tab-pane fade active show" id="AllRooms">
										<div class="table-responsive">
											<table class="table card-table display mb-4 shadow-hover table-responsive-lg" id="guestTable-all3">
												<thead>
													<tr>
														<th class="bg-none">
															<div class="form-check style-1">
															  <input class="form-check-input" type="checkbox" value="" id="checkAll3">
															</div>
														</th>
														<th>Room Name</th>
														<th>Bed Type</th>
														<th>Room Floor</th>
														<th>Facilities</th>
														<th>Rate</th>
														<th>Status</th>
														<th class="bg-none"></th>
													</tr>
												</thead>
												<tbody>
                                                    <?php if (empty($rooms)): ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No rooms found.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($rooms as $room): ?>
                                                            <?php 
                                                                $amenities_arr = !empty($room['amenities']) ? json_decode($room['amenities'], true) : [];
                                                                if (!is_array($amenities_arr)) {
                                                                    $amenities_arr = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                                                                }
                                                                $amenities_str = implode(', ', array_map('htmlspecialchars', $amenities_arr));
                                                                
                                                                $photos_arr = !empty($room['photos']) ? json_decode($room['photos'], true) : [];
                                                                $photo = (is_array($photos_arr) && !empty($photos_arr)) ? $photos_arr[0] : 'assets/images/room/room4.jpg';
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check style-1">
                                                                      <input class="form-check-input" type="checkbox" value="">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="room-list-bx d-flex align-items-center">
                                                                        <img class="me-3 rounded" src="<?php echo htmlspecialchars($photo); ?>" alt="" style="width:50px; height:50px; object-fit:cover;">
                                                                        <div>
                                                                            <span class="text-secondary fs-14 d-block">#R-<?php echo htmlspecialchars($room['id']); ?></span>
                                                                            <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_number']); ?></span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_type_id'] ?? 'Standard'); ?></span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <span class="fs-16 font-w500"><?php echo htmlspecialchars($room['property_name']); ?></span>
                                                                    </div>
                                                                </td>
                                                                <td class="facility">
                                                                    <div>
                                                                        <span class="fs-16 comments"><?php echo $amenities_str ? $amenities_str : 'Standard Amenities'; ?></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="">
                                                                        <span class="mb-2">Rate</span>	
                                                                        <span class="font-w500">TSh <?php echo number_format($room['price'] ?? 0); ?><small class="fs-14 ms-2">/night</small></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="btn <?php echo (strtolower($room['status']) === 'available') ? 'btn-success' : 'btn-danger'; ?> btn-md text-uppercase"><?php echo htmlspecialchars($room['status']); ?></span>
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
                                                                            <a class="dropdown-item" href="add-room.php?id=<?php echo $room['id']; ?>">Edit</a>
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
									<div class="tab-pane" id="ActiveEmployee">
										<div class="table-responsive">
											<table class="table card-table display mb-4 shadow-hover table-responsive-lg" id="guestTable-all1">
												<thead>
													<tr>
														<th class="bg-none">
															<div class="form-check style-1">
															  <input class="form-check-input" type="checkbox" value="" id="checkAll1">
															</div>
														</th>
														<th>Room Name</th>
														<th>Bed Type</th>
														<th>Room Floor</th>
														<th>Facilities</th>
														<th>Rate</th>
														<th>Status</th>
														<th class="bg-none"></th>
													</tr>
												</thead>
												<tbody>
                                                    <?php 
                                                        $activeRooms = array_filter($rooms, function($r) {
                                                            return strtolower($r['status']) !== 'maintenance';
                                                        });
                                                    ?>
                                                    <?php if (empty($activeRooms)): ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No active rooms found.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($activeRooms as $room): ?>
                                                            <?php 
                                                                $amenities_arr = !empty($room['amenities']) ? json_decode($room['amenities'], true) : [];
                                                                if (!is_array($amenities_arr)) {
                                                                    $amenities_arr = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                                                                }
                                                                $amenities_str = implode(', ', array_map('htmlspecialchars', $amenities_arr));
                                                                
                                                                $photos_arr = !empty($room['photos']) ? json_decode($room['photos'], true) : [];
                                                                $photo = (is_array($photos_arr) && !empty($photos_arr)) ? $photos_arr[0] : 'assets/images/room/room4.jpg';
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check style-1">
                                                                      <input class="form-check-input" type="checkbox" value="">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="room-list-bx d-flex align-items-center">
                                                                        <img class="me-3 rounded" src="<?php echo htmlspecialchars($photo); ?>" alt="" style="width:50px; height:50px; object-fit:cover;">
                                                                        <div>
                                                                            <span class="text-secondary fs-14 d-block">#R-<?php echo htmlspecialchars($room['id']); ?></span>
                                                                            <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_number']); ?></span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_type_id'] ?? 'Standard'); ?></span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <span class="fs-16 font-w500"><?php echo htmlspecialchars($room['property_name']); ?></span>
                                                                    </div>
                                                                </td>
                                                                <td class="facility">
                                                                    <div>
                                                                        <span class="fs-16 comments"><?php echo $amenities_str ? $amenities_str : 'Standard Amenities'; ?></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="">
                                                                        <span class="mb-2">Rate</span>	
                                                                        <span class="font-w500">TSh <?php echo number_format($room['price'] ?? 0); ?><small class="fs-14 ms-2">/night</small></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="btn <?php echo (strtolower($room['status']) === 'available') ? 'btn-success' : 'btn-danger'; ?> btn-md text-uppercase"><?php echo htmlspecialchars($room['status']); ?></span>
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
                                                                            <a class="dropdown-item" href="add-room.php?id=<?php echo $room['id']; ?>">Edit</a>
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
									<div class="tab-pane" id="InactiveEmployee">
										<div class="table-responsive">
											<table class="table card-table display mb-4 shadow-hover table-responsive-lg" id="guestTable-all2">
												<thead>
													<tr>
														<th class="bg-none">
															<div class="form-check style-1">
															  <input class="form-check-input" type="checkbox" value="" id="checkAll2">
															</div>
														</th>
														<th>Room Name</th>
														<th>Bed Type</th>
														<th>Room Floor</th>
														<th>Facilities</th>
														<th>Rate</th>
														<th>Status</th>
														<th class="bg-none"></th>
													</tr>
												</thead>
												<tbody>
                                                    <?php 
                                                        $inactiveRooms = array_filter($rooms, function($r) {
                                                            return strtolower($r['status']) === 'maintenance';
                                                        });
                                                    ?>
                                                    <?php if (empty($inactiveRooms)): ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center">No inactive rooms found.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($inactiveRooms as $room): ?>
                                                            <?php 
                                                                $amenities_arr = !empty($room['amenities']) ? json_decode($room['amenities'], true) : [];
                                                                if (!is_array($amenities_arr)) {
                                                                    $amenities_arr = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                                                                }
                                                                $amenities_str = implode(', ', array_map('htmlspecialchars', $amenities_arr));
                                                                
                                                                $photos_arr = !empty($room['photos']) ? json_decode($room['photos'], true) : [];
                                                                $photo = (is_array($photos_arr) && !empty($photos_arr)) ? $photos_arr[0] : 'assets/images/room/room4.jpg';
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check style-1">
                                                                      <input class="form-check-input" type="checkbox" value="">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="room-list-bx d-flex align-items-center">
                                                                        <img class="me-3 rounded" src="<?php echo htmlspecialchars($photo); ?>" alt="" style="width:50px; height:50px; object-fit:cover;">
                                                                        <div>
                                                                            <span class="text-secondary fs-14 d-block">#R-<?php echo htmlspecialchars($room['id']); ?></span>
                                                                            <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_number']); ?></span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="fs-16 font-w500 text-nowrap"><?php echo htmlspecialchars($room['room_type_id'] ?? 'Standard'); ?></span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <span class="fs-16 font-w500"><?php echo htmlspecialchars($room['property_name']); ?></span>
                                                                    </div>
                                                                </td>
                                                                <td class="facility">
                                                                    <div>
                                                                        <span class="fs-16 comments"><?php echo $amenities_str ? $amenities_str : 'Standard Amenities'; ?></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="">
                                                                        <span class="mb-2">Rate</span>	
                                                                        <span class="font-w500">TSh <?php echo number_format($room['price'] ?? 0); ?><small class="fs-14 ms-2">/night</small></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="btn btn-danger btn-md text-uppercase"><?php echo htmlspecialchars($room['status']); ?></span>
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
                                                                            <a class="dropdown-item" href="add-room.php?id=<?php echo $room['id']; ?>">Edit</a>
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
				$('#reportrange span').php(start.format('D MMMM YYYY') + ' &nbsp - &nbsp ' + end.format('D MMMM YYYY'));
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
</script>

</body>
</html>