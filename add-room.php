<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();
$userRole = $_SESSION['user_role'] ?? 'admin';
$userId = $_SESSION['user_id'] ?? 2;

// Fetch properties for selection
if ($userRole === 'admin') {
    $properties = $db->query("SELECT * FROM properties")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM properties WHERE host_id = ?");
    $stmt->execute([$userId]);
    $properties = $stmt->fetchAll();
}

$room = null;
$is_edit = false;

// If edit mode
if (isset($_GET['id'])) {
    $roomId = intval($_GET['id']);
    $stmt = $db->prepare("
        SELECT r.*, p.name as property_name 
        FROM rooms r 
        JOIN properties p ON r.property_id = p.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if ($room) {
        $is_edit = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $is_edit ? 'Edit Room' : 'Add Room'; ?> | <?php echo $DexignZoneSettings['site_level']['site_title'] ?></title>
    <?php include 'elements/meta.php';?>
    <link rel="shortcut icon" type="image/png" href="<?php echo $DexignZoneSettings['site_level']['favicon']?>">
    <?php include 'elements/page-css.php'; ?>
    <style>
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
            height: 120px;
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
            width: 24px;
            height: 24px;
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
                <div class="row page-titles">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="room-list.php">Rooms</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)"><?php echo $is_edit ? 'Edit Room' : 'Add Room'; ?></a></li>
                    </ol>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header border-0 pb-0">
                                <h4 class="fs-20 font-w600 text-black"><?php echo $is_edit ? 'Edit Physical Room details' : 'Register New Physical Room'; ?></h4>
                                <a href="room-list.php" class="btn btn-secondary light btn-sm">Back to List</a>
                            </div>
                            <div class="card-body">
                                <form id="roomForm" onsubmit="saveRoom(event)">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w500 text-dark">Select Property/Lodge</label>
                                            <?php if ($is_edit): ?>
                                                <input type="text" class="form-control style-1 border" value="<?php echo htmlspecialchars($room['property_name']); ?>" readonly>
                                                <input type="hidden" id="roomPropertyId" value="<?php echo $room['property_id']; ?>">
                                            <?php else: ?>
                                                <select id="roomPropertyId" class="form-control default-select style-1 border" required>
                                                    <?php foreach ($properties as $prop): ?>
                                                        <option value="<?php echo $prop['id']; ?>"><?php echo htmlspecialchars($prop['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label font-w500 text-dark">Room Number / Room Name</label>
                                            <input type="text" id="roomNum" class="form-control style-1 border" value="<?php echo htmlspecialchars($room['room_number'] ?? ''); ?>" placeholder="e.g. Room 101" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label font-w500 text-dark">Room Type / Category</label>
                                            <select id="roomType" class="form-control default-select style-1 border" required>
                                                <option value="Deluxe" <?php echo ($room && $room['room_type_id'] === 'Deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                                                <option value="Standard" <?php echo ($room && $room['room_type_id'] === 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                                <option value="Suite" <?php echo ($room && $room['room_type_id'] === 'Suite') ? 'selected' : ''; ?>>Suite</option>
                                                <option value="Executive" <?php echo ($room && $room['room_type_id'] === 'Executive') ? 'selected' : ''; ?>>Executive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label font-w500 text-dark">Floor</label>
                                            <input type="text" id="roomFloor" class="form-control style-1 border" value="<?php echo htmlspecialchars($room['floor'] ?? ''); ?>" placeholder="e.g. 1st Floor">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label font-w500 text-dark">Nightly Price (TSh)</label>
                                            <input type="number" id="roomPrice" class="form-control style-1 border" value="<?php echo $room['price'] ?? '90000'; ?>" placeholder="e.g. 90000" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label font-w500 text-dark">Max Adults</label>
                                            <input type="number" id="roomAdults" class="form-control style-1 border" value="<?php echo $room['max_adults'] ?? '2'; ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label font-w500 text-dark">Max Children</label>
                                            <input type="number" id="roomChildren" class="form-control style-1 border" value="<?php echo $room['max_children'] ?? '0'; ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label font-w500 text-dark">Bed Configuration</label>
                                            <input type="text" id="roomBeds" class="form-control style-1 border" value="<?php echo htmlspecialchars($room['bed_configuration'] ?? ''); ?>" placeholder="e.g. 1 King Bed">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label font-w500 text-dark">Room Size (m²)</label>
                                            <input type="text" id="roomSize" class="form-control style-1 border" value="<?php echo htmlspecialchars($room['room_size'] ?? ''); ?>" placeholder="e.g. 28">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label font-w500 text-dark">Room Status</label>
                                        <select id="roomStatus" class="form-control default-select style-1 border">
                                            <option value="available" <?php echo ($room && $room['status'] === 'available') ? 'selected' : ''; ?>>Active / Available</option>
                                            <option value="maintenance" <?php echo ($room && $room['status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="inactive" <?php echo ($room && $room['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label font-w500 text-dark">Unique Room Description</label>
                                        <textarea id="roomDesc" class="form-control style-1 border" rows="4" placeholder="Describe the unique elements of this specific physical room..."><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label font-w600 text-dark mb-2">Room Amenities</label>
                                        <?php 
                                            $amenities_arr = !empty($room['amenities']) ? json_decode($room['amenities'], true) : [];
                                            if (!is_array($amenities_arr)) {
                                                $amenities_arr = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
                                            }
                                        ?>
                                        <div class="row">
                                            <div class="col-6 col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input room-amenity" type="checkbox" value="Air conditioning" id="am-ac" <?php echo in_array('Air conditioning', $amenities_arr) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="am-ac">Air conditioning</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input room-amenity" type="checkbox" value="Wi-Fi" id="am-wifi" <?php echo in_array('Wi-Fi', $amenities_arr) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="am-wifi">Wi-Fi</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input room-amenity" type="checkbox" value="LED TV" id="am-tv" <?php echo in_array('LED TV', $amenities_arr) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="am-tv">LED TV</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input room-amenity" type="checkbox" value="Balcony" id="am-balcony" <?php echo in_array('Balcony', $amenities_arr) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="am-balcony">Balcony</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input room-amenity" type="checkbox" value="Shower" id="am-shower" <?php echo in_array('Shower', $amenities_arr) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="am-shower">Shower</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label font-w600 text-dark">Room Photos (Minimum 4 images required)</label>
                                        <div class="row g-2 mb-3" id="imagePreviewsContainer"></div>
                                        <div class="upload-dropzone text-center p-5" id="dropzoneArea">
                                            <i class="flaticon-381-picture-1 fs-40 text-primary mb-2 d-block"></i>
                                            <span class="fs-16 font-w600 text-primary d-block mb-1">Click to Upload Room Photos or Drag & Drop</span>
                                            <span class="fs-13 text-muted">Supports PNG, JPG, JPEG, WEBP (Max 4MB each)</span>
                                            <input type="file" id="fileInput" class="d-none" multiple accept="image/*">
                                        </div>
                                        <div class="alert alert-danger py-2 mt-2 d-none" id="uploadErrorMsg">Please upload at least 4 photos for this room.</div>
                                    </div>

                                    <div class="pt-3 border-top text-end">
                                        <a href="room-list.php" class="btn btn-danger light me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary px-5">Save Room Details</button>
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
        let uploadedUrls = [];
        const isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
        const roomId = <?php echo $is_edit ? $room['id'] : 'null'; ?>;
        
        // Initial photos setup if edit mode
        <?php 
            $photos_arr = !empty($room['photos']) ? json_decode($room['photos'], true) : [];
            if (!is_array($photos_arr)) {
                $photos_arr = !empty($room['photos']) ? explode(',', $room['photos']) : [];
            }
        ?>
        const initialPhotos = <?php echo json_encode($photos_arr); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const dropzoneArea = document.getElementById('dropzoneArea');
            const fileInput = document.getElementById('fileInput');
            const previewsContainer = document.getElementById('imagePreviewsContainer');
            
            // Render initial photos
            initialPhotos.forEach((url, i) => {
                const cardId = 'card-exist-' + i;
                const card = document.createElement('div');
                card.className = 'col-md-3 col-sm-6 mb-2';
                card.id = cardId;
                card.innerHTML = `
                    <div class="preview-card">
                        <img src="${url}" alt="Preview">
                        <button type="button" class="remove-btn">&times;</button>
                    </div>
                `;
                previewsContainer.appendChild(card);
                uploadedUrls.push(url);
                card.querySelector('.remove-btn').addEventListener('click', () => {
                    card.remove();
                    uploadedUrls = uploadedUrls.filter(u => u !== url);
                });
            });

            // Click trigger
            dropzoneArea.addEventListener('click', () => fileInput.click());

            // Drag & Drop
            dropzoneArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzoneArea.style.borderColor = '#1a7e65';
                dropzoneArea.style.background = '#eef7f3';
            });
            dropzoneArea.addEventListener('dragleave', () => {
                dropzoneArea.style.borderColor = '#135846';
                dropzoneArea.style.background = '#f4f9f7';
            });
            dropzoneArea.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzoneArea.style.borderColor = '#135846';
                dropzoneArea.style.background = '#f4f9f7';
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                Array.from(files).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    const cardId = 'card-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                    const card = document.createElement('div');
                    card.className = 'col-md-3 col-sm-6 mb-2';
                    card.id = cardId;
                    card.innerHTML = `
                        <div class="preview-card">
                            <img src="${URL.createObjectURL(file)}" alt="Preview">
                            <button type="button" class="remove-btn">&times;</button>
                            <div class="upload-progress" id="progress-${cardId}"></div>
                        </div>
                    `;
                    previewsContainer.appendChild(card);

                    uploadFile(file, cardId);
                });
            }

            function uploadFile(file, cardId) {
                const formData = new FormData();
                formData.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'http://127.0.0.1:8000/api/upload', true);
                const apiToken = "<?php echo $_SESSION['api_token'] ?? ''; ?>";
                if (apiToken) xhr.setRequestHeader('Authorization', 'Bearer ' + apiToken);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        const bar = document.getElementById('progress-' + cardId);
                        if (bar) bar.style.width = percent + '%';
                    }
                });

                xhr.onload = function() {
                    let finalUrl = '';
                    if (xhr.status === 200) {
                        const res = JSON.parse(xhr.responseText);
                        finalUrl = res.url;
                    } else {
                        alert('Image upload failed. Please verify storage backend.');
                        if (document.getElementById(cardId)) document.getElementById(cardId).remove();
                        return;
                    }
                    const card = document.getElementById(cardId);
                    if (card) {
                        card.setAttribute('data-url', finalUrl);
                        uploadedUrls.push(finalUrl);
                        card.querySelector('.remove-btn').addEventListener('click', () => {
                            card.remove();
                            uploadedUrls = uploadedUrls.filter(u => u !== finalUrl);
                        });
                    }
                };
                xhr.send(formData);
            }
        });

        function saveRoom(e) {
            e.preventDefault();
            
            if (uploadedUrls.length < 4) {
                const error = document.getElementById('uploadErrorMsg');
                error.classList.remove('d-none');
                error.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            const propertyId = document.getElementById('roomPropertyId').value;
            const room_number = document.getElementById('roomNum').value.trim();
            const room_type_id = document.getElementById('roomType').value;
            const floor = document.getElementById('roomFloor').value.trim();
            const price = parseFloat(document.getElementById('roomPrice').value) || 0;
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
                photos: uploadedUrls
            };

            const apiUrl = isEdit 
                ? `http://127.0.0.1:8000/api/rooms/${roomId}`
                : `http://127.0.0.1:8000/api/properties/${propertyId}/rooms`;
                
            const method = isEdit ? 'PUT' : 'POST';
            const apiToken = "<?php echo $_SESSION['api_token'] ?? ''; ?>";

            fetch(apiUrl, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + apiToken
                },
                body: JSON.stringify(roomData)
            })
            .then(res => {
                if (res.status === 201 || res.status === 200) {
                    window.location.href = 'room-list.php';
                } else {
                    return res.json().then(err => { throw new Error(err.message || 'Database error occurred') });
                }
            })
            .catch(err => {
                alert('Error saving room details: ' + err.message);
            });
        }
    </script>
</body>
</html>
