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
        document.addEventListener('DOMContentLoaded', function() {
            const descInput = document.getElementById('lodgeDescriptionInput');
            const charCount = document.getElementById('desc-char-count');
            const btnGenerate = document.getElementById('btn-generate-desc');

            const realGeneratedDesc = <?php echo json_encode($realGeneratedDesc); ?>;

            function updateCounter() {
                if (!descInput || !charCount) return;
                const len = descInput.value.length;
                charCount.textContent = `${len.toLocaleString()} / 2,000 chars`;
            }

            if (descInput) {
                descInput.addEventListener('input', updateCounter);
                updateCounter();
            }

            if (btnGenerate) {
                btnGenerate.addEventListener('click', async function() {
                    const propertyId = <?php echo (int) $property['id']; ?>;
                    const apiToken = <?php echo json_encode($_SESSION['api_token'] ?? ''); ?>;

                    btnGenerate.disabled = true;
                    btnGenerate.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';

                    try {
                        const res = await fetch(`http://127.0.0.1:8000/api/properties/${propertyId}/generate-description`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${apiToken}`
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.description) {
                                descInput.value = data.description;
                                updateCounter();
                                descInput.focus();
                            }
                        } else {
                            // Fallback to local real database generated text if API call error
                            descInput.value = <?php echo json_encode($realGeneratedDesc); ?>;
                            updateCounter();
                        }
                    } catch (e) {
                        descInput.value = <?php echo json_encode($realGeneratedDesc); ?>;
                        updateCounter();
                    } finally {
                        btnGenerate.disabled = false;
                        btnGenerate.innerHTML = '<i class="fas fa-magic me-1"></i> Auto-Generate Description';
                    }
                });
            }

            // ==================== PHOTO GALLERY MANAGEMENT SCRIPT ====================
            const dropzoneArea = document.getElementById('dropzone-area');
            const fileInput = document.getElementById('photoFileInput');
            const btnTriggerUpload = document.getElementById('btn-trigger-upload');
            const galleryGrid = document.getElementById('gallery-grid');
            const emptyGalleryState = document.getElementById('empty-gallery-state');
            const coverInput = document.getElementById('coverImageUrlInput');
            const photoCountBadge = document.getElementById('photo-count-badge');
            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const progressPercent = document.getElementById('upload-progress-percent');
            const errorAlert = document.getElementById('upload-error-alert');
            const successAlert = document.getElementById('upload-success-alert');

            let photos = [];
            const initialImageVal = coverInput.value.trim();
            if (initialImageVal) {
                photos = initialImageVal.split(',').map(s => s.trim()).filter(Boolean);
            }

            function renderGallery() {
                galleryGrid.innerHTML = '';
                photoCountBadge.textContent = `${photos.length} Photo${photos.length === 1 ? '' : 's'}`;

                if (photos.length === 0) {
                    emptyGalleryState.classList.remove('d-none');
                } else {
                    emptyGalleryState.classList.add('d-none');
                    photos.forEach((url, idx) => {
                        const isCover = (idx === 0);
                        const col = document.createElement('div');
                        col.className = 'col-6 col-md-4 col-lg-3';
                        col.setAttribute('draggable', 'true');
                        col.dataset.index = idx;

                        col.innerHTML = `
                            <div class="card border ${isCover ? 'border-primary border-2 shadow' : 'border-light'} rounded-3 overflow-hidden h-100 position-relative group-hover">
                                <div style="height: 160px; overflow: hidden; background: #eef2f0; position: relative;">
                                    <img src="${url}" class="w-100 h-100" style="object-fit: cover;" alt="Lodge photo ${idx + 1}">
                                    ${isCover ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2 font-w700 shadow-sm"><i class="fas fa-star me-1"></i> Cover Photo</span>' : ''}
                                    <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                                        <button type="button" class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center btn-remove-photo" data-index="${idx}" style="width: 28px; height: 28px;" title="Remove Photo">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-footer bg-white p-2 d-flex justify-content-between align-items-center">
                                    <small class="text-muted font-w600">Photo #${idx + 1}</small>
                                    ${!isCover ? `
                                        <button type="button" class="btn btn-outline-primary btn-xs font-w600 btn-set-cover" data-index="${idx}">
                                            Make Cover
                                        </button>
                                    ` : '<span class="text-primary font-w700 small">Main Banner</span>'}
                                </div>
                            </div>
                        `;
                        galleryGrid.appendChild(col);
                    });
                }

                // Update hidden cover image input
                coverInput.value = photos.join(',');
                attachGalleryEvents();
            }

            function attachGalleryEvents() {
                // Remove photo buttons
                document.querySelectorAll('.btn-remove-photo').forEach(btn => {
                    btn.onclick = function(e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.index);
                        photos.splice(idx, 1);
                        renderGallery();
                    };
                });

                // Set Cover photo buttons
                document.querySelectorAll('.btn-set-cover').forEach(btn => {
                    btn.onclick = function(e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.index);
                        const selected = photos.splice(idx, 1)[0];
                        photos.unshift(selected); // Move to first position as main banner
                        renderGallery();
                    };
                });

                // Simple drag and drop reordering
                let dragSrcIndex = null;
                document.querySelectorAll('#gallery-grid > div').forEach(col => {
                    col.addEventListener('dragstart', function(e) {
                        dragSrcIndex = parseInt(this.dataset.index);
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    col.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                    });
                    col.addEventListener('drop', function(e) {
                        e.preventDefault();
                        const targetIndex = parseInt(this.dataset.index);
                        if (dragSrcIndex !== null && dragSrcIndex !== targetIndex) {
                            const moved = photos.splice(dragSrcIndex, 1)[0];
                            photos.splice(targetIndex, 0, moved);
                            renderGallery();
                        }
                    });
                });
            }

            // Click triggers file picker
            btnTriggerUpload.addEventListener('click', () => fileInput.click());
            dropzoneArea.addEventListener('click', (e) => {
                if (e.target !== btnTriggerUpload && !btnTriggerUpload.contains(e.target)) {
                    fileInput.click();
                }
            });

            // Drag and drop zone events
            ['dragenter', 'dragover'].forEach(evt => {
                dropzoneArea.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropzoneArea.classList.add('bg-white', 'border-success');
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                dropzoneArea.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropzoneArea.classList.remove('bg-white', 'border-success');
                });
            });

            dropzoneArea.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    handleFiles(files);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    handleFiles(this.files);
                }
            });

            async function handleFiles(fileList) {
                errorAlert.classList.add('d-none');
                successAlert.classList.add('d-none');
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                const maxSizeBytes = 10 * 1024 * 1024; // 10MB

                const uploadQueue = [];
                for (let i = 0; i < fileList.length; i++) {
                    const f = fileList[i];
                    if (!validTypes.includes(f.type.toLowerCase())) {
                        showError(`"${f.name}" is an unsupported file format. Please upload JPG, PNG, or WEBP images.`);
                        return;
                    }
                    if (f.size > maxSizeBytes) {
                        showError(`"${f.name}" exceeds the maximum 10MB file size limit.`);
                        return;
                    }
                    uploadQueue.push(f);
                }

                if (uploadQueue.length === 0) return;

                // Show progress bar
                progressContainer.classList.remove('d-none');
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';

                const apiToken = <?php echo json_encode($_SESSION['api_token'] ?? ''); ?>;
                let uploadedCount = 0;

                for (let i = 0; i < uploadQueue.length; i++) {
                    const file = uploadQueue[i];
                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const res = await fetch('http://127.0.0.1:8000/api/upload', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${apiToken}`
                            },
                            body: formData
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.url) {
                                photos.push(data.url);
                                uploadedCount++;
                            }
                        } else {
                            showError(`Failed to upload ${file.name}.`);
                        }
                    } catch (err) {
                        showError(`Network error while uploading ${file.name}.`);
                    }

                    const pct = Math.round(((i + 1) / uploadQueue.length) * 100);
                    progressBar.style.width = `${pct}%`;
                    progressPercent.textContent = `${pct}%`;
                }

                progressContainer.classList.add('d-none');
                fileInput.value = '';

                if (uploadedCount > 0) {
                    showSuccess(`Successfully uploaded and compressed ${uploadedCount} photo${uploadedCount > 1 ? 's' : ''}!`);
                    renderGallery();
                }
            }

            function showError(msg) {
                errorAlert.textContent = msg;
                errorAlert.classList.remove('d-none');
            }

            function showSuccess(msg) {
                successAlert.textContent = msg;
                successAlert.classList.remove('d-none');
                setTimeout(() => successAlert.classList.add('d-none'), 5000);
            }

            // Initial render
            renderGallery();
        });
    </script>
</body>
</html>
