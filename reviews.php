<?php 
	 require_once __DIR__ . '/config/dz.php';
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
				<div class="row mt-4">
					<div class="col-xl-12">
						<div class="row">

							<div class="my-4 d-flex justify-content-between align-items-center flex-wrap">
								<div class="card-action coin-tabs mb-4">
									<ul class="nav nav-tabs" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" data-bs-toggle="tab" href="#AllCustomerReviews">All Customer Reviews</a>
										</li>
									</ul>
								</div>
								<div class="newest mb-4">
									<select class="default-select">
										<option>Newest</option>
										<option>Oldest</option>
									</select>
								</div>
							</div>
							<div class="col-xl-12">
								<div class="card">
									<div class="card-body p-0">
										<div class="tab-content">	
											<div class="tab-pane active show" id="AllCustomerReviews">
												<div class="table-responsive">
													<table class="table card-table display mb-4 shadow-hover table-responsive-lg review-tbl" id="guestTable-all">
														<thead>
															<tr>
																<th class="bg-none">
																	<div class="form-check style-1">
																	  <input class="form-check-input" type="checkbox" value="" id="checkAll">
																	</div>
																</th>
																<th>Order ID</th>
																<th>Date</th>
																<th>Customer</th>
																<th class="text-center">Comment</th>

															</tr>
														</thead>
														<tbody id="admin-reviews-tbody">
															<!-- Dynamic rows injected here -->
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
		function TravlCarousel()
			{

				/*  testimonial one function by = owl.carousel.js */
				jQuery('.reviews-slider').owlCarousel({
					loop:false,
					margin:15,
					nav:false,
					autoplaySpeed: 3000,
					navSpeed: 3000,
					paginationSpeed: 3000,
					slideSpeed: 3000,
					smartSpeed: 3000,
					autoplay: false,
					animateOut: 'fadeOut',
					dots:false,
					navigation:false,
					navText: ['', ''],
					responsive:{
						0:{
							items:1
						},
						
						768:{
							items:2
						},			
						
						1400:{
							items:2
						},
						1600:{
							items:3
						},
						1750:{
							items:3
						}
					}
				})
			}

			jQuery(window).on('load',function(){
				
				// Fetch real reviews
				const adminToken = localStorage.getItem('fastnet_admin_token');
				const apiUrl = 'local-api-reviews.php';
				
				fetch(apiUrl, {
					headers: {
						'Authorization': `Bearer ${adminToken}`,
						'Accept': 'application/json'
					}
				})
				.then(res => res.json())
				.then(reviews => {
					if (!Array.isArray(reviews)) reviews = [];
					
					const tbody = document.getElementById('admin-reviews-tbody');
					const slider = document.getElementById('admin-reviews-slider');
					
					let tbodyHtml = '';
					let sliderHtml = '';
					
					reviews.forEach((r, idx) => {
						const d = new Date(r.created_at);
						const dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
						const rating = parseInt(r.rating) || 5;
						let starsHtml = '';
						for(let i=1; i<=5; i++) {
							starsHtml += `<li><a href="javascript:void(0);"><i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-secondary'}"></i></a></li>`;
						}
						
						// Table Row
						tbodyHtml += `
							<tr>
								<td>
									<div class="form-check style-1">
									  <input class="form-check-input" type="checkbox" value="">
									</div>
								</td>
								<td><span>#${r.id}</span></td>
								<td><span class="text-nowrap">${dateStr}</span></td>
								<td><span class="text-nowrap">${r.user_name}</span><br><small class="text-muted">${r.property_name}</small></td>
								<td class="job-desk1">
									<span><ul class="stars">${starsHtml}</ul></span>
									<span><p class="fs-16">${r.comment || ''}</p></span>
								</td>

							</tr>
						`;
						

					});
					
					if (tbody) tbody.innerHTML = tbodyHtml;
				})
				.catch(err => {
					console.error("Error loading reviews", err);
					setTimeout(function(){
						TravlCarousel();
					}, 1000); 
				});
				
			});
	</script>

</body>
</html>