<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'admin';
$userId = $_SESSION['user_id'] ?? 2;

$property = null;
$propertyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If propertyId not supplied, try fetching the first lodge owned by user
if (!$propertyId) {
    if ($userRole === 'admin') {
        $stmt = $db->query("SELECT * FROM properties ORDER BY id ASC LIMIT 1");
        $property = $stmt->fetch();
    } else {
        $stmt = $db->prepare("SELECT * FROM properties WHERE host_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$userId]);
        $property = $stmt->fetch();
    }
    if ($property) {
        $propertyId = $property['id'];
    }
} else {
    // Verify ownership
    if ($userRole === 'admin') {
        $stmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->execute([$propertyId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM properties WHERE id = ? AND host_id = ?");
        $stmt->execute([$propertyId, $userId]);
    }
    $property = $stmt->fetch();
}

if (!$property) {
    header('Location: room-list.php?error=' . urlencode('Lodge not found or unauthorized access.'));
    exit();
}

// Fetch property rooms and amenities to construct real database-derived description
$stmtRooms = $db->prepare("SELECT * FROM rooms WHERE property_id = ?");
$stmtRooms->execute([$property['id']]);
$propertyRooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

$roomTypes = [];
$allAmenities = [];
foreach ($propertyRooms as $rm) {
    if (!empty($rm['room_type_id'])) $roomTypes[] = trim($rm['room_type_id']);
    if (!empty($rm['amenities'])) {
        $decoded = is_string($rm['amenities']) ? json_decode($rm['amenities'], true) : $rm['amenities'];
        if (!is_array($decoded)) {
            $decoded = explode(',', $rm['amenities']);
        }
        foreach ($decoded as $am) {
            $trimmed = trim($am);
            if (!empty($trimmed) && !in_array($trimmed, $allAmenities)) {
                $allAmenities[] = $trimmed;
            }
        }
    }
}
$uniqueRoomTypes = array_unique($roomTypes);

$realGeneratedDesc = "Welcome to " . htmlspecialchars($property['name']) . ", a premier accommodation choice located in " . htmlspecialchars($property['area'] ?? $property['city']) . ", " . htmlspecialchars($property['city']) . ". ";
if (!empty($uniqueRoomTypes)) {
    $realGeneratedDesc .= "Our lodge offers thoughtfully appointed " . implode(', ', $uniqueRoomTypes) . " accommodations designed for maximum comfort and relaxation. ";
} else {
    $realGeneratedDesc .= "Our lodge offers thoughtfully appointed guest rooms designed for maximum comfort and relaxation. ";
}

if (!empty($allAmenities)) {
    $realGeneratedDesc .= "Guests enjoy essential facilities including " . implode(', ', array_slice($allAmenities, 0, 5)) . " to ensure a seamless stay. ";
}

$realGeneratedDesc .= "Situated with convenient access to local attractions and transit, " . htmlspecialchars($property['name']) . " provides warm hospitality and dedicated service for business and leisure travelers alike.";

$currentDescription = trim($property['description'] ?? '');
if (empty($currentDescription)) {
    $currentDescription = $realGeneratedDesc;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $price_per_night = floatval($_POST['price_per_night'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');

    $api_token = $_SESSION['api_token'] ?? '';
    $apiUrl = "http://127.0.0.1:8000/api/properties/{$property['id']}";

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => $name,
        'description' => $description,
        'address' => $address,
        'city' => $city,
        'area' => $area,
        'price_per_night' => $price_per_night,
        'image_url' => $image_url,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer {$api_token}",
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $success_message = "Lodge details successfully updated!";
        // Refresh local property record
        $stmt = $db->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->execute([$property['id']]);
        $property = $stmt->fetch();
    } else {
        $respData = json_decode($response, true);
        $error_message = $respData['message'] ?? "Failed to update lodge details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Lodge Details | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
</head>
<body>
    <?php include 'elements/pre-loader.php'; ?>

    <div id="main-wrapper">
        <?php include 'elements/header.php'; ?>
        <?php include 'elements/sidebar.php'; ?>

        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="room-list.php">Lodges</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Edit Lodge Details</a></li>
                    </ol>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header border-0 pb-0">
                                <h4 class="card-title">Edit Property / Lodge: <?php echo htmlspecialchars($property['name']); ?></h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($success_message)): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <?php echo htmlspecialchars($success_message); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <?php echo htmlspecialchars($error_message); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="edit-lodge.php?id=<?php echo $property['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w600">Lodge / Property Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($property['name']); ?>" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w600">Base Price Per Night (TSh) <span class="text-danger">*</span></label>
                                            <input type="number" name="price_per_night" class="form-control" value="<?php echo htmlspecialchars($property['price_per_night']); ?>" step="500" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w600">City <span class="text-danger">*</span></label>
                                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($property['city']); ?>" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w600">Area / Neighborhood <span class="text-danger">*</span></label>
                                            <input type="text" name="area" class="form-control" value="<?php echo htmlspecialchars($property['area']); ?>" required>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label font-w600">Full Address</label>
                                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($property['address'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label font-w600 text-dark mb-0">Description <span class="text-danger">*</span></label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span id="desc-char-count" class="text-muted small">0 / 2,000 chars</span>
                                                    <button type="button" id="btn-generate-desc" class="btn btn-outline-primary btn-xs font-w600">
                                                        <i class="fas fa-magic me-1"></i> Auto-Generate Description
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea name="description" id="lodgeDescriptionInput" class="form-control font-w500" rows="6" placeholder="Provide a clean, attractive description of your lodge for booking customers..." required><?php echo htmlspecialchars($currentDescription); ?></textarea>
                                        </div>

                                        <!-- Redesigned Lodge Photos / Images Upload Section -->
                                        <div class="col-md-12 mb-4">
                                            <div class="card border border-primary border-opacity-25 shadow-sm" style="border-radius: 14px; background: #fafdfc;">
                                                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                                                    <div>
                                                        <h5 class="mb-0 text-primary font-w700"><i class="fas fa-camera me-2"></i>Lodge Photos & Image Gallery</h5>
                                                        <small class="text-muted">High-quality photos of rooms, reception, dining, exterior, and facilities help guests choose your lodge.</small>
                                                    </div>
                                                    <span class="badge bg-light text-dark border font-w600" id="photo-count-badge">0 Photos</span>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Hidden input storing comma-separated gallery image URLs -->
                                                    <input type="hidden" name="image_url" id="coverImageUrlInput" value="<?php echo htmlspecialchars($property['image_url'] ?? ''); ?>">

                                                    <!-- Large Drag & Drop Upload Zone -->
                                                    <div id="dropzone-area" class="p-4 p-md-5 text-center border border-2 border-dashed border-primary rounded-3 bg-light cursor-pointer mb-4 transition-all" style="border-radius: 14px; background-color: #f4f8f6 !important; border-color: #135846 !important;">
                                                        <input type="file" id="photoFileInput" multiple accept="image/jpeg,image/png,image/webp" class="d-none">
                                                        <div class="mb-3">
                                                            <div class="icon-shape bg-primary text-white rounded-circle mx-auto d-flex align-items-center justify-content-center shadow" style="width: 70px; height: 70px;">
                                                                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                                            </div>
                                                        </div>
                                                        <h4 class="font-w700 text-dark mb-2">Add photos of your lodge</h4>
                                                        <p class="text-secondary font-w500 mb-3" style="max-width: 520px; margin: 0 auto; font-size: 14px;">
                                                            Drag & drop high-resolution lodge photos here, or click the button below to browse from your device.
                                                        </p>
                                                        
                                                        <button type="button" id="btn-trigger-upload" class="btn btn-primary font-w700 px-4 py-2 text-white shadow-sm me-2 mb-2">
                                                            <i class="fas fa-plus-circle me-2"></i>Upload Photos
                                                        </button>

                                                        <div class="mt-3 text-muted small">
                                                            <span><i class="fas fa-check-circle text-success me-1"></i> Formats: JPG, JPEG, PNG, WEBP</span>
                                                            <span class="mx-2">•</span>
                                                            <span><i class="fas fa-weight-hanging text-info me-1"></i> Max File Size: 10 MB each</span>
                                                            <span class="mx-2">•</span>
                                                            <span><i class="fas fa-compress-arrows-alt text-primary me-1"></i> Auto WebP Compression Enabled</span>
                                                        </div>
                                                    </div>

                                                    <!-- Upload Progress Bar Container -->
                                                    <div id="upload-progress-container" class="mb-4 d-none">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span id="upload-progress-label" class="font-w600 text-primary small"><i class="fas fa-spinner fa-spin me-1"></i> Uploading & Optimizing photos...</span>
                                                            <span id="upload-progress-percent" class="font-w700 text-dark small">0%</span>
                                                        </div>
                                                        <div class="progress" style="height: 8px; border-radius: 4px;">
                                                            <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Feedback Alerts -->
                                                    <div id="upload-error-alert" class="alert alert-danger d-none alert-dismissible fade show"></div>
                                                    <div id="upload-success-alert" class="alert alert-success d-none alert-dismissible fade show"></div>

                                                    <!-- Responsive Photo Gallery Grid -->
                                                    <div id="gallery-grid" class="row g-3">
                                                        <!-- Dynamically populated or empty state -->
                                                    </div>

                                                    <!-- First-Time Owner Empty State -->
                                                    <div id="empty-gallery-state" class="text-center p-4 border rounded-3 bg-white">
                                                        <div class="my-3">
                                                            <i class="fas fa-images fa-3x text-muted opacity-50"></i>
                                                        </div>
                                                        <h5 class="font-w700 text-dark mb-2">Show guests what makes your lodge special</h5>
                                                        <p class="text-secondary font-w500 mb-0" style="max-width: 600px; margin: 0 auto; font-size: 13.5px; line-height: 1.6;">
                                                            High-quality photos of rooms, bathrooms, exterior areas, reception, dining areas, facilities, and surroundings help customers understand your property and increase bookings.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary font-w600"><i class="fas fa-save me-1"></i> Save Changes</button>
                                        <a href="room-list.php" class="btn btn-outline-secondary font-w600">Back to Rooms & Lodges</a>
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
        window.LODGE_GALLERY_CONFIG = {
            realGeneratedDesc: <?php echo json_encode($realGeneratedDesc); ?>,
            propertyId: <?php echo (int) $property['id']; ?>,
            apiToken: <?php echo json_encode($_SESSION['api_token'] ?? ''); ?>
        };
    </script>
    <script src="assets/js/custom/lodge-gallery.js"></script>
</body>
</html>
