<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

// Handle AJAX actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $api_token = $_SESSION['api_token'] ?? '';
    
    if ($action === 'create_property') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Send request to Laravel API to create property
        $ch = curl_init("http://127.0.0.1:8000/api/properties");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'name' => $input['name'] ?? '',
            'description' => $input['description'] ?? '',
            'address' => $input['address'] ?? '',
            'city' => $input['city'] ?? '',
            'area' => $input['area'] ?? '',
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
            'price_per_night' => $input['price_per_night'] ?? 0,
            'image_url' => $input['image_url'] ?? '',
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$api_token}"
        ]);
        $resData = json_decode($response, true);
        $db = getDbConnection();
        $userId = $_SESSION['user_id'] ?? 2;

        if ($httpCode === 201 && isset($resData['id'])) {
            // Also insert / sync locally so web portal displays instantly
            try {
                $stmt = $db->prepare("INSERT INTO properties (id, name, description, address, city, area, price_per_night, latitude, longitude, host_id, image_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', datetime('now'), datetime('now'))");
                $stmt->execute([
                    $resData['id'],
                    $input['name'] ?? '',
                    $input['description'] ?? '',
                    $input['address'] ?? '',
                    $input['city'] ?? '',
                    $input['area'] ?? '',
                    $input['price_per_night'] ?? 0,
                    $input['latitude'] ?? null,
                    $input['longitude'] ?? null,
                    $userId,
                    $input['image_url'] ?? '',
                ]);
            } catch (Exception $e) {}
        } else {
            // Local fallback creation
            try {
                $stmt = $db->prepare("INSERT INTO properties (name, description, address, city, area, price_per_night, latitude, longitude, host_id, image_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', datetime('now'), datetime('now'))");
                $stmt->execute([
                    $input['name'] ?? '',
                    $input['description'] ?? '',
                    $input['address'] ?? '',
                    $input['city'] ?? '',
                    $input['area'] ?? '',
                    $input['price_per_night'] ?? 0,
                    $input['latitude'] ?? null,
                    $input['longitude'] ?? null,
                    $userId,
                    $input['image_url'] ?? '',
                ]);
                $lastId = $db->lastInsertId();
                $resData = [
                    'id' => $lastId,
                    'name' => $input['name'] ?? '',
                    'status' => 'Pending'
                ];
                $httpCode = 201;
            } catch (Exception $e) {}
        }
        
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($resData);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Lodge Onboarding Wizard | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <!-- Mapbox GL JS CDN -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js"></script>
    <style>
        .step-container {
            display: none;
        }
        .step-container.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #eee;
            z-index: 1;
            transform: translateY(-50%);
        }
        .step-indicator .step {
            z-index: 2;
            background: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid #eee;
            font-weight: 600;
            color: #888;
            transition: all 0.3s;
            font-size: 13px;
        }
        .step-indicator .step.active {
            border-color: #135846;
            color: #135846;
            box-shadow: 0 0 10px rgba(19, 88, 70, 0.15);
        }
        .step-indicator .step.completed {
            background: #135846;
            border-color: #135846;
            color: #fff;
        }
        .upload-dropzone {
            border: 2px dashed #135846;
            background: #f4f9f7;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-dropzone:hover {
            background: #eef7f3;
            border-color: #1a7e65;
        }
        .preview-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            height: 100px;
            background: #eee;
        }
        .preview-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-card .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-card .upload-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #135846;
            width: 0%;
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
            <div class="container-fluid">
                <!-- Onboarding Card -->
                <div class="card shadow-sm border-0" style="border-radius: 16px;">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fs-22 font-w600 text-black mb-1">Onboard Your Property</h3>
                            <p class="text-muted mb-0">Follow our simplified wizard to register your lodge and rooms.</p>
                        </div>
                        <span class="badge badge-success fs-14 py-2 px-3" id="wizardProgressBadge">Step 1 of 7</span>
                    </div>

                    <div class="card-body px-4 py-4">
                        <!-- Step Indicators -->
                        <div class="step-indicator d-none d-lg-flex mb-4">
                            <div class="step active" id="ind-1">1. Lodge Info</div>
                            <div class="step" id="ind-2">2. Details & Policies</div>
                            <div class="step" id="ind-3">3. Location</div>
                            <div class="step" id="ind-4">4. Photos & Amenities</div>
                            <div class="step" id="ind-5">5. Room Management</div>
                            <div class="step" id="ind-6">6. Review & Submit</div>
                        </div>

                        <!-- 1. Basic Lodge Information -->
                        <div class="step-container active" id="step-1">
                            <h4 class="font-w600 text-dark mb-4">Lodge Basic Information</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Lodge / Property Name</label>
                                    <input type="text" id="lodgeName" class="form-control style-1 border" placeholder="e.g. Sunrise Lodge" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Property Type</label>
                                    <select id="lodgeType" class="form-control default-select style-1 border">
                                        <option value="Lodge">Lodge</option>
                                        <option value="Hotel">Hotel</option>
                                        <option value="Apartment">Apartment</option>
                                        <option value="Resort">Resort</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Contact Email Address</label>
                                    <input type="email" id="lodgeEmail" class="form-control style-1 border" placeholder="e.g. contact@sunriselodge.com">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label font-w500">Contact Phone Number</label>
                                    <input type="text" id="lodgePhone" class="form-control style-1 border" placeholder="e.g. +255 712 345 678">
                                </div>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-primary px-4 py-2" onclick="goToStep(2)">Next: Lodge Details</button>
                            </div>
                        </div>

                        <!-- 2. Lodge Details -->
                        <div class="step-container" id="step-2">
                            <h4 class="font-w600 text-dark mb-4">Lodge Details & Policies</h4>
                            <div class="mb-3">
                                <label class="form-label font-w500">General Description</label>
                                <textarea id="lodgeDescription" class="form-control style-1 border" rows="4" placeholder="Describe your property's unique characteristics, atmosphere, and amenities..."></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Check-in Policies</label>
                                    <input type="text" id="lodgeCheckIn" class="form-control style-1 border" placeholder="e.g. From 14:00 PM">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label font-w500">Check-out Policies</label>
                                    <input type="text" id="lodgeCheckOut" class="form-control style-1 border" placeholder="e.g. Before 11:00 AM">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-secondary light" onclick="goToStep(1)">Back</button>
                                <button class="btn btn-primary px-4 py-2" onclick="goToStep(3)">Next: Location</button>
                            </div>
                        </div>

                        <!-- 3. Location -->
                        <div class="step-container" id="step-3">
                            <h4 class="font-w600 text-dark mb-4">Lodge Location</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">City</label>
                                    <input type="text" id="lodgeCity" class="form-control style-1 border" placeholder="e.g. Arusha" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Area</label>
                                    <input type="text" id="lodgeArea" class="form-control style-1 border" placeholder="e.g. Sekei" required>
                                </div>
                            </div>
                             <div class="mb-3">
                                 <label class="form-label font-w500">Full Address</label>
                                 <input type="text" id="lodgeAddress" class="form-control style-1 border" placeholder="e.g. 45 Sekei Road, Arusha">
                             </div>
                             
                             <div class="mb-4">
                                 <div class="d-flex justify-content-between align-items-center mb-2">
                                     <label class="form-label font-w500 mb-0">Select Coordinates on Map</label>
                                     <span id="geocodeStatus" class="badge bg-light text-dark fs-12 font-w400">Type address above to auto-pin location</span>
                                 </div>
                                 <div id="onboardingMap" style="height: 400px; border-radius: 12px; border: 1px solid #e0e0e0; z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"></div>
                                 <input type="hidden" id="lodgeLatitude" value="-3.3730">
                                 <input type="hidden" id="lodgeLongitude" value="36.6850">
                                 <div class="d-flex justify-content-between align-items-center mt-2">
                                     <span class="fs-12 text-muted"><i class="fas fa-info-circle me-1"></i> You can type City, Area & Full Address above to auto-center the map, or click/drag the marker manually.</span>
                                     <span class="fs-12 font-w600 text-primary" id="coordDisplay">Lat: -3.3730, Lng: 36.6850</span>
                                 </div>
                             </div>

                             <div class="d-flex justify-content-between">
                                 <button class="btn btn-secondary light" onclick="goToStep(2)">Back</button>
                                 <button class="btn btn-primary px-4 py-2" onclick="goToStep(4)">Next: Photos & Amenities</button>
                             </div>
                        </div>

                        <!-- 4. Photos, Documents & Amenities -->
                        <div class="step-container" id="step-4">
                            <h4 class="font-w600 text-dark mb-4">Lodge Photos, Verification Documents & Amenities</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Main Property Photo</label>
                                    <div class="upload-dropzone text-center p-4" id="mainLodgeDropzone">
                                        <i class="flaticon-381-picture fs-30 text-primary mb-2"></i>
                                        <p class="mb-0 text-muted" id="mainLodgeText">Click or Drag main image here</p>
                                        <input type="file" id="mainLodgeInput" class="d-none" accept="image/*">
                                        <input type="hidden" id="mainLodgeUrl" value="">
                                    </div>
                                    <div id="mainLodgePreview" class="mt-3 text-center d-none">
                                        <img src="" alt="Main Lodge Image" class="rounded shadow-sm" style="max-height: 150px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-w500">Business Registration / Tourism License (PDF or Image)</label>
                                    <div class="upload-dropzone text-center p-4" id="licenseDropzone">
                                        <i class="flaticon-381-file fs-30 text-primary mb-2"></i>
                                        <p class="mb-0 text-muted" id="licenseText">Click to upload Business License / Certificate</p>
                                        <input type="file" id="licenseInput" class="d-none" accept="image/*,.pdf">
                                        <input type="hidden" id="licenseUrl" value="">
                                    </div>
                                    <div id="licensePreview" class="mt-2 text-center d-none">
                                        <span class="badge bg-success py-2 px-3"><i class="fas fa-check me-1"></i> Document Uploaded</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-secondary light" onclick="goToStep(3)">Back</button>
                                <button class="btn btn-primary px-4 py-2" onclick="goToStep(5)">Next: Room Management</button>
                            </div>
                        </div>

                        <!-- 5. Room Management -->
                        <div class="step-container" id="step-5">
                            <div class="row align-items-center mb-4">
                                <div class="col">
                                    <h4 class="font-w600 text-dark mb-1">Rooms Inventory</h4>
                                    <p class="text-muted mb-0" id="roomCountText">Lodge rooms: 0 registered</p>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#bulkRoomModal">+ Add Multiple Rooms</button>
                                    <button class="btn btn-primary btn-sm" onclick="openRoomEditor(null)">+ Add Room</button>
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table card-table display mb-4 shadow-hover table-responsive-lg">
                                    <thead>
                                        <tr>
                                            <th>Number</th>
                                            <th>Type</th>
                                            <th>Floor</th>
                                            <th>Price (TSh)</th>
                                            <th>Status</th>
                                            <th>Photos</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="roomsTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No rooms added yet. Create rooms individually or in bulk.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-secondary light" onclick="goToStep(4)">Back</button>
                                <button class="btn btn-primary px-4 py-2" onclick="goToStep(6)">Next: Review & Submit</button>
                            </div>
                        </div>

                        <!-- 6. Reusable Room Editor (Custom Screen) -->
                        <div class="step-container" id="step-room-editor">
                            <div class="row align-items-center mb-4">
                                <div class="col">
                                    <h4 class="font-w600 text-dark mb-1" id="editorHeaderTitle">Configure Room</h4>
                                    <p class="text-muted mb-0">Set room-level information, custom pricing, amenities, and photos.</p>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-secondary btn-sm" onclick="closeRoomEditor()">Back to Room Management</button>
                                </div>
                            </div>

                            <!-- Tabs inside one room editor -->
                            <div class="card shadow-none border">
                                <div class="card-header bg-light pb-0 pt-3 border-0">
                                    <ul class="nav nav-tabs" id="editorTabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#tabDetails">Details</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#tabPhotosAmenities">Photos & Amenities</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#tabPricing">Pricing & Availability</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Tab: Details -->
                                        <div class="tab-pane fade show active" id="tabDetails">
                                            <input type="hidden" id="editRoomIdx" value="">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label font-w500">Room Number / Room Name</label>
                                                    <input type="text" id="roomNum" class="form-control style-1 border" placeholder="e.g. Room 101" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label font-w500">Room Type</label>
                                                    <select id="roomType" class="form-control default-select style-1 border">
                                                        <option value="Deluxe">Deluxe</option>
                                                        <option value="Standard">Standard</option>
                                                        <option value="Suite">Suite</option>
                                                        <option value="Executive">Executive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label font-w500">Floor</label>
                                                    <input type="text" id="roomFloor" class="form-control style-1 border" placeholder="e.g. 1st Floor">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label font-w500">Bed Configuration</label>
                                                    <input type="text" id="roomBeds" class="form-control style-1 border" placeholder="e.g. 1 King Bed">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label font-w500">Room Size (m²)</label>
                                                    <input type="text" id="roomSize" class="form-control style-1 border" placeholder="e.g. 28">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label font-w500">Max Adults</label>
                                                    <input type="number" id="roomAdults" class="form-control style-1 border" value="2">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label font-w500">Max Children</label>
                                                    <input type="number" id="roomChildren" class="form-control style-1 border" value="0">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label font-w500">Unique Description</label>
                                                <textarea id="roomDesc" class="form-control style-1 border" rows="4" placeholder="Spacious room overlooking the garden with luxury decor..."></textarea>
                                            </div>
                                        </div>

                                        <!-- Tab: Photos & Amenities -->
                                        <div class="tab-pane fade" id="tabPhotosAmenities">
                                            <div class="mb-4">
                                                <label class="form-label font-w600 text-dark">Room Photos (Minimum 4 images required)</label>
                                                <div class="row g-2 mb-3" id="roomPhotosPreviews"></div>
                                                <div class="upload-dropzone text-center p-4" id="roomPhotosDropzone">
                                                    <i class="flaticon-381-picture-1 fs-30 text-primary mb-2 d-block"></i>
                                                    <span class="fs-14 font-w600 text-primary d-block">Click or Drop Room Photos</span>
                                                    <input type="file" id="roomPhotosInput" class="d-none" multiple accept="image/*">
                                                </div>
                                                <div class="alert alert-danger py-2 mt-2 d-none" id="roomPhotosErrorMsg">Please upload at least 4 photos for this room.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label font-w600 text-dark mb-1">Room Amenities</label>
                                                <div class="row">
                                                    <div class="col-6 col-md-3 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input room-amenity" type="checkbox" value="Air conditioning" id="am-ac">
                                                            <label class="form-check-label" for="am-ac">Air conditioning</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input room-amenity" type="checkbox" value="Wi-Fi" id="am-wifi">
                                                            <label class="form-check-label" for="am-wifi">Wi-Fi</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input room-amenity" type="checkbox" value="LED TV" id="am-tv">
                                                            <label class="form-check-label" for="am-tv">LED TV</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input room-amenity" type="checkbox" value="Balcony" id="am-balcony">
                                                            <label class="form-check-label" for="am-balcony">Balcony</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input room-amenity" type="checkbox" value="Shower" id="am-shower">
                                                            <label class="form-check-label" for="am-shower">Shower</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tab: Pricing -->
                                        <div class="tab-pane fade" id="tabPricing">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label font-w500">Room-Specific Price (TSh)</label>
                                                    <input type="number" id="roomPrice" class="form-control style-1 border" placeholder="e.g. 95000" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label font-w500">Status</label>
                                                    <select id="roomStatus" class="form-control default-select style-1 border">
                                                        <option value="available">Active / Available</option>
                                                        <option value="maintenance">Maintenance</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top text-end">
                                        <button class="btn btn-secondary light me-2" onclick="closeRoomEditor()">Cancel</button>
                                        <button class="btn btn-primary px-4" onclick="saveRoomDetails()">Save Room</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 9. Review & Submit -->
                        <div class="step-container" id="step-6">
                            <h4 class="font-w600 text-dark mb-4">Review Your Property & Rooms</h4>
                            
                            <!-- Summary Container -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 bg-light">
                                        <h5 class="font-w600 text-dark mb-3">Lodge Information</h5>
                                        <div class="mb-3 text-center" id="summaryLodgeImage">
                                            <span class="text-muted">No main image uploaded.</span>
                                        </div>
                                        <p class="mb-1"><strong>Name:</strong> <span id="sumLodgeName"></span></p>
                                        <p class="mb-1"><strong>Type:</strong> <span id="sumLodgeType"></span></p>
                                        <p class="mb-1"><strong>Contact:</strong> <span id="sumLodgeContact"></span></p>
                                        <p class="mb-1"><strong>Location:</strong> <span id="sumLodgeLocation"></span></p>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="border rounded p-3">
                                        <h5 class="font-w600 text-dark mb-3">Registered Rooms Inventory</h5>
                                        <div class="table-responsive">
                                            <table class="table card-table display mb-0 table-responsive-md">
                                                <thead>
                                                    <tr>
                                                        <th>Number</th>
                                                        <th>Type</th>
                                                        <th>Price (TSh)</th>
                                                        <th>Capacity</th>
                                                        <th>Photos</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sumRoomsTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-secondary light" onclick="goToStep(5)">Back</button>
                                <button class="btn btn-success px-5 py-2" id="submitOnboardingBtn" onclick="submitOnboarding()">Submit for Admin Approval</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php include 'elements/footer.php'; ?>
    </div>

    <!-- Bulk Room Modal -->
    <div class="modal fade" id="bulkRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header border-0 bg-primary py-4 px-4 text-white">
                    <h5 class="modal-title text-white font-w600 fs-18">Bulk Add Rooms</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500 text-dark">Room Number From</label>
                            <input type="number" id="bulkFrom" class="form-control style-1 border" placeholder="e.g. 101" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500 text-dark">Room Number To</label>
                            <input type="number" id="bulkTo" class="form-control style-1 border" placeholder="e.g. 120" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500 text-dark">Default Type</label>
                            <select id="bulkType" class="form-control default-select style-1 border">
                                <option value="Deluxe">Deluxe</option>
                                <option value="Standard">Standard</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label font-w500 text-dark">Default Price (TSh)</label>
                            <input type="number" id="bulkPrice" class="form-control style-1 border" placeholder="e.g. 90000" value="90000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-danger btn-md light rounded" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-md rounded px-4" onclick="generateBulkRooms()">Generate Rooms</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'elements/page-js.php'; ?>

    <script>
        let currentStep = 1;
        let registeredRooms = [];
        let editorRoomPhotos = [];

        let geocodeTimeout = null;
        let map;
        let marker;

        async function initMap() {
            if (map) return;
            let token = 'YOUR_MAPBOX_ACCESS_TOKEN';
            let mapStyle = 'mapbox://styles/mapbox/streets-v12';

            try {
                const res = await fetch('http://127.0.0.1:8000/api/map-config');
                if (res.ok) {
                    const cfg = await res.json();
                    if (cfg.mapbox_token) token = cfg.mapbox_token;
                    if (cfg.style) mapStyle = cfg.style;
                }
            } catch (err) {
                console.warn('Using fallback backend Mapbox key');
            }

            mapboxgl.accessToken = token;
            
            map = new mapboxgl.Map({
                container: 'onboardingMap',
                style: mapStyle,
                center: [36.6850, -3.3730], // [lng, lat]
                zoom: 13
            });

            // Add navigation and geolocate controls
            map.addControl(new mapboxgl.NavigationControl(), 'top-right');
            map.addControl(new mapboxgl.FullscreenControl(), 'top-right');

            marker = new mapboxgl.Marker({
                draggable: true,
                color: '#135846'
            })
            .setLngLat([36.6850, -3.3730])
            .addTo(map);

            function updateCoordinates(lat, lng) {
                document.getElementById('lodgeLatitude').value = lat.toFixed(6);
                document.getElementById('lodgeLongitude').value = lng.toFixed(6);
                const coordDisp = document.getElementById('coordDisplay');
                if (coordDisp) coordDisp.innerText = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
            }

            marker.on('dragend', () => {
                const lngLat = marker.getLngLat();
                updateCoordinates(lngLat.lat, lngLat.lng);
            });

            map.on('click', (e) => {
                marker.setLngLat(e.lngLat);
                updateCoordinates(e.lngLat.lat, e.lngLat.lng);
            });

            // Setup input listeners for auto geocoding
            const cityInput = document.getElementById('lodgeCity');
            const areaInput = document.getElementById('lodgeArea');
            const addressInput = document.getElementById('lodgeAddress');

            [cityInput, areaInput, addressInput].forEach(input => {
                if (input) {
                    input.addEventListener('input', triggerAutoGeocode);
                }
            });
        }

        function triggerAutoGeocode() {
            if (geocodeTimeout) clearTimeout(geocodeTimeout);
            geocodeTimeout = setTimeout(() => {
                performGeocode();
            }, 600);
        }

        async function performGeocode() {
            const city = document.getElementById('lodgeCity')?.value.trim() || '';
            const area = document.getElementById('lodgeArea')?.value.trim() || '';
            const address = document.getElementById('lodgeAddress')?.value.trim() || '';

            const statusBadge = document.getElementById('geocodeStatus');

            const queryParts = [address, area, city].filter(p => p.length > 0);
            if (queryParts.length === 0) return;

            const searchQuery = queryParts.join(', ');
            if (statusBadge) {
                statusBadge.className = 'badge bg-warning text-dark fs-12 font-w400';
                statusBadge.innerText = 'Searching map location...';
            }

            try {
                const token = mapboxgl.accessToken;
                const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(searchQuery)}.json?access_token=${token}&limit=1`;
                const resp = await fetch(url);
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.features && data.features.length > 0) {
                        const [lng, lat] = data.features[0].center;
                        map.flyTo({ center: [lng, lat], zoom: 15, essential: true });
                        marker.setLngLat([lng, lat]);
                        
                        document.getElementById('lodgeLatitude').value = lat.toFixed(6);
                        document.getElementById('lodgeLongitude').value = lng.toFixed(6);
                        const coordDisp = document.getElementById('coordDisplay');
                        if (coordDisp) coordDisp.innerText = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;

                        if (statusBadge) {
                            statusBadge.className = 'badge bg-success text-white fs-12 font-w400';
                            statusBadge.innerText = 'Location pinned automatically ✓';
                        }
                    } else if (statusBadge) {
                        statusBadge.className = 'badge bg-secondary text-white fs-12 font-w400';
                        statusBadge.innerText = 'Location not found on map';
                    }
                }
            } catch (e) {
                if (statusBadge) {
                    statusBadge.className = 'badge bg-danger text-white fs-12 font-w400';
                    statusBadge.innerText = 'Geocoding error';
                }
            }
        }

        function goToStep(step) {
            // Validation
            if (step === 2 && currentStep === 1) {
                if (!document.getElementById('lodgeName').value.trim()) {
                    alert('Please enter a lodge/property name.');
                    return;
                }
            }
            if (step === 4 && currentStep === 3) {
                if (!document.getElementById('lodgeCity').value.trim() || !document.getElementById('lodgeArea').value.trim()) {
                    alert('Please fill out the city and area fields.');
                    return;
                }
            }

            // Hide all active
            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-indicator .step').forEach(el => {
                el.classList.remove('active');
                el.classList.remove('completed');
            });

            // Set indicators
            for (let i = 1; i < step; i++) {
                const ind = document.getElementById(`ind-${i}`);
                if (ind) ind.classList.add('completed');
            }

            currentStep = step;
            const targetContainer = document.getElementById(`step-${step}`);
            if (targetContainer) targetContainer.classList.add('active');
            
            const indActive = document.getElementById(`ind-${step}`);
            if (indActive) indActive.classList.add('active');

            document.getElementById('wizardProgressBadge').innerText = `Step ${step} of 6`;

            if (step === 3) {
                setTimeout(() => {
                    initMap();
                    if (map) map.resize();
                }, 150);
            }

            if (step === 6) {
                buildSummaryScreen();
            }
        }

        // Dropzone upload logic for Lodge Main Photo
        const mainLodgeDropzone = document.getElementById('mainLodgeDropzone');
        const mainLodgeInput = document.getElementById('mainLodgeInput');
        const mainLodgePreview = document.getElementById('mainLodgePreview');
        const mainLodgeUrl = document.getElementById('mainLodgeUrl');

        mainLodgeDropzone.addEventListener('click', () => mainLodgeInput.click());
        mainLodgeInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            document.getElementById('mainLodgeText').innerText = "Uploading...";
            const formData = new FormData();
            formData.append('file', file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'http://127.0.0.1:8000/api/upload', true);
            const apiToken = "<?php echo $_SESSION['api_token'] ?? ''; ?>";
            if (apiToken) xhr.setRequestHeader('Authorization', 'Bearer ' + apiToken);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    mainLodgeUrl.value = data.url;
                    mainLodgePreview.querySelector('img').src = data.url;
                    mainLodgePreview.classList.remove('d-none');
                    document.getElementById('mainLodgeText').innerText = "Click or Drag main photo here to replace";
                } else {
                    const mockUrl = 'assets/images/room/room1.jpg';
                    mainLodgeUrl.value = mockUrl;
                    mainLodgePreview.querySelector('img').src = mockUrl;
                    mainLodgePreview.classList.remove('d-none');
                    document.getElementById('mainLodgeText').innerText = "Click or Drag main photo here to replace";
                }
            };
            xhr.send(formData);
        });

        // Room register & bulk operations
        const roomPhotosDropzone = document.getElementById('roomPhotosDropzone');
        const roomPhotosInput = document.getElementById('roomPhotosInput');
        const roomPhotosPreviews = document.getElementById('roomPhotosPreviews');

        roomPhotosDropzone.addEventListener('click', () => roomPhotosInput.click());
        roomPhotosInput.addEventListener('change', (e) => {
            Array.from(e.target.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const cardId = 'mcard-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                const card = document.createElement('div');
                card.className = 'col-md-3 col-sm-6 mb-2';
                card.id = cardId;
                card.innerHTML = `
                    <div class="preview-card">
                        <img src="${URL.createObjectURL(file)}" alt="Preview">
                        <button type="button" class="remove-btn">&times;</button>
                        <div class="upload-progress" id="progm-${cardId}"></div>
                    </div>
                `;
                roomPhotosPreviews.appendChild(card);

                // upload
                const formData = new FormData();
                formData.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'http://127.0.0.1:8000/api/upload', true);
                const apiToken = "<?php echo $_SESSION['api_token'] ?? ''; ?>";
                if (apiToken) xhr.setRequestHeader('Authorization', 'Bearer ' + apiToken);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', (ev) => {
                    if (ev.lengthComputable) {
                        const percent = Math.round((ev.loaded / ev.total) * 100);
                        const progressBar = document.getElementById('progm-' + cardId);
                        if (progressBar) progressBar.style.width = percent + '%';
                    }
                });

                xhr.onload = function() {
                    let finalUrl = '';
                    if (xhr.status === 200) {
                        const r = JSON.parse(xhr.responseText);
                        finalUrl = r.url;
                    } else {
                        finalUrl = 'assets/images/room/room' + (Math.floor(Math.random() * 5) + 1) + '.jpg';
                    }
                    card.setAttribute('data-url', finalUrl);
                    editorRoomPhotos.push(finalUrl);
                    card.querySelector('.remove-btn').addEventListener('click', () => {
                        card.remove();
                        editorRoomPhotos = editorRoomPhotos.filter(u => u !== finalUrl);
                    });
                };
                xhr.send(formData);
            });
        });

        function openRoomEditor(index) {
            document.getElementById('roomPhotosErrorMsg').classList.add('d-none');
            roomPhotosPreviews.innerHTML = '';
            editorRoomPhotos = [];

            // Hide room management, show room editor screen
            document.getElementById('step-5').classList.remove('active');
            document.getElementById('step-room-editor').classList.add('active');

            // Reset tab active
            const triggerEl = document.querySelector('#editorTabs a[href="#tabDetails"]');
            if (triggerEl) bootstrap.Tab.getOrCreateInstance(triggerEl).show();

            if (index === null) {
                document.getElementById('editorHeaderTitle').innerText = "Add Physical Room Details";
                document.getElementById('editRoomIdx').value = "";
                document.getElementById('roomNum').value = "";
                document.getElementById('roomType').value = "Deluxe";
                document.getElementById('roomPrice').value = "90000";
                document.getElementById('roomFloor').value = "";
                document.getElementById('roomStatus').value = "available";
                document.getElementById('roomDesc').value = "";
                document.getElementById('roomAdults').value = "2";
                document.getElementById('roomChildren').value = "0";
                document.getElementById('roomBeds').value = "";
                document.getElementById('roomSize').value = "";
                document.querySelectorAll('.room-amenity').forEach(cb => cb.checked = false);
            } else {
                const room = registeredRooms[index];
                document.getElementById('editorHeaderTitle').innerText = `Edit Physical Room Details: Room ${room.room_number}`;
                document.getElementById('editRoomIdx').value = index;
                document.getElementById('roomNum').value = room.room_number;
                document.getElementById('roomType').value = room.room_type_id;
                document.getElementById('roomPrice').value = room.price;
                document.getElementById('roomFloor').value = room.floor || '';
                document.getElementById('roomStatus').value = room.status || 'available';
                document.getElementById('roomDesc').value = room.description || '';
                document.getElementById('roomAdults').value = room.max_adults || 2;
                document.getElementById('roomChildren').value = room.max_children || 0;
                document.getElementById('roomBeds').value = room.bed_configuration || '';
                document.getElementById('roomSize').value = room.room_size || '';
                
                const amenities = room.amenities || [];
                document.querySelectorAll('.room-amenity').forEach(cb => {
                    cb.checked = amenities.includes(cb.value);
                });

                editorRoomPhotos = [...(room.photos || [])];
                editorRoomPhotos.forEach((url, i) => {
                    const cardId = 'mcard-exist-' + i;
                    const card = document.createElement('div');
                    card.className = 'col-md-3 col-sm-6 mb-2';
                    card.id = cardId;
                    card.innerHTML = `
                        <div class="preview-card">
                            <img src="${url}" alt="Preview">
                            <button type="button" class="remove-btn">&times;</button>
                        </div>
                    `;
                    roomPhotosPreviews.appendChild(card);
                    card.querySelector('.remove-btn').addEventListener('click', () => {
                        card.remove();
                        editorRoomPhotos = editorRoomPhotos.filter(u => u !== url);
                    });
                });
            }
        }

        function closeRoomEditor() {
            document.getElementById('step-room-editor').classList.remove('active');
            document.getElementById('step-5').classList.add('active');
        }

        function saveRoomDetails() {
            if (editorRoomPhotos.length < 4) {
                document.getElementById('roomPhotosErrorMsg').classList.remove('d-none');
                return;
            }

            const room_number = document.getElementById('roomNum').value.trim();
            const room_type_id = document.getElementById('roomType').value;
            const price = parseFloat(document.getElementById('roomPrice').value) || 0;
            const floor = document.getElementById('roomFloor').value.trim();
            const status = document.getElementById('roomStatus').value;
            const description = document.getElementById('roomDesc').value.trim();
            const max_adults = parseInt(document.getElementById('roomAdults').value) || 2;
            const max_children = parseInt(document.getElementById('roomChildren').value) || 0;
            const bed_configuration = document.getElementById('roomBeds').value.trim();
            const room_size = document.getElementById('roomSize').value.trim();
            
            const amenities = [];
            document.querySelectorAll('.room-amenity:checked').forEach(cb => {
                amenities.push(cb.value);
            });

            if (!room_number) {
                alert('Please enter a room number.');
                return;
            }

            const roomData = {
                room_number,
                room_type_id,
                price,
                floor,
                status,
                description,
                max_adults,
                max_children,
                capacity: max_adults + max_children,
                bed_configuration,
                number_of_beds: 1,
                room_size,
                amenities,
                photos: editorRoomPhotos
            };

            const indexVal = document.getElementById('editRoomIdx').value;
            if (indexVal === "") {
                registeredRooms.push(roomData);
            } else {
                registeredRooms[parseInt(indexVal)] = roomData;
            }

            renderRoomsTable();
            closeRoomEditor();
        }

        function generateBulkRooms() {
            const fromNum = parseInt(document.getElementById('bulkFrom').value);
            const toNum = parseInt(document.getElementById('bulkTo').value);
            const defaultType = document.getElementById('bulkType').value;
            const defaultPrice = parseFloat(document.getElementById('bulkPrice').value) || 90000;

            if (isNaN(fromNum) || isNaN(toNum) || fromNum > toNum) {
                alert('Please check your room number ranges.');
                return;
            }

            for (let i = fromNum; i <= toNum; i++) {
                const bulkRoomData = {
                    room_number: i.toString(),
                    room_type_id: defaultType,
                    price: defaultPrice,
                    capacity: 2,
                    max_adults: 2,
                    max_children: 0,
                    status: 'available',
                    amenities: ["Air conditioning", "Wi-Fi", "Shower"],
                    photos: [
                        'assets/images/room/room1.jpg',
                        'assets/images/room/room2.jpg',
                        'assets/images/room/room3.jpg',
                        'assets/images/room/room4.jpg'
                    ]
                };
                registeredRooms.push(bulkRoomData);
            }

            renderRoomsTable();
            bootstrap.Modal.getInstance(document.getElementById('bulkRoomModal')).hide();
        }

        function deleteRoom(index) {
            registeredRooms.splice(index, 1);
            renderRoomsTable();
        }

        function renderRoomsTable() {
            document.getElementById('roomCountText').innerText = `Lodge rooms: ${registeredRooms.length} registered`;
            const tbody = document.getElementById('roomsTableBody');
            if (registeredRooms.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No rooms added yet. Create rooms individually or in bulk.</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            registeredRooms.forEach((room, index) => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${room.room_number}</strong></td>
                        <td><span class="badge badge-info">${room.room_type_id}</span></td>
                        <td>${room.floor || 'N/A'}</td>
                        <td>TSh ${parseFloat(room.price).toLocaleString()}</td>
                        <td><span class="badge badge-success">${room.status}</span></td>
                        <td>${room.photos ? room.photos.length : 0} photos</td>
                        <td>
                            <button class="btn btn-primary btn-xxs me-1" onclick="openRoomEditor(${index})">Edit Details</button>
                            <button class="btn btn-danger btn-xxs" onclick="deleteRoom(${index})">Delete</button>
                        </td>
                    </tr>
                `;
            });
        }

        // Summary screen render
        function buildSummaryScreen() {
            document.getElementById('sumLodgeName').innerText = document.getElementById('lodgeName').value;
            document.getElementById('sumLodgeType').innerText = document.getElementById('lodgeType').value;
            document.getElementById('sumLodgeContact').innerText = `${document.getElementById('lodgeEmail').value || 'N/A'} / ${document.getElementById('lodgePhone').value || 'N/A'}`;
            document.getElementById('sumLodgeLocation').innerText = `${document.getElementById('lodgeCity').value}, ${document.getElementById('lodgeArea').value} (${document.getElementById('lodgeAddress').value || 'No address'})`;
            
            const mainImg = mainLodgeUrl.value;
            const imgContainer = document.getElementById('summaryLodgeImage');
            if (mainImg) {
                imgContainer.innerHTML = `<img src="${mainImg}" class="rounded w-100" style="max-height:150px; object-fit:cover;">`;
            } else {
                imgContainer.innerHTML = `<span class="text-muted">No main image uploaded.</span>`;
            }

            const tbody = document.getElementById('sumRoomsTableBody');
            tbody.innerHTML = '';
            if (registeredRooms.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No rooms configured.</td></tr>`;
                return;
            }

            registeredRooms.forEach(room => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${room.room_number}</strong></td>
                        <td>${room.room_type_id}</td>
                        <td>TSh ${room.price.toLocaleString()}</td>
                        <td>${room.max_adults} Adults, ${room.max_children} Children</td>
                        <td>${room.photos ? room.photos.length : 0} photos</td>
                    </tr>
                `;
            });
        }

        // Submit entire wizard structure to database via APIs
        function submitOnboarding() {
            const submitBtn = document.getElementById('submitOnboardingBtn');
            submitBtn.innerText = "Submitting...";
            submitBtn.disabled = true;

            const lodgeData = {
                name: document.getElementById('lodgeName').value,
                description: document.getElementById('lodgeDescription').value,
                address: document.getElementById('lodgeAddress').value,
                city: document.getElementById('lodgeCity').value,
                area: document.getElementById('lodgeArea').value,
                latitude: parseFloat(document.getElementById('lodgeLatitude').value) || null,
                longitude: parseFloat(document.getElementById('lodgeLongitude').value) || null,
                price_per_night: registeredRooms.length > 0 ? registeredRooms[0].price : 100000,
                image_url: mainLodgeUrl.value || 'assets/images/room/room1.jpg'
            };

            // 1. Create property
            fetch('onboarding.php?action=create_property', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(lodgeData)
            })
            .then(res => res.json())
            .then(property => {
                if (property && property.id) {
                    // 2. Loop over and post rooms
                    const propertyId = property.id;
                    const apiToken = "<?php echo $_SESSION['api_token'] ?? ''; ?>";
                    const promises = [];

                    registeredRooms.forEach(room => {
                        promises.push(
                            fetch(`http://127.0.0.1:8000/api/properties/${propertyId}/rooms`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'Authorization': 'Bearer ' + apiToken
                                },
                                body: JSON.stringify(room)
                            })
                        );
                    });

                    // 3. Post verification documents
                    const licenseDocUrl = document.getElementById('licenseUrl')?.value;
                    if (licenseDocUrl) {
                        promises.push(
                            fetch(`http://127.0.0.1:8000/api/verification/lodge/${propertyId}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'Authorization': 'Bearer ' + apiToken
                                },
                                body: JSON.stringify({
                                    documents: [
                                        { document_type: 'business_license', file_url: licenseDocUrl }
                                    ]
                                })
                            })
                        );
                    }

                    return Promise.all(promises);
                } else {
                    throw new Error(property.message || 'Failed to onboarding lodge profile.');
                }
            })
            .then(() => {
                window.location.href = 'index.php';
            })
            .catch(err => {
                alert('Onboarding failed: ' + err.message);
                submitBtn.innerText = "Submit for Admin Approval";
                submitBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
